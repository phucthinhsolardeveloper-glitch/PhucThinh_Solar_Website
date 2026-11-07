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
