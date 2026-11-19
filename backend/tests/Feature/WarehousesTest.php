<?php

use App\Domain\Catalog\Bulk\BulkCatalog;
use App\Domain\Catalog\Bulk\BulkColumns;
use App\Domain\Catalog\Bulk\BulkPlanner;
use App\Domain\Shipping\Shipments;
use App\Domain\Shipping\Warehouses;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Http::preventStrayRequests());

/** Two warehouses on two different wards, with their own managers. */
function twoWarehouses(): array
{
    if (! DB::table('wards')->whereNotNull('tax_code')->exists()) {
        test()->artisan('pts:ward-codes');
    }
    $wards = DB::table('wards')->whereNotNull('tax_code')->limit(3)->get();
    $make = fn (string $name, string $manager, string $phone, $ward) => (int) DB::table('store_locations')->insertGetId([
        'name' => $name, 'address' => '1 Đường '.$name, 'is_visible' => true, 'is_warehouse' => true, 'sender_name' => $manager, 'sender_phone' => $phone,
        'ward_id' => $ward->id, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);

    return [$make('Kho thử A', 'Chị Lan', '0911111111', $wards[0]), $make('Kho thử B', 'Anh Minh', '0922222222', $wards[1]), $wards];
}

function orderWith(array $productIds, ?int $warehouse = null, $ward = null): Order
{
    $ward ??= DB::table('wards')->whereNotNull('tax_code')->first();
    DB::table('settings')->updateOrInsert(['key' => 'shipping_sender_ward_id'], ['value' => json_encode((string) $ward->id)]);
    app(SiteSettings::class)->clear();
    $id = DB::table('orders')->insertGetId(['code' => 'WH'.strtoupper(bin2hex(random_bytes(2))), 'customer_name' => 'Khách', 'phone' => '0900000002', 'address' => '2 Đường Thử',
        'province_id' => $ward->province_id, 'ward_id' => $ward->id, 'payment_code' => 'manual', 'payment_status' => 'unpaid', 'subtotal' => 1000000, 'shipping_fee' => 0,
        'total' => 1000000, 'status' => 'confirmed', 'warehouse_id' => $warehouse, 'created_at' => now(), 'updated_at' => now()]);
    foreach ($productIds as $productId) {
        DB::table('order_items')->insert(['order_id' => $id, 'product_id' => $productId, 'name' => 'Hàng '.$productId, 'regular_price' => 500000, 'quantity' => 1]);
    }

    return Order::query()->findOrFail($id);
}

function staffUser(): User
{
    return User::query()->create(['name' => 'Kế toán kho', 'username' => 'ke-toan-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['orders'], 'is_active' => true]);
}

it('lists only branches marked as warehouses', function () {
    [$a] = twoWarehouses();
    DB::table('store_locations')->insert(['name' => 'Trụ sở thử', 'address' => 'x', 'is_visible' => true, 'is_warehouse' => false, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $options = app(Warehouses::class)->options();
    expect($options)->toHaveKey($a)->and(array_values($options))->not->toContain('Trụ sở thử');
});

it('names one warehouse only when every product sits in the same one', function () {
    [$a, $b] = twoWarehouses();
    [$p1, $p2, $p3] = Product::query()->limit(3)->pluck('id')->all();
    Product::query()->whereKey([$p1, $p2])->update(['warehouse_id' => $a]);
    Product::query()->whereKey($p3)->update(['warehouse_id' => $b]);
    $w = app(Warehouses::class);

    expect($w->singleFor([$p1, $p2]))->toBe($a)
        ->and($w->singleFor([$p1, $p3]))->toBeNull()
        ->and($w->holding([$p1, $p3])['ids'])->toBe(collect([$a, $b])->sort()->values()->all());
    Product::query()->whereKey($p2)->update(['warehouse_id' => null]);
    expect($w->singleFor([$p1, $p2]))->toBeNull()->and($w->holding([$p1, $p2]))->toBe(['ids' => [$a], 'unassigned' => 1]);
});

it('ships an order from the warehouse that holds its goods, with that warehouse\'s own sender', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    [$a, $b, $wards] = twoWarehouses();
    [$p1, $p2] = Product::query()->limit(2)->pluck('id')->all();
    Product::query()->whereKey([$p1, $p2])->update(['warehouse_id' => $b]);

    $shipment = app(Shipments::class)->create(orderWith([$p1, $p2], null, $wards[2]), ['weight_kg' => 2, 'service_id' => '91', 'payer_id' => '10'], staffUser());
    expect($shipment->request['s_name'])->toBe('Anh Minh')->and($shipment->request['s_phone'])->toBe('0922222222')
        ->and($shipment->request['s_ward_code'])->toBe((string) $wards[1]->tax_code)->and($shipment->request['s_address'])->toBe('1 Đường Kho thử B');
});

it('uses the warehouse staff chose over what the products say', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    [$a, $b, $wards] = twoWarehouses();
    [$p1] = Product::query()->limit(1)->pluck('id')->all();
    Product::query()->whereKey($p1)->update(['warehouse_id' => $b]);

    $shipment = app(Shipments::class)->create(orderWith([$p1], $a, $wards[2]), ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10'], staffUser());
    expect($shipment->request['s_name'])->toBe('Chị Lan')->and($shipment->request['s_ward_code'])->toBe((string) $wards[0]->tax_code);
});

it('refuses to create a waybill for an order split across warehouses until one is chosen', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    [$a, $b, $wards] = twoWarehouses();
    [$p1, $p2] = Product::query()->limit(2)->pluck('id')->all();
    Product::query()->whereKey($p1)->update(['warehouse_id' => $a]);
    Product::query()->whereKey($p2)->update(['warehouse_id' => $b]);
    $order = orderWith([$p1, $p2], null, $wards[2]);

    expect(fn () => app(Shipments::class)->create($order, ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10'], staffUser()))
        ->toThrow(ValidationException::class, 'nhiều kho');
    $order->update(['warehouse_id' => $a]);
    expect(app(Shipments::class)->create($order->fresh(), ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10'], staffUser())->request['s_name'])->toBe('Chị Lan');
});

it('falls back to the default sender when no warehouse is involved', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    [, , $wards] = twoWarehouses();
    [$p1] = Product::query()->limit(1)->pluck('id')->all();
    Product::query()->whereKey($p1)->update(['warehouse_id' => null]);
    DB::table('settings')->updateOrInsert(['key' => 'shipping_sender_name'], ['value' => json_encode('Công ty mặc định')]);
    app(SiteSettings::class)->clear();

    $shipment = app(Shipments::class)->create(orderWith([$p1], null, $wards[2]), ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10'], staffUser());
    expect($shipment->request['s_name'])->toBe('Công ty mặc định')->and($shipment->request['s_ward_code'])->toBe((string) $wards[2]->tax_code);
});

it('reads the warehouse by name from the product workbook and refuses an unknown one', function () {
    [$a] = twoWarehouses();
    $p = Product::query()->where('is_visible', true)->whereNotNull('code')->where('code', '!=', '')->where('regular_price', '>', 0)->firstOrFail();
    $plan = fn (string $name) => (new BulkPlanner(new BulkCatalog, [], false))->plan([['_row' => '2', 'id' => (string) $p->id, 'warehouse' => $name]], []);

    expect($plan('kho THỬ a')['rows'][0]['columns'])->toBe(['warehouse_id' => $a])
        ->and($plan('Kho không có')['errors'])->toBe(1)
        ->and($plan(BulkColumns::CLEAR)['rows'][0]['columns'])->toBe([]); // nothing to clear yet
    $p->update(['warehouse_id' => $a]);
    expect($plan(BulkColumns::CLEAR)['rows'][0]['columns'])->toBe(['warehouse_id' => null])
        ->and($plan('Kho thử A')['rows'][0]['columns'])->toBe([]); // already there
});
