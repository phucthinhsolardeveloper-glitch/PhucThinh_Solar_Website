<?php

use App\Domain\Commerce\Shipping;
use App\Domain\Shipping\CheckoutFee;
use App\Domain\Shipping\NhatTin\NtlClient;
use App\Domain\Shipping\NhatTin\NtlException;
use App\Domain\Shipping\Shipments;
use App\Models\Order;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function ntlStaff(): User
{
    return User::query()->create(['name' => 'Kho', 'username' => 'kho-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['orders'], 'is_active' => true]);
}

function ntlOrder(string $kind = 'cod'): Order
{
    if (! DB::table('wards')->whereNotNull('tax_code')->exists()) {
        test()->artisan('pts:ward-codes');
    }
    $ward = DB::table('wards')->whereNotNull('tax_code')->first();
    DB::table('settings')->updateOrInsert(['key' => 'shipping_sender_ward_id'], ['value' => json_encode((string) $ward->id)]);
    app(SiteSettings::class)->clear();
    $method = DB::table('payment_methods')->where('kind', $kind)->value('id');
    $id = DB::table('orders')->insertGetId(['code' => 'NT'.strtoupper(bin2hex(random_bytes(2))), 'customer_name' => 'Khách', 'phone' => '0900000001', 'address' => '1 Đường Thử',
        'province_id' => $ward->province_id, 'ward_id' => $ward->id, 'payment_method_id' => $method, 'payment_code' => 'manual', 'payment_status' => 'unpaid',
        'subtotal' => 1000000, 'shipping_fee' => 30000, 'total' => 1030000, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('order_items')->insert(['order_id' => $id, 'name' => 'Đèn thử', 'regular_price' => 1000000, 'quantity' => 1]);

    return Order::query()->findOrFail($id);
}

beforeEach(fn () => Http::preventStrayRequests());

it('quotes, creates one waybill, follows it and cancels it in mock mode without calling anyone', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    $order = ntlOrder('cod');
    $shipments = app(Shipments::class);

    $quotes = $shipments->quote($order, ['weight_kg' => 2]);
    expect(collect($quotes)->pluck('service_id')->all())->toBe(['91', '90', '81'])->and($quotes[0]['fee'])->toBeGreaterThan(0)
        ->and($shipments->codFor($order))->toBe(1030000);

    $shipment = $shipments->create($order, ['weight_kg' => 2, 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => 1030000], ntlStaff());
    expect($shipment->bill_code)->toStartWith('MOCK')->and($shipment->status)->toBe('draft')->and($shipment->environment)->toBe('mock')
        ->and($shipment->request['r_ward_code'])->toBeString()->and($shipment->request['cod_amount'])->toBe(1030000);
    expect(fn () => $shipments->create($order, ['weight_kg' => 2, 'service_id' => '91'], ntlStaff()))->toThrow(ValidationException::class, 'đã có vận đơn');

    $shipments->refresh($shipment);
    expect($shipment->fresh()->status)->toBe('in_transit')->and($shipment->events()->count())->toBe(2);
    $shipments->refresh($shipment);
    expect($shipment->events()->count())->toBe(2);

    $shipment->update(['status' => 'draft']);
    $shipments->cancel($shipment->fresh(), ntlStaff());
    expect($shipment->fresh()->status)->toBe('cancelled')->and($order->fresh()->payment_status)->toBe('unpaid');
});

it('collects nothing on delivery for paid or bank transfer orders', function () {
    $order = ntlOrder('bank_transfer');
    expect(app(Shipments::class)->codFor($order))->toBe(0);
});

it('keeps production locked and sandbox blocked until the place codes are checked', function () {
    config(['shipping.nhattin.mode' => 'production', 'shipping.nhattin.production_unlocked' => false]);
    expect(app(NtlClient::class)->readiness()['ready'])->toBeFalse();
    expect(fn () => app(NtlClient::class)->call('POST', '/v3/bill/calc-fee'))->toThrow(NtlException::class, 'khóa');

    config(['shipping.nhattin.mode' => 'sandbox', 'shipping.nhattin.username' => 'u', 'shipping.nhattin.password' => 'p', 'shipping.nhattin.partner_id' => '1']);
    expect(fn () => app(Shipments::class)->quote(ntlOrder(), ['weight_kg' => 1]))->toThrow(ValidationException::class, 'MAPPING_REQUIRED');
});

it('marks a sandbox create that timed out as unknown and never sends it twice', function () {
    config(['shipping.nhattin.mode' => 'sandbox', 'shipping.nhattin.username' => 'u', 'shipping.nhattin.password' => 'p', 'shipping.nhattin.partner_id' => '1',
        'shipping.nhattin.write_enabled' => true, 'shipping.nhattin.locations_verified' => true]);
    Http::fake([
        'apisandbox.ntlogistics.vn/v1/auth/sign-in' => Http::response(['data' => ['jwt_token' => 'tok', 'token_expires_in' => '1m']]),
        'apisandbox.ntlogistics.vn/v3/bill/create' => fn () => throw new ConnectionException('timeout'),
    ]);
    $order = ntlOrder();
    expect(fn () => app(Shipments::class)->create($order, ['weight_kg' => 1, 'service_id' => '91'], ntlStaff()))->toThrow(ValidationException::class, 'trước khi tạo lại');
    expect($order->shipments()->value('status'))->toBe('unknown');
    expect(fn () => app(Shipments::class)->create($order, ['weight_kg' => 1, 'service_id' => '91'], ntlStaff()))->toThrow(ValidationException::class, 'đã có vận đơn');
});

it('accepts status pushes only with the token, ignores repeats and never moves a delivered waybill back', function () {
    config(['shipping.nhattin.mode' => 'mock', 'shipping.nhattin.webhook_token' => 'secret-token']);
    $order = ntlOrder();
    $shipment = app(Shipments::class)->create($order, ['weight_kg' => 1, 'service_id' => '91'], ntlStaff());
    $push = ['bill_no' => $shipment->bill_code, 'status_id' => 4, 'status_name' => 'Đã giao', 'status_time' => now()->timestamp];

    $this->postJson('/api/integrations/ntl/webhook', $push)->assertStatus(401);
    $this->postJson('/api/integrations/ntl/webhook', $push, ['Authorization' => 'Bearer wrong'])->assertStatus(401);
    $this->postJson('/api/integrations/ntl/webhook', $push, ['Authorization' => 'secret-token'])->assertOk()->assertJsonPath('known', true);
    $this->postJson('/api/integrations/ntl/webhook', $push, ['Authorization' => 'Bearer secret-token'])->assertOk();
    expect($shipment->fresh()->status)->toBe('delivered')->and($shipment->events()->count())->toBe(1);

    $this->postJson('/api/integrations/ntl/webhook', ['bill_no' => $shipment->bill_code, 'status_id' => 13, 'status_time' => now()->timestamp - 60], ['Authorization' => 'secret-token'])->assertOk();
    expect($shipment->fresh()->status)->toBe('delivered')->and($order->fresh()->payment_status)->toBe('unpaid');
});

it('prices delivery with Nhất Tín at checkout, marks a mock price and sends bulky or unknown parcels to staff', function () {
    config(['shipping.nhattin.mode' => 'mock']);
    $ward = DB::table('wards')->whereNotNull('tax_code')->first();
    $fee = app(CheckoutFee::class);

    $quote = app(Shipping::class)->quote((int) $ward->province_id, (int) $ward->id, 1000000, ['kg' => 3, 'known' => true]);
    expect($quote['priced'])->toBeTrue()->and($quote['selected']['provider'])->toBe('nhattin')->and($quote['selected']['amount'])->toBeGreaterThan(0)
        ->and($quote['selected']['label'])->toContain('mô phỏng')->and($quote['selected']['note'])->toContain('không phải báo giá');

    expect($fee->quote((int) $ward->id, ['kg' => 80, 'known' => true], 1)['priced'])->toBeFalse()
        ->and($fee->quote((int) $ward->id, ['kg' => 80, 'known' => true], 1)['note'])->toContain('cồng kềnh');

    config(['shipping.nhattin.mode' => 'sandbox', 'shipping.nhattin.username' => 'u', 'shipping.nhattin.password' => 'p', 'shipping.nhattin.partner_id' => '1', 'shipping.nhattin.locations_verified' => true]);
    expect($fee->quote((int) $ward->id, ['kg' => 0, 'known' => false], 1)['note'])->toContain('xác nhận kiện hàng');
    config(['shipping.nhattin.locations_verified' => false]);
    expect($fee->quote((int) $ward->id, ['kg' => 2, 'known' => true], 1))->toBeNull();
});
