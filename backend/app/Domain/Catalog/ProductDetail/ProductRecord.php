<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\CategoryTree;

/**
 * A products row with every field the product rules read, nulls turned into empty values and the
 * product's top and second-level categories resolved from the tree.
 */
final class ProductRecord
{
    public const SPECS = ['battery_capacity', 'panel_power', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color', 'material', 'charging_time', 'lighting_time',
        'waterproof_rating', 'led_chip', 'lighting_area', 'installation_height', 'panel_dimensions', 'carton_quantity', 'carton_dimensions', 'weight', 'warranty'];

    /** @return array<string, mixed> */
    public static function from(array $row, CategoryTree $tree): array
    {
        $category = (int) ($row['category_id'] ?? 0);
        $level = fn (int $depth) => $category ? (int) ($tree->atDepth($category, $depth)['id'] ?? 0) : 0;
        $record = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'code' => (string) ($row['code'] ?? ''),
            'summary' => (string) ($row['summary'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'highlights' => (string) ($row['highlights'] ?? ''),
            'applications' => (string) ($row['applications'] ?? ''),
            'installation' => (string) ($row['installation'] ?? ''),
            'specs_html' => (string) ($row['specs_html'] ?? ''),
            'purchase_policy' => (string) ($row['purchase_policy'] ?? ''),
            'price_range' => (string) ($row['price_range'] ?? ''),
            'regular_price' => (float) ($row['regular_price'] ?? 0),
            'sale_price' => (float) ($row['sale_price'] ?? 0),
            'is_visible' => (bool) $row['is_visible'],
            'quote_only' => (bool) ($row['quote_only'] ?? false),
            'is_bestseller' => (bool) ($row['is_bestseller'] ?? false),
            'image' => (string) ($row['image'] ?? ''),
            'image_secondary' => (string) ($row['image_secondary'] ?? ''),
            'video_url' => (string) ($row['video_url'] ?? ''),
            'pdp_profile' => (string) ($row['pdp_profile'] ?? ''),
            'power_watts' => $row['power_watts'] === null ? '' : (string) $row['power_watts'],
            'package_contents' => (string) ($row['package_contents'] ?? ''),
            'additional_specs' => (string) ($row['additional_specs'] ?? ''),
            'brand_id' => (int) ($row['brand_id'] ?? 0),
            'category_id' => $category,
            'top_category_id' => $level(1),
            'second_category_id' => $level(2),
        ];
        foreach (self::SPECS as $field) {
            $record[$field] = (string) ($row[$field] ?? '');
        }

        return $record;
    }
}
