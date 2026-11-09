<?php

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\PromoFrames;
use App\Filament\Resources\ProductCampaigns\Pages\ManageProductCampaigns;
use App\Models\Product;
use App\Models\ProductCampaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function campaign(array $values): ProductCampaign
{
    return ProductCampaign::query()->create($values + ['name' => 'Thử', 'frame_image' => 'khung.png', 'priority' => 0, 'is_visible' => true]);
}

function frameFor(Product $product): ?string
{
    return (new PromoFrames(app(CategoryTree::class)))->for($product->only(['id', 'category_id', 'brand_id']));
}

it('picks the frame by priority, then the narrowest scope, and only while the campaign runs', function () {
    $product = Product::query()->where('is_visible', true)->whereNotNull('brand_id')->whereNotNull('category_id')->firstOrFail();
    $parent = (int) (DB::table('categories')->where('id', $product->category_id)->value('parent_id') ?: $product->category_id);

    expect(frameFor($product))->toBeNull();
    campaign(['applies_to_all' => true, 'frame_image' => 'tat-ca.png']);
    expect(frameFor($product))->toBe('/upload/photo/tat-ca.png');
    campaign(['category_ids' => [$parent], 'frame_image' => 'danh-muc.png']);
    campaign(['brand_ids' => [$product->brand_id], 'frame_image' => 'thuong-hieu.png']);
    expect(frameFor($product))->toBe($parent === $product->category_id ? '/upload/photo/danh-muc.png' : '/upload/photo/thuong-hieu.png');
    campaign(['product_ids' => [$product->id], 'frame_image' => 'rieng.png']);
    expect(frameFor($product))->toBe('/upload/photo/rieng.png');
    campaign(['applies_to_all' => true, 'priority' => 9, 'frame_image' => 'uu-tien.png']);
    expect(frameFor($product))->toBe('/upload/photo/uu-tien.png');
    campaign(['applies_to_all' => true, 'priority' => 99, 'frame_image' => 'het-han.png', 'ends_at' => now()->subDay()]);
    campaign(['applies_to_all' => true, 'priority' => 99, 'frame_image' => 'sap-chay.png', 'starts_at' => now()->addDay()]);
    campaign(['applies_to_all' => true, 'priority' => 99, 'frame_image' => 'tat.png', 'is_visible' => false]);
    expect(frameFor($product))->toBe('/upload/photo/uu-tien.png');
});

it('puts the frame on catalogue cards only while a campaign covers them', function () {
    $product = Product::query()->where('is_visible', true)->whereNotNull('image')->firstOrFail();
    $card = fn () => collect($this->getJson('/api/v1/search?keyword='.urlencode((string) $product->name))->assertOk()->json('products'))->firstWhere('id', $product->id);
    expect($card())->not->toBeNull()->not->toHaveKey('promoFrame');
    campaign(['category_ids' => [$product->category_id], 'frame_image' => 'danh-muc.png']);
    expect($card()['promoFrame'] ?? null)->toBe('/upload/photo/danh-muc.png');
    campaign(['product_ids' => [$product->id], 'frame_image' => 'tet.png']);
    expect($card()['promoFrame'] ?? null)->toBe('/upload/photo/tet.png');
});

it('requires a scope unless the campaign covers every product', function () {
    $this->actingAs(User::query()->create(['name' => 'Thử', 'username' => 'pc-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]), 'admin');
    Livewire\Livewire::test(ManageProductCampaigns::class)
        ->callAction('create', ['name' => 'Không phạm vi', 'frame_image' => ['khung.png'], 'priority' => 0])
        ->assertHasActionErrors(['product_ids']);
});
