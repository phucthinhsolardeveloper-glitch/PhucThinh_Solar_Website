<?php

use App\Mail\OrderNotification;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

// The whole shop journey, rolled back after each test. Mail is faked.
beforeEach(function () {
    config(['session.driver' => 'file']);
    Mail::fake();
    RateLimiter::clear('form:checkout|127.0.0.1');
    $GLOBALS['shopCookies'] = [];
});

/** Sends a request carrying the session cookie of the previous one, like a browser. */
function shop(string $method, string $uri, array $body = [])
{
    // Cookies come back already encrypted, so they are replayed as they are.
    $test = test()->withUnencryptedCookies($GLOBALS['shopCookies']);
    $response = $method === 'GET' ? $test->getJson($uri) : $test->postJson($uri, $body);
    foreach ($response->headers->getCookies() as $cookie) {
        $GLOBALS['shopCookies'][$cookie->getName()] = $cookie->getValue();
    }

    return $response;
}

function buyableProduct(): object
{
    return DB::table('products')->where('is_visible', true)->where('quote_only', false)
        ->where(fn ($q) => $q->where('regular_price', '>', 0)->orWhere('sale_price', '>', 0))->orderBy('id')->first();
}

it('adds, merges, updates and removes cart lines', function () {
    $product = buyableProduct();
    $token = FormToken::issue();
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => $token, 'productId' => $product->id, 'quantity' => 2])->assertOk()->assertJsonPath('count', 1);
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => $token, 'productId' => $product->id, 'quantity' => 3])->assertOk()->assertJsonPath('count', 1);
    $cart = shop('GET', '/api/v1/cart')->assertOk()->assertJsonPath('items.0.quantity', 5);
    expect($cart->json('csrfToken'))->toMatch('/^[a-f0-9]{64}$/');
    $code = $cart->json('items.0.code');

    shop('POST', '/api/v1/cart', ['action' => 'update', 'csrfToken' => $token, 'productId' => $product->id, 'quantity' => 1, 'code' => $code])->assertOk();
    shop('GET', '/api/v1/cart')->assertJsonPath('items.0.quantity', 1);
    shop('POST', '/api/v1/cart', ['action' => 'update', 'csrfToken' => $token, 'productId' => $product->id + 100000, 'quantity' => 1, 'code' => $code])->assertStatus(409);
    shop('POST', '/api/v1/cart', ['action' => 'delete', 'csrfToken' => $token, 'code' => $code])->assertOk()->assertJsonPath('count', 0);
    shop('POST', '/api/v1/cart', ['action' => 'delete', 'csrfToken' => $token, 'code' => $code])->assertNotFound();
});

it('places an order once, mails shop and customer, and shows the thank-you page once', function () {
    $product = buyableProduct();
    $ward = DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')
        ->where('w.is_visible', true)->where('p.is_visible', true)->select('w.id', 'w.province_id as id_city')->first();
    $payment = DB::table('payment_methods')->where('is_visible', true)->value('id');
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 2]);
    $checkout = shop('GET', '/api/v1/checkout')->assertOk()->assertJsonPath('cartReady', true);
    $body = ['csrfToken' => $checkout->json('csrfToken'), 'checkoutToken' => $checkout->json('checkoutToken'), 'data' => [
        'fullname' => 'Khách thử', 'phone' => '0912345678', 'email' => 'khach@example.test', 'city' => $ward->id_city, 'ward' => $ward->id, 'address' => '1 Đường Thử', 'payments' => $payment]];

    shop('POST', '/api/v1/checkout', ['data' => ['phone' => '1']] + $body)->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
    $placed = shop('POST', '/api/v1/checkout', $body)->assertOk()->assertJsonPath('ok', true);
    $order = DB::table('orders')->where('code', $placed->json('orderCode'))->first();
    expect($order->checkout_token)->toBe($checkout->json('checkoutToken'))
        ->and($order->status)->toBe('new')
        ->and((int) DB::table('order_items')->where('order_id', $order->id)->value('quantity'))->toBe(2);
    Mail::assertSent(OrderNotification::class, 2);

    // A second submit of the same form lands on the same order.
    shop('POST', '/api/v1/checkout', $body)->assertOk()->assertJsonPath('orderCode', $order->code);
    expect(DB::table('orders')->where('checkout_token', $order->checkout_token)->count())->toBe(1);

    $thanks = '/api/v1/order-thanks?token='.$placed->json('thanksToken');
    shop('GET', $thanks)->assertOk()->assertJsonPath('order.code', $order->code)->assertJsonPath('conversionPending', true);
    shop('GET', $thanks)->assertOk()->assertJsonPath('conversionPending', false);
    shop('GET', '/api/v1/cart')->assertJsonPath('count', 0);
});

it('quotes the ward delivery fee and rejects a ward from another province', function () {
    $ward = DB::table('wards')->where('is_visible', true)->select('id', 'province_id as id_city')->first();
    shop('POST', '/api/v1/shipping-quote', ['city' => $ward->id_city, 'ward' => $ward->id])->assertOk()->assertJsonPath('selected.provider', 'local');
    shop('POST', '/api/v1/shipping-quote', ['city' => $ward->id_city + 1000, 'ward' => $ward->id])->assertStatus(422);
});

it('answers VNPay IPN with its own codes and never trusts an unsigned call', function () {
    config(['commerce.vnpay.hash_secret' => 'test-secret']);
    shop('GET', '/api/v1/payment/vnpay?mode=ipn&vnp_TxnRef=X&vnp_SecureHash=bad')->assertOk()->assertJsonPath('RspCode', '97');
});

