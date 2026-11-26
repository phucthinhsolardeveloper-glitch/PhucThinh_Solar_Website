<?php

use App\Domain\Commerce\CustomerOrderMail;
use App\Domain\Commerce\ManualPayment;
use App\Domain\Commerce\OrderLifecycle;
use App\Domain\Commerce\OrderSheet;
use App\Domain\Outbox\Outbox;
use App\Filament\Pages\MailTemplatesPage;
use App\Mail\OrderNotification;
use App\Models\Order;
use App\Models\User;
use App\Support\FormToken;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

// The order sheet and the mail a buyer may receive. Nothing is sent: mail is faked and the queue never runs.
beforeEach(function () {
    Mail::fake();
    Queue::fake();
    config(['session.driver' => 'file', 'mail.admin_to' => 'sales@example.test']);
    RateLimiter::clear('form:checkout|127.0.0.1');
    $GLOBALS['shopCookies'] = [];
    DB::table('settings')->where('key', 'order_mail')->delete();
});

function mailSwitches(array $on): void
{
    DB::table('settings')->updateOrInsert(['key' => 'order_mail'], ['value' => json_encode(array_fill_keys($on, true))]);
}

function sheetStaff(array $permissions = []): User
{
    return User::query()->create(['name' => 'Nhân viên thử', 'username' => 'sheet-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR,
        'permissions' => $permissions, 'is_active' => true]);
}

/** Buys two of the first buyable product through the real checkout and returns the order. */
function placeSheetOrder(string $email = 'khach.thu@example.test'): Order
{
    $product = DB::table('products')->where('is_visible', true)->where('quote_only', false)
        ->where(fn ($q) => $q->where('regular_price', '>', 0)->orWhere('sale_price', '>', 0))->orderBy('id')->first();
    $ward = DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')
        ->where('w.is_visible', true)->where('p.is_visible', true)->select('w.id', 'w.province_id as id_city')->first();
    $payment = DB::table('payment_methods')->where('is_visible', true)->where('kind', 'cash')->value('id');
    shop('POST', '/api/v1/cart', ['action' => 'add', 'csrfToken' => FormToken::issue(), 'productId' => $product->id, 'quantity' => 2])->assertOk();
    $checkout = shop('GET', '/api/v1/checkout')->assertOk();
    $body = ['csrfToken' => $checkout->json('csrfToken'), 'checkoutToken' => $checkout->json('checkoutToken'), 'data' => [
        'fullname' => 'Trần Thị Đặng Hưởng', 'phone' => '0912 345 678', 'email' => $email, 'city' => $ward->id_city, 'ward' => $ward->id, 'address' => '1 Đường Thử Nghiệm', 'payments' => $payment,
    ]];
    shop('POST', '/api/v1/checkout', $body)->assertOk();
    shop('POST', '/api/v1/checkout', $body)->assertOk();

    return Order::query()->orderByDesc('id')->firstOrFail();
}

function queuedMail(int $orderId, string $suffix = 'customer'): ?OrderNotification
{
    $payload = DB::table('outbox_messages')->where('dedupe_key', 'order:'.$orderId.':'.$suffix)->value('payload');
    $mail = $payload === null ? null : Outbox::mailable((string) $payload);

    return $mail instanceof OrderNotification ? $mail : null;
}

function customerKeys(int $orderId): array
{
    return DB::table('outbox_messages')->where('dedupe_key', 'like', 'order:'.$orderId.':customer%')->orderBy('id')->pluck('dedupe_key')->all();
}

it('stores one sheet when an order is placed and keeps it when the catalog and company details change', function () {
    $order = placeSheetOrder();
    $sheets = app(OrderSheet::class);
    $stored = $sheets->stored($order->id);

    expect(DB::table('order_documents')->where('order_id', $order->id)->count())->toBe(1)
        ->and($stored['code'])->toBe($order->code)
        ->and($stored['customer']['name'])->toBe('Trần Thị Đặng Hưởng')
        ->and($stored['customer']['phone'])->toBe('0912345678')
        ->and($stored['items'][0]['quantity'])->toBe(2)
        ->and((float) $stored['totals']['total'])->toBe((float) $order->total);

    DB::table('products')->update(['name' => 'Tên mới', 'regular_price' => 1]);
    DB::table('settings')->updateOrInsert(['key' => 'hotline'], ['value' => json_encode('0000 000 000')]);
    app(SiteSettings::class)->clear();
    $sheets->capture($order->id);

    expect($sheets->stored($order->id))->toBe($stored)
        ->and($sheets->build($order->id)['company']['hotline'])->toBe('0000 000 000');
});

