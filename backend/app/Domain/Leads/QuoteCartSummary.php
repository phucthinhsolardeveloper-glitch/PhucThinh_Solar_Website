<?php

namespace App\Domain\Leads;

use App\Domain\Commerce\Cart;
use Illuminate\Support\Facades\DB;

/**
 * Read-only, server-authoritative quote snapshot from the visitor's cart session.
 * Never trust client-supplied SKUs, quantities, prices or product names in a lead.
 */
final class QuoteCartSummary
{
    public function __construct(private readonly Cart $cart) {}

    public function render(): ?string
    {
        $lines = $this->cart->lines();
        if (! $lines || count($lines) > 100) {
            return null;
        }

        $ids = array_values(array_unique(array_map(fn ($line) => (int) $line['product_id'], $lines)));
        if (in_array(0, $ids, true)) {
            return null;
        }
        $products = DB::table('products')
            ->whereIn('id', $ids)
            ->where('is_visible', true)
            ->get(['id', 'name', 'code', 'slug'])
            ->keyBy('id');
        if ($products->count() !== count($ids)) {
            return null;
        }

        $rows = ['Danh sách thiết bị cần báo giá (đối chiếu từ giỏ hàng):'];
        foreach ($lines as $index => $line) {
            $id = (int) $line['product_id'];
            $quantity = (int) $line['quantity'];
            if ($quantity < 1 || $quantity > 999) {
                return null;
            }
            $product = $products->get($id);
            if (! $product) {
                return null;
            }
            $name = self::oneLine($product->name, 150);
            $code = self::oneLine($product->code ?: 'Chưa có mã', 60);
            $slug = self::oneLine($product->slug, 190);
            $parts = [
                ($index + 1).'. '.$name,
                'Mã: '.$code,
                'ID: '.$id,
                'SL: '.$quantity,
            ];

            foreach (['color' => 'Màu', 'size' => 'Kích cỡ'] as $key => $label) {
                $optionId = (int) ($line[$key] ?? 0);
                if ($optionId > 0) {
                    $optionName = self::oneLine($this->cart->optionLabel($key, $optionId), 80);
                    $parts[] = $label.': '.($optionName !== '' ? $optionName : '#'.$optionId);
                }
            }

            $parts[] = 'Trang: /san-pham/'.$slug;
            $rows[] = implode(' | ', $parts);
        }

        return implode("\n", $rows);
    }

    private static function oneLine(mixed $value, int $max): string
    {
        $plain = Cart::text($value);
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? '';

        return mb_substr(trim($plain), 0, $max);
    }
}
