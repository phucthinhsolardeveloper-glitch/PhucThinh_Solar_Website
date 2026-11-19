<?php

use App\Mail\AdminNotification;
use App\Mail\MailActions;
use App\Mail\MailTemplates;
use App\Mail\OrderNotification;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;

function sampleOrderMail(string $key, array $actions = [], array $imageItem = []): OrderNotification
{
    $copy = app(MailTemplates::class)->copy($key, ['order_code' => 'T-1', 'customer_name' => 'An', 'customer_phone' => '0912345678']);

    return new OrderNotification($copy, ['Mã đơn hàng' => 'T-1', 'Họ tên' => 'An', 'Điện thoại' => '0912345678', 'Email' => 'an@example.com', 'Địa chỉ' => '15 Nguyễn Huệ, Gia Lai', 'Thanh toán' => 'COD'],
        [['name' => 'Đèn thử', 'option' => '', 'quantity' => 2, 'price' => 100000.0, 'total' => 200000.0] + $imageItem], ['subtotal' => 200000.0, 'shipping' => 0.0, 'total' => 200000.0], actions: $actions);
}

it('shows the order summary, a status chip, product photos and buttons in the receipt to the customer', function () {
    DB::table('settings')->updateOrInsert(['key' => 'zalo'], ['value' => json_encode('0912 345 678')]);
    app(SiteSettings::class)->clear();
    $html = sampleOrderMail('order_customer', app(MailActions::class)->customer(), ['image' => 'https://example.test/upload/product/a.jpg'])->render();

    expect($html)->toContain('Đã tiếp nhận đơn hàng')->toContain('Mã đơn hàng')->toContain('T-1')->toContain('200.000đ')
        ->toContain('src="https://example.test/upload/product/a.jpg"')->toContain('https://zalo.me/0912345678')->toContain('href="tel:')
        ->toContain('Chờ xác nhận')->toContain('Người nhận')->toContain('Giao đến')->toContain('Xác nhận đơn');
});

it('gives staff a status chip and a button into Admin, without the customer call buttons', function () {
    $actions = app(MailActions::class)->staff('/admin/orders/7/edit', '0912 345 678');
    $html = sampleOrderMail('order_admin', $actions)->render();

    expect($html)->toContain('Đơn hàng mới')->toContain('Mở trong Admin')->toContain('/admin/orders/7/edit')->toContain('tel:0912345678')->not->toContain('zalo.me');
});

it('lays a lead notice out as detail rows with a call link, and still renders its plain-text twin', function () {
    $copy = app(MailTemplates::class)->copy('contact_admin', ['customer_name' => 'An', 'contact_subject' => 'Hỏi giá']);
    $mail = new AdminNotification($copy, ['Họ tên' => 'An', 'Điện thoại' => '0912 345 678', 'Nội dung' => "Dòng một\nDòng hai"], actions: app(MailActions::class)->staff('/admin/leads', '0912 345 678'));
    $html = $mail->render();

    expect($html)->toContain('Liên hệ mới')->toContain('href="tel:0912345678"')->toContain('Dòng một')->toContain('Mở trong Admin');
    $this->artisan('pts:mail-preview', ['dir' => sys_get_temp_dir().'/pts-mail-preview-'.bin2hex(random_bytes(3))])->assertSuccessful();
});
