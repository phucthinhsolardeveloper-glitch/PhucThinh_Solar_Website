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

it('uses purchase-relevant price bands for hybrid and storage categories', function () {
    $inverter = Category::query()->create(['name' => 'Biến tần', 'slug' => 'bien-tan-inverter', 'depth' => 1, 'is_visible' => true]);
    Category::query()->create(['name' => 'Hybrid / lưu trữ', 'slug' => 'hybrid-luu-tru', 'depth' => 2, 'parent_id' => $inverter->id, 'is_visible' => true]);
    Category::query()->create(['name' => 'Pin lưu trữ', 'slug' => 'pin-luu-tru', 'depth' => 1, 'is_visible' => true]);
    $hybridRanges = CatalogFilters::priceRangesForCategory('bien-tan-inverter/hybrid-luu-tru');
    $storageRanges = CatalogFilters::priceRangesForCategory('pin-luu-tru');
    [$hybridSql, $hybridParams] = CatalogFilters::price('1,2,3', $hybridRanges);
    [$storageSql, $storageParams] = CatalogFilters::price('1,2,3', $storageRanges);

    expect($hybridRanges['1'][1])->toBe(50000000)
        ->and($storageRanges['1'][1])->toBe(100000000)
        ->and($hybridParams)->toBe([0, 50000000, 50000000, 100000000, 100000000])
        ->and($storageParams)->toBe([0, 100000000, 100000000, 1500000000, 1500000000])
        ->and($hybridSql)->toContain('sale_price')
        ->and(CatalogFilters::sort('price', true))->toContain('p.sale_price')
        ->and(CatalogFilters::sort('price', true))->toContain('p.quote_only=0')
        ->and(CatalogFilters::sort('price-desc', true))->toEndWith('desc')
        ->and(CatalogFilters::priceRangesForCategory('den-nlmt'))->toBe(CatalogFilters::PRICE_RANGES);

    $this->getJson('/api/v1/catalog?category=bien-tan-inverter/hybrid-luu-tru')->assertOk()
        ->assertJsonPath('seo.title', 'Hybrid / lưu trữ | Phúc Thịnh Solar')
        ->assertJsonPath('filters.priceRanges.0.label', 'Dưới 50.000.000đ')
        ->assertJsonPath('filters.priceRanges.1.label', '50.000.000đ - 100.000.000đ');

    $this->getJson('/api/v1/catalog?category=pin-luu-tru')->assertOk()
        ->assertJsonPath('filters.priceRanges.0.label', 'Dưới 100.000.000đ')
        ->assertJsonPath('filters.priceRanges.2.label', 'Trên 1.500.000.000đ');
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

it('lists the brands related to a category in its header, also those with no product there yet', function () {
    $top = DB::table('categories')->whereNull('parent_id')->where('is_visible', true)->orderBy('id')->first();
    $other = DB::table('categories')->whereNull('parent_id')->where('is_visible', true)->where('id', '!=', $top->id)->first();
    $brand = DB::table('brands')->insertGetId(['name' => 'Hãng thử liên quan', 'slug' => 'hang-thu-lien-quan', 'image' => 'hang-thu.png', 'is_visible' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('brand_category')->insert(['brand_id' => $brand, 'category_id' => $top->id]);
    $names = fn (string $slug) => collect($this->getJson('/api/v1/catalog?category='.$slug)->assertOk()->json('filters.partners'))->pluck('name')->all();

    expect($names($top->slug))->toContain('Hãng thử liên quan')->and($names($other->slug))->not->toContain('Hãng thử liên quan');
});
