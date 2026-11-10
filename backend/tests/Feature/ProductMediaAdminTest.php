<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('keeps a newly created product private until an editor makes it visible', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'product-draft-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $slug = 'product-draft-'.bin2hex(random_bytes(4));

    Livewire::test(CreateProduct::class)->fillForm([
        'name' => 'Sản phẩm đang biên tập', 'slug' => $slug, 'category_id' => Category::query()->value('id'),
    ])->call('create')->assertHasNoFormErrors();

    expect(Product::query()->where('slug', $slug)->firstOrFail()->is_visible)->toBeFalse();
});

it('uploads, reorders and removes product gallery entries through Filament', function () {
    Storage::fake('upload_product');
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'product-media-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $slug = 'media-admin-'.bin2hex(random_bytes(4));

    Livewire::test(CreateProduct::class)->fillForm([
        'name' => 'Sản phẩm thử media', 'slug' => $slug, 'category_id' => Category::query()->value('id'),
        'is_visible' => false, 'image' => [UploadedFile::fake()->image('main.jpg')],
        'images' => [
            ['path' => [UploadedFile::fake()->image('one.jpg')], 'caption' => 'Ảnh một', 'is_visible' => true],
            ['path' => [UploadedFile::fake()->image('two.jpg')], 'caption' => 'Ảnh hai', 'is_visible' => true],
        ],
    ])->call('create')->assertHasNoFormErrors();

    $product = Product::query()->where('slug', $slug)->firstOrFail();
    expect($product->images)->toHaveCount(2);
    Storage::disk('upload_product')->assertExists($product->image);
    foreach ($product->images as $image) {
        Storage::disk('upload_product')->assertExists($image->path);
    }

    $rows = $product->images->map(fn ($image) => [
        'id' => $image->id, 'path' => [$image->path], 'caption' => $image->caption, 'is_visible' => true,
    ])->all();
    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->fillForm(['images' => array_reverse($rows)])->call('save')->assertHasNoFormErrors();
    expect($product->fresh()->images->pluck('caption')->all())->toBe(['Ảnh hai', 'Ảnh một']);

    Livewire::test(EditProduct::class, ['record' => $product->id])
        ->fillForm(['images' => [$rows[1]]])->call('save')->assertHasNoFormErrors();
    expect($product->fresh()->images->pluck('caption')->all())->toBe(['Ảnh hai']);
    Storage::disk('upload_product')->assertExists($rows[0]['path'][0]);
});
