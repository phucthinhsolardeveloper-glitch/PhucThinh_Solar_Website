<?php

// Runs the Nhất Tín sandbox code path against tools/sandbox-sim inside the candidate backend container:
//   docker compose ... exec -T backend php < ../../tools/sandbox-sim/ntl-rehearsal.php
// It creates two test orders and a temporary staff account, and removes them (with their waybills,
// events and audit rows) at the end. Refuses to run unless NTL_SANDBOX_URL points at the simulator.

use App\Domain\Shipping\CheckoutFee;
use App\Domain\Shipping\Shipments;
use App\Models\Order;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sim = 'http://sim:8080';
if (config('shipping.nhattin.hosts.sandbox') !== $sim.'/ntl' || config('shipping.nhattin.mode') !== 'sandbox') {
    fwrite(STDERR, "Not pointed at the simulator; stopping.\n");
    exit(1);
}

$results = [];
$check = function (string $name, bool $ok, string $detail = '') use (&$results) {
    $results[] = [$ok ? 'PASS' : 'FAIL', $name, $detail];
};
$orders = [];
$previousWard = DB::table('settings')->where('key', 'shipping_sender_ward_id')->value('value');
$staff = User::query()->create(['name' => 'Kho thử SIM', 'username' => 'sim-kho-'.bin2hex(random_bytes(3)), 'password' => bin2hex(random_bytes(16)),
    'role' => User::ROLE_EDITOR, 'permissions' => ['orders'], 'is_active' => true]);

try {
    $ward = DB::table('wards')->whereNotNull('tax_code')->orderBy('id')->first();
    DB::table('settings')->updateOrInsert(['key' => 'shipping_sender_ward_id'], ['value' => json_encode((string) $ward->id)]);
    app(SiteSettings::class)->clear();
    $method = DB::table('payment_methods')->where('kind', 'cod')->value('id') ?? DB::table('payment_methods')->value('id');
    $newOrder = function () use ($ward, $method, &$orders) {
        $id = DB::table('orders')->insertGetId(['code' => 'SIM'.strtoupper(bin2hex(random_bytes(2))), 'customer_name' => 'Khách thử SIM', 'phone' => '0900000000',
            'address' => '1 Đường Thử', 'province_id' => $ward->province_id, 'ward_id' => $ward->id, 'payment_method_id' => $method, 'payment_code' => 'manual',
            'payment_status' => 'unpaid', 'subtotal' => 1000000, 'shipping_fee' => 0, 'total' => 1000000, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('order_items')->insert(['order_id' => $id, 'name' => 'Hàng thử SIM', 'regular_price' => 1000000, 'quantity' => 1]);
        $orders[] = $id;

        return Order::query()->findOrFail($id);
    };
    $shipments = app(Shipments::class);

    $checkout = app(CheckoutFee::class)->quote((int) $ward->id, ['kg' => 2, 'known' => true], 1000000);
    $check('checkout on APP_ENV=production with sandbox prices uses the area table', $checkout === null, json_encode($checkout));

    $order = $newOrder();
    $quotes = $shipments->quote($order, ['weight_kg' => 2]);
    $check('quote over HTTP returns three services', count($quotes) === 3 && $quotes[0]['fee'] > 0, json_encode($quotes, JSON_UNESCAPED_UNICODE));

    $shipment = $shipments->create($order, ['weight_kg' => 2, 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => 1000000], $staff);
    $check('create gives a draft SIM waybill', str_starts_with((string) $shipment->bill_code, 'SIM') && $shipment->status === 'draft', $shipment->bill_code.' '.$shipment->status);
    try {
        $shipments->create($order, ['weight_kg' => 2, 'service_id' => '91'], $staff);
        $check('second create is refused', false);
    } catch (Throwable $e) {
        $check('second create is refused', str_contains($e->getMessage(), 'đã có vận đơn') || str_contains(json_encode(method_exists($e, 'errors') ? $e->errors() : []), 'đã có vận đơn'));
    }

    Http::post($sim.'/_sim/ntl/next', ['fail' => '401']);
    $shipments->refresh($shipment);
    $check('expired token is renewed once and tracking still works', $shipment->events()->count() >= 1, 'events '.$shipment->events()->count());

    $push = Http::post($sim.'/_sim/ntl/push', ['bill_code' => $shipment->bill_code, 'status_id' => 2, 'status_name' => 'Chờ lấy hàng'])->json();
    $check('webhook with token accepted', ($push['status'] ?? 0) === 200 && str_contains((string) ($push['body'] ?? ''), '"known":true'), json_encode($push));
    $check('webhook moved status to waiting_pickup', $shipment->fresh()->status === 'waiting_pickup', $shipment->fresh()->status);

    $shipments->cancel($shipment->fresh(), $staff);
    $check('cancel over HTTP', $shipment->fresh()->status === 'cancelled');
    Http::post($sim.'/_sim/ntl/push', ['bill_code' => $shipment->bill_code, 'status_id' => 4, 'status_name' => 'Đã giao']);
    $check('a late push never moves a cancelled waybill', $shipment->fresh()->status === 'cancelled', $shipment->fresh()->status);

    $second = $newOrder();
    Http::post($sim.'/_sim/ntl/next', ['fail' => 'timeout']);
    try {
        $shipments->create($second, ['weight_kg' => 1, 'service_id' => '91'], $staff);
        $check('timeout on create is reported', false);
    } catch (Throwable $e) {
        $stuck = $second->shipments()->first();
        $check('timeout on create leaves an unknown waybill, not retried', $stuck?->status === 'unknown' && $second->shipments()->count() === 1, (string) $stuck?->status);
    }
    $stuck->forceFill(['updated_at' => now()->subMinutes(5)])->save();
    $shipments->reconcile($stuck, '', $staff);
    $retry = $shipments->create($second->fresh(), ['weight_kg' => 1, 'service_id' => '91'], $staff);
    $check('reconcile (not on portal) then a new waybill with the next ref code', $retry->status === 'draft' && str_ends_with($retry->ref_code, '-2'), $retry->ref_code);
} catch (Throwable $e) {
    $check('unexpected error', false, get_class($e).': '.$e->getMessage());
} finally {
    $shipmentIds = DB::table('shipments')->whereIn('order_id', $orders)->pluck('id');
    DB::table('shipment_events')->whereIn('shipment_id', $shipmentIds)->delete();
    DB::table('shipments')->whereIn('order_id', $orders)->delete();
    DB::table('admin_audit_events')->where('entity_type', 'Order')->whereIn('entity_id', $orders)->delete();
    DB::table('order_items')->whereIn('order_id', $orders)->delete();
    DB::table('orders')->whereIn('id', $orders)->delete();
    $previousWard === null ? DB::table('settings')->where('key', 'shipping_sender_ward_id')->delete()
        : DB::table('settings')->where('key', 'shipping_sender_ward_id')->update(['value' => $previousWard]);
    app(SiteSettings::class)->clear();
    $staff->delete();
}

foreach ($results as [$state, $name, $detail]) {
    echo $state.'  '.$name.($detail !== '' && $state === 'FAIL' ? '  ('.mb_substr($detail, 0, 200).')' : '')."\n";
}
echo 'Cleaned up: '.count($orders).' test orders, their waybills and audit rows, the temporary staff account.'."\n";
exit(in_array('FAIL', array_column($results, 0), true) ? 1 : 0);
