<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductContentPresenter;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\ContentBlocks;
use App\Legacy\Support\LegacyText;
use App\Legacy\Support\StatusFlags;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the /api/v1/catalog payload, same contract as api/pts_v1_catalog.php
 * (frontend/shared/contracts/public-catalog.ts): products of a category path with filters, sort and
 * paging, plus the category landing extras (hero, "choose by need" cards, FAQ, support banner, SEO).
 */
class CatalogAssembler
{
    private const LEVELS = [
        'idl' => ['table_product_list', 'id_list', null],
        'idc' => ['table_product_cat', 'id_cat', 'id_list'],
        'idi' => ['table_product_item', 'id_item', 'id_cat'],
        'ids' => ['table_product_sub', 'id_sub', 'id_item'],
    ];

    public function __construct(
        private readonly ProductMediaUrls $urls,
        private readonly LegacySettings $settings,
        private readonly ProductCard $cards,
    ) {}

    /** @return array<string, mixed>|null null when the category path does not resolve */
    public function build(CatalogRequest $request): ?array
    {
        $db = DB::connection('legacy');
        $pricesVisible = $this->settings->productPricesVisible();
        $segments = $request->categoryPath === '' ? [] : explode('/', $request->categoryPath);
        $resolved = $segments ? $this->resolvePath($db, $segments) : [];
        if ($segments && ! $resolved) {
            return null;
        }

        $baseWhere = "p.type = ? and find_in_set('hienthi', p.status)";
        $baseParams = ['san-pham'];
        $category = [];
        $categoryRow = [];
        $listId = 0;
        if ($resolved) {
            $last = array_key_last($resolved);
            [$table, $column] = self::LEVELS[$last];
            $baseWhere .= " and p.{$column} = ?";
            $baseParams[] = $resolved[$last];
            $categoryRow = (array) ($db->selectOne('select id,namevi,slugvi,status,photo'.($last === 'idc' ? ',id_list' : '')
                ." from {$table} where id = ? and type = ? and find_in_set('hienthi',status) limit 1", [$resolved[$last], 'san-pham']) ?? []);
            $category = $this->categoryDto($categoryRow, $segments);
            if ($category === null) {
                return null;
            }
            // Product-type checkboxes only make sense on a level-1 category page.
            if ($last === 'idl') {
                $listId = $resolved[$last];
            }
        }

        $subcategoryIds = $listId > 0 ? $request->subcategoryIds : [];
        $price = $pricesVisible ? $request->price : '';
        $keyword = $request->keyword !== '' ? CatalogFilters::keywordSlug($request->keyword) : '';
        $where = $baseWhere;
        $params = $baseParams;
        if ($request->brandIds) {
            $where .= ' and p.id_brand in ('.implode(',', array_fill(0, count($request->brandIds), '?')).')';
            array_push($params, ...$request->brandIds);
        }
        if ($subcategoryIds) {
            $where .= ' and p.id_cat in ('.implode(',', array_fill(0, count($subcategoryIds), '?')).')';
            array_push($params, ...$subcategoryIds);
        }
        foreach ([$price !== '' ? CatalogFilters::price($price) : ['', []], CatalogFilters::needs($request->needKeys)] as [$sql, $values]) {
            $where .= $sql;
            array_push($params, ...$values);
        }
        if ($keyword !== '') {
            $where .= ' and (p.namevi like ? or p.code like ? or p.slugvi like ? or p.slugen like ?)';
            array_push($params, ...array_fill(0, 4, '%'.$keyword.'%'));
        }

        $total = (int) ($db->selectOne("select count(*) as total from table_product p where {$where}", $params)->total ?? 0);
        $order = CatalogFilters::sort($request->sort, $pricesVisible) ?? CatalogFilters::defaultOrder();
        $offset = ($request->page - 1) * $request->perPage;
        $rows = $db->select('select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat,p.waterproof_rating,p.lighting_time'
            ." from table_product p where {$where} order by {$order} limit {$offset}, {$request->perPage}", $params);
        $pricing = new PricingPolicy($pricesVisible);
        $products = [];
        foreach ($rows as $row) {
            $dto = $this->cards->dto((array) $row, $pricing);
            if ($dto !== null) {
                $products[] = $dto;
            }
        }

        $categories = [];
        foreach ($db->select("select pl.id,pl.namevi,pl.slugvi,pl.status from table_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status)"
            ." and exists (select 1 from table_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", ['san-pham', 'san-pham']) as $row) {
            $dto = $this->categoryDto((array) $row, [(string) $row->slugvi]);
            if ($dto !== null) {
                $categories[] = $dto;
            }
        }
        // Brand choices follow the category only, so ticking a brand never empties the list of brands.
        $brands = [];
        foreach ($db->select('select distinct pb.id,pb.namevi from table_product p inner join table_product_brand pb on pb.id = p.id_brand and pb.type = p.type'
            ." and find_in_set('hienthi',pb.status) where {$baseWhere} and p.id_brand > 0 order by pb.numb,pb.id desc", $baseParams) as $row) {
            $name = LegacyText::plain($row->namevi);
            if ((int) $row->id > 0 && $name !== '') {
                $brands[] = ['id' => (int) $row->id, 'name' => $name];
            }
        }
        $subcategories = [];
        if ($listId > 0) {
            foreach ($db->select("select id,namevi,slugvi,status from table_product_cat where id_list = ? and type = ? and find_in_set('hienthi',status) order by numb,id desc", [$listId, 'san-pham']) as $row) {
                $name = LegacyText::plain($row->namevi);
                $slug = LegacyText::slug($row->slugvi);
                if ((new StatusFlags($row->status))->visible() && (int) $row->id > 0 && $name !== '' && $slug !== '') {
                    $subcategories[] = ['id' => (int) $row->id, 'name' => $name, 'slug' => $slug];
                }
            }
        }
        $needsEnabled = str_contains($request->categoryPath, 'den-') || str_contains($request->categoryPath, 'tru-den');

        return array_merge([
            'schemaVersion' => 1,
            'locale' => 'vi',
            'category' => $category,
            'categories' => $categories,
            'products' => $products,
            'pagination' => ['page' => $request->page, 'perPage' => $request->perPage, 'total' => max(0, $total)],
            'filters' => [
                'brands' => $brands,
                'priceRanges' => array_map(fn ($key, $range) => ['key' => (string) $key, 'label' => $range[2]], array_keys(CatalogFilters::PRICE_RANGES), CatalogFilters::PRICE_RANGES),
                'subcategories' => $subcategories,
                'needs' => $needsEnabled ? array_map(fn ($key, $label) => ['key' => $key, 'label' => $label], array_keys(CatalogFilters::NEED_LABELS), CatalogFilters::NEED_LABELS) : [],
            ],
            'selected' => [
                'brandIds' => $request->brandIds,
                'priceKeys' => $price !== '' ? array_values(array_unique(explode(',', $price))) : [],
                'subcategoryIds' => $subcategoryIds,
                'sort' => $request->sort,
                'needKeys' => $request->needKeys,
            ],
            'parity' => [
                'prices' => $pricesVisible ? ['included' => true] : ['included' => false, 'reason' => 'public_pricing_disabled'],
                'filters' => ['included' => true],
                'sort' => ['included' => true],
            ],
        ], (new CatalogLanding($db, $this->urls))->extras($request->categoryPath, $resolved, $categoryRow, $category));
    }

