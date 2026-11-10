<?php

namespace App\Domain\Catalog;

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use Illuminate\Support\Facades\DB;

/**
 * Up to four products side by side: the same spec table the product page shows, lined up by label so
 * "Dung lượng pin" of one sits next to "Dung lượng pin" of the other. Rows where every product says the
 * same thing are flagged so the page can fold them away.
 */
final class ProductCompare
{
    public const MAX = 4;

    public function __construct(private readonly ProductDetailAssembler $details) {}

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (array_diff(array_keys($get), ['ids']) || ! isset($get['ids']) || ! is_string($get['ids']) || ! preg_match('/^[1-9][0-9]{0,9}(?:,[1-9][0-9]{0,9}){0,'.(self::MAX - 1).'}$/', $get['ids'])) {
            return $bad;
        }
        $ids = array_values(array_unique(array_map('intval', explode(',', $get['ids']))));
        $slugs = DB::table('products')->whereIn('id', $ids)->where('is_visible', true)->pluck('slug', 'id');

        $products = [];
        foreach ($ids as $id) {
            $detail = isset($slugs[$id]) ? $this->details->forSlug((string) $slugs[$id]) : null;
            if ($detail) {
                $p = $detail['product'];
                $products[] = [
                    'id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'url' => $p['url'], 'code' => $p['code'], 'brand' => $p['brand'] ?? '',
                    'image' => $p['media'][0] ?? '', 'pricing' => $detail['pricing'], 'specifications' => $p['specifications'],
                ];
            }
        }

        $rows = [];
        foreach ($products as $index => $product) {
            foreach ($product['specifications'] as $spec) {
                if (trim($spec['value']) === '') {
                    continue;
                }
                $key = self::rowKey($spec['label']);
                $rows[$key] ??= ['label' => $spec['label'], 'values' => array_fill(0, count($products), '')];
                if ($rows[$key]['values'][$index] === '') {
                    $rows[$key]['values'][$index] = $spec['value'];
                }
            }
        }
        $rows = array_values(array_map(fn ($row) => $row + ['same' => count($products) > 1 && count(array_unique(array_map(self::rowKey(...), $row['values']))) === 1], $rows));
        // Rows most products answer come first; a row only one product fills is still shown, lower down.
        usort($rows, fn ($a, $b) => count(array_filter($b['values'], fn ($v) => $v !== '')) <=> count(array_filter($a['values'], fn ($v) => $v !== '')));

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'max' => self::MAX,
            'products' => array_map(fn ($p) => array_diff_key($p, ['specifications' => true]), $products),
            'rows' => $rows,
        ]];
    }

    private static function rowKey(string $text): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($text)));
    }
}
