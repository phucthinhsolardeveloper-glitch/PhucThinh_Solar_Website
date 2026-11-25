<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\PricingPolicy;
use App\Models\Product;

/** The "Giá khách thấy" cell: what the visitor gets, decided by the site's own price rule, and why. */
final class ProductPriceState
{
    /** @return array{quote: bool, text: string, detail: ?string, color: ?string, icon: ?string} */
    public static function of(Product $product, bool $pricesVisible): array
    {
        $price = (new PricingPolicy($pricesVisible))->forProduct($product->only(['regular_price', 'sale_price', 'quote_only']));
        $regular = (float) $product->regular_price;
        $sale = (float) $product->sale_price;
        $stored = $sale > 0 ? $sale : $regular;

        if ($price['mode'] === 'price') {
            $discounted = $sale > 0 && $sale < $regular;

            return ['quote' => false, 'text' => self::money($stored),
                'detail' => $discounted ? 'Giá gốc '.self::money($regular).', giảm '.max(1, (int) round((1 - $sale / $regular) * 100)).'%' : null,
                'color' => null, 'icon' => null];
        }

        [$detail, $color, $icon] = match (true) {
            $stored <= 0 => ['Chưa nhập giá', 'gray', null],
            (bool) $product->quote_only => ['Đang ẩn giá '.self::money($stored), 'warning', 'heroicon-m-eye-slash'],
            default => ['Cả website đang ẩn giá ('.self::money($stored).')', 'warning', 'heroicon-m-eye-slash'],
        };

        return ['quote' => true, 'text' => 'Liên hệ báo giá', 'detail' => $detail, 'color' => $color, 'icon' => $icon];
    }

    private static function money(float $value): string
    {
        return number_format($value, 0, ',', '.').'đ';
    }
}