    /**
     * list/cat/item/sub ids for a slug path, each level a visible child of the one before.
     *
     * @param  list<string>  $segments
     * @return array<string, int>
     */
    private function resolvePath(Connection $db, array $segments): array
    {
        $segments = array_values(array_filter(array_map('trim', $segments)));
        if (count($segments) < 1 || count($segments) > 4) {
            return [];
        }
        $resolved = [];
        $parentId = 0;
        foreach (array_keys(self::LEVELS) as $depth => $key) {
            if (! isset($segments[$depth])) {
                break;
            }
            [$table, , $parentColumn] = self::LEVELS[$key];
            $query = $db->table($table)->where('slugvi', $segments[$depth])->where('type', 'san-pham')->whereRaw("find_in_set('hienthi',status)");
            if ($parentColumn !== null) {
                $query->where($parentColumn, $parentId);
            }
            $id = $query->value('id');
            if ($id === null) {
                return [];
            }
            $resolved[$key] = $parentId = (int) $id;
        }

        return $resolved;
    }

    /** @param list<string> $slugs */
    private function categoryDto(array $row, array $slugs): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $name = LegacyText::plain($row['namevi'] ?? '');
        $clean = array_map(fn ($slug) => LegacyText::slug($slug), $slugs);
        if (! (new StatusFlags($row['status'] ?? ''))->visible() || $id <= 0 || $name === '' || ! $clean || in_array('', $clean, true)) {
            return null;
        }

        return ['id' => $id, 'name' => $name, 'slug' => end($slugs), 'url' => '/danh-muc-san-pham/'.implode('/', $clean).'/'];
    }

    /** FAQ lines and "cards" blocks of a list/cat content section. */
    public static function sectionParts(Connection $db, string $scope, int $id): array
    {
        $blocks = ContentBlocks::decode($db->table('table_content_section')->where('scope', $scope)->where('scope_id', $id)
            ->whereRaw("find_in_set('hienthi',status)")->value('blocksvi'));
        [$faq, $rest] = ProductContentPresenter::splitFaq($blocks);

        return ['faq' => $faq, 'cards' => array_values(array_filter($rest, fn ($block) => ($block['type'] ?? '') === 'cards'))];
    }
}
