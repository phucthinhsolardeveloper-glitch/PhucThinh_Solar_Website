<?php

use Illuminate\Support\Facades\DB;

// Query rules and error codes of the product endpoint; every field is covered by pts:snapshot.

it('rejects extra parameters and malformed slugs', function () {
    $this->getJson('/api/v1/product-detail?slug=abc&x=1')->assertStatus(400)->assertExactJson(['error' => ['code' => 'BAD_QUERY']]);
    $this->getJson('/api/v1/product-detail?slug=Bad%20Slug')->assertStatus(400);
    $this->getJson('/api/v1/product-detail')->assertStatus(400);
});

it('answers 404 for an unknown product', function () {
    $this->getJson('/api/v1/product-detail?slug=khong-ton-tai-xyz')->assertStatus(404)->assertExactJson(['error' => ['code' => 'PRODUCT_NOT_FOUND']]);
});

it('returns the v1 shape for a visible product', function () {
    $slug = DB::table('products')->where('is_visible', true)->value('slug');

    $this->getJson('/api/v1/product-detail?slug='.$slug)->assertOk()
        ->assertJsonPath('schemaVersion', 1)
        ->assertJsonPath('product.slug', $slug)
        ->assertJsonPath('canonical.url', '/san-pham/'.$slug)
        ->assertJsonStructure(['product' => ['id', 'name', 'media'], 'breadcrumbs', 'pricing' => ['mode', 'currency'], 'content' => ['blocks', 'faqs'], 'seo' => ['title']]);
});

it('sends only Admin-verified Merchant facts, so the page never claims stock it has not confirmed', function () {
    $slug = DB::table('products')->where('is_visible', true)->value('slug');
    DB::table('products')->where('slug', $slug)->update(['merchant_availability' => null, 'merchant_gtin' => null, 'merchant_mpn' => null]);
    $this->getJson('/api/v1/product-detail?slug='.$slug)->assertOk()->assertJsonMissingPath('product.merchant');

    // An invalid GTIN is never passed on; an unknown availability value is not "in stock".
    DB::table('products')->where('slug', $slug)->update(['merchant_availability' => 'maybe', 'merchant_gtin' => '1234567890123']);
    $this->getJson('/api/v1/product-detail?slug='.$slug)->assertOk()->assertJsonMissingPath('product.merchant');

    DB::table('products')->where('slug', $slug)->update(['merchant_availability' => 'in_stock', 'merchant_gtin' => '042100005264', 'merchant_mpn' => 'MPN-22']);
    $this->getJson('/api/v1/product-detail?slug='.$slug)->assertOk()
        ->assertJsonPath('product.merchant', ['availability' => 'in_stock', 'gtin' => '042100005264', 'mpn' => 'MPN-22']);
});

it('does not attach storage or backup use cases to a grid-tie inverter', function (array $row, array $categorySlugs) {
    $cards = app(\App\Domain\Catalog\ProductDetail\ProductApplications::class)
        ->cards([], $row, ['slug' => 'bien-tan-inverter'], 'inverter', $categorySlugs);

    expect($cards['items'])->not->toBeEmpty()
        ->and(json_encode($cards, JSON_UNESCAPED_UNICODE))->not->toContain('lưu trữ', 'dự phòng', 'backup')
        ->and($cards['items'][0]['title'])->toBe('Hệ thống điện mặt trời hòa lưới');
})->with([
    // On the live catalog the typed field is empty: the "Hòa lưới" category is what says it.
    'grid-tie category, type not filled in' => [['name' => 'Inverter SolaX X3-PRO-20', 'code' => 'X3-PRO- 20'], ['bien-tan-inverter', 'hoa-luoi']],
    'typed as grid-tie' => [['name' => 'Inverter thử', 'inverter_type' => 'Hoà lưới'], ['bien-tan-inverter']],
]);

it('keeps storage and backup use cases for an inverter explicitly marked hybrid', function () {
    $row = ['name' => 'Inverter Hybrid thử', 'inverter_type' => 'Hybrid (có lưu trữ)'];
    $cards = app(\App\Domain\Catalog\ProductDetail\ProductApplications::class)
        ->cards([], $row, ['slug' => 'bien-tan-inverter'], 'inverter');

    expect(json_encode($cards, JSON_UNESCAPED_UNICODE))->toContain('Lưu trữ & dự phòng');
});

it('lets a typed hybrid inverter keep its storage use cases even when filed under grid-tie', function () {
    $cards = app(\App\Domain\Catalog\ProductDetail\ProductApplications::class)
        ->cards([], ['name' => 'Inverter thử', 'inverter_type' => 'Hybrid hòa lưới có lưu trữ'], ['slug' => 'bien-tan-inverter'], 'inverter', ['bien-tan-inverter', 'hoa-luoi']);

    expect(json_encode($cards, JSON_UNESCAPED_UNICODE))->toContain('Lưu trữ & dự phòng');
});
