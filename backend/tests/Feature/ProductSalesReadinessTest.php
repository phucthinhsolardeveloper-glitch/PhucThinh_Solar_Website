<?php

use App\Domain\Catalog\ProductSalesReadiness;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('finds actionable gaps on public products and clears them after staff fills the fields', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->update(['image' => null, 'summary' => null, 'regular_price' => 0, 'quote_only' => false]);
    $product->seo()->delete();
    $product->visibleArticles()->detach();
    $readiness = app(ProductSalesReadiness::class);

    expect($readiness->issues($product->fresh()))->toBe(['Thiếu ảnh', 'SEO riêng', 'Mô tả ngắn', 'Giá / báo giá', 'Liên kết bài viết']);
    foreach (array_keys(ProductSalesReadiness::FILTERS) as $gap) {
        expect($readiness->filter(Product::query(), $gap)->whereKey($product->id)->exists())->toBeTrue();
    }

    $article = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $article->products()->attach($product->id, ['is_visible' => true, 'sort_order' => 0]);
    $product->update(['image' => 'product.jpg', 'summary' => 'Mô tả có ích', 'regular_price' => 120000]);
    $product->seo()->create(['title' => 'Tiêu đề riêng', 'description' => 'Mô tả riêng']);

    expect($readiness->issues($product->fresh()))->toBe([]);
    foreach (array_keys(ProductSalesReadiness::FILTERS) as $gap) {
        expect($readiness->filter(Product::query(), $gap)->whereKey($product->id)->exists())->toBeFalse();
    }
});

it('shows sales and SEO gap filters to catalog editors without changing products', function () {
    $editor = User::query()->create(['name' => 'Biên tập sản phẩm', 'username' => 'sales-readiness-editor',
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->actingAs($editor, 'admin');

    Livewire::test(ListProducts::class)->assertSee('Cần bổ sung')
        ->filterTable('sales_readiness', 'seo')->assertHasNoErrors();
});
