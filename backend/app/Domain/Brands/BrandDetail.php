<?php

namespace App\Domain\Brands;

use App\Domain\Catalog\Listing\CatalogFilters;
use App\Domain\Catalog\Listing\ProductCard;
use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;
use App\Legacy\Support\LegacyText;
use App\Support\SiteSettings;

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
        $brand = $this->row("select id,namevi,slugvi,status,photo from table_product_brand where slugvi = ? and type = 'san-pham' and find_in_set('hienthi',status) limit 1", [$slug]);
        $id = (int) ($brand['id'] ?? 0);
        $name = LegacyText::plain($brand['namevi'] ?? '');
        $brandSlug = LegacyText::slug($brand['slugvi'] ?? '');
        if (! $brand || $id <= 0 || ! self::visible($brand) || $brandSlug === '' || $name === '') {
            return $notFound;
        }

        $pricesVisible = $this->settings->productPricesVisible();
        $sort = in_array($get['sort'] ?? '', ['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'], true) ? $get['sort'] : 'default';
        $where = "p.id_brand = ? and p.type = 'san-pham' and find_in_set('hienthi', p.status)";
        $params = [$id];
        $priceKeys = $pricesVisible && isset($get['price']) ? array_values(array_unique(explode(',', $get['price']))) : [];
        if ($priceKeys) {
            [$sql, $values] = CatalogFilters::price(implode(',', $priceKeys));
            $where .= $sql;
            array_push($params, ...$values);
        }
        $total = (int) ($this->row("select count(*) as total from table_product p where {$where}", $params)['total'] ?? 0);
        $order = CatalogFilters::sort($sort, $pricesVisible) ?? 'p.id desc';
        $rows = $this->rows("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from table_product p where {$where} order by {$order} limit ".(($page - 1) * $perPage).', '.$perPage, $params);

        $gallery = [];
        $ids = array_values(array_unique(array_filter(array_map(fn ($row) => (int) $row['id'], $rows))));
        if ($ids) {
            foreach ($this->rows('select id_parent,photo,status from table_gallery where id_parent in ('.implode(',', array_fill(0, count($ids), '?')).") and com = 'product' and kind = 'man' and val = 'san-pham' and find_in_set('hienthi',status) order by numb,id", $ids) as $photo) {
                $file = trim((string) $photo['photo']);
                $productId = (int) $photo['id_parent'];
                if (! self::visible($photo) || $file === '' || $this->urls->product($file) === '' || in_array($file, $gallery[$productId] ?? [], true)) {
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
        foreach ($this->rows("select namevi,slugvi,id,type,status from table_product_brand where type = 'san-pham' and find_in_set('hienthi',status) order by numb,id desc") as $option) {
            $optionName = LegacyText::plain($option['namevi']);
            $optionSlug = LegacyText::slug($option['slugvi']);
            if ((int) $option['id'] > 0 && $optionName !== '' && $optionSlug !== '' && self::visible($option)) {
                $brandOptions[] = ['id' => (int) $option['id'], 'name' => $optionName, 'slug' => $optionSlug, 'url' => '/thuong-hieu/'.$optionSlug.'/'];
            }
        }
        $seoRow = $this->row("select titlevi,descriptionvi,keywordsvi from table_seo where id_parent = ? and com = 'product' and act = 'man_brand' and type = 'san-pham' limit 1", [$id]);
        $title = ! empty($seoRow['titlevi']) ? LegacyText::seo($seoRow['titlevi']) : LegacyText::seo($name);
        if ($title !== '' && stripos($title, 'phúc thịnh s') === false) {
            $title .= ' | Phúc Thịnh Solar';
        }

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'path' => '/thuong-hieu/'.$brandSlug.'/',
            'brand' => ['id' => $id, 'name' => $name, 'slug' => $brandSlug, 'url' => '/thuong-hieu/'.$brandSlug.'/'],
            'seo' => ['title' => $title, 'description' => ! empty($seoRow['descriptionvi']) ? LegacyText::seo($seoRow['descriptionvi']) : '',
                'keywords' => ! empty($seoRow['keywordsvi']) ? LegacyText::seo($seoRow['keywordsvi']) : '', 'image' => $this->urls->visual($brand['photo'], 'upload/product/')],
            'products' => $products,
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => max(0, $total)],
            'filters' => ['brands' => $brandOptions, 'priceRanges' => $pricesVisible ? array_map(fn ($key, $range) => ['key' => (string) $key, 'label' => $range[2]], array_keys(CatalogFilters::PRICE_RANGES), CatalogFilters::PRICE_RANGES) : []],
            'selected' => ['sort' => $sort, 'priceKeys' => $priceKeys],
            'parity' => [
                'prices' => $pricesVisible ? ['included' => true] : ['included' => false, 'reason' => 'public_pricing_disabled'],
                'seoOverrides' => ['included' => true],
            ],
        ]];
    }
}
