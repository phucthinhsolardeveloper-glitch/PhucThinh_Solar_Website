<?php

use App\Domain\Catalog\Listing\CatalogFilters;

it('refuses malformed catalog queries like the PHP endpoint', function (array $query) {
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

it('builds keyword slugs the way the PHP site does', function () {
    expect(CatalogFilters::keywordSlug('Đèn  Blue Carbon 100W!'))->toBe('den-blue-carbon-100w')
        ->and(CatalogFilters::keywordSlug('  Đường '))->toBe('duong');
});
