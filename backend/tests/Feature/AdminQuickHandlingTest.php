<?php

use App\Domain\Commerce\OrderExporter;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Brand;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function quickAdmin(array $permissions): User
{
    return User::query()->create(['name' => 'Xử lý nhanh', 'username' => 'quick-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

function quickOrder(array $extra = []): Order
{
    $id = DB::table('orders')->insertGetId($extra + ['code' => 'QK-'.bin2hex(random_bytes(3)), 'customer_name' => 'Khách thử', 'phone' => '0900000002',
        'address' => '1 Đường Thử', 'payment_code' => 'manual', 'payment_status' => 'unpaid', 'subtotal' => 100000, 'shipping_fee' => 0,
        'total' => 100000, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);

    return Order::query()->findOrFail($id);
}

it('prints the ward and province once when checkout already wrote them into the address', function () {
    $provinceId = DB::table('provinces')->insertGetId(['name' => 'Tỉnh Thử', 'created_at' => now(), 'updated_at' => now()]);
    $wardId = DB::table('wards')->insertGetId(['province_id' => $provinceId, 'name' => 'Phường Thử', 'created_at' => now(), 'updated_at' => now()]);
    $province = DB::table('provinces')->find($provinceId);
    $ward = DB::table('wards')->find($wardId);
    $full = quickOrder(['address' => '12 Lê Lợi, '.$ward->name.', '.$province->name, 'province_id' => $province->id, 'ward_id' => $ward->id]);
    $street = quickOrder(['address' => '12 Lê Lợi', 'province_id' => $province->id, 'ward_id' => $ward->id]);

    $expected = '12 Lê Lợi, '.$ward->name.', '.$province->name;
    expect(OrderExporter::address($full->load('ward', 'province')))->toBe($expected)
        ->and(OrderExporter::address($street->load('ward', 'province')))->toBe($expected);
});

it('changes an order status from the list through the lifecycle rules', function () {
    $this->actingAs(quickAdmin(['orders']), 'admin');
    $order = quickOrder();

    Livewire::test(ListOrders::class)->callTableAction('quickStatus', $order, ['status' => 'confirmed', 'notes' => 'Đã gọi khách'])
        ->assertHasNoActionErrors();
    expect($order->fresh()->status)->toBe('confirmed')->and($order->fresh()->notes)->toBe('Đã gọi khách');

    // new -> done skips a step and stays refused, as on the edit page.
    $other = quickOrder();
    Livewire::test(ListOrders::class)->callTableAction('quickStatus', $other, ['status' => 'done'])->assertHasActionErrors(['status']);
    expect($other->fresh()->status)->toBe('new');

    $done = quickOrder(['status' => 'done']);
    Livewire::test(ListOrders::class)->assertTableActionHidden('quickStatus', $done);
});

it('changes a lead status from the list without dropping its assignee', function () {
    $editor = quickAdmin(['leads']);
    $this->actingAs($editor, 'admin');
    $lead = Lead::query()->create(['kind' => 'contact', 'name' => 'Khách', 'phone' => '0900000003', 'status' => 'new', 'assigned_to' => $editor->id]);

    Livewire::test(ManageLeads::class)->callTableAction('quickStatus', $lead, ['status' => 'contacted', 'notes' => 'Đã gọi'])
        ->assertHasNoActionErrors();
    expect($lead->fresh()->status)->toBe('contacted')->and($lead->fresh()->assigned_to)->toBe($editor->id)
        ->and($lead->fresh()->events()->count())->toBe(1);
});

it('toggles visibility straight from a list', function () {
    $this->actingAs(quickAdmin(['catalog']), 'admin');
    $brand = Brand::query()->create(['name' => 'Hãng thử', 'slug' => 'hang-thu-'.bin2hex(random_bytes(3)), 'is_visible' => true]);

    Livewire::test(ListBrands::class)->call('updateTableColumnState', 'is_visible', (string) $brand->getKey(), false);
    expect($brand->fresh()->is_visible)->toBeFalse();
});

it('offers "Lưu" back to the list and "Lưu, ở lại trang" on edit pages', function () {
    $this->actingAs(quickAdmin(['catalog']), 'admin');
    $brand = Brand::query()->create(['name' => 'Hãng thử', 'slug' => 'hang-thu-'.bin2hex(random_bytes(3)), 'is_visible' => true]);
    $page = App\Filament\Resources\Brands\Pages\EditBrand::class;

    Livewire::test($page, ['record' => $brand->getKey()])->fillForm(['name' => 'Hãng ở lại'])
        ->callAction(Filament\Actions\Testing\TestAction::make('saveAndStay')->schemaComponent('form-actions', schema: 'content'))->assertHasNoActionErrors()->assertNoRedirect();
    expect($brand->fresh()->name)->toBe('Hãng ở lại');

    Livewire::test($page, ['record' => $brand->getKey()])->fillForm(['name' => 'Hãng quay về'])
        ->call('save')->assertHasNoFormErrors()
        ->assertRedirect(App\Filament\Resources\Brands\BrandResource::getUrl('index'));
    expect($brand->fresh()->name)->toBe('Hãng quay về');
});
