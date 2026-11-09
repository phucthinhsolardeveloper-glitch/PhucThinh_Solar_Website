<?php

namespace App\Domain\Brands;

use App\Domain\Catalog\Listing\CatalogFilters;
use App\Domain\Catalog\Listing\ProductCard;
use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Models\Brand;
use App\Support\SiteSettings;
use App\Support\Text;

/** Products of one brand (api/pts_v1_brand_detail.php, /thuong-hieu/<slug>/), with sort, price filter and paging. */
class BrandDetail extends PageSupport
{
    public function __construct(ProductMediaUrls $urls, private readonly SiteSettings $settings, private readonly ProductCard $cards)
    {
        parent::__construct($urls);
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (array_diff(array_keys($get), ['slug', 'page', 'per_page', 'sort', 'price']) || ! isset($get['slug']) || ! is_string($get['slug'])) {
            return $bad;
        }
        $slug = trim($get['slug'], '/');
        $int = '/^[1-9][0-9]*$/';
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || (isset($get['page']) && ! preg_match($int, (string) $get['page'])) || (isset($get['per_page']) && ! preg_match($int, (string) $get['per_page']))) {
            return $bad;
        }
        $page = (int) ($get['page'] ?? 1);
        $perPage = (int) ($get['per_page'] ?? 20);
        if ($page > 10000 || $perPage > 48 || (isset($get['sort']) && ! preg_match('/^[a-z-]+$/', (string) $get['sort']))
            || (isset($get['price']) && (! is_string($get['price']) || ! preg_match('/^[123](?:,[123])*$/', $get['price']) || count(array_unique(explode(',', $get['price']))) !== count(explode(',', $get['price']))))) {
            return $bad;
        }
        $notFound = [404, ['error' => ['code' => 'BRAND_NOT_FOUND']]];
        $brand = $this->db->table('brands')->where('slug', $slug)->where('is_visible', true)->first(['id', 'name', 'slug', 'image']);
        $name = Text::plain((string) $brand?->name);
        $brandSlug = Text::slug((string) $brand?->slug);
        if (! $brand || $brandSlug === '' || $name === '') {
            return $notFound;
        }
        $id = (int) $brand->id;

        $pricesVisible = $this->settings->productPricesVisible();
        $sort = in_array($get['sort'] ?? '', ['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'], true) ? $get['sort'] : 'default';
        $where = 'p.brand_id = ? and p.is_visible = 1';
        $params = [$id];
        $priceKeys = $pricesVisible && isset($get['price']) ? array_values(array_unique(explode(',', $get['price']))) : [];
        if ($priceKeys) {
            [$sql, $values] = CatalogFilters::price(implode(',', $priceKeys));
            $where .= $sql;
            array_push($params, ...$values);
        }
        $total = (int) ($this->row("select count(*) as total from products p where {$where}", $params)['total'] ?? 0);
        $order = CatalogFilters::sort($sort, $pricesVisible) ?? 'p.id desc';
        $rows = $this->rows('select '.CatalogFilters::CARD_BASE." from products p where {$where} order by {$order} limit ".(($page - 1) * $perPage).', '.$perPage, $params);

        $gallery = [];
        $ids = array_values(array_unique(array_filter(array_map(fn ($row) => (int) $row['id'], $rows))));
        if ($ids) {
            foreach ($this->db->table('product_images')->whereIn('product_id', $ids)->where('is_visible', true)->orderBy('sort_order')->orderBy('id')->get(['product_id', 'path']) as $photo) {
                $file = trim((string) $photo->path);
                $productId = (int) $photo->product_id;
                if ($file === '' || $this->urls->product($file) === '' || in_array($file, $gallery[$productId] ?? [], true)) {
                    continue;
                }
                $gallery[$productId][] = $file;
            }
        }
        $pricing = new PricingPolicy($pricesVisible);
        $products = [];
        foreach ($rows as $row) {
            if ($dto = $this->cards->dto($row, $pricing, $gallery[(int) $row['id']] ?? [])) {
                $products[] = $dto;
            }
        }
        $brandOptions = [];
        foreach ($this->db->table('brands')->where('is_visible', true)->orderBy('sort_order')->orderByDesc('id')->get(['id', 'name', 'slug']) as $option) {
            $optionName = Text::plain($option->name);
            $optionSlug = Text::slug($option->slug);
            if ($optionName !== '' && $optionSlug !== '') {
                $brandOptions[] = ['id' => (int) $option->id, 'name' => $optionName, 'slug' => $optionSlug, 'url' => '/thuong-hieu/'.$optionSlug.'/'];
            }
        }
        $seoRow = $this->db->table('seo_metas')->where('owner_type', Brand::class)->where('owner_id', $id)->first(['title', 'description', 'keywords']);
        $title = filled($seoRow?->title) ? Text::seo($seoRow->title) : Text::seo($name);
        if ($title !== '' && stripos($title, 'phúc thịnh s') === false) {
            $title .= ' | Phúc Thịnh Solar';
        }

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/thuong-hieu/'.$brandSlug.'/',
            'brand' => ['id' => $id, 'name' => $name, 'slug' => $brandSlug, 'url' => '/thuong-hieu/'.$brandSlug.'/'],
            'seo' => ['title' => $title, 'description' => filled($seoRow?->description) ? Text::seo($seoRow->description) : '',
                'keywords' => filled($seoRow?->keywords) ? Text::seo($seoRow->keywords) : '', 'image' => $this->urls->visual((string) $brand->image, 'upload/product/')],
            'products' => $products,
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => max(0, $total)],
            'filters' => ['brands' => $brandOptions, 'priceRanges' => $pricesVisible ? array_map(fn ($key, $range) => ['key' => (string) $key, 'label' => $range[2]], array_keys(CatalogFilters::PRICE_RANGES), CatalogFilters::PRICE_RANGES) : []],
            'selected' => ['sort' => $sort, 'priceKeys' => $priceKeys],
            'pricesVisible' => $pricesVisible,
        ]];
    }
}
