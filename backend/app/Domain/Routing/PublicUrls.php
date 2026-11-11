<?php

namespace App\Domain\Routing;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\PostVisibility;
use App\Models\Album;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PageItem;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Review;
use App\Models\SeoMeta;
use App\Models\StoreLocation;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

/**
 * Where a record from Admin shows up on the website, so every screen can offer "Xem trên website".
 * Same addresses as the sitemap and the Nuxt pages. In production Admin and site share one host, so paths stay
 * relative; locally SITE_PUBLIC_URL points at the Nuxt server (config site.public_url).
 */
final class PublicUrls
{
    /** SeoMeta.page → page. */
    private const SEO_PAGES = ['home' => '/', 'gioi-thieu' => '/gioi-thieu/', 'san-pham' => '/san-pham/', 'giai-phap' => '/giai-phap/',
        'du-an' => '/du-an-da-thuc-hien/', 'tin-tuc' => '/tin-tuc/', 'thu-vien-anh' => '/thu-vien-anh/', 'video' => '/video/',
        'dai-ly' => '/dai-ly/', 'lien-he' => '/lien-he/'];

    /** First part of a PageItem section → the page it fills. */
    private const SECTION_PAGES = ['home' => '/', 'about' => '/gioi-thieu/', 'contact' => '/lien-he/', 'dealer' => '/dai-ly/', 'catalog' => '/san-pham/',
        'search' => '/tim-kiem/', 'solutions' => '/giai-phap/', 'news' => '/tin-tuc/', 'projects' => '/du-an-da-thuc-hien/', 'gallery' => '/thu-vien-anh/', 'video' => '/video/'];

    public static function base(): string
    {
        return rtrim((string) config('site.public_url', ''), '/');
    }

    public static function home(): string
    {
        return self::base().'/';
    }

    /** Public path of the page showing this record, or null when it has none. */
    public static function path(Model $record): ?string
    {
        $path = match (true) {
            $record instanceof Product => self::slugged('/san-pham/', $record->slug),
            $record instanceof Brand => self::slugged('/thuong-hieu/', $record->slug, '/'),
            $record instanceof Category => self::categoryPath($record),
            $record instanceof Post => match ($record->kind) {
                'solution' => self::slugged('/giai-phap/', $record->slug, '/'),
                'policy' => self::slugged('/ho-tro/', $record->slug, '/'),
                default => self::slugged('/', $record->slug),
            },
            $record instanceof PostCategory => (int) $record->depth === 1 ? self::slugged('/', $record->slug) : null,
            $record instanceof Album => self::slugged('/', $record->slug, '/'),
            $record instanceof Video => '/video/',
            $record instanceof Banner => '/',
            $record instanceof StoreLocation => '/lien-he/',
            $record instanceof Review => ($slug = Product::query()->whereKey($record->product_id)->value('slug')) ? self::slugged('/san-pham/', (string) $slug) : null,
            $record instanceof Redirect => str_starts_with((string) $record->from_path, '/') ? (string) $record->from_path : null,
            $record instanceof SeoMeta => self::SEO_PAGES[(string) $record->page] ?? null,
            $record instanceof PageItem => self::SECTION_PAGES[strtok((string) $record->section, '.') ?: ''] ?? null,
            default => null,
        };

        return $path === '' ? null : $path;
    }

    public static function url(Model $record): ?string
    {
        $path = self::path($record);

        return $path === null ? null : self::base().$path;
    }

    /**
     * False when the page would answer "not found" right now: a hidden record, or a category with nothing visible in it.
     * Banners, page blocks, SEO and the like live inside other pages, so they count as live.
     */
    public static function isLive(Model $record): bool
    {
        if ($record instanceof Category) {
            return (bool) $record->is_visible && app(CategoryTree::class)->hasVisibleProducts((int) $record->getKey());
        }
        if ($record instanceof Post) {
            return PostVisibility::isLive($record);
        }
        if (array_key_exists('is_visible', $record->getAttributes())) {
            return (bool) $record->getAttribute('is_visible');
        }

        return true;
    }

    private static function slugged(string $prefix, mixed $slug, string $suffix = ''): ?string
    {
        $slug = trim((string) $slug, '/');

        return $slug === '' ? null : $prefix.$slug.$suffix;
    }

    private static function categoryPath(Category $category): ?string
    {
        $path = trim(app(CategoryTree::class)->path((int) $category->getKey()), '/');

        return $path === '' ? null : '/'.$path.'/';
    }
}
