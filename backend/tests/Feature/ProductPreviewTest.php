<?php

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Models\Product;
use Illuminate\Support\Facades\URL;

it('shows a hidden product page through a signed link only', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->update(['is_visible' => false]);

    $this->getJson('/api/v1/product-detail?slug='.$product->slug)->assertNotFound();
    $this->getJson('/api/v1/product-preview/'.$product->id)->assertForbidden();

    $signed = URL::temporarySignedRoute('product.preview', now()->addMinutes(15), ['product' => $product->id], absolute: false);
    $this->getJson($signed)->assertOk()->assertJsonPath('product.id', $product->id)->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

    $link = ProductDetailAssembler::previewUrl($product->id);
    expect($link)->toContain('/xem-truoc/san-pham/'.$product->id.'?expires=')->toContain('signature=');

    $this->travel(16)->minutes();
    $this->getJson($signed)->assertForbidden();
});
