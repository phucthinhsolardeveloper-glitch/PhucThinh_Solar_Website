<?php

use App\Domain\Catalog\ProductDuplicator;
use App\Domain\Routing\Redirects;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

// Slugs are made from names and stay unique; renaming a public page keeps its old link alive.

function slugOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'slug-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

it('makes the slug from the name and numbers a taken one', function () {
    $this->actingAs(slugOwner(), 'admin');
    $name = 'Thương hiệu Thử Nghiệm '.bin2hex(random_bytes(3));

    Livewire\Livewire::test(CreateBrand::class)->fillForm(['name' => $name])->assertFormSet(['slug' => Str::slug($name)])
        ->call('create')->assertHasNoFormErrors();
    Livewire\Livewire::test(CreateBrand::class)->fillForm(['name' => $name, 'slug' => ''])->call('create')->assertHasNoFormErrors();

    expect(Brand::query()->where('name', $name)->orderBy('id')->pluck('slug')->all())->toBe([Str::slug($name), Str::slug($name).'-2']);
});

it('refuses a typed slug that is taken and suggests a free one', function () {
    $this->actingAs(slugOwner(), 'admin');
    $taken = Product::query()->value('slug');

    Livewire\Livewire::test(CreateBrand::class)->fillForm(['name' => 'Thử', 'slug' => $taken])->call('create')
        ->assertHasFormErrors(['slug']);
    expect(Brand::query()->where('slug', $taken)->exists())->toBeFalse();
});

it('regenerates the slug from the name on request', function () {
    $this->actingAs(slugOwner(), 'admin');
    $brand = Brand::query()->firstOrFail();
    Livewire\Livewire::test(EditBrand::class, ['record' => $brand->id])
        ->fillForm(['name' => 'Tên Hoàn Toàn Mới Zx'])
        ->callFormComponentAction('slug', 'slugFromName')
        ->assertFormSet(['slug' => 'ten-hoan-toan-moi-zx']);
});

it('keeps the old address of a renamed page working and collapses chains', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $first = $product->slug;
    $product->update(['slug' => 'ten-moi-mot']);
    $product->update(['slug' => 'ten-moi-hai']);

    $this->getJson('/api/v1/redirect?path=/san-pham/'.$first)->assertOk()->assertJsonPath('location', '/san-pham/ten-moi-hai');
    $this->getJson('/api/v1/redirect?path=/san-pham/ten-moi-mot')->assertOk()->assertJsonPath('location', '/san-pham/ten-moi-hai');

    $product->update(['slug' => $first]);
    $this->getJson('/api/v1/redirect?path=/san-pham/'.$first)->assertNotFound();
    $this->getJson('/api/v1/redirect?path=/san-pham/ten-moi-hai')->assertOk()->assertJsonPath('location', '/san-pham/'.$first);
    $this->getJson('/api/v1/redirect?path=https://evil.test/')->assertNotFound();
    $this->getJson('/api/v1/redirect?path=/x&extra=1')->assertStatus(400);
});

it('moves everything below a renamed category', function () {
    $child = Category::query()->whereNotNull('parent_id')->where('is_visible', true)->firstOrFail();
    $parent = Category::query()->findOrFail($child->parent_id);
    $oldChildPath = $child->publicPath();
    $parent->update(['slug' => $parent->slug.'-moi']);

    expect(Redirects::find($oldChildPath))->toBe($child->fresh()->publicPath());
});

it('names a product copy "-ban-sao", then "-ban-sao-2"', function () {
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $duplicator = app(ProductDuplicator::class);
    $first = $duplicator->duplicate($product);
    $second = $duplicator->duplicate($product);

    expect($first->slug)->toBe(substr($product->slug, 0, 190).'-ban-sao')
        ->and($second->slug)->toBe(substr($product->slug, 0, 190).'-ban-sao-2');
});

it('lets an owner add a redirect by hand and refuses outside links', function () {
    $this->actingAs(slugOwner(), 'admin');
    Livewire\Livewire::test(ManageRedirects::class)
        ->callAction('create', ['from_path' => '/Khuyen-Mai-Cu/', 'to_path' => '/tin-tuc'])->assertHasNoActionErrors();
    expect(Redirects::find('/khuyen-mai-cu'))->toBe('/tin-tuc');

    Livewire\Livewire::test(ManageRedirects::class)
        ->callAction('create', ['from_path' => 'https://evil.test/x', 'to_path' => '/'])->assertHasActionErrors(['from_path']);
});