it('marks an order paid only for a correctly signed VNPay IPN with the right amount, once', function () {
    config(['commerce.vnpay.hash_secret' => 'test-secret']);
    $id = DB::table('orders')->insertGetId(['code' => 'VNTEST', 'total' => 150000, 'subtotal' => 150000, 'payment_status' => 'unpaid', 'customer_name' => 'Khách thử',
        'phone' => '0912345678', 'email' => 'k@example.test', 'address' => 'x', 'checkout_token' => str_repeat('b', 64), 'payment_code' => 'vnpay', 'created_at' => now(), 'updated_at' => now()]);
    $signed = function (array $params) {
        ksort($params);
        $query = implode('&', array_map(fn ($k, $v) => urlencode($k).'='.urlencode($v), array_keys($params), $params));

        return '/api/v1/payment/vnpay?mode=ipn&'.$query.'&vnp_SecureHash='.hash_hmac('sha512', $query, 'test-secret');
    };
    shop('GET', $signed(['vnp_TxnRef' => 'VNTEST', 'vnp_Amount' => '100', 'vnp_ResponseCode' => '00', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '04');
    shop('GET', $signed(['vnp_TxnRef' => 'MISSING', 'vnp_Amount' => '15000000', 'vnp_ResponseCode' => '00', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '01');
    DB::table('orders')->insert(['code' => 'MANUAL', 'total' => 150000, 'subtotal' => 150000, 'payment_status' => 'unpaid', 'payment_code' => 'manual',
        'customer_name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'x', 'created_at' => now(), 'updated_at' => now()]);
    shop('GET', $signed(['vnp_TxnRef' => 'MANUAL', 'vnp_Amount' => '15000000', 'vnp_ResponseCode' => '00', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '01');
    expect(DB::table('orders')->where('code', 'MANUAL')->value('payment_status'))->toBe('unpaid');
    shop('GET', $signed(['vnp_TxnRef' => 'VNTEST', 'vnp_Amount' => '15000000', 'vnp_ResponseCode' => '00', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '00');
    expect(DB::table('orders')->where('id', $id)->value('payment_status'))->toBe('paid')
        ->and(DB::table('payment_transactions')->where('order_id', $id)->value('status'))->toBe('paid');
    shop('GET', $signed(['vnp_TxnRef' => 'VNTEST', 'vnp_Amount' => '15000000', 'vnp_ResponseCode' => '00', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '02');
    shop('GET', $signed(['vnp_TxnRef' => 'VNTEST', 'vnp_Amount' => '15000000', 'vnp_ResponseCode' => '24', 'vnp_TransactionNo' => '1']))->assertJsonPath('RspCode', '02');
    expect(DB::table('orders')->where('id', $id)->value('payment_status'))->toBe('paid')
        ->and(DB::table('payment_transactions')->where('order_id', $id)->value('status'))->toBe('paid');
});

it('retries a pending VNPay checkout with the same order and payment redirect', function () {
    config(['commerce.vnpay' => [
        'active' => true, 'tmn_code' => 'TEST', 'hash_secret' => 'test-secret',
        'endpoint' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'return_url' => 'https://example.test/api/v1/payment/vnpay',
        'ipn_url' => 'https://example.test/api/v1/payment/vnpay?mode=ipn',
    ]]);
    $product = buyableProduct();
    $ward = DB::table('wards')->where('is_visible', true)->first();
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 1]);
    $checkout = shop('GET', '/api/v1/checkout')->assertOk();
    $body = ['csrfToken' => $checkout->json('csrfToken'), 'checkoutToken' => $checkout->json('checkoutToken'), 'data' => [
        'fullname' => 'Khách thử', 'phone' => '0912345678', 'email' => 'khach@example.test', 'city' => $ward->province_id,
        'ward' => $ward->id, 'address' => '1 Đường Thử', 'payments' => -1]];

    $first = shop('POST', '/api/v1/checkout', $body)->assertOk();
    $retry = shop('POST', '/api/v1/checkout', $body)->assertOk()->assertJsonPath('orderCode', $first->json('orderCode'));
    expect($first->json('redirect'))->toStartWith('https://sandbox.vnpayment.vn/')
        ->and($retry->json('redirect'))->toStartWith('https://sandbox.vnpayment.vn/')
        ->and(DB::table('orders')->where('checkout_token', $body['checkoutToken'])->count())->toBe(1)
        ->and(DB::table('payment_transactions')->where('reference', $first->json('orderCode'))->where('status', 'pending')->count())->toBe(1);
});

it('accepts the classic checkout form and sends errors back to the cart as a one-time notice', function () {
    $product = buyableProduct();
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 1]);
    $checkout = shop('GET', '/api/v1/checkout');
    $response = test()->withUnencryptedCookies($GLOBALS['shopCookies'])->post('/api/v1/checkout', [
        'form_csrf' => $checkout->json('csrfToken'), 'checkout_token' => $checkout->json('checkoutToken'), 'dataOrder' => ['fullname' => 'Khách thử', 'phone' => 'sai'],
    ]);
    $response->assertStatus(303)->assertRedirect('/gio-hang/');
    shop('GET', '/api/v1/checkout-notice')->assertJsonPath('form.fullname', 'Khách thử')->assertJsonPath('messages.1', 'Số điện thoại không hợp lệ')
        ->assertJsonMissingPath('messages.99');
    shop('GET', '/api/v1/checkout-notice')->assertJsonPath('messages', []);
});
