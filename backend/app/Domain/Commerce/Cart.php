<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductRecord;
use App\Support\Text;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;

/**
 * Shopping cart kept in the visitor's Laravel session (class.Cart.php kept it in the PHP session).
 * A line is {productid, qty, color, size, code}; code = md5(product id . color . size), so adding the
 * same product and option again raises the quantity (capped at 999).
 */
class Cart
{
    public function __construct(private readonly Session $session) {}

    /** @return list<array{productid: int, qty: int, color: int, size: int, code: string}> */
    public function lines(): array
    {
        $lines = $this->session->get('cart', []);

        return is_array($lines) ? array_values(array_filter($lines, 'is_array')) : [];
    }

    public function add(int $productId, int $quantity, int $color = 0, int $size = 0): void
    {
        $quantity = max(1, min(999, $quantity));
        $code = md5($productId.$color.$size);
        $lines = $this->lines();
        foreach ($lines as &$line) {
            if ($line['code'] === $code) {
                $line['qty'] = min(999, (int) $line['qty'] + $quantity);
                $this->session->put('cart', $lines);

                return;
            }
        }
        unset($line);
        $lines[] = ['productid' => $productId, 'qty' => $quantity, 'color' => $color, 'size' => $size, 'code' => $code];
        $this->session->put('cart', $lines);
    }

    /** @return 'ok'|'not_found'|'mismatch' */
    public function update(string $code, int $productId, int $quantity): string
    {
        $lines = $this->lines();
        foreach ($lines as &$line) {
            if (hash_equals((string) $line['code'], $code)) {
                if ((int) $line['productid'] !== $productId) {
                    return 'mismatch';
                }
                $line['qty'] = $quantity;
                $this->session->put('cart', $lines);

                return 'ok';
            }
        }

        return 'not_found';
    }

    public function remove(string $code): bool
    {
        $lines = $this->lines();
        $kept = array_values(array_filter($lines, fn ($line) => ! hash_equals((string) $line['code'], $code)));
        $this->session->put('cart', $kept);

        return count($kept) !== count($lines);
    }

    public function clear(): void
    {
        $this->session->forget('cart');
    }

    /** @return array<string, mixed>|null the product in the shape the price and cart rules read */
    public function product(int $id): ?array
    {
        $row = $id > 0 ? DB::table('products')->where('id', $id)->first() : null;

        return $row ? ProductRecord::from((array) $row, app(CategoryTree::class)) : null;
    }

    /** Name of a colour or size option chosen for a cart line. */
    public function optionLabel(string $kind, int $id): string
    {
        return $id > 0 ? (string) (DB::table('product_options')->where('kind', $kind)->where('id', $id)->value('name') ?? '') : '';
    }

    /** Sale price when set, else list price, times quantity (Cart::getOrderTotal()). */
    public function total(): float
    {
        $sum = 0.0;
        foreach ($this->lines() as $line) {
            $product = $this->product((int) $line['productid']) ?? [];
            $price = ! empty($product['sale_price']) ? (float) $product['sale_price'] : (float) ($product['regular_price'] ?? 0);
            $sum += $price * (int) $line['qty'];
        }

        return $sum;
    }

    /** Quotation only: prices hidden site-wide, the product marked quotation-only, or no price at all. */
    public static function requiresQuote(array $product, bool $pricesVisible): bool
    {
        return ! $pricesVisible || ! empty($product['quote_only'])
            || ((float) ($product['regular_price'] ?? 0) <= 0 && (float) ($product['sale_price'] ?? 0) <= 0);
    }

    public static function text(mixed $value): string
    {
        return Text::plain($value);
    }
}
