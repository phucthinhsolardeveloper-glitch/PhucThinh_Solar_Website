<?php

use App\Domain\Commerce\ManualPayment;
use App\Domain\Commerce\VietQr;
use App\Models\Order;
use App\Models\User;
use App\Support\FormToken;
use App\Support\SiteSettings;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

// Test-only account: a well-known demo number, never the company's.
function demoBank(): void
{
    foreach (['bank_bin' => '970415', 'bank_account_no' => '113366668888', 'bank_account_name' => 'Tai khoan thu nghiem'] as $key => $value) {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
    }
    app(SiteSettings::class)->clear();
}

beforeEach(function () {
    config(['session.driver' => 'file']);
    Mail::fake();
    RateLimiter::clear('form:checkout|127.0.0.1');
    $GLOBALS['shopCookies'] = [];
});

function placeTransferOrder(): array
{
    $product = DB::table('products')->where('is_visible', true)->where('quote_only', false)->where('regular_price', '>', 0)->orderBy('id')->first();
    $ward = DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')->where('w.is_visible', true)->select('w.id', 'w.province_id')->first();
    $method = DB::table('payment_methods')->where('kind', 'bank_transfer')->value('id');
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 1]);
    $checkout = shop('GET', '/api/v1/checkout')->assertOk();
    $placed = shop('POST', '/api/v1/checkout', ['csrfToken' => $checkout->json('csrfToken'), 'checkoutToken' => $checkout->json('checkoutToken'), 'data' => [
        'fullname' => 'Khách thử', 'phone' => '0912345678', 'email' => 'khach@example.test', 'city' => $ward->province_id, 'ward' => $ward->id,
        'address' => '1 Đường Thử', 'payments' => $method]])->assertOk();

    return [$placed, Order::query()->where('code', $placed->json('orderCode'))->firstOrFail()];
}

it('builds a valid NAPAS payload that decodes back to the same account, amount and note', function () {
    $payload = VietQr::payload('970415', '113366668888', 1235000, 'PTSABC123');
    expect($payload)->toStartWith('000201010212')->toContain('0010A000000727')->toContain('5407123500058')->toContain('0809PTSABC123')
        ->and(substr($payload, -4))->toBe(VietQr::crc(substr($payload, 0, -4)))
        ->and(VietQr::crc('123456789'))->toBe('29B1');

    $png = tempnam(sys_get_temp_dir(), 'qr').'.png';
    (new QRCode(new QROptions(['outputType' => QROutputInterface::GDIMAGE_PNG, 'scale' => 6, 'outputBase64' => false])))->render($payload, $png);
    expect((string) (new QRCode)->readFromFile($png))->toBe($payload);
});

it('offers bank transfer only once the owner has set the receiving account', function () {
    DB::table('settings')->whereIn('key', ['bank_bin', 'bank_account_no', 'bank_account_name'])->delete();
    app(SiteSettings::class)->clear();
    $kinds = fn () => DB::table('payment_methods')->whereIn('id', collect(shop('GET', '/api/v1/checkout')->json('payments'))->pluck('id'))->pluck('kind')->all();
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => DB::table('products')->where('is_visible', true)->where('regular_price', '>', 0)->value('id'), 'quantity' => 1]);
    expect($kinds())->not->toContain('bank_transfer');
    demoBank();
    expect($kinds())->toContain('bank_transfer');
});

it('shows one fixed QR per order, lets the buyer report the transfer, and only staff mark it paid', function () {
    demoBank();
    [$placed, $order] = placeTransferOrder();
    $thanks = '/api/v1/order-thanks?token='.$placed->json('thanksToken');
    $first = shop('GET', $thanks)->assertOk()->assertJsonPath('paymentState', 'awaiting_transfer')
        ->assertJsonPath('transfer.amount', (int) $order->total)->assertJsonPath('transfer.accountNo', '113366668888')
        ->assertJsonPath('transfer.note', 'PTS'.$order->code);
    expect($first->json('transfer.qrImage'))->toStartWith('data:image/svg+xml;base64,');
    shop('GET', $thanks)->assertJsonPath('transfer.note', 'PTS'.$order->code);
    expect(DB::table('payment_instructions')->where('order_id', $order->id)->count())->toBe(1);

    shop('POST', '/api/v1/order-thanks/transferred', ['token' => $placed->json('thanksToken')])->assertOk();
    shop('POST', '/api/v1/order-thanks/transferred', ['token' => str_repeat('a', 48)])->assertNotFound();
    expect($order->fresh()->transfer_reported_at)->not->toBeNull()->and($order->fresh()->payment_status)->toBe('unpaid');

    $staff = User::query()->create(['name' => 'Kế toán', 'username' => 'kt-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => ['orders', 'orders_payment'], 'is_active' => true]);
    expect(fn () => app(ManualPayment::class)->confirm($order, $staff, 'FT2611', (int) $order->total - 1000))->toThrow(ValidationException::class, 'chênh lệch');
    app(ManualPayment::class)->confirm($order, $staff, 'FT2611', (int) $order->total);
    expect($order->fresh()->payment_status)->toBe('paid');
    shop('GET', $thanks)->assertJsonPath('paymentState', 'paid');
});
