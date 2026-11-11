<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Support\Fields;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('reuses a picture already on the server instead of uploading it again', function () {
    Storage::fake('upload_product');
    Storage::disk('upload_product')->put('den-pha-cu-abc123.jpg', 'x');
    Storage::disk('upload_product')->put('tam-pin-longi-def456.png', 'x');
    Cache::forget('upload-library:product');

    expect(array_keys(Fields::libraryOptions('product', 'longi')))->toBe(['tam-pin-longi-def456.png'])
        ->and(Fields::libraryOptions('product', 'longi')['tam-pin-longi-def456.png'])->toContain('/thumbs/96x96x1/upload/product/tam-pin-longi-def456.png');

    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'lib-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $product = Product::query()->where('is_visible', true)->firstOrFail();
    $product->images()->delete(); // the faked disk has none of the real gallery files
    $product->update(['image_secondary' => null, 'feed_image' => null]);

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->callAction(TestAction::make('pickExisting')->schemaComponent('image'), ['file' => 'den-pha-cu-abc123.jpg'])
        ->assertHasNoActionErrors()
        ->call('save')->assertHasNoFormErrors();
    expect($product->fresh()->image)->toBe('den-pha-cu-abc123.jpg');
});
