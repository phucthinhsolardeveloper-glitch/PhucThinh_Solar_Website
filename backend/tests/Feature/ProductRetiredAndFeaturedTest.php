<?php

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Models\Category;
use App\Models\Product;

function retiredFixture(): array
{
    $tag = bin2hex(random_bytes(3));
    $top = Category::query()->create(['name' => 'Thiết bị '.$tag, 'slug' => 'thiet-bi-'.$tag, 'depth' => 1, 'is_visible' => true]);
    $sub = Category::query()->create(['name' => 'Nhóm '.$tag, 'slug' => 'nhom-'.$tag, 'depth' => 2, 'parent_id' => $top->id, 'is_visible' => true]);

    return [$tag, $top, $sub];
}

it('tells a visitor of the old link of a hidden product its name and the nearest visible category', function () {
    [$tag, , $sub] = retiredFixture();
    // A hidden category is skipped: the visitor is sent to the nearest visible one above it.
    $hiddenSub = Category::query()->create(['name' => 'Ẩn '.$tag, 'slug' => 'an-'.$tag, 'depth' => 2, 'parent_id' => $sub->parent_id, 'is_visible' => false]);
    Product::query()->create(['name' => 'Đèn cũ '.$tag, 'slug' => 'den-cu-'.$tag, 'category_id' => $sub->id, 'is_visible' => false]);
    Product::query()->create(['name' => 'Đèn trong nhóm ẩn '.$tag, 'slug' => 'den-nhom-an-'.$tag, 'category_id' => $hiddenSub->id, 'is_visible' => false]);

    $this->getJson('/api/v1/product-retired?slug=den-cu-'.$tag)->assertOk()
        ->assertJsonPath('schemaVersion', 1)->assertJsonPath('name', 'Đèn cũ '.$tag)
        ->assertJsonPath('category.name', 'Nhóm '.$tag)->assertJsonPath('category.url', "/danh-muc-san-pham/thiet-bi-{$tag}/nhom-{$tag}/");
    $this->getJson('/api/v1/product-retired?slug=den-nhom-an-'.$tag)->assertOk()->assertJsonPath('category.name', 'Thiết bị '.$tag);
});

it('answers 404 for a visible or unknown product and 400 for a bad query', function () {
    [$tag, , $sub] = retiredFixture();
    Product::query()->create(['name' => 'Đèn mới '.$tag, 'slug' => 'den-moi-'.$tag, 'category_id' => $sub->id, 'is_visible' => true]);

    $this->getJson('/api/v1/product-retired?slug=den-moi-'.$tag)->assertNotFound()->assertExactJson(['error' => ['code' => 'PRODUCT_NOT_FOUND']]);
    $this->getJson('/api/v1/product-retired?slug=khong-co-'.$tag)->assertNotFound();
    $this->getJson('/api/v1/product-retired?other=1')->assertStatus(400);
    $this->getJson('/api/v1/product-retired')->assertStatus(400);
});

it('makes a preview link that lasts the days asked for and still shows the hidden product', function () {
    [$tag, , $sub] = retiredFixture();
    $product = Product::query()->create(['name' => 'Đèn riêng '.$tag, 'slug' => 'den-rieng-'.$tag, 'category_id' => $sub->id, 'is_visible' => false]);

    $short = ProductDetailAssembler::previewUrl((int) $product->id);
    $long = ProductDetailAssembler::previewUrl((int) $product->id, 60 * 24 * 7);
    $expires = fn (string $url) => (int) (function () use ($url) { parse_str((string) parse_url($url, PHP_URL_QUERY), $q); return $q['expires']; })();

    expect($expires($short))->toBeLessThan(now()->addMinutes(20)->timestamp)
        ->and($expires($long))->toBeGreaterThan(now()->addDays(6)->timestamp)->toBeLessThan(now()->addDays(8)->timestamp);
    $this->getJson('/api/v1/product-preview/'.$product->id.'?'.parse_url($long, PHP_URL_QUERY))->assertOk()->assertJsonPath('product.name', 'Đèn riêng '.$tag);
    $this->getJson('/api/v1/product-preview/'.$product->id.'?expires='.$expires($long).'&signature='.str_repeat('a', 64))->assertForbidden();
});

it('marks featured products on the card and puts them first under the Featured sort', function () {
    [$tag, $top, $sub] = retiredFixture();
    foreach (['a', 'b'] as $n) {
        Product::query()->create(['name' => "SP {$n} {$tag}", 'slug' => "sp-{$n}-{$tag}", 'category_id' => $sub->id, 'is_visible' => true, 'sort_order' => 1]);
    }
    $star = Product::query()->create(['name' => 'SP nổi bật '.$tag, 'slug' => 'sp-noi-bat-'.$tag, 'category_id' => $sub->id, 'is_visible' => true, 'sort_order' => 9, 'is_bestseller' => true]);

    $featured = $this->getJson("/api/v1/catalog?category=thiet-bi-{$tag}&sort=featured")->assertOk()->assertJsonPath('selected.sort', 'featured')->json('products');
    expect($featured[0]['id'])->toBe((int) $star->id)->and($featured[0]['featured'])->toBeTrue()
        ->and(array_key_exists('featured', $featured[1]))->toBeFalse();

    $default = $this->getJson("/api/v1/catalog?category=thiet-bi-{$tag}")->assertOk()->json('products');
    expect(collect($default)->firstWhere('id', $star->id)['featured'])->toBeTrue();
});

it('gives staff a "Link gửi khách" button that shows a 7-day link for the product', function () {
    $owner = App\Models\User::query()->create(['name' => 'Chủ', 'username' => 'share-link-owner-'.bin2hex(random_bytes(3)), 'password' => 'test-password',
        'role' => App\Models\User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    [$tag, , $sub] = retiredFixture();
    $product = Product::query()->create(['name' => 'Đèn gửi khách '.$tag, 'slug' => 'den-gui-khach-'.$tag, 'category_id' => $sub->id, 'is_visible' => false]);

    $page = Livewire\Livewire::test(App\Filament\Resources\Products\Pages\EditProduct::class, ['record' => $product->id])->assertActionExists('share')->mountAction('share');
    $link = $page->instance()->mountedActions[0]['data']['link'];
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

    expect($link)->toContain('/xem-truoc/san-pham/'.$product->id.'?expires=')
        ->and((int) $query['expires'])->toBeGreaterThan(now()->addDays(6)->timestamp);
});
