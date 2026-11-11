<?php

use App\Domain\Catalog\ProductSpecTemplates;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

function typedOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'typed-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

function productIn(string $categorySlug): Product
{
    $category = Category::query()->where('slug', $categorySlug)->firstOrFail();
    $product = Product::query()->firstOrFail()->replicate();
    $product->fill(['slug' => 'typed-'.bin2hex(random_bytes(4)), 'code' => null, 'category_id' => $category->id, 'pdp_profile' => null,
        'is_visible' => false, 'additional_specs' => [['key' => '', 'label' => 'Ghi chú lắp đặt', 'value' => 'Lắp nơi thoáng']]]);
    $product->save();

    return $product;
}

it('picks the product type from the deepest category that sets one', function () {
    expect(ProductSpecTemplates::typeOf(productIn('hoa-luoi')))->toBe('inverter')
        ->and(ProductSpecTemplates::typeOf(productIn('bo-dieu-khien-sac-mppt')))->toBe('charge_controller')
        ->and(ProductSpecTemplates::typeOf(productIn('may-bom-nlmt')))->toBe('pump_hardware')
        ->and(ProductSpecTemplates::typeOf(productIn('cap-day-dien')))->toBe('accessory');
});

it('saves the inverter boxes as keyed specs with their unit and keeps free rows', function () {
    $this->actingAs(typedOwner(), 'admin');
    $product = productIn('hoa-luoi');

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->assertFormFieldExists('typed_specs.ac_power')->assertFormFieldIsVisible('typed_specs.mppt_count')
        ->assertFormFieldIsHidden('typed_specs.quang_thong')
        ->fillForm(['typed_specs.ac_power' => '6', 'typed_specs.phase_count' => '1 pha', 'warranty' => '10 năm'])
        ->call('save')->assertHasNoFormErrors();

    $specs = collect($product->fresh()->additional_specs);
    expect($specs->firstWhere('key', 'ac_power'))->toMatchArray(['label' => 'Công suất AC định mức', 'value' => '6 kW'])
        ->and($specs->firstWhere('key', 'phase_count')['value'])->toBe('1 pha')
        ->and($specs->firstWhere('label', 'Ghi chú lắp đặt')['value'])->toBe('Lắp nơi thoáng')
        ->and($product->fresh()->warranty)->toBe('10 năm');

    // The box shows the number alone next to its unit.
    Livewire::test(EditProduct::class, ['record' => $product->getKey()])->assertFormSet(['typed_specs.ac_power' => '6']);
});

it('keeps specs typed for the old type when the category changes', function () {
    $this->actingAs(typedOwner(), 'admin');
    $product = productIn('hoa-luoi');
    $product->update(['additional_specs' => [['key' => 'mppt_count', 'label' => 'Số MPPT / số chuỗi đầu vào', 'value' => '2 MPPT']]]);
    $panels = Category::query()->where('slug', 'tam-pin-nang-luong-mat-troi')->firstOrFail();

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->fillForm(['category_id' => $panels->id])->assertFormFieldIsVisible('typed_specs.open_circuit_voltage')
        ->fillForm(['typed_specs.open_circuit_voltage' => '49,5'])->call('save')->assertHasNoFormErrors();

    $specs = collect($product->fresh()->additional_specs);
    expect($specs->firstWhere('key', 'open_circuit_voltage')['value'])->toBe('49,5V')
        ->and($specs->firstWhere('key', 'mppt_count')['value'])->toBe('2 MPPT');
});

it('names the main specs a type still misses', function () {
    $product = productIn('bo-dieu-khien-sac-pwm');
    expect(ProductSpecTemplates::missingRequired($product))->toContain('Công nghệ sạc', 'Điện áp hệ thống');
    $product->update(['additional_specs' => [['key' => '', 'label' => 'Điện áp hệ thống', 'value' => '12/24V']]]);
    expect(ProductSpecTemplates::missingRequired($product->fresh()))->not->toContain('Điện áp hệ thống');
});

it('keys hand-typed spec rows only with --apply, and reads the inverter sentence', function () {
    $product = productIn('hoa-luoi');
    $product->update(['warranty' => null, 'additional_specs' => [
        ['label' => 'Thông tin', 'value' => '5kw Single Phase Inverter 2 MPPT, +CT; With Wifi, BH 5 năm'],
        ['label' => 'Max backup AC', 'value' => '5 kW'],
    ]]);

    Artisan::call('pts:spec-keys');
    expect(collect($product->fresh()->additional_specs)->pluck('key')->filter()->all())->toBe([]);

    Artisan::call('pts:spec-keys', ['--apply' => true]);
    $specs = collect($product->fresh()->additional_specs);
    expect($specs->firstWhere('key', 'backup_power')['value'])->toBe('5 kW')
        ->and($specs->firstWhere('key', 'ac_power')['value'])->toBe('5 kW')
        ->and($specs->firstWhere('key', 'phase_count')['value'])->toBe('1 pha')
        ->and($specs->firstWhere('key', 'mppt_count')['value'])->toBe('2 MPPT')
        ->and($specs->firstWhere('label', 'Thông tin'))->not->toBeNull()
        ->and($product->fresh()->warranty)->toBe('5 năm');

    Artisan::call('pts:spec-keys');
    expect(Artisan::output())->not->toContain('#'.$product->id.' ');
});
