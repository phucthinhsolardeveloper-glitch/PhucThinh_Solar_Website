<?php

namespace App\Legacy\Catalog;

use App\Legacy\Support\ContentBlocks;
use Illuminate\Support\Facades\DB;

/**
 * Port of productResolveSections() and productPickInheritedBlocks() (libraries/product_content_blocks.php):
 * which description blocks a product shows, and where they come from.
 *
 * Product mode: own | both | hide | inherit (default when the product has no row).
 * Inherit source: auto (brand, then level-2 category, then level-1) | brand | category | stack.
 * Result source: product | own | both | brand | cat | list | stack | hide | ''.
 */
class ProductContentResolver
{
    /**
     * @param  array{id: int|string, id_brand?: int|string|null, id_cat?: int|string|null, id_list?: int|string|null}  $product
     * @return array{blocks: list<array<string, mixed>>, source: string}
     */
    public function resolve(array $product, string $lang = 'vi'): array
    {
        $out = ['blocks' => [], 'source' => ''];
        if (empty($product['id'])) {
            return $out;
        }
        $lang = $lang === 'en' ? 'en' : 'vi';
        $column = 'blocks'.$lang;

        // Another language falls back to the Vietnamese blocks when it has none.
        $rows = DB::connection('legacy')->table('table_content_section')
            ->selectRaw("scope, mode, inherit_source, coalesce(nullif({$column}, ''), blocksvi) as blocks")
            ->whereRaw("find_in_set('hienthi', status)")
            ->where(function ($query) use ($product) {
                $query->where(fn ($q) => $q->where('scope', 'product')->where('scope_id', (int) $product['id']))
                    ->orWhere(fn ($q) => $q->where('scope', 'brand')->where('scope_id', (int) ($product['id_brand'] ?? 0)))
                    ->orWhere(fn ($q) => $q->where('scope', 'cat')->where('scope_id', (int) ($product['id_cat'] ?? 0)))
                    ->orWhere(fn ($q) => $q->where('scope', 'list')->where('scope_id', (int) ($product['id_list'] ?? 0)));
            })
            ->get();
        if ($rows->isEmpty()) {
            return $out;
        }

        // Later rows of the same scope win, exactly like the PHP loop that fills $byScope.
        $byScope = [];
        foreach ($rows as $row) {
            $byScope[$row->scope] = $row;
        }

        $mode = isset($byScope['product']) ? (string) $byScope['product']->mode : 'inherit';
        $inheritSource = isset($byScope['product']) && ! empty($byScope['product']->inherit_source) ? (string) $byScope['product']->inherit_source : 'auto';

        if ($mode === 'hide') {
            return ['blocks' => [], 'source' => 'hide'];
        }
        $own = isset($byScope['product']) ? ContentBlocks::decode($byScope['product']->blocks) : [];

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
        $blocksOf = fn (string $scope) => isset($byScope[$scope]) ? ContentBlocks::decode($byScope[$scope]->blocks) : [];

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
}