it('refuses a stored sheet that was altered', function () {
    $order = placeSheetOrder();
    DB::table('order_documents')->where('order_id', $order->id)->update(['snapshot' => '{"code":"KHAC"}']);

    expect(app(OrderSheet::class)->stored($order->id))->toBeNull();
});

it('renders the same document from the same sheet, without payment or invoice claims', function () {
    $sheets = app(OrderSheet::class);
    $sample = $sheets->sample();
    $pdf = $sheets->pdf($sample);
    $html = view('pdf.order-sheet', ['sheet' => $sample, 'reference' => 'x'])->render();

    // Only the random file identifier differs between two renders.
    $stable = fn (string $bytes) => preg_replace('/\/ID\s*\[[^\]]*\]/', '', $bytes);
    expect($stable($sheets->pdf($sample)))->toBe($stable($pdf))
        ->and(substr($pdf, 0, 5))->toBe('%PDF-')
        ->and($pdf)->toContain('DejaVuSans')
        ->and(strlen($pdf))->toBeLessThan(400_000)
        ->and($html)->toContain('PHIẾU ĐƠN HÀNG', 'Nguyễn Thị Mẫu', 'Chờ xác nhận', 'chưa gồm phí vận chuyển', 'không thay hóa đơn giá trị gia tăng')
        ->and($html)->not->toContain('Đã thanh toán');
});

it('escapes what a buyer typed and fetches nothing from outside while rendering', function () {
    $sheets = app(OrderSheet::class);
    $sample = $sheets->sample();
    $sample['note'] = '<img src="http://127.0.0.1:9/x.png"><script>alert(1)</script>';
    $html = view('pdf.order-sheet', ['sheet' => $sample, 'reference' => 'x'])->render();

    expect($html)->not->toContain('<script>')->not->toContain('<img src="http')
        ->and(substr($sheets->pdf($sample), 0, 5))->toBe('%PDF-');
});

it('sends the same confirmation as before while every switch is off', function () {
    $order = placeSheetOrder();
    $mail = queuedMail($order->id);

    expect($mail)->not->toBeNull()
        ->and($mail->sheet)->toBe([])
        ->and($mail->attachments())->toBe([])
        ->and($mail->render())->not->toContain('đính kèm');

    $lifecycle = app(OrderLifecycle::class);
    $lifecycle->update($order, 'confirmed', null);
    app(ManualPayment::class)->confirm($order->refresh(), sheetStaff(), 'PHIEU-THU-1', (int) $order->total);
    $lifecycle->update($order->refresh(), 'shipping', null);

    expect(customerKeys($order->id))->toBe(['order:'.$order->id.':customer']);
});

it('attaches the sheet to the buyer only, and the queued message still carries it', function () {
    mailSwitches(['sheet']);
    $order = placeSheetOrder();
    $mail = queuedMail($order->id);
    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe('phieu-don-hang-'.$order->code.'.pdf')
        ->and($attachments[0]->mime)->toBe('application/pdf')
        ->and($mail->render())->toContain('Phiếu đơn hàng (PDF) được đính kèm')
        ->and(queuedMail($order->id, 'admin')->attachments())->toBe([]);
});

it('rebuilds a message queued before sheets existed', function () {
    $old = new OrderNotification(['subject' => 'Cũ', 'heading' => 'Cũ', 'intro' => 'Cũ', 'closing' => '', 'audience' => 'customer'],
        ['Mã đơn hàng' => 'CU1'], [], ['subtotal' => 0.0, 'shipping' => 0.0, 'total' => 0.0]);
    $serialized = serialize($old);
    expect(preg_match('/^O:\d+:"[^"]+":(\d+):/', $serialized, $m))->toBe(1);
    // Drop the two new properties, as in a payload written by the previous release.
    $stripped = str_replace(['s:5:"sheet";a:0:{}', "s:38:\"\0App\\Mail\\OrderNotification\0sheetBytes\";N;"], '', $serialized, $removed);
    expect($removed)->toBe(2);
    $stripped = preg_replace('/^(O:\d+:"[^"]+":)'.$m[1].':/', '${1}'.($m[1] - 2).':', $stripped);
    $mail = Outbox::mailable(base64_encode($stripped));

    expect($mail)->toBeInstanceOf(OrderNotification::class)
        ->and($mail->attachments())->toBe([])
        ->and($mail->render())->toContain('Đã tiếp nhận');
});

