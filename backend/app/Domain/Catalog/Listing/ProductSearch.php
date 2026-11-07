<?php

namespace App\Domain\Catalog\Listing;

use App\Domain\Catalog\PricingPolicy;
use App\Legacy\Settings\LegacySettings;
use App\Legacy\Support\LegacyText;
use Illuminate\Support\Facades\DB;

/**
 * Product search (api/pts_v1_search.php, same matching as sources/search.php): the raw keyword against
 * names, specs and content, its slug form against slugs, and a compact form (no separators) against
 * model codes, so "BCT-WR10" also finds "bctwr10".
 */
class ProductSearch
{
    private const RAW_FIELDS = ['p.namevi', 'p.nameen', 'p.code', 'p.pdp_profile', 'p.descvi', 'p.descen', 'p.highlightsvi', 'p.highlightsen',
        'p.applicationsvi', 'p.applicationsen', 'p.installationvi', 'p.installationen', 'p.contentvi', 'p.contenten', 'p.additional_specs', 'p.options',
        'p.thongsokythuatvi', 'p.thongsokythuaten', 'p.warranty', 'p.battery_capacity', 'p.panel_power', 'p.light_source', 'p.lamp_dimensions',
        'p.color_temperature', 'p.light_color', 'p.material', 'p.charging_time', 'p.lighting_time', 'p.waterproof_rating', 'p.led_chip', 'p.lighting_area',
        'p.installation_height', 'p.panel_dimensions', 'p.package_contents', 'p.carton_quantity', 'p.carton_dimensions', 'p.khoanggia'];

    private const COMPACT_FIELDS = ['p.code', 'p.slugvi', 'p.slugen', 'p.pdp_profile', 'p.options', 'p.additional_specs', 'p.thongsokythuatvi', 'p.thongsokythuaten',
        'p.contentvi', 'p.contenten', 'p.descvi', 'p.descen'];

    public function __construct(private readonly LegacySettings $settings, private readonly ProductCard $cards) {}

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        $bad = [400, ['error' => ['code' => 'BAD_QUERY']]];
        if (array_diff(array_keys($get), ['keyword', 'page', 'per_page', 'brand', 'price', 'sort'])) {
            return $bad;
        }
        $keyword = isset($get['keyword']) && is_scalar($get['keyword']) ? trim((string) $get['keyword']) : '';
        $int = '/^[1-9][0-9]*$/';
        $valid = fn (string $key, string $pattern) => ! isset($get[$key]) || (is_string($get[$key]) && preg_match($pattern, $get[$key]));
        if ($keyword === '' || mb_strlen($keyword) > 100 || preg_match('/[\x00-\x1F\x7F]/', $keyword) || ! $valid('page', $int) || ! $valid('per_page', $int)
            || ! $valid('brand', '/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/') || ! $valid('price', '/^[123](?:,[123])*$/')
            || (isset($get['sort']) && ! in_array($get['sort'], ['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'], true))) {
            return $bad;
        }
        $page = (int) ($get['page'] ?? 1);
        $perPage = (int) ($get['per_page'] ?? 20);
        if ($page > 10000 || $perPage > 48) {
            return $bad;
        }
        $slugForm = LegacyText::changeTitle(htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'));
        if ($slugForm === '') {
            return $bad;
        }
        $sort = $get['sort'] ?? 'default';
        $pricesVisible = $this->settings->productPricesVisible();

        $clauses = [];
        $params = ['san-pham'];
        foreach (self::RAW_FIELDS as $field) {
            $clauses[] = $field.' LIKE ?';
            $params[] = '%'.$keyword.'%';
        }
        array_push($clauses, 'p.slugvi LIKE ?', 'p.slugen LIKE ?');
        array_push($params, '%'.$slugForm.'%', '%'.$slugForm.'%');
        $clauses[] = 'exists (select 1 from table_product_brand pb where pb.id = p.id_brand and (pb.namevi LIKE ? or pb.nameen LIKE ? or pb.slugvi LIKE ? or pb.slugen LIKE ?))';
        array_push($params, '%'.$keyword.'%', '%'.$keyword.'%', '%'.$slugForm.'%', '%'.$slugForm.'%');
        $compact = (string) preg_replace('/[^a-z0-9]/', '', strtolower($slugForm));
        if ($compact !== '') {
            foreach (self::COMPACT_FIELDS as $field) {
                $expr = 'LOWER('.$field.')';
                foreach (['-', ' ', '_', '/', '.'] as $separator) {
                    $expr = "REPLACE({$expr}, '{$separator}', '')";
                }
                $clauses[] = $expr.' LIKE ?';
                $params[] = '%'.$compact.'%';
            }
        }
        $where = 'p.type = ? and ('.implode(' or ', $clauses).") and find_in_set('hienthi',p.status)";
        $brandIds = isset($get['brand']) ? array_values(array_unique(array_map('intval', explode(',', $get['brand'])))) : [];
        $priceKeys = isset($get['price']) && $pricesVisible ? array_values(array_unique(explode(',', $get['price']))) : [];
        if ($brandIds) {
            $where .= ' and p.id_brand in ('.implode(',', array_fill(0, count($brandIds), '?')).')';
            array_push($params, ...$brandIds);
        }
        if ($priceKeys) {
            [$sql, $values] = CatalogFilters::price(implode(',', $priceKeys));
            $where .= $sql;
            array_push($params, ...$values);
        }
        $db = DB::connection('legacy');
        $total = (int) ($db->selectOne("select count(*) total from table_product p where {$where}", $params)->total ?? 0);
        $order = CatalogFilters::sort($sort, $pricesVisible) ?? CatalogFilters::defaultOrder(false);
        $pricing = new PricingPolicy($pricesVisible);
        $products = [];
        foreach ($db->select("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from table_product p where {$where} order by {$order} limit ".(($page - 1) * $perPage).', '.$perPage, $params) as $row) {
            if ($dto = $this->cards->dto((array) $row, $pricing)) {
                $products[] = $dto;
            }
        }
        $brands = [];
        foreach ($db->select("select id,namevi from table_product_brand where type = 'san-pham' and find_in_set('hienthi',status) order by numb,id desc") as $row) {
            $name = LegacyText::plain($row->namevi);
            if ((int) $row->id > 0 && $name !== '') {
                $brands[] = ['id' => (int) $row->id, 'name' => $name];
            }
        }

        return [200, [
            'schemaVersion' => 1,
            'locale' => 'vi',
            'keyword' => LegacyText::plain($keyword),
            'products' => $products,
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => $total],
            'filters' => ['brands' => $brands, 'priceRanges' => $pricesVisible ? array_map(fn ($key, $range) => ['key' => (string) $key, 'label' => $range[2]], array_keys(CatalogFilters::PRICE_RANGES), CatalogFilters::PRICE_RANGES) : []],
            'selected' => ['brandIds' => $brandIds, 'priceKeys' => $priceKeys, 'sort' => $sort],
        ]];
    }
}
