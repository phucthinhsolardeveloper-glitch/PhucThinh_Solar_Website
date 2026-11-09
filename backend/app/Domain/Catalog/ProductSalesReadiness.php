<?php

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/** Finds editable gaps on public products; these are prompts for staff, not a search ranking score. */
class ProductSalesReadiness
{
    public const FILTERS = [
        'image' => 'Thiếu ảnh chính',
        'seo' => 'Chưa có SEO riêng đủ tiêu đề và mô tả',
        'summary' => 'Thiếu mô tả ngắn',
        'price' => 'Chưa có giá hoặc chưa chọn báo giá',
        'article' => 'Chưa liên kết bài viết',
    ];

    /** Data worth completing, but not counted as a sales gap on each row. */
    public const DATA_FILTERS = [
        'feed' => 'Thiếu ảnh Google Shopping',
        'content' => 'Thiếu nội dung chi tiết',
        'specs' => 'Thiếu thông số kỹ thuật',
        'power' => 'Thiếu công suất',
        'shipping' => 'Thiếu kích thước đóng gói',
        'discount' => 'Đang giảm giá',
    ];

    /** @return list<string> */
    public function issues(Product $product): array
    {
        if (! $product->is_visible) {
            return [];
        }

        $seo = $product->relationLoaded('seo') ? $product->seo : $product->seo()->first();
        $articleCount = $product->getAttribute('visible_articles_count');
        $articleCount ??= $product->visibleArticles()->count();

        $issues = [];
        if (trim((string) $product->image) === '') {
            $issues[] = 'Thiếu ảnh';
        }
        if (! $seo || trim((string) $seo->getAttribute('title')) === '' || trim((string) $seo->getAttribute('description')) === '') {
            $issues[] = 'SEO riêng';
        }
        if (trim((string) $product->summary) === '') {
            $issues[] = 'Mô tả ngắn';
        }
        if (! $product->quote_only && (float) $product->regular_price <= 0) {
            $issues[] = 'Giá / báo giá';
        }
        if ((int) $articleCount === 0) {
            $issues[] = 'Liên kết bài viết';
        }

        return $issues;
    }

    /** @param Builder<Product> $query @return Builder<Product> */
    public function filter(Builder $query, string $gap): Builder
    {
        if (! array_key_exists($gap, self::FILTERS + self::DATA_FILTERS)) {
            return $query;
        }

        $query->where('products.is_visible', true);

        return match ($gap) {
            'image' => $query->whereRaw("TRIM(COALESCE(products.image, '')) = ''"),
            'seo' => $query->whereDoesntHave('seo', fn (Builder $seo) => $seo
                ->whereRaw("TRIM(COALESCE(title, '')) <> ''")
                ->whereRaw("TRIM(COALESCE(description, '')) <> ''")),
            'summary' => $query->whereRaw("TRIM(COALESCE(products.summary, '')) = ''"),
            'price' => $query->where('products.quote_only', false)
                ->where(fn (Builder $price) => $price->whereNull('products.regular_price')->orWhere('products.regular_price', '<=', 0)),
            'article' => $query->whereDoesntHave('visibleArticles'),
            'feed' => $query->whereRaw("TRIM(COALESCE(products.feed_image, '')) = ''"),
            'content' => $query->whereRaw("TRIM(COALESCE(products.body, '')) = ''")
                ->whereDoesntHave('contentSection', fn (Builder $section) => $section->where('is_visible', true)->whereNotNull('blocks')->where('blocks', '!=', '[]')),
            'specs' => $query->whereRaw("TRIM(COALESCE(products.specs_html, '')) = ''")->whereRaw("TRIM(COALESCE(products.additional_specs, '')) IN ('', '[]')"),
            'power' => $query->where(fn (Builder $power) => $power->whereNull('products.power_watts')->orWhere('products.power_watts', '<=', 0)),
            'shipping' => $query->where(fn (Builder $box) => $box->whereRaw("TRIM(COALESCE(products.carton_dimensions, '')) = ''")->orWhereRaw("TRIM(COALESCE(products.weight, '')) IN ('', '0')")),
            'discount' => $query->where('products.sale_price', '>', 0)->whereColumn('products.sale_price', '<', 'products.regular_price'),
        };
    }
}
