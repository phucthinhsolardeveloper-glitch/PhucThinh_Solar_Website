<?php

namespace App\Domain\Analytics;

use App\Domain\Catalog\CategoryTree;
use Illuminate\Support\Facades\DB;

/** A readable name for a public path ("/danh-muc-san-pham/den-nlmt/den-pha/" -> "Danh mục: Đèn Pha"), for Admin reports. */
final class PageTitles
{
    private const FIXED = ['/' => 'Trang chủ', '/san-pham' => 'Tất cả sản phẩm', '/gioi-thieu' => 'Giới thiệu', '/lien-he' => 'Liên hệ', '/dai-ly' => 'Đại lý',
        '/tin-tuc' => 'Tin tức', '/giai-phap' => 'Giải pháp', '/du-an-da-thuc-hien' => 'Dự án đã thực hiện', '/thu-vien-anh' => 'Thư viện ảnh', '/video' => 'Video',
        '/gio-hang' => 'Giỏ hàng', '/thanh-toan' => 'Thanh toán', '/tim-kiem' => 'Tìm kiếm', '/thuong-hieu' => 'Thương hiệu', '/so-sanh' => 'So sánh sản phẩm'];

    /** @param list<string> $paths @return array<string, string> path => name ('' when unknown) */
    public static function for(array $paths): array
    {
        $out = [];
        foreach ($paths as $path) {
            $clean = '/'.trim((string) parse_url($path, PHP_URL_PATH), '/');
            $clean = $clean === '/' ? '/' : $clean;
            $segments = array_values(array_filter(explode('/', $clean)));
            $out[$path] = self::FIXED[$clean] ?? match (true) {
                ($segments[0] ?? '') === 'danh-muc-san-pham' && count($segments) > 1 => self::category(array_slice($segments, 1)),
                ($segments[0] ?? '') === 'san-pham' && count($segments) === 2 => self::name('products', 'name', $segments[1], 'Sản phẩm: '),
                ($segments[0] ?? '') === 'thuong-hieu' && count($segments) === 2 => self::name('brands', 'name', $segments[1], 'Thương hiệu: '),
                ($segments[0] ?? '') === 'giai-phap' && count($segments) === 2 => self::name('posts', 'title', $segments[1], 'Giải pháp: '),
                ($segments[0] ?? '') === 'ho-tro' && count($segments) === 2 => self::name('posts', 'title', $segments[1], 'Chính sách: '),
                count($segments) === 1 => self::name('posts', 'title', $segments[0], '') ?: self::name('albums', 'title', $segments[0], 'Album: ')
                    ?: self::name('post_categories', 'name', $segments[0], 'Chuyên mục: '),
                default => '',
            };
        }

        return $out;
    }

    /** @param list<string> $segments */
    private static function category(array $segments): string
    {
        $id = app(CategoryTree::class)->resolvePath($segments);
        $node = $id ? app(CategoryTree::class)->get($id) : null;

        return $node ? 'Danh mục: '.$node['name'] : '';
    }

    private static function name(string $table, string $column, string $slug, string $prefix): string
    {
        $name = (string) DB::table($table)->where('slug', urldecode($slug))->value($column);

        return $name === '' ? '' : $prefix.$name;
    }
}
