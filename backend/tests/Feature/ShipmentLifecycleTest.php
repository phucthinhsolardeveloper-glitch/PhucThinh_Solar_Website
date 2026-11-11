<?php

use App\Domain\Shipping\Shipments;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Shipment;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
 * One rule for when a Nhất Tín waybill may be made, whatever the way in: service, order page, row button or bulk.
 * Mock mode only; Http::preventStrayRequests() proves nothing reaches Nhất Tín.
 */

beforeEach(function () {
    Http::preventStrayRequests();
    config(['shipping.nhattin.mode' => 'mock']);
});

function parcelFor(Order $order, string $status, ?string $bill = null): Shipment
{
    return $order->shipments()->create(['provider' => 'nhattin', 'environment' => 'mock', 'ref_code' => $order->code.'-'.($order->shipments()->count() + 1),
        'status' => $status, 'bill_code' => $bill, 'service_id' => '91', 'payer_id' => '10', 'weight_kg' => 1, 'cod_amount' => 0, 'cargo_value' => 0]);
}

const PARCEL = ['weight_kg' => 1, 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => 0];

it('allows a waybill only for orders still to be sent', function (string $status, bool $allowed) {
    $order = ntlOrder();
    $order->update(['status' => $status]);

    expect(Shipments::blocker($order) === null)->toBe($allowed);
    if ($allowed) {
        expect(app(Shipments::class)->create($order, PARCEL, ntlStaff())->bill_code)->toStartWith('MOCK');
    } else {
        expect(fn () => app(Shipments::class)->create($order, PARCEL, ntlStaff()))->toThrow(ValidationException::class);
        expect($order->shipments()->count())->toBe(0);
    }
})->with([
    'new' => ['new', true],
    'confirmed' => ['confirmed', true],
    'shipping without any waybill' => ['shipping', false],
    'done' => ['done', false],
    'cancelled' => ['cancelled', false],
]);

it('says what to do with the waybill that blocks a new one', function (string $parcel, string $hint) {
    $order = ntlOrder();
    parcelFor($order, $parcel, $parcel === 'unknown' || $parcel === 'create_pending' ? null : 'MOCK-OLD-'.$parcel);

    expect(Shipments::blocker($order))->toContain($hint);
    expect(fn () => app(Shipments::class)->create($order, PARCEL, ntlStaff()))->toThrow(ValidationException::class, $hint);
    expect($order->shipments()->count())->toBe(1);
})->with([
    'draft' => ['draft', 'Hủy vận đơn đó'],
    'in transit' => ['in_transit', 'Hủy vận đơn đó'],
    'failed' => ['failed', 'hủy vận đơn đó'],
    'returning' => ['returning', 'Chờ hàng về'],
    'unknown after a timeout' => ['unknown', 'Đối chiếu vận đơn chưa rõ'],
    'request still going out' => ['create_pending', 'Đối chiếu vận đơn chưa rõ'],
    'delivered' => ['delivered', 'giao thành công'],
]);

it('sends a returned or cancelled parcel again as a new waybill', function () {
    $order = ntlOrder();
    $order->update(['status' => 'shipping']);
    parcelFor($order, 'returned', 'MOCK-RETURNED');

    $again = app(Shipments::class)->create($order, PARCEL, ntlStaff());
    expect($again->ref_code)->toBe($order->code.'-2')->and($order->shipments()->count())->toBe(2)->and($order->fresh()->status)->toBe('shipping');

    $confirmed = ntlOrder();
    parcelFor($confirmed, 'cancelled', 'MOCK-CANCELLED');
    expect(Shipments::blocker($confirmed))->toBeNull();
});

it('never makes two waybills when the same order is sent twice or the first request is still out', function () {
    $order = ntlOrder();
    app(Shipments::class)->create($order, PARCEL, ntlStaff());
    expect(fn () => app(Shipments::class)->create($order, PARCEL, ntlStaff()))->toThrow(ValidationException::class);

    // A request that has not answered yet (create_pending) blocks a second one, and cannot be closed before it had time to finish.
    $slow = ntlOrder();
    $pending = parcelFor($slow, 'create_pending');
    expect(fn () => app(Shipments::class)->create($slow, PARCEL, ntlStaff()))->toThrow(ValidationException::class, 'Đối chiếu');
    expect(fn () => app(Shipments::class)->reconcile($pending, '', ntlStaff()))->toThrow(ValidationException::class, 'không ở trạng thái');
    $this->travel(3)->minutes();
    app(Shipments::class)->reconcile($pending->fresh(), '', ntlStaff());
    expect(app(Shipments::class)->create($slow, PARCEL, ntlStaff())->ref_code)->toBe($slow->code.'-2')
        ->and($slow->shipments()->whereIn('status', Shipments::ACTIVE)->count())->toBe(1);
});

