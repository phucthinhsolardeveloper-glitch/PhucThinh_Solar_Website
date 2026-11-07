<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Domain\Catalog\CategoryTree;

/**
 * A products row in the shape the product rules read (spec fields, profile, content inheritance):
 * name/summary/body under their page keys, category levels resolved from the tree.
 */
final class ProductRecord
{
    private const SPECS = ['battery_capacity', 'panel_power', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color', 'material', 'charging_time', 'lighting_time',
        'waterproof_rating', 'led_chip', 'lighting_area', 'installation_height', 'panel_dimensions', 'carton_quantity', 'carton_dimensions', 'weight', 'warranty'];

    /** @return array<string, mixed> */
    public static function from(array $row, CategoryTree $tree): array
    {
        $category = (int) ($row['category_id'] ?? 0);
        $level = fn (int $depth) => $category ? (int) ($tree->atDepth($category, $depth)['id'] ?? 0) : 0;
        $record = [
            'id' => (int) $row['id'],
            'type' => 'san-pham',
            'namevi' => (string) $row['name'],
            'slugvi' => (string) $row['slug'],
            'code' => (string) ($row['code'] ?? ''),
            'descvi' => (string) ($row['summary'] ?? ''),
            'contentvi' => (string) ($row['body'] ?? ''),
            'highlightsvi' => (string) ($row['highlights'] ?? ''),
            'applicationsvi' => (string) ($row['applications'] ?? ''),
            'installationvi' => (string) ($row['installation'] ?? ''),
            'thongsokythuatvi' => (string) ($row['specs_html'] ?? ''),
            'chinhsachmuahangvi' => (string) ($row['purchase_policy'] ?? ''),
            'khoanggia' => (string) ($row['price_range'] ?? ''),
            'regular_price' => (float) ($row['regular_price'] ?? 0),
            'sale_price' => (float) ($row['sale_price'] ?? 0),
            'status' => implode(',', array_filter([$row['is_visible'] ? 'hienthi' : null, $row['quote_only'] ? 'baogia' : null, $row['is_bestseller'] ? 'banchay' : null])),
            'photo' => (string) ($row['image'] ?? ''),
            'photo2' => (string) ($row['image_secondary'] ?? ''),
            'video_url' => (string) ($row['video_url'] ?? ''),
            'pdp_profile' => (string) ($row['pdp_profile'] ?? ''),
            'cong_suat' => $row['power_watts'] === null ? '' : (string) $row['power_watts'],
            'package_contents' => (string) ($row['package_contents'] ?? ''),
            'additional_specs' => (string) ($row['additional_specs'] ?? ''),
            'id_brand' => (int) ($row['brand_id'] ?? 0),
            'category_id' => $category,
            'id_list' => $level(1),
            'id_cat' => $level(2),
            'id_item' => $level(3),
            'id_sub' => $level(4),
        ];
        foreach (self::SPECS as $field) {
            $record[$field] = (string) ($row[$field] ?? '');
        }

        return $record;
    }
}
