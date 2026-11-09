<?php

namespace App\Domain\Catalog\ProductDetail;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Which description blocks a product shows, and where they come from (the PHP productResolveSections()).
 * The product's own section, its brand's, its level-2 category's ("cat") and its top category's ("list").
 *
 * Product mode: own | both | hide | inherit (default when the product has no row).
 * Inherit source: auto (brand, then level-2 category, then level-1) | brand | category | stack.
 * Result source: product | own | both | brand | cat | list | stack | hide | ''.
 */
class ProductContentResolver
{
    /**
     * @param  array{id: int|string, brand_id?: int|string|null, second_category_id?: int|string|null, top_category_id?: int|string|null}  $product
     * @return array{blocks: list<array<string, mixed>>, source: string}
     */
    public function resolve(array $product, string $lang = 'vi'): array
    {
        $out = ['blocks' => [], 'source' => ''];
        if (empty($product['id'])) {
            return $out;
        }
        $owners = [
            'product' => [Product::class, (int) $product['id']],
            'brand' => [Brand::class, (int) ($product['brand_id'] ?? 0)],
            'cat' => [Category::class, (int) ($product['second_category_id'] ?? 0)],
            'list' => [Category::class, (int) ($product['top_category_id'] ?? 0)],
        ];
        $rows = collect();
        foreach ($owners as $scope => [$type, $id]) {
            if ($id <= 0) {
                continue;
            }
            $row = DB::table('content_sections')->where('owner_type', $type)->where('owner_id', $id)->where('is_visible', true)->first(['mode', 'inherit_source', 'blocks']);
            if ($row) {
                $row->scope = $scope;
                $rows->push($row);
            }
        }
        if ($rows->isEmpty()) {
            return $out;
        }

        $byScope = [];
        foreach ($rows as $row) {
            $byScope[$row->scope] = $row;
        }

        $mode = isset($byScope['product']) ? (string) $byScope['product']->mode : 'inherit';
        $inheritSource = isset($byScope['product']) && ! empty($byScope['product']->inherit_source) ? (string) $byScope['product']->inherit_source : 'auto';

        if ($mode === 'hide') {
            return ['blocks' => [], 'source' => 'hide'];
        }
        $own = isset($byScope['product']) ? self::blocks($byScope['product']->blocks) : [];

        if ($mode === 'own') {
            return ['blocks' => $own, 'source' => $own ? 'product' : 'own'];
        }
        if ($mode === 'both') {
            $inherited = $this->pickInherited($byScope, $inheritSource);
            $blocks = array_merge($own, $inherited['blocks']);

            return ['blocks' => $blocks, 'source' => $blocks ? ($own ? 'both' : $inherited['source']) : ''];
        }
        if ($own) {
            return ['blocks' => $own, 'source' => 'product'];
        }

        return $this->pickInherited($byScope, $inheritSource);
    }

    /**
     * @param  array<string, object>  $byScope
     * @return array{blocks: list<array<string, mixed>>, source: string}
     */
    private function pickInherited(array $byScope, string $source): array
    {
        $blocksOf = fn (string $scope) => isset($byScope[$scope]) ? self::blocks($byScope[$scope]->blocks) : [];

        if ($source === 'brand') {
            $blocks = $blocksOf('brand');

            return ['blocks' => $blocks, 'source' => $blocks ? 'brand' : ''];
        }
        if ($source === 'category') {
            foreach (['cat', 'list'] as $scope) {
                if ($blocks = $blocksOf($scope)) {
                    return ['blocks' => $blocks, 'source' => $scope];
                }
            }

            return ['blocks' => [], 'source' => ''];
        }
        if ($source === 'stack') {
            $combined = [];
            foreach (['cat', 'list', 'brand'] as $scope) {
                $combined = array_merge($combined, $blocksOf($scope));
            }

            return ['blocks' => $combined, 'source' => $combined ? 'stack' : ''];
        }
        foreach (['brand', 'cat', 'list'] as $scope) {
            if ($blocks = $blocksOf($scope)) {
                return ['blocks' => $blocks, 'source' => $scope];
            }
        }

        return ['blocks' => [], 'source' => ''];
    }

    /** @return list<array<string, mixed>> */
    private static function blocks(mixed $json): array
    {
        $blocks = json_decode((string) $json, true);

        return is_array($blocks) ? array_values(array_filter($blocks, fn ($block) => is_array($block) && ! empty($block['type']))) : [];
    }
}
