<?php

use App\Domain\Catalog\Listing\CatalogFilters;
use App\Models\Category;
use App\Models\Product;

it('refuses malformed catalog queries with BAD_QUERY', function (array $query) {
    $this->getJson('/api/v1/catalog?'.http_build_query($query))
        ->assertStatus(400)->assertExactJson(['error' => ['code' => 'BAD_QUERY']]);
})->with([
    'unknown key' => [['other' => '1']],
    'page zero' => [['page' => '0']],
    'too many per page' => [['per_page' => '49']],
    'empty id in list' => [['brand' => '1,,2']],
    'unknown price band' => [['price' => '4']],
    'repeated need' => [['is_need' => 'du-an-cong-trinh,du-an-cong-trinh']],
    'long keyword' => [['keyword' => str_repeat('a', 101)]],
]);

it('answers 404 for a category path that does not exist', function () {
    $this->getJson('/api/v1/catalog?category=khong-ton-tai')->assertNotFound()->assertExactJson(['error' => ['code' => 'CATEGORY_NOT_FOUND']]);
});

it('returns the v1 catalog shape for /san-pham/', function () {
    $this->getJson('/api/v1/catalog?per_page=3')->assertOk()
        ->assertJsonPath('schemaVersion', 1)
        ->assertJsonPath('pagination.perPage', 3)
        ->assertJsonPath('canonical.url', '/san-pham/')
        ->assertJsonStructure(['category', 'categories', 'products', 'filters' => ['brands', 'priceRanges', 'subcategories', 'needs'], 'selected', 'categoryHero', 'categoryContent', 'support', 'seo']);
});

it('builds keyword slugs without accents or punctuation', function () {
    expect(CatalogFilters::keywordSlug('Đèn  Blue Carbon 100W!'))->toBe('den-blue-carbon-100w')
        ->and(CatalogFilters::keywordSlug('  Đường '))->toBe('duong');
});

it('gives a sub-category page the trail of its parents, like the product page', function () {
    $tag = bin2hex(random_bytes(3));
    $top = Category::query()->create(['name' => 'Đèn '.$tag, 'slug' => 'den-'.$tag, 'depth' => 1, 'is_visible' => true]);
    $sub = Category::query()->create(['name' => 'Đèn pha '.$tag, 'slug' => 'den-pha-'.$tag, 'depth' => 2, 'parent_id' => $top->id, 'is_visible' => true]);
    Product::query()->create(['name' => 'Pha '.$tag, 'slug' => 'pha-'.$tag, 'regular_price' => 1, 'is_visible' => true, 'category_id' => $sub->id]);

    $this->getJson("/api/v1/catalog?category=den-{$tag}/den-pha-{$tag}")->assertOk()
        ->assertJsonPath('breadcrumbs', [['label' => 'Đèn '.$tag, 'url' => "/danh-muc-san-pham/den-{$tag}/"]]);
    $this->getJson("/api/v1/catalog?category=den-{$tag}")->assertOk()->assertJsonPath('breadcrumbs', []);
});
