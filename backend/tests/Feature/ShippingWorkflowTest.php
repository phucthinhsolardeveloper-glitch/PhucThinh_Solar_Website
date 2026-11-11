<?php

use App\Domain\Admin\WorkQueue;
use App\Domain\Shipping\ShipmentAutomation;
use App\Domain\Shipping\Shipments;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['shipping.nhattin.mode' => 'mock']);
});

function shippingOwner(): User
{
    return User::query()->create(['name' => 'Kho', 'username' => 'ship-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

it('creates waybills for several orders from the list, skipping the ones that need none', function () {
    $this->actingAs(shippingOwner(), 'admin');
    $new = ntlOrder('cod');
    $new->update(['status' => 'new']);
    $confirmed = ntlOrder('bank');
    $done = ntlOrder('cod');
    $done->update(['status' => 'done']);

    Livewire::test(ListOrders::class)
        ->callTableBulkAction('shipSelected', [$new, $confirmed, $done], ['service_id' => '91', 'payer_id' => '10', 'fallback_kg' => 2])
        ->assertHasNoTableBulkActionErrors()->assertNotified('Tạo 2/3 vận đơn');

    expect($new->shipments()->count())->toBe(1)->and($confirmed->shipments()->count())->toBe(1)->and($done->shipments()->count())->toBe(0)
        ->and($new->fresh()->status)->toBe('confirmed')
        ->and((int) $new->shipments()->first()->cod_amount)->toBe(1030000)
        ->and((int) $confirmed->shipments()->first()->cod_amount)->toBe(0)
        ->and((float) $new->shipments()->first()->weight_kg)->toBe(2.0);
});

it('works the parcel weight out from the products', function () {
    $order = ntlOrder('cod');
    $product = Product::query()->firstOrFail();
    $product->update(['weight_kg' => 3.5]);
    DB::table('order_items')->where('order_id', $order->id)->update(['product_id' => $product->id, 'quantity' => 2]);

    expect(app(ShipmentAutomation::class)->weight($order))->toBe(['kg' => 7.0, 'known' => true]);
    DB::table('order_items')->insert(['order_id' => $order->id, 'name' => 'Phụ kiện', 'regular_price' => 1, 'quantity' => 1]);
    expect(app(ShipmentAutomation::class)->weight($order, 0.5))->toBe(['kg' => 7.5, 'known' => false]);
});

it('moves the order with its parcel and calls for help when a parcel is in trouble', function () {
    $owner = shippingOwner();
    $order = ntlOrder('cod');
    $shipment = app(Shipments::class)->create($order, ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => 1030000], $owner);

    app(Shipments::class)->refresh($shipment); // mock journey: in transit
    expect($order->fresh()->status)->toBe('shipping');

    app(Shipments::class)->webhook(['bill_no' => $shipment->bill_code, 'status_id' => '4', 'status_name' => 'Giao thành công', 'status_time' => time()]);
    expect($shipment->fresh()->status)->toBe('delivered')->and($order->fresh()->status)->toBe('done');
    expect(collect(app(WorkQueue::class)->sections($owner))->firstWhere('key', 'cod')['count'] ?? 0)->toBeGreaterThan(0);

    $other = ntlOrder('cod');
    $parcel = app(Shipments::class)->create($other, ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => 0], $owner);
    app(Shipments::class)->webhook(['bill_no' => $parcel->bill_code, 'status_id' => '7', 'status_name' => 'Không liên lạc được', 'status_time' => time()]);
    expect($owner->notifications()->get()->pluck('data.title')->implode(' | '))->toContain('Có sự cố');
});

it('prints several orders on one sheet run, one page each', function () {
    $this->actingAs(shippingOwner(), 'admin');
    $a = ntlOrder('cod');
    $b = ntlOrder('cod');
    $html = $this->get(route('order.print-many', ['ids' => $a->id.','.$b->id]))->assertOk()->getContent();
    expect(substr_count($html, 'class="sheet"'))->toBe(2)
        ->and($html)->toContain($a->code)->toContain($b->code)->toContain('In 2 đơn hàng');
});
