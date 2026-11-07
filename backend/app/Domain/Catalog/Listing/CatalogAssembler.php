<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductContentPresenter;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Models\Category;
use App\Support\SiteSettings;
use App\Support\StatusFlags;
use App\Support\Text;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the /api/v1/catalog payload, same contract as api/pts_v1_catalog.php
 * (frontend/shared/contracts/public-catalog.ts): products of a category path with filters, sort and
 * paging, plus the category landing extras (hero, "choose by need" cards, FAQ, support banner, SEO).
 */
class CatalogAssembler
{
    public function __construct(
        private readonly ProductMediaUrls $urls,
        private readonly SiteSettings $settings,
        private readonly ProductCard $cards,
        private readonly CategoryTree $tree,
    ) {}

    /** @return array<string, mixed>|null null when the category path does not resolve */
    public function build(CatalogRequest $request): ?array
    {
        $db = DB::connection();
        $pricesVisible = $this->settings->productPricesVisible();
        $segments = $request->categoryPath === '' ? [] : explode('/', $request->categoryPath);
        $selectedId = $segments ? $this->tree->resolvePath($segments) : null;
        if ($segments && $selectedId === null) {
            return null;
        }

        $baseWhere = 'p.is_visible = 1';
        $baseParams = [];
        $category = [];
        $categoryRow = [];
        $listId = 0;
        if ($selectedId !== null) {
            $ids = $this->tree->descendantIds($selectedId);
            $baseWhere .= ' and p.category_id in ('.implode(',', array_fill(0, count($ids), '?')).')';
            array_push($baseParams, ...$ids);
            $node = $this->tree->get($selectedId);
            $categoryRow = ['id' => $node['id'], 'namevi' => $node['name'], 'slugvi' => $node['slug'], 'status' => 'hienthi', 'photo' => $node['image'] ?? '',
                'id_list' => $node['depth'] === 2 ? $node['parent_id'] : null, 'depth' => $node['depth']];
            $category = $this->categoryDto($categoryRow, $segments);
            if ($category === null) {
                return null;
            }
            // Product-type checkboxes only make sense on a top category page.
            if ((int) $node['depth'] === 1) {
                $listId = $selectedId;
            }
        }

        $subcategoryIds = $listId > 0 ? $request->subcategoryIds : [];
        $price = $pricesVisible ? $request->price : '';
        $keyword = $request->keyword !== '' ? CatalogFilters::keywordSlug($request->keyword) : '';
        $where = $baseWhere;
        $params = $baseParams;
        if ($request->brandIds) {
            $where .= ' and p.brand_id in ('.implode(',', array_fill(0, count($request->brandIds), '?')).')';
            array_push($params, ...$request->brandIds);
        }
        if ($subcategoryIds) {
            $ids = array_merge(...array_map(fn ($id) => $this->tree->get($id) && (int) $this->tree->get($id)['parent_id'] === $listId ? $this->tree->descendantIds($id) : [], $subcategoryIds)) ?: [0];
            $where .= ' and p.category_id in ('.implode(',', array_fill(0, count($ids), '?')).')';
            array_push($params, ...$ids);
        }
        foreach ([$price !== '' ? CatalogFilters::price($price) : ['', []], CatalogFilters::needs($request->needKeys, $this->tree)] as [$sql, $values]) {
            $where .= $sql;
            array_push($params, ...$values);
        }
        if ($keyword !== '') {
            $where .= ' and (p.name like ? or p.code like ? or p.slug like ?)';
            array_push($params, ...array_fill(0, 3, '%'.$keyword.'%'));
        }

        $total = (int) ($db->selectOne("select count(*) as total from products p where {$where}", $params)->total ?? 0);
        $order = CatalogFilters::sort($request->sort, $pricesVisible) ?? CatalogFilters::defaultOrder($this->tree);
        $offset = ($request->page - 1) * $request->perPage;
        $rows = $db->select('select '.CatalogFilters::CARD_COLUMNS." from products p where {$where} order by {$order} limit {$offset}, {$request->perPage}", $params);
        $pricing = new PricingPolicy($pricesVisible);
        $products = [];
        foreach ($rows as $row) {
            $dto = $this->cards->dto((array) $row, $pricing);
            if ($dto !== null) {
                $products[] = $dto;
            }
        }

        $categories = [];
        foreach ($this->tree->roots() as $node) {
            if (! $node['is_visible'] || ! $this->tree->hasVisibleProducts((int) $node['id'])) {
                continue;
            }
            $dto = $this->categoryDto(['id' => $node['id'], 'namevi' => $node['name'], 'slugvi' => $node['slug'], 'status' => 'hienthi'], [(string) $node['slug']]);
            if ($dto !== null) {
                $categories[] = $dto;
            }
        }
        // Brand choices follow the category only, so ticking a brand never empties the list of brands.
        $brands = [];
        foreach ($db->select("select distinct b.id, b.name as namevi, b.sort_order from products p inner join brands b on b.id = p.brand_id and b.is_visible = 1 where {$baseWhere} order by b.sort_order, b.id desc", $baseParams) as $row) {
            $name = Text::plain($row->namevi);
            if ((int) $row->id > 0 && $name !== '') {
                $brands[] = ['id' => (int) $row->id, 'name' => $name];
            }
        }
        $subcategories = [];
        if ($listId > 0) {
            foreach ($this->tree->children($listId) as $node) {
                $name = Text::plain($node['name']);
                $slug = Text::slug($node['slug']);
                if ($node['is_visible'] && $name !== '' && $slug !== '') {
                    $subcategories[] = ['id' => (int) $node['id'], 'name' => $name, 'slug' => $slug];
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
            'pricesVisible' => $pricesVisible,
        ], (new CatalogLanding($db, $this->urls))->extras($request->categoryPath, $selectedId, $categoryRow, $category));
    }

    /** @param list<string> $slugs */
    private function categoryDto(array $row, array $slugs): ?array
    {
        $id = (int) ($row['id'] ?? 0);
        $name = Text::plain($row['namevi'] ?? '');
        $clean = array_map(fn ($slug) => Text::slug($slug), $slugs);
        if (! (new StatusFlags($row['status'] ?? ''))->visible() || $id <= 0 || $name === '' || ! $clean || in_array('', $clean, true)) {
            return null;
        }

        return ['id' => $id, 'name' => $name, 'slug' => end($slugs), 'url' => '/danh-muc-san-pham/'.implode('/', $clean).'/'];
    }

    /** FAQ lines and "cards" blocks of a category's content section. */
    public static function sectionParts(Connection $db, int $categoryId): array
    {
        $blocks = json_decode((string) $db->table('content_sections')->where('owner_type', Category::class)->where('owner_id', $categoryId)->where('is_visible', true)->value('blocks'), true);
        [$faq, $rest] = ProductContentPresenter::splitFaq(is_array($blocks) ? $blocks : []);

        return ['faq' => $faq, 'cards' => array_values(array_filter($rest, fn ($block) => ($block['type'] ?? '') === 'cards'))];
    }
}
