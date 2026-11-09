<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductRecord;
use App\Support\Text;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;

/**
 * Shopping cart kept in the visitor's Laravel session.
 * A line is {product_id, quantity, color, size, code}; code = md5(product id . color . size), so adding the
 * same product and option again raises the quantity (capped at 999).
 */
class Cart
{
    public function __construct(private readonly Session $session) {}

    /** @return list<array{product_id: int, quantity: int, color: int, size: int, code: string}> */
    public function lines(): array
    {
        $lines = $this->session->get('cart', []);

        return is_array($lines) ? array_values(array_filter($lines, fn ($line) => is_array($line) && isset($line['product_id'], $line['quantity'], $line['code']))) : [];
    }

    public function add(int $productId, int $quantity, int $color = 0, int $size = 0): void
    {
        $quantity = max(1, min(999, $quantity));
        $code = md5($productId.$color.$size);
        $lines = $this->lines();
        foreach ($lines as &$line) {
            if ($line['code'] === $code) {
                $line['quantity'] = min(999, (int) $line['quantity'] + $quantity);
                $this->session->put('cart', $lines);

                return;
            }
        }
        unset($line);
        $lines[] = ['product_id' => $productId, 'quantity' => $quantity, 'color' => $color, 'size' => $size, 'code' => $code];
        $this->session->put('cart', $lines);
    }

    /** @return 'ok'|'not_found'|'mismatch' */
    public function update(string $code, int $productId, int $quantity): string
    {
        $lines = $this->lines();
        foreach ($lines as &$line) {
            if (hash_equals((string) $line['code'], $code)) {
                if ((int) $line['product_id'] !== $productId) {
                    return 'mismatch';
                }
                $line['quantity'] = $quantity;
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

    /** @return array{kg: float, known: bool} packed weight of the cart; known is false when a product has no weight */
    public function parcel(): array
    {
        $kg = 0.0;
        $known = true;
        foreach ($this->lines() as $line) {
            $weight = (float) DB::table('products')->where('id', (int) $line['product_id'])->value('weight_kg');
            $known = $known && $weight > 0;
            $kg += $weight * (int) $line['quantity'];
        }

        return ['kg' => round($kg, 2), 'known' => $known && $kg > 0];
    }

    /** Sale price when set, else list price, times quantity. */
    public function total(): float
    {
        $sum = 0.0;
        foreach ($this->lines() as $line) {
            $product = $this->product((int) $line['product_id']) ?? [];
            $price = ! empty($product['sale_price']) ? (float) $product['sale_price'] : (float) ($product['regular_price'] ?? 0);
            $sum += $price * (int) $line['quantity'];
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
