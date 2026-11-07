<?php

namespace App\Domain\Catalog\Health;

use App\Legacy\Catalog\ProductContentResolver;
use App\Legacy\Catalog\ProductImageResolver;
use App\Legacy\Support\StatusFlags;
use Illuminate\Support\Facades\DB;

/**
 * Finds catalog data a visitor would see as broken, before a visitor does. Read-only; uses the same
 * rules the site uses (image pick, content inheritance), so a reported product really shows the problem.
 */
class CatalogHealth
{
    /** Checks, in the order they are shown. Severity "error" means visibly broken on the site. */
    public const CHECKS = [
        'no_image' => ['error', 'Sản phẩm đang hiện nhưng không có ảnh nào'],
        'image_from_gallery' => ['warning', 'Ảnh chính trống hoặc mất file, đang dùng ảnh thư viện'],
        'sale_above_regular' => ['error', 'Giá khuyến mãi cao hơn giá bán'],
        'quote_with_price' => ['info', 'Đánh dấu báo giá nhưng vẫn nhập giá'],
        'no_price' => ['warning', 'Đang hiện nhưng không có giá, sẽ thành "Liên hệ báo giá"'],
        'no_description' => ['warning', 'Đang hiện nhưng không có khối mô tả nào'],
        'missing_category' => ['error', 'Trỏ tới danh mục không tồn tại'],
        'hidden_category' => ['warning', 'Nằm trong danh mục đang ẩn'],
        'missing_brand' => ['error', 'Trỏ tới thương hiệu không tồn tại'],
        'duplicate_slug' => ['error', 'Đường dẫn trùng với bản ghi khác'],
    ];

    public function __construct(
        private readonly ProductImageResolver $images,
        private readonly ProductContentResolver $content,
    ) {}

    /**
     * @return array<string, list<array{id: int, name: string}>>
     */
    public function run(): array
    {
        $issues = array_fill_keys(array_keys(self::CHECKS), []);
        $legacy = DB::connection('legacy');
        $lists = $legacy->table('table_product_list')->pluck('status', 'id');
        $cats = $legacy->table('table_product_cat')->pluck('status', 'id');
        $brands = $legacy->table('table_product_brand')->pluck('id', 'id');

        $products = $legacy->table('table_product')->where('type', 'san-pham')->orderBy('id')
            ->get(['id', 'type', 'namevi', 'photo', 'status', 'regular_price', 'sale_price', 'id_list', 'id_cat', 'id_brand']);

        foreach ($products as $product) {
            $row = (array) $product;
            $flags = new StatusFlags($product->status);
            if (! $flags->visible()) {
                continue;
            }
            $ref = ['id' => (int) $product->id, 'name' => (string) $product->namevi];

            $image = $this->images->resolve($row);
            if (! $image['has_image']) {
                $issues['no_image'][] = $ref;
            } elseif ($image['source'] === 'gallery') {
                $issues['image_from_gallery'][] = $ref;
            }

            $regular = (float) $product->regular_price;
            $sale = (float) $product->sale_price;
            if ($regular > 0 && $sale > $regular) {
                $issues['sale_above_regular'][] = $ref;
            }
            if ($flags->has('baogia') && ($regular > 0 || $sale > 0)) {
                $issues['quote_with_price'][] = $ref;
            }
            if (! $flags->has('baogia') && $regular <= 0 && $sale <= 0) {
                $issues['no_price'][] = $ref;
            }

            $resolved = $this->content->resolve($row);
            if ($resolved['blocks'] === [] && $resolved['source'] !== 'hide') {
                $issues['no_description'][] = $ref;
            }

            foreach ([['id_list', $lists], ['id_cat', $cats]] as [$column, $known]) {
                $id = (int) $product->{$column};
                if ($id <= 0) {
                    continue;
                }
                if (! $known->has($id)) {
                    $issues['missing_category'][] = $ref;
                } elseif (! (new StatusFlags($known[$id]))->visible()) {
                    $issues['hidden_category'][] = $ref;
                }
            }
            if ((int) $product->id_brand > 0 && ! $brands->has((int) $product->id_brand)) {
                $issues['missing_brand'][] = $ref;
            }
        }

        $issues['duplicate_slug'] = $this->duplicateSlugs();

        return array_map(fn ($rows) => array_values(array_unique($rows, SORT_REGULAR)), $issues);
    }

    /**
     * Every table with a slugvi column shares one URL space, as the PHP router resolves them.
     *
     * @return list<array{id: int, name: string}>
     */
    private function duplicateSlugs(): array
    {
        $legacy = DB::connection('legacy');
        $tables = $legacy->table('information_schema.columns')
            ->whereRaw('table_schema = database()')->where('column_name', 'slugvi')->where('table_name', 'like', 'table\_%')
            ->pluck('table_name');
        $seen = [];
        foreach ($tables as $table) {
            foreach ($legacy->table($table)->where('slugvi', '!=', '')->get(['id', 'slugvi']) as $row) {
                $seen[(string) $row->slugvi][] = ['id' => (int) $row->id, 'name' => $table.': '.$row->slugvi];
            }
        }
        $out = [];
        foreach ($seen as $rows) {
            if (count($rows) > 1) {
                array_push($out, ...$rows);
            }
        }

        return $out;
    }
}
