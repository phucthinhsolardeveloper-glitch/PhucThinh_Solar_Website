<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\Text;

/** A product tile in lists (ptsCatalogProductDto): pictures, power, up to three quick specs, price. */
class ProductCard
{
    private const MISSING = '/^(?:không công bố|không áp dụng|không có thông tin|chưa cập nhật|đang cập nhật|chưa xác minh|chưa tìm được|n\/a|na|-)(?:\s|$)/iu';

    public function __construct(private readonly ProductMediaUrls $urls) {}

    /** @param list<string> $gallery gallery file names already checked on disk, shown between the two main photos */
    public function dto(array $row, PricingPolicy $pricing, array $gallery = []): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $name = Text::plain($row['name'] ?? '');
        $slug = Text::slug($row['slug'] ?? '');
        if (empty($row['is_visible']) || $id <= 0 || $name === '' || $slug === '') {
            return null;
        }
        $media = [$this->urls->product($row['image'] ?? '')];
        foreach ($gallery as $file) {
            $media[] = '/upload/product/'.$file;
        }
        $media[] = $this->urls->product($row['image_secondary'] ?? '');
        $power = is_numeric($row['power_watts'] ?? null) && (float) $row['power_watts'] > 0 && is_finite((float) $row['power_watts']) ? (float) $row['power_watts'] : null;
        $quick = $power !== null ? [(int) round($power).'W'] : [];
        foreach (['waterproof_rating', 'lighting_time'] as $field) {
            $value = Text::plain($row[$field] ?? '');
            if ($value !== '' && ! preg_match(self::MISSING, $value)) {
                $quick[] = $value;
            }
        }

        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'url' => '/san-pham/'.$slug,
            'media' => array_values(array_unique(array_filter($media))),
            'powerWatts' => $power,
            'quickSpecs' => array_slice($quick, 0, 3),
            'pricing' => $pricing->forProduct($row),
        ];
    }
}
