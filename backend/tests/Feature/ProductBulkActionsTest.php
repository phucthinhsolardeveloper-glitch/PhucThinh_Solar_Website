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

it('edits name, category, brand and prices right on the list once the pencil is on, and refuses bad values', function () {
    $this->actingAs(bulkAdmin(), 'admin');
    $product = pricedProducts(1)[0];
    $brand = Brand::query()->where('id', '<>', (int) $product->brand_id)->firstOrFail();
    $category = App\Models\Category::query()->where('id', '<>', $product->category_id)->firstOrFail();
    $key = (string) $product->getKey();

    Livewire\Livewire::test(ListProducts::class)->assertTableColumnDoesNotExist('regular_price');
    $page = Livewire\Livewire::withQueryParams(['sua' => 1])->test(ListProducts::class)->assertTableColumnExists('regular_price');
    $page->call('updateTableColumnState', 'regular_price', $key, '2000000')
        ->call('updateTableColumnState', 'sale_price', $key, '1800000')
        ->call('updateTableColumnState', 'brand_id', $key, (string) $brand->id)
        ->call('updateTableColumnState', 'category_id', $key, (string) $category->id)
        ->call('updateTableColumnState', 'name', $key, 'Tên mới sửa trên bảng');
    $fresh = $product->fresh();
    expect((int) $fresh->regular_price)->toBe(2_000_000)->and((int) $fresh->sale_price)->toBe(1_800_000)
        ->and($fresh->brand_id)->toBe($brand->id)->and($fresh->category_id)->toBe($category->id)->and($fresh->name)->toContain('Tên mới sửa trên bảng');

    // A sale price above the regular price, and an empty name, are refused and nothing is saved.
    $page->call('updateTableColumnState', 'sale_price', $key, '9000000')->call('updateTableColumnState', 'name', $key, '');
    expect((int) $product->fresh()->sale_price)->toBe(1_800_000)->and($product->fresh()->name)->not->toBe('');

    // People without the bulk permission never get the pencil.
    $this->flushSession();
    $this->actingAs(bulkAdmin(['catalog']), 'admin');
    Livewire\Livewire::test(ListProducts::class)->assertActionHidden('inlineEdit');
});

it('offers 100 and 200 rows per page on lists', function () {
    $this->actingAs(bulkAdmin(), 'admin');
    Livewire\Livewire::test(ListProducts::class)->set('tableRecordsPerPage', 200)->assertSuccessful();
    expect(Filament\Tables\Table::make(Livewire\Livewire::test(ListProducts::class)->instance())->getPaginationPageOptions())->toContain(100, 200);
});
