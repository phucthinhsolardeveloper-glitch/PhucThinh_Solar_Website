<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\CategoryTree;
use App\Support\Text;

/** SQL fragments for the catalog filters and orderings, kept parameterised (product_price_filter.php, product_need_filter.php, product_sort.php). */
final class CatalogFilters
{
    /** key => [min, max|null, label] */
    public const PRICE_RANGES = [
        '1' => [0, 1000000, 'Dưới 1.000.000đ'],
        '2' => [1000000, 3000000, '1.000.000đ - 3.000.000đ'],
        '3' => [3000000, null, 'Trên 3.000.000đ'],
    ];

    public const NEED_LABELS = [
        'du-an-cong-trinh' => 'Dự án & công trình',
        'chieu-sang-gia-dinh' => 'Chiếu sáng gia đình',
        'san-vuon-canh-quan' => 'Sân vườn & cảnh quan',
    ];

    /** Lighting categories first; inside a need, the keyword fallback never pulls products from the lighting list. */
    private const NEEDS = [
        'du-an-cong-trinh' => [
            'categories' => ['den-duong', 'den-pha-nang-luong-mat-troi', 'den-ufo'],
            'keywords' => ['đường', 'giao thông', 'khu dân cư', 'công trình', 'nhà xưởng', 'khu sản xuất', 'bãi xe', 'bãi đỗ'],
        ],
        'chieu-sang-gia-dinh' => [
            'categories' => ['den-op-tran'],
            'keywords' => ['gia đình', 'nhà ở', 'hiên nhà', 'hành lang', 'ban công', 'nhà phố', 'biệt thự', 'sân nhà'],
        ],
        'san-vuon-canh-quan' => [
            'categories' => ['den-san-vuon'],
            'keywords' => ['sân vườn', 'cảnh quan', 'khuôn viên', 'lối đi', 'cổng', 'resort', 'homestay', 'tiểu cảnh', 'lối dạo'],
        ],
    ];

    /** Card columns of a product row ("p"), named the way the card and price rules read them. */
    public const CARD_COLUMNS = self::CARD_BASE.", coalesce(p.waterproof_rating,'') as waterproof_rating, coalesce(p.lighting_time,'') as lighting_time";

    /** Cards on search, brand and related lists show the power only (no water rating or lighting time), as before. */
    public const CARD_BASE = "p.id, p.name as namevi, p.slug as slugvi, coalesce(p.image,'') as photo, coalesce(p.image_secondary,'') as photo2,"
        ." concat_ws(',', if(p.is_visible,'hienthi',null), if(p.quote_only,'baogia',null)) as status, coalesce(p.regular_price,0) as regular_price,"
        ." coalesce(p.sale_price,0) as sale_price, coalesce(p.power_watts,'') as cong_suat";

    /** @return array{0: string, 1: list<mixed>} price bands on the price the buyer pays; quotation-only products never match */
    public static function price(string $keys): array
    {
        $conditions = [];
        $params = [];
        $effective = 'if(coalesce(p.sale_price,0)>0,p.sale_price,coalesce(p.regular_price,0))';
        foreach (explode(',', $keys) as $key) {
            if (! isset(self::PRICE_RANGES[$key])) {
                continue;
            }
            [$min, $max] = self::PRICE_RANGES[$key];
            if ($max === null) {
                $conditions[] = "({$effective} >= ?)";
                $params[] = $min;
            } else {
                $conditions[] = "({$effective} >= ? and {$effective} < ?)";
                array_push($params, $min, $max);
            }
        }

        return $conditions ? [' and p.quote_only=0 and ('.implode(' or ', $conditions).')', $params] : ['', []];
    }

    /**
     * Lighting needs: the matching lighting categories, plus a keyword match for products outside the lighting group.
     *
     * @param  list<string>  $keys
     * @return array{0: string, 1: list<mixed>}
     */
    public static function needs(array $keys, CategoryTree $tree): array
    {
        $conditions = [];
        $params = [];
        $lighting = array_values(array_filter($tree->roots(), fn ($node) => $node['slug'] === 'den-nlmt'));
        $lightingIds = $lighting ? $tree->descendantIds((int) $lighting[0]['id']) : [0];
        foreach ($keys as $key) {
            $need = self::NEEDS[$key];
            $categoryIds = [0];
            foreach ($tree->roots() as $root) {
                foreach ($tree->children((int) $root['id']) as $child) {
                    if ($child['is_visible'] && in_array($child['slug'], $need['categories'], true)) {
                        array_push($categoryIds, ...$tree->descendantIds((int) $child['id']));
                    }
                }
            }
            $keywordSql = '('.implode(' or ', array_fill(0, count($need['keywords']), '(p.name like ? or p.summary like ?)')).')';
            $conditions[] = '(p.category_id in ('.implode(',', array_fill(0, count($categoryIds), '?')).')'
                .' or ((p.category_id is null or p.category_id not in ('.implode(',', array_fill(0, count($lightingIds), '?')).')) and '.$keywordSql.'))';
            array_push($params, ...$categoryIds, ...$lightingIds);
            foreach ($need['keywords'] as $keyword) {
                array_push($params, '%'.$keyword.'%', '%'.$keyword.'%');
            }
        }

        return $conditions ? [' and ('.implode(' or ', $conditions).')', $params] : ['', []];
    }

    /** null = no explicit order; price sorts fall back to default when prices are hidden. */
    public static function sort(string $key, bool $pricesVisible): ?string
    {
        return match ($key) {
            'abc' => 'p.name asc',
            'abc-desc' => 'p.name desc',
            'price' => $pricesVisible ? 'coalesce(p.regular_price,0) asc' : null,
            'price-desc' => $pricesVisible ? 'coalesce(p.regular_price,0) desc' : null,
            'new' => 'p.created_at desc',
            'new-desc' => 'p.created_at asc',
            default => null,
        };
    }

    /**
     * Owner-chosen lighting order (flood, street, garden, UFO, ceiling), then products with a picture,
     * then Admin order.
     */
    public static function defaultOrder(?CategoryTree $tree = null): string
    {
        $hasPhoto = "(p.image is not null and trim(p.image) <> '') or exists (select 1 from product_images g where g.product_id = p.id and g.is_visible = 1)";
        $rank = '';
        if ($tree !== null) {
            $ranks = ['den-pha-nang-luong-mat-troi' => 10, 'den-duong' => 20, 'den-san-vuon' => 30, 'den-ufo' => 40, 'den-op-tran' => 50];
            $clauses = [];
            foreach ($tree->roots() as $root) {
                if ($root['slug'] === 'den-san-vuon') {
                    $clauses[] = 'when p.category_id in ('.implode(',', $tree->descendantIds((int) $root['id'])).') then 30';
                }
                if ($root['slug'] !== 'den-nlmt') {
                    continue;
                }
                foreach ($tree->children((int) $root['id']) as $child) {
                    if (isset($ranks[$child['slug']])) {
                        array_unshift($clauses, 'when p.category_id in ('.implode(',', $tree->descendantIds((int) $child['id'])).') then '.$ranks[$child['slug']]);
                    }
                }
            }
            $rank = $clauses ? 'case '.implode(' ', $clauses).' else 1000 end, ' : '';
        }

        return "{$rank}case when {$hasPhoto} then 0 else 1 end, p.sort_order, p.id desc";
    }

    /** Search slug of a keyword, as the PHP $func->changeTitle() builds it. */
    public static function keywordSlug(string $text): string
    {
        return Text::changeTitle($text);
    }
}
