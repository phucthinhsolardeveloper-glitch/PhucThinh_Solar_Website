<?php

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\Listing\CatalogLanding;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Routing\Redirects;
use App\Domain\Seo\Sitemap;
use Illuminate\Support\Facades\DB;

// Links the site prints go straight to the indexed address; old addresses keep redirecting.

it('points a printed link at the new address without counting a visit to the old one', function () {
    Redirects::record('/danh-muc-san-pham/thu-cu/', '/danh-muc-san-pham/thu-moi/', true, 'manual');

    expect(Redirects::current('/danh-muc-san-pham/thu-cu/con/?is_need=a#b'))->toBe('/danh-muc-san-pham/thu-moi/con/?is_need=a#b')
        ->and(Redirects::current('/danh-muc-san-pham/thu-cu/'))->toBe('/danh-muc-san-pham/thu-moi/')
        ->and(Redirects::current('/san-pham/'))->toBe('/san-pham/')
        ->and(Redirects::current(''))->toBe('')
        ->and(Redirects::current('https://example.com/danh-muc-san-pham/thu-cu/'))->toBe('https://example.com/danh-muc-san-pham/thu-cu/')
        ->and((int) DB::table('redirects')->where('from_path', '/danh-muc-san-pham/thu-cu/')->value('hits'))->toBe(0)
        ->and(Redirects::find('/danh-muc-san-pham/thu-cu/'))->toBe('/danh-muc-san-pham/thu-moi/')
        ->and((int) DB::table('redirects')->where('from_path', '/danh-muc-san-pham/thu-cu/')->value('hits'))->toBe(1);
});

it('links article groups and article breadcrumbs by their indexed address', function () {
    $site = (string) $this->getJson('/api/v1/site')->assertOk()->getContent();
    expect(str_replace('\/', '/', $site))->not->toContain('"/category/');

    $post = DB::table('posts as p')->join('post_categories as c', 'c.id', '=', 'p.category_id')->where('p.kind', 'article')->where('p.is_visible', true)
        ->where('c.kind', 'article')->where('c.is_visible', true)->orderByDesc('p.id')->first(['p.slug', 'c.slug as category']);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonPath('article.category.url', '/'.$post->category);
});

it('lists the photo and video pages in the sitemap only when they have something', function () {
    $paths = fn () => array_column(app(Sitemap::class)->entries(), 'path');
    DB::table('albums')->update(['is_visible' => false]);
    DB::table('videos')->update(['is_visible' => false]);
    expect($paths())->not->toContain('/thu-vien-anh/', '/video/');

    DB::table('albums')->insert(['title' => 'Album thử', 'slug' => 'album-thu-seo', 'is_visible' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('videos')->insert(['title' => 'Video thử', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'is_visible' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
    expect($paths())->toContain('/thu-vien-anh/', '/video/');
});

it('rewrites a category card link only when its old address no longer answers', function () {
    $stocked = DB::table('products')->where('is_visible', true)->whereNotNull('category_id')->value('category_id');
    $live = '/'.trim(app(CategoryTree::class)->path((int) $stocked), '/').'/';
    Redirects::record($live, '/san-pham/', false, 'manual');
    Redirects::record('/danh-muc-san-pham/khong-con/', '/san-pham/', false, 'manual');
    $landing = new CatalogLanding(DB::connection(), app(ProductMediaUrls::class));
    $followed = new ReflectionMethod($landing, 'followed');

    expect($followed->invoke($landing, $live))->toBe($live)
        ->and($followed->invoke($landing, '/danh-muc-san-pham/khong-con/'))->toBe('/san-pham/');
});

it('lists a brand page only while the brand has a product on the website', function () {
    $paths = fn () => array_column(app(Sitemap::class)->entries(), 'path');
    $brand = DB::table('brands as b')->join('products as p', 'p.brand_id', '=', 'b.id')->where('b.is_visible', true)->where('p.is_visible', true)->first(['b.id', 'b.slug']);
    expect($paths())->toContain('/thuong-hieu/'.$brand->slug.'/');

    DB::table('products')->where('brand_id', $brand->id)->update(['is_visible' => false]);
    expect($paths())->not->toContain('/thuong-hieu/'.$brand->slug.'/');
    $this->getJson('/api/v1/brand-detail?slug='.$brand->slug)->assertOk()->assertJsonPath('pagination.total', 0);
});
