<?php

namespace App\Domain\Catalog\Health;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductContentResolver;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
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
        'missing_category' => ['error', 'Chưa gắn danh mục'],
        'hidden_category' => ['warning', 'Nằm trong danh mục đang ẩn'],
        'missing_brand' => ['error', 'Trỏ tới thương hiệu không tồn tại'],
        'duplicate_slug' => ['error', 'Đường dẫn trùng với bản ghi khác'],
    ];

    /** Tables whose slug is a top-level address on the site. */
    private const SLUG_TABLES = ['products', 'brands', 'posts', 'albums'];

    public function __construct(
        private readonly ProductMediaUrls $media,
        private readonly ProductContentResolver $content,
        private readonly CategoryTree $tree,
    ) {}

    /**
     * @return array<string, list<array{id: int, name: string}>>
     */
    public function run(): array
    {
        $issues = array_fill_keys(array_keys(self::CHECKS), []);
        $brands = DB::table('brands')->pluck('id', 'id');
        $gallery = DB::table('product_images')->where('is_visible', true)->orderBy('sort_order')->orderBy('id')
            ->get(['product_id', 'path'])->groupBy('product_id');

        $products = DB::table('products')->where('is_visible', true)->orderBy('id')
            ->get(['id', 'name', 'image', 'quote_only', 'regular_price', 'sale_price', 'category_id', 'brand_id']);

        foreach ($products as $product) {
            $ref = ['id' => (int) $product->id, 'name' => (string) $product->name];

            if ($this->media->product($product->image) === '') {
                $fromGallery = collect($gallery->get($product->id, []))->contains(fn ($image) => $this->media->product($image->path) !== '');
                $issues[$fromGallery ? 'image_from_gallery' : 'no_image'][] = $ref;
            }

            $regular = (float) $product->regular_price;
            $sale = (float) $product->sale_price;
            if ($regular > 0 && $sale > $regular) {
                $issues['sale_above_regular'][] = $ref;
            }
            if ($product->quote_only && ($regular > 0 || $sale > 0)) {
                $issues['quote_with_price'][] = $ref;
            }
            if (! $product->quote_only && $regular <= 0 && $sale <= 0) {
                $issues['no_price'][] = $ref;
            }

            $chain = $product->category_id ? $this->tree->chain((int) $product->category_id) : [];
            $resolved = $this->content->resolve([
                'id' => (int) $product->id,
                'id_brand' => (int) $product->brand_id,
                'id_list' => (int) ($chain[0]['id'] ?? 0),
                'id_cat' => (int) ($chain[1]['id'] ?? 0),
            ]);
            if ($resolved['blocks'] === [] && $resolved['source'] !== 'hide') {
                $issues['no_description'][] = $ref;
            }

            if ($chain === []) {
                $issues['missing_category'][] = $ref;
            } elseif (collect($chain)->contains(fn ($node) => ! $node['is_visible'])) {
                $issues['hidden_category'][] = $ref;
            }
            if ((int) $product->brand_id > 0 && ! $brands->has((int) $product->brand_id)) {
                $issues['missing_brand'][] = $ref;
            }
        }

        $issues['duplicate_slug'] = $this->duplicateSlugs();

        return array_map(fn ($rows) => array_values(array_unique($rows, SORT_REGULAR)), $issues);
    }

    /**
     * Top categories, products, brands, posts and albums share one address space.
     *
     * @return list<array{id: int, name: string}>
     */
    private function duplicateSlugs(): array
    {
        $seen = [];
        $sources = [['categories', DB::table('categories')->whereNull('parent_id')]];
        foreach (self::SLUG_TABLES as $table) {
            $sources[] = [$table, DB::table($table)];
        }
        foreach ($sources as [$table, $query]) {
            foreach ($query->where('slug', '!=', '')->get(['id', 'slug']) as $row) {
                $seen[(string) $row->slug][] = ['id' => (int) $row->id, 'name' => $table.': '.$row->slug];
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