it('refuses a waybill from a screen opened before the order was cancelled', function () {
    $this->actingAs(ntlStaff(), 'admin');
    $order = ntlOrder();
    $page = Livewire::test(EditOrder::class, ['record' => $order->id])->assertActionVisible('ntlCreate');

    Order::query()->whereKey($order->id)->update(['status' => 'cancelled']);
    $page->callAction('ntlCreate', PARCEL);

    expect($order->shipments()->count())->toBe(0);
    Livewire::test(EditOrder::class, ['record' => $order->id])->assertActionHidden('ntlCreate')->assertActionHidden('shipNext');
});

it('skips cancelled, finished and blocked orders in bulk and says why', function () {
    $this->actingAs(ntlStaff(), 'admin');
    $ok = ntlOrder();
    $cancelled = ntlOrder();
    $cancelled->update(['status' => 'cancelled']);
    $stuck = ntlOrder();
    parcelFor($stuck, 'unknown');

    Livewire::test(ListOrders::class)
        ->callTableBulkAction('shipSelected', [$ok, $cancelled, $stuck], ['service_id' => '91', 'payer_id' => '10', 'fallback_kg' => 1])
        ->assertNotified('Tạo 1/3 vận đơn');
    expect($ok->shipments()->count())->toBe(1)->and($cancelled->shipments()->count())->toBe(0)->and($stuck->shipments()->count())->toBe(1);
});

it('follows the running waybill, not an older one kept for history', function () {
    $this->actingAs(ntlStaff(), 'admin');
    $order = ntlOrder();
    $old = parcelFor($order, 'cancelled', 'MOCK-OLD');
    $new = parcelFor($order, 'draft', 'MOCK-NEW');

    expect(Shipments::current($order)->id)->toBe($new->id);
    Livewire::test(EditOrder::class, ['record' => $order->id])->callAction('ntlTrack');
    expect($new->events()->count())->toBeGreaterThan(0)->and($old->events()->count())->toBe(0);

    // A newer request whose result is unknown has no bill code yet: nothing to follow, and the old code is not used instead.
    $other = ntlOrder();
    parcelFor($other, 'cancelled', 'MOCK-OLD-2');
    parcelFor($other, 'unknown');
    Livewire::test(EditOrder::class, ['record' => $other->id])->assertActionHidden('ntlTrack')->assertActionVisible('ntlReconcile');
});

it('keeps a cancelled order cancelled when its parcel is delivered, and calls for help', function () {
    $owner = ntlStaff();
    $order = ntlOrder();
    $shipment = app(Shipments::class)->create($order, PARCEL, $owner);
    $order->update(['status' => 'cancelled']);

    app(Shipments::class)->webhook(['bill_no' => $shipment->bill_code, 'status_id' => '4', 'status_name' => 'Giao thành công', 'status_time' => time()]);

    expect($shipment->fresh()->status)->toBe('delivered')->and($order->fresh()->status)->toBe('cancelled')
        ->and($owner->notifications()->get()->pluck('data.title')->implode(' | '))->toContain('đã hủy nhưng vận đơn');
});

it('only shows the journey from the row button of a finished order whose last waybill was cancelled', function () {
    $this->actingAs(ntlStaff(), 'admin');
    $order = ntlOrder();
    parcelFor($order, 'cancelled', 'MOCK-GONE');
    $order->update(['status' => 'done']);

    Livewire::test(ListOrders::class)->mountAction(TestAction::make('shipping')->table($order))
        ->callMountedAction();
    expect($order->shipments()->count())->toBe(1);

    // The same button still makes the waybill for an order that may have one.
    $open = ntlOrder();
    Livewire::test(ListOrders::class)->callAction(TestAction::make('shipping')->table($open), PARCEL);
    expect($open->shipments()->count())->toBe(1);
});
