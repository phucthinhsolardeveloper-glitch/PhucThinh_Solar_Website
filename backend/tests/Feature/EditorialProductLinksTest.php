<?php

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('links an editorial article to visible products through Admin and public API', function () {
    Storage::fake('uploads');
    Storage::disk('uploads')->putFileAs('product', UploadedFile::fake()->image('linked.jpg'), 'linked.jpg');
    app()->instance(ProductMediaUrls::class, new ProductMediaUrls(Storage::disk('uploads')->path('')));
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'editorial-links-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->update(['image' => 'linked.jpg']);

    Livewire::test(EditArticle::class, ['record' => $post->id])
        ->fillForm(['products' => [$product->id]])->call('save')->assertHasNoFormErrors();
    expect($post->fresh()->products->pluck('id')->all())->toContain($product->id);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()
        ->assertJsonPath('article.linkedProducts.0.id', $product->id)
        ->assertJsonPath('article.linkedProducts.0.url', '/san-pham/'.$product->slug)
        ->assertJsonPath('article.linkedProducts.0.image', '/upload/product/linked.jpg');

    $product->update(['is_visible' => false]);
    $this->getJson('/api/v1/news-detail?slug='.$post->slug)->assertOk()->assertJsonMissingPath('article.linkedProducts');
});
