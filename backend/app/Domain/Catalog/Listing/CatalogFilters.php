<?php

namespace App\Domain\Catalog\Listing;

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

    /** @return array{0: string, 1: list<mixed>} */
    public static function price(string $keys): array
    {
        $conditions = [];
        $params = [];
        $effective = 'if(sale_price>0,sale_price,regular_price)';
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

        return $conditions ? [" and not find_in_set('baogia',status) and (".implode(' or ', $conditions).')', $params] : ['', []];
    }

    /**
     * @param  list<string>  $keys
     * @return array{0: string, 1: list<mixed>}
     */
    public static function needs(array $keys): array
    {
        $conditions = [];
        $params = [];
        foreach ($keys as $key) {
            $need = self::NEEDS[$key];
            $placeholders = implode(',', array_fill(0, count($need['categories']), '?'));
            $keywordSql = '('.implode(' or ', array_fill(0, count($need['keywords']), '(namevi like ? or descvi like ?)')).')';
            $conditions[] = "(id_cat in (select pc.id from table_product_cat pc where pc.type = ? and find_in_set('hienthi',pc.status) and pc.slugvi in ({$placeholders}))"
                ." or (id_list not in (select pl.id from table_product_list pl where pl.type = ? and pl.slugvi = ?) and {$keywordSql}))";
            array_push($params, 'san-pham', ...$need['categories']);
            array_push($params, 'san-pham', 'den-nlmt');
            foreach ($need['keywords'] as $keyword) {
                array_push($params, '%'.$keyword.'%', '%'.$keyword.'%');
            }
        }

        return $conditions ? [' and ('.implode(' or ', $conditions).')', $params] : ['', []];
    }

    /** null = no explicit order; price sorts fall back to default when prices are hidden, as the PHP switch did. */
    public static function sort(string $key, bool $pricesVisible): ?string
    {
        return match ($key) {
            'abc' => 'p.namevi asc',
            'abc-desc' => 'p.namevi desc',
            'price' => $pricesVisible ? 'p.regular_price asc' : null,
            'price-desc' => $pricesVisible ? 'p.regular_price desc' : null,
            'new' => 'p.date_created desc',
            'new-desc' => 'p.date_created asc',
            default => null,
        };
    }

    /** Owner-chosen lighting order (flood, street, garden, UFO, ceiling), then products with a picture, then manual order. */
    public static function defaultOrder(): string
    {
        $lighting = "(select id from table_product_list where type = 'san-pham' and slugvi = 'den-nlmt' limit 1)";
        $garden = "(select id from table_product_list where type = 'san-pham' and slugvi = 'den-san-vuon' limit 1)";
        $clauses = [];
        foreach (['den-pha-nang-luong-mat-troi' => 10, 'den-duong' => 20, 'den-san-vuon' => 30, 'den-ufo' => 40, 'den-op-tran' => 50] as $slug => $rank) {
            $clauses[] = "when p.id_list = {$lighting} and p.id_cat = (select id from table_product_cat where type = 'san-pham' and id_list = {$lighting} and slugvi = '{$slug}' limit 1) then {$rank}";
        }
        $clauses[] = "when p.id_list = {$garden} then 30";
        $hasPhoto = "(p.photo is not null and trim(p.photo) <> '') or exists (select 1 from table_gallery g where g.id_parent = p.id and g.com = 'product' and g.kind = 'man' and g.val = p.type and find_in_set('hienthi', g.status))";

        return 'case '.implode(' ', $clauses)." else 1000 end, case when {$hasPhoto} then 0 else 1 end, p.numb,p.id desc";
    }

    /** Search slug of a keyword, as the PHP $func->changeTitle() builds it. */
    public static function keywordSlug(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $map = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd' => 'đ|Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ',
            '' => '`|\~|\!|\@|\#|\||\$|\%|\^|\&|\*|\(|\)|\+|\=|\,|\.|\/|\?|\>|\<|\'|\"|\“|\”|\:|\;|_',
        ];
        foreach ($map as $ascii => $pattern) {
            $text = (string) preg_replace("/({$pattern})/i", (string) $ascii, $text);
        }
        $text = strtolower($text);
        $text = (string) preg_replace('/[^a-z0-9-\s]/', '', $text);
        $text = (string) preg_replace('/([\s]+)/', '-', $text);
        $text = str_replace(['%20', ' '], '-', $text);
        foreach (['/\-\-\-\-\-/', '/\-\-\-\-/', '/\-\-\-/', '/\-\-/'] as $dashes) {
            $text = (string) preg_replace($dashes, '-', $text);
        }

        return (string) preg_replace('/\@\-|\-\@|\@/', '', '@'.$text.'@');
    }
}
