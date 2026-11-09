<?php

namespace App\Domain\Catalog;

use Illuminate\Support\Facades\DB;

/**
 * Which promotion frame a product card shows. Among the running campaigns that cover a product, the
 * highest priority wins; at equal priority the narrowest scope wins (product, then sub-category, then
 * brand, then top category, then everything), then the newest campaign.
 */
final class PromoFrames
{
    /** @var list<array<string, mixed>>|null */
    private ?array $campaigns = null;

    /** @var array<int, list<int>> category id => itself and its ancestors */
    private array $chains = [];

    public function __construct(private readonly CategoryTree $tree) {}

    /** @param array<string, mixed> $product row with id, category_id, brand_id */
    public function for(array $product): ?string
    {
        $best = null;
        foreach ($this->campaigns() as $campaign) {
            $reach = $this->reach($campaign, $product);
            if ($reach < 0) {
                continue;
            }
            $rank = [(int) $campaign['priority'], $reach, (int) $campaign['id']];
            if ($best === null || $rank > $best['rank']) {
                $best = ['rank' => $rank, 'image' => $campaign['frame_image']];
            }
        }

        return $best ? '/upload/photo/'.$best['image'] : null;
    }

    /** -1 when the campaign does not cover the product, otherwise how narrowly it does. */
    private function reach(array $campaign, array $product): int
    {
        $best = $campaign['applies_to_all'] ? 0 : -1;
        if (in_array((int) ($product['id'] ?? 0), $campaign['product_ids'], true)) {
            return 40;
        }
        foreach ($this->chain((int) ($product['category_id'] ?? 0)) as $depth => $categoryId) {
            if (in_array($categoryId, $campaign['category_ids'], true)) {
                $best = max($best, $depth === 0 ? 30 : 10);
            }
        }
        if ((int) ($product['brand_id'] ?? 0) > 0 && in_array((int) $product['brand_id'], $campaign['brand_ids'], true)) {
            $best = max($best, 20);
        }

        return $best;
    }

    /** @return list<int> the product's own category first, then its parents */
    private function chain(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        return $this->chains[$categoryId] ??= array_reverse(array_map(fn ($node) => (int) $node['id'], $this->tree->chain($categoryId)));
    }

    /** @return list<array<string, mixed>> running campaigns with a picture */
    private function campaigns(): array
    {
        if ($this->campaigns !== null) {
            return $this->campaigns;
        }
        $now = now();
        $ids = fn ($json) => array_values(array_map('intval', json_decode((string) $json, true) ?: []));

        return $this->campaigns = DB::table('product_campaigns')->where('is_visible', true)->where('frame_image', '!=', '')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->get()->map(fn ($row) => ['id' => (int) $row->id, 'priority' => (int) $row->priority, 'frame_image' => (string) $row->frame_image,
                'applies_to_all' => (bool) $row->applies_to_all, 'category_ids' => $ids($row->category_ids),
                'brand_ids' => $ids($row->brand_ids), 'product_ids' => $ids($row->product_ids)])->all();
    }
}