it('mails each step once when its switch is on', function () {
    mailSwitches(['confirmed', 'shipping', 'paid']);
    $order = placeSheetOrder();
    $lifecycle = app(OrderLifecycle::class);

    $lifecycle->update($order, 'confirmed', null);
    $lifecycle->update($order->refresh(), 'confirmed', 'Ghi chú thêm');
    app(ManualPayment::class)->confirm($order->refresh(), sheetStaff(), 'PHIEU-THU-1', (int) $order->total);
    $lifecycle->update($order->refresh(), 'shipping', null);
    $lifecycle->update($order->refresh(), 'done', null);
    app(CustomerOrderMail::class)->queue($order->id, 'confirmed');

    $base = 'order:'.$order->id.':customer';
    expect(customerKeys($order->id))->toBe([$base, $base.':confirmed', $base.':paid', $base.':shipping']);
    $shipping = queuedMail($order->id, 'customer:shipping');
    expect($shipping->attachments())->toBe([])
        ->and($shipping->render())->toContain('đang được giao', $order->code)->not->toContain('đính kèm')
        ->and(queuedMail($order->id, 'customer:paid')->render())->toContain('không thay hóa đơn')->not->toContain('Giao hàng');
});

it('stays silent for a test waybill and for an address that cannot receive mail', function () {
    mailSwitches(['confirmed', 'shipping']);
    $order = placeSheetOrder();
    app(OrderLifecycle::class)->update($order, 'confirmed', null);
    DB::table('shipments')->insert(['order_id' => $order->id, 'provider' => 'nhattin', 'environment' => 'mock', 'ref_code' => $order->code.'-1', 'status' => 'in_transit', 'weight_kg' => 1, 'cod_amount' => 0, 'service_id' => '91', 'payer_id' => '10', 'created_at' => now(), 'updated_at' => now()]);
    app(OrderLifecycle::class)->update($order->refresh(), 'shipping', null);

    expect(customerKeys($order->id))->not->toContain('order:'.$order->id.':customer:shipping');

    DB::table('orders')->where('id', $order->id)->update(['email' => 'khong-phai-email']);
    expect(app(CustomerOrderMail::class)->queue($order->id, 'confirmed'))->toBeFalse();
});

it('still changes the order when its mail cannot be prepared', function () {
    mailSwitches(['confirmed']);
    $order = placeSheetOrder();
    DB::connection()->beforeExecuting(function (string $query) {
        if (str_starts_with($query, 'insert ignore into `outbox_messages`')) {
            throw new RuntimeException('simulated');
        }
    });
    app(OrderLifecycle::class)->update($order, 'confirmed', null);

    expect($order->refresh()->status)->toBe('confirmed');
});

it('serves the sheet to staff who handle orders, uncached', function () {
    $order = placeSheetOrder();
    $url = '/admin/orders/'.$order->id.'/phieu.pdf';

    $this->flushSession();
    $this->get($url)->assertForbidden();
    $this->actingAs(sheetStaff(['catalog']), 'admin')->get($url)->assertForbidden();
    $response = $this->actingAs(sheetStaff(['orders']), 'admin')->get($url.'?tai=1')->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toBe('attachment; filename="phieu-don-hang-'.$order->code.'.pdf"')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store', 'private');

    DB::table('order_documents')->where('order_id', $order->id)->delete();
    $this->get($url)->assertOk();
    expect(DB::table('order_documents')->where('order_id', $order->id)->exists())->toBeFalse();
    $this->get('/admin/orders/'.$order->id.'/edit')->assertOk()->assertSee('Phiếu PDF');
});

it('keeps the switches off until someone saves them, and records who did', function () {
    $this->actingAs(sheetStaff(['settings']), 'admin');
    Livewire\Livewire::test(MailTemplatesPage::class)->assertFormSet(['auto.sheet' => false, 'auto.confirmed' => false])->call('save');
    expect(DB::table('settings')->where('key', 'order_mail')->exists())->toBeFalse();

    Livewire\Livewire::test(MailTemplatesPage::class)->fillForm(['auto.sheet' => true])->call('save')->assertHasNoFormErrors();
    expect(CustomerOrderMail::enabled('sheet'))->toBeTrue()
        ->and(CustomerOrderMail::enabled('shipping'))->toBeFalse()
        ->and(DB::table('admin_audit_events')->where('action', 'mail.automation')->exists())->toBeTrue();

    $this->get('/admin/phieu-don-hang-mau.pdf')->assertOk();
    $this->flushSession();
    $this->actingAs(sheetStaff(['orders']), 'admin')->get('/admin/phieu-don-hang-mau.pdf')->assertForbidden();
});
