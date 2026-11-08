<?php

use App\Domain\Catalog\ProductDuplicator;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('duplicates a product and its editable content without exposing or linking commerce history', function () {
    $source = Product::query()->whereHas('contentSection')->whereHas('images')->firstOrFail();
    $copy = app(ProductDuplicator::class)->duplicate($source);

    expect($copy->id)->not->toBe($source->id)
        ->and($copy->slug)->not->toBe($source->slug)
        ->and($copy->is_visible)->toBeFalse()
        ->and($copy->code)->toBeNull()
        ->and($copy->views)->toBe(0)
        ->and($copy->regular_price)->toBe($source->regular_price)
        ->and($copy->category_id)->toBe($source->category_id)
        ->and($copy->images()->count())->toBe($source->images()->count())
        ->and($copy->documents()->count())->toBe($source->documents()->count())
        ->and($copy->contentSection?->blocks)->toBe($source->contentSection?->blocks)
        ->and($copy->seo?->title)->toBe($source->seo?->title)
        ->and($copy->reviews()->count())->toBe(0)
        ->and(DB::table('order_items')->where('product_id', $copy->id)->count())->toBe(0);

    $copy->update(['name' => 'Đã sửa bản sao']);
    expect($source->fresh()->name)->not->toBe('Đã sửa bản sao');
});

it('offers the copy action to a catalog editor but denies unrelated editors', function () {
    $product = Product::query()->firstOrFail();
    $catalogEditor = User::query()->create(['name' => 'Editor catalog', 'username' => 'copy-catalog-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->actingAs($catalogEditor, 'admin');
    $before = Product::query()->count();
    Livewire\Livewire::test(EditProduct::class, ['record' => $product->id])
        ->callAction('duplicate')->assertHasNoActionErrors();
    expect(Product::query()->count())->toBe($before + 1);

    $this->flushSession();
    $contentEditor = User::query()->create(['name' => 'Editor content', 'username' => 'copy-content-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => User::ROLE_EDITOR, 'permissions' => ['content'], 'is_active' => true]);
    $this->actingAs($contentEditor, 'admin');
    $this->get('/admin/products/'.$product->id.'/edit')->assertForbidden();
});
