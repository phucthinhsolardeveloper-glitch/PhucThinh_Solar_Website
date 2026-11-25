<?php

use App\Domain\Content\PublishChecks;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductBulkActions;
use App\Filament\Resources\Products\ProductPriceState;
use App\Models\Product;
use App\Models\User;
use Filament\Notifications\NotificationMailbox;
use Livewire\Livewire;

function stateAdmin(): User
{
    return User::query()->create(['name' => 'Trạng thái', 'username' => 'state-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

/** Reads (and empties) the pending notices and returns the "Hoàn tác" button of the newest one. */
function undoButton(): array
{
    $mailbox = app(NotificationMailbox::class);
    $mailbox->flush();
    $notices = $mailbox->pull();

    return end($notices)['actions'][0];
}

function stateProducts(int $n = 3)
{
    $ids = Product::query()->where('is_visible', true)->orderBy('id')->limit($n)->pluck('id');
    Product::query()->whereKey($ids)->get()->each->update(['regular_price' => 1_000_000, 'sale_price' => 800_000, 'quote_only' => false]);

    return Product::query()->whereKey($ids)->orderBy('id')->get();
}

it('says what the visitor sees in the price cell, and which price is being kept', function () {
    $product = stateProducts(1)[0];
    expect(ProductPriceState::of($product, true))->toMatchArray(['quote' => false, 'text' => '800.000đ', 'detail' => 'Giá gốc 1.000.000đ, giảm 20%']);

    $product->quote_only = true;
    expect(ProductPriceState::of($product, true))->toMatchArray(['quote' => true, 'text' => 'Liên hệ báo giá', 'detail' => 'Đang ẩn giá 800.000đ']);
    $product->quote_only = false;
    expect(ProductPriceState::of($product, false)['detail'])->toBe('Cả website đang ẩn giá (800.000đ)');
    $product->regular_price = $product->sale_price = 0;
    expect(ProductPriceState::of($product, true)['detail'])->toBe('Chưa nhập giá');
});

it('does not nag about a price that is kept while hidden', function () {
    $product = stateProducts(1)[0];
    $product->update(['quote_only' => true]);
    expect(app(PublishChecks::class)->product($product->fresh())['warnings'])->not->toContain('Đánh dấu báo giá nhưng vẫn nhập giá');
});

it('filters by one tap and counts what each tab holds', function () {
    $this->actingAs(stateAdmin(), 'admin');
    $products = stateProducts();
    $products[0]->update(['is_visible' => false]);
    $products[1]->update(['quote_only' => true]);

    Livewire::test(ListProducts::class)->set('tableRecordsPerPage', 200)
        ->set('activeTab', 'hidden')->assertCanSeeTableRecords([$products[0]])->assertCanNotSeeTableRecords([$products[2]])
        ->set('activeTab', 'quote')->assertCanSeeTableRecords([$products[1]])->assertCanNotSeeTableRecords([$products[2]]);

    $tabs = Livewire::test(ListProducts::class)->filterTable('category_id', $products[0]->category_id)->instance()->getTabs();
    expect((int) $tabs['hidden']->getBadge())->toBe(Product::query()->where('category_id', $products[0]->category_id)->where('is_visible', false)->count());
});

it('says what a bulk switch did and takes it back, leaving later edits alone', function () {
    $this->actingAs(stateAdmin(), 'admin');
    $products = stateProducts();
    $products[2]->update(['is_visible' => false]);

    $page = Livewire::test(ListProducts::class)->callTableBulkAction('hide', $products);
    expect(Product::query()->whereKey($products->modelKeys())->where('is_visible', true)->count())->toBe(0);

    $button = undoButton();
    $token = $button['eventData']['token'] ?? null;
    expect($token)->toBeString()->and($button['event'])->toBe(ProductBulkActions::UNDO_EVENT)->and($button['label'])->toBe('Hoàn tác');

    $page->dispatch(ProductBulkActions::UNDO_EVENT, token: $token)->assertNotified('Đã hoàn tác 2 sản phẩm');
    // The one that was hidden before the bulk step stays hidden.
    expect($products->map(fn ($p) => $p->fresh()->is_visible)->all())->toBe([true, true, false]);
    $page->dispatch(ProductBulkActions::UNDO_EVENT, token: $token)->assertNotified('Không hoàn tác được');
});

it('takes a percent price change back to the exact old prices', function () {
    $this->actingAs(stateAdmin(), 'admin');
    $products = stateProducts(2);
    $page = Livewire::test(ListProducts::class)->callTableBulkAction('adjustPrices', $products, ['percent' => 7.5, 'round_to' => 1000]);
    expect((int) $products[0]->fresh()->regular_price)->toBe(1_075_000);
    $token = undoButton()['eventData']['token'];
    $products[1]->fresh()->update(['regular_price' => 2_000_000]);

    $page->dispatch(ProductBulkActions::UNDO_EVENT, token: $token);
    expect((int) $products[0]->fresh()->regular_price)->toBe(1_000_000)->and((int) $products[0]->fresh()->sale_price)->toBe(800_000)
        ->and((int) $products[1]->fresh()->regular_price)->toBe(2_000_000)->and((int) $products[1]->fresh()->sale_price)->toBe(800_000);
});

it('names what happened in the notice', function () {
    $this->actingAs(stateAdmin(), 'admin');
    $products = stateProducts(2);
    Livewire::test(ListProducts::class)->callTableBulkAction('quoteOn', $products)->assertNotified('Đã ẩn giá 2 sản phẩm');
});

it('saves visibility and a hidden price from the quick edit', function () {
    $this->actingAs(stateAdmin(), 'admin');
    $product = stateProducts(1)[0];
    Livewire::test(ListProducts::class)->callTableAction('quickEdit', $product, ['is_visible' => false, 'quote_only' => true, 'regular_price' => 3_000_000, 'sale_price' => 0])
        ->assertHasNoTableActionErrors();
    $fresh = $product->fresh();
    expect($fresh->is_visible)->toBeFalse()->and($fresh->quote_only)->toBeTrue()->and((int) $fresh->regular_price)->toBe(3_000_000);
});
