<?php

use App\Domain\Catalog\ProductBulkUpdate;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;

function bulkAdmin(array $permissions = []): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'bulk-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

function pricedProducts(int $n = 2)
{
    $products = Product::query()->where('is_visible', true)->where('regular_price', '>', 0)->limit($n)->get();
    $products->each->update(['regular_price' => 1_000_000, 'sale_price' => 900_000, 'quote_only' => false]);

    return $products->fresh();
}

it('turns ticked products into "contact for a quote" and back, keeping their prices', function () {
    $this->actingAs(bulkAdmin(), 'admin');
    $products = pricedProducts();

    Livewire\Livewire::test(ListProducts::class)->callTableBulkAction('quoteOn', $products)->assertHasNoTableBulkActionErrors();
    $products->each(fn ($p) => expect($p->fresh()->quote_only)->toBeTrue()->and((int) $p->fresh()->regular_price)->toBe(1_000_000));
    $slug = $products[0]->slug;
    expect($this->getJson('/api/v1/product-detail?slug='.$slug)->assertOk()->json())->not->toBeEmpty();

    Livewire\Livewire::test(ListProducts::class)->callTableBulkAction('quoteOff', $products);
    expect($products[0]->fresh()->quote_only)->toBeFalse();
    expect(DB::table('admin_audit_events')->where('entity_type', 'Product')->where('entity_id', $products[0]->id)->count())->toBeGreaterThanOrEqual(2);
});

it('changes prices by percent with rounding and skips products without a price', function () {
    $this->actingAs(bulkAdmin(), 'admin');
    $products = pricedProducts(1);
    $free = Product::query()->whereKeyNot($products[0]->id)->firstOrFail();
    $free->update(['regular_price' => 0, 'sale_price' => 0]);

    $result = app(ProductBulkUpdate::class)->adjustPrices($products->push($free), 12.34);
    expect($result)->toBe(['changed' => 1, 'skipped' => 1])
        ->and((int) $products[0]->fresh()->regular_price)->toBe(1_123_000)
        ->and((int) $products[0]->fresh()->sale_price)->toBe(1_011_000);

    Livewire\Livewire::test(ListProducts::class)->callTableBulkAction('setDiscount', [$products[0]], ['percent' => 25, 'round_to' => 10000])->assertHasNoTableBulkActionErrors();
    expect((int) $products[0]->fresh()->sale_price)->toBe(840_000);
    Livewire\Livewire::test(ListProducts::class)->callTableBulkAction('clearDiscount', [$products[0]]);
    expect((int) $products[0]->fresh()->sale_price)->toBe(0);
});

it('moves products between brands and refuses people without the bulk permission', function () {
    $this->actingAs(bulkAdmin(), 'admin');
    $products = pricedProducts();
    $brand = Brand::query()->firstOrFail();
    Livewire\Livewire::test(ListProducts::class)->callTableBulkAction('setBrand', $products, ['brand_id' => $brand->id]);
    expect($products[1]->fresh()->brand_id)->toBe($brand->id);

    $this->flushSession();
    $this->actingAs(bulkAdmin(['catalog']), 'admin');
    Livewire\Livewire::test(ListProducts::class)->assertTableBulkActionHidden('quoteOn');
});

it('keeps products that have reviews when deleting in bulk', function () {
    $products = pricedProducts();
    DB::table('reviews')->insert(['product_id' => $products[0]->id, 'stars' => 5, 'content' => 'x', 'name' => 'x', 'status' => 'pending']);
    expect(app(ProductBulkUpdate::class)->delete($products))->toBe(['deleted' => 1, 'kept' => 1])
        ->and(Product::query()->whereKey($products[0]->id)->exists())->toBeTrue();
});
