<?php

use App\Domain\Commerce\BankTransfer;
use App\Domain\Seo\Sitemap;
use App\Support\FormToken;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

// A buyer finds an order by code and the phone it was placed with; nothing else about the buyer comes back.

beforeEach(fn () => RateLimiter::clear('order-lookup|127.0.0.1'));

function lookupOrder(): object
{
    $id = DB::table('orders')->insertGetId(['code' => 'TRACUU', 'customer_name' => 'Khách Tra Cứu', 'phone' => '0912345678', 'email' => 'tra@example.test',
        'address' => '1 Đường Thử', 'payment_code' => 'manual', 'payment_status' => 'unpaid', 'subtotal' => 200000, 'shipping_fee' => 0, 'total' => 200000,
        'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('order_items')->insert(['order_id' => $id, 'product_id' => null, 'name' => 'Đèn thử', 'regular_price' => 100000, 'quantity' => 2, 'created_at' => now(), 'updated_at' => now()]);

    return DB::table('orders')->find($id);
}

function lookup(array $body)
{
    return test()->postJson('/api/v1/order-lookup', ['csrfToken' => FormToken::issue()] + $body);
}

it('shows an order to whoever has its code and phone, without the buyer’s address, e-mail or phone', function () {
    lookupOrder();
    $response = lookup(['code' => ' tracuu ', 'phone' => '0912 345 678'])->assertOk()
        ->assertJsonPath('order.code', 'TRACUU')->assertJsonPath('order.statusLabel', 'Đã xác nhận')
        ->assertJsonPath('order.paymentLabel', 'Chưa thanh toán')->assertJsonPath('order.items.0.total', 200000)
        ->assertJsonPath('order.shipment', null);

    expect((string) $response->getContent())->not->toContain('1 Đường Thử', 'tra@example.test', '0912345678', 'Khách Tra Cứu')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('answers a wrong phone like an unknown code and slows down repeated misses', function () {
    lookupOrder();
    lookup(['code' => 'TRACUU', 'phone' => '0900000000'])->assertNotFound()->assertJsonPath('error.code', 'ORDER_NOT_FOUND');
    lookup(['code' => 'KHONGCO', 'phone' => '0912345678'])->assertNotFound()->assertJsonPath('error.code', 'ORDER_NOT_FOUND');
    lookup(['code' => '', 'phone' => 'abc'])->assertStatus(422);
    test()->postJson('/api/v1/order-lookup', ['code' => 'TRACUU', 'phone' => '0912345678'])->assertStatus(419);
    foreach (range(1, 8) as $ignored) {
        lookup(['code' => 'SAI'.$ignored, 'phone' => '0912345678']);
    }
    lookup(['code' => 'TRACUU', 'phone' => '0912345678'])->assertStatus(429);
});

it('shows the carrier’s steps only for a real waybill', function () {
    $order = lookupOrder();
    $mock = DB::table('shipments')->insertGetId(['order_id' => $order->id, 'provider' => 'nhattin', 'environment' => 'mock', 'ref_code' => 'TRACUU-1', 'bill_code' => 'MOCK1',
        'status' => 'in_transit', 'weight_kg' => 1, 'cod_amount' => 0, 'service_id' => '91', 'payer_id' => '10', 'created_at' => now(), 'updated_at' => now()]);
    lookup(['code' => 'TRACUU', 'phone' => '0912345678'])->assertJsonPath('order.shipment', null);

    $real = DB::table('shipments')->insertGetId(['order_id' => $order->id, 'provider' => 'nhattin', 'environment' => 'production', 'ref_code' => 'TRACUU-2', 'bill_code' => 'NT123',
        'status' => 'in_transit', 'weight_kg' => 1, 'cod_amount' => 0, 'service_id' => '91', 'payer_id' => '10', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('shipment_events')->insert(['shipment_id' => $real, 'source' => 'tracking', 'status_raw' => '15', 'status_name' => 'Đang vận chuyển', 'happened_at' => now(), 'fingerprint' => str_repeat('a', 64)]);
    lookup(['code' => 'TRACUU', 'phone' => '0912345678'])->assertJsonPath('order.shipment.billCode', 'NT123')
        ->assertJsonPath('order.shipment.events.0.label', 'Đang vận chuyển');
    expect($mock)->toBeInt();
});

it('shows the transfer QR again while a bank-transfer order is unpaid', function () {
    foreach (['bank_bin' => '970405', 'bank_account_no' => '5020201003091', 'bank_account_name' => 'Cong ty thu'] as $key => $value) {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value)]);
    }
    app(SiteSettings::class)->clear();
    $order = lookupOrder();
    DB::transaction(fn () => app(BankTransfer::class)->issue((int) $order->id, $order->code, 200000));

    lookup(['code' => 'TRACUU', 'phone' => '0912345678'])->assertJsonPath('order.transfer.note', 'PTSTRACUU')->assertJsonPath('order.transfer.amount', 200000);
    DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'paid']);
    expect(lookup(['code' => 'TRACUU', 'phone' => '0912345678'])->json('order'))->not->toHaveKey('transfer');
});

it('lists the lookup page in the sitemap', function () {
    expect(array_column(app(Sitemap::class)->entries(), 'path'))->toContain('/tra-cuu-don-hang/');
});
