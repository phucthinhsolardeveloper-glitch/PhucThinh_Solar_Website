<?php

use App\Filament\Resources\Provinces\Pages\CreateProvince;
use App\Filament\Resources\Provinces\Pages\EditProvince;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Filament\Resources\Wards\Pages\ManageWards;
use App\Filament\Resources\Wards\WardResource;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

it('creates a province and ward and exposes them through the two-level public API', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'province-owner', 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
    $this->actingAs($owner, 'admin');

    Livewire::test(CreateProvince::class)->fillForm([
        'name' => 'Tỉnh Kiểm Thử', 'code' => 'KT', 'sort_order' => 1, 'is_visible' => true,
    ])->call('create')->assertHasNoFormErrors();
    $province = Province::query()->where('code', 'KT')->firstOrFail();

    Livewire::test(ManageWards::class)->callAction('create', data: [
        'province_id' => $province->id, 'name' => 'Phường Kiểm Thử', 'code' => 'KT01',
        'shipping_fee' => 12000, 'sort_order' => 1, 'is_visible' => true,
    ])->assertHasNoActionErrors();
    $ward = Ward::query()->where('code', 'KT01')->firstOrFail();
    expect($ward->province_id)->toBe($province->id);

    $this->getJson('/api/v1/lead-form')->assertOk()->assertJsonFragment(['id' => $province->id, 'name' => 'Tỉnh Kiểm Thử']);
    $this->getJson('/api/v1/checkout-wards?city='.$province->id)->assertOk()
        ->assertJsonFragment(['id' => $ward->id, 'name' => 'Phường Kiểm Thử']);

    Livewire::test(EditProvince::class, ['record' => $province->id])
        ->fillForm(['is_visible' => false])->call('save')->assertHasNoFormErrors();
    $this->getJson('/api/v1/checkout-wards?city='.$province->id)->assertNotFound();
    expect(ProvinceResource::canDelete($province))->toBeFalse()
        ->and(WardResource::canDelete($ward))->toBeFalse();
});

it('limits province and ward management to the orders area', function () {
    $editor = User::query()->create(['name' => 'Biên tập', 'username' => 'province-editor', 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['catalog'], 'is_active' => true]);
    $this->actingAs($editor, 'admin');
    $this->get('/admin/provinces')->assertForbidden();
    $this->get('/admin/wards')->assertForbidden();
});
