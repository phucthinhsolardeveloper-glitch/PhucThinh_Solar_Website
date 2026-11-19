<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\StoreLocations\Pages\ManageStoreLocations;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function warehouseOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'wh-'.bin2hex(random_bytes(4)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

function adminWarehouse(string $name = 'Kho quản trị'): int
{
    return (int) DB::table('store_locations')->insertGetId(['name' => $name, 'address' => '9 Đường Kho', 'is_visible' => true, 'is_warehouse' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
}

it('turns a branch into a warehouse with its own manager, phone and ward', function () {
    $this->actingAs(warehouseOwner(), 'admin');
    $branch = StoreLocation::query()->create(['name' => 'Chi nhánh thử', 'address' => '5 Đường Thử', 'is_visible' => true]);
    $ward = DB::table('wards')->first();

    Livewire::test(ManageStoreLocations::class)->callTableAction('edit', $branch, data: [
        'name' => 'Kho thử', 'address' => '5 Đường Thử', 'is_warehouse' => true, 'sender_name' => 'Chị Hoa', 'sender_phone' => '0933333333',
        'province_id' => $ward->province_id, 'ward_id' => $ward->id,
    ])->assertHasNoTableActionErrors();

    $branch->refresh();
    expect($branch->is_warehouse)->toBeTrue()->and($branch->sender_name)->toBe('Chị Hoa')->and($branch->sender_phone)->toBe('0933333333')->and((int) $branch->ward_id)->toBe((int) $ward->id);
});

it('assigns a warehouse to many ticked products at once, and clears it', function () {
    $this->actingAs(warehouseOwner(), 'admin');
    $warehouse = adminWarehouse();
    $products = Product::query()->where('is_visible', true)->limit(3)->get();

    Livewire::test(ListProducts::class)->callTableBulkAction('setWarehouse', $products, data: ['warehouse_id' => $warehouse])->assertHasNoTableBulkActionErrors();
    $products->each(fn ($p) => expect((int) $p->fresh()->warehouse_id)->toBe($warehouse));

    Livewire::test(ListProducts::class)->callTableBulkAction('setWarehouse', $products, data: ['warehouse_id' => null])->assertHasNoTableBulkActionErrors();
    $products->each(fn ($p) => expect($p->fresh()->warehouse_id)->toBeNull());
});

it('lets staff set the shipping warehouse on an order, with an audit entry, and warns when goods are split', function () {
    $this->actingAs(warehouseOwner(), 'admin');
    [$a, $b] = [adminWarehouse('Kho A'), adminWarehouse('Kho B')];
    [$p1, $p2] = Product::query()->limit(2)->pluck('id')->all();
    Product::query()->whereKey($p1)->update(['warehouse_id' => $a]);
    Product::query()->whereKey($p2)->update(['warehouse_id' => $b]);
    $ward = DB::table('wards')->first();
    $id = DB::table('orders')->insertGetId(['code' => 'WA'.strtoupper(bin2hex(random_bytes(2))), 'customer_name' => 'Khách', 'phone' => '0900000003', 'address' => 'x', 'province_id' => $ward->province_id,
        'ward_id' => $ward->id, 'payment_code' => 'manual', 'payment_status' => 'unpaid', 'subtotal' => 1, 'shipping_fee' => 0, 'total' => 1, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
    foreach ([$p1, $p2] as $p) {
        DB::table('order_items')->insert(['order_id' => $id, 'product_id' => $p, 'name' => 'Hàng', 'regular_price' => 1, 'quantity' => 1]);
    }

    expect(OrderResource::warehouseHint(Order::query()->findOrFail($id)))->toContain('nhiều kho')->toContain('Kho A')->toContain('Kho B');

    Livewire::test(EditOrder::class, ['record' => $id])->fillForm(['status' => 'new', 'warehouse_id' => $b])->call('save')->assertHasNoFormErrors();
    expect((int) DB::table('orders')->where('id', $id)->value('warehouse_id'))->toBe($b)
        ->and(DB::table('admin_audit_events')->where('action', 'order.warehouse')->orderByDesc('id')->value('after'))->toContain((string) $b);
});
