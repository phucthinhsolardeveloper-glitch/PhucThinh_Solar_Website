<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/** Sample data is deliberately synthetic and never touches customer orders or leads. */
class MailTemplateStudio
{
    private const VALUES = [
        'customer_name' => 'Khách thử nghiệm', 'customer_phone' => '0900000000',
        'company' => 'Công ty thử nghiệm', 'contact_subject' => 'Yêu cầu thử nghiệm',
        'order_code' => 'TEST-000',
    ];

    public function __construct(private readonly MailTemplates $templates) {}

    public function mailable(string $key, array $draft): Mailable
    {
        $copy = $this->templates->copy($key, self::VALUES, $draft);
        $fields = ['Họ tên' => self::VALUES['customer_name'], 'Điện thoại' => self::VALUES['customer_phone'], 'Nội dung' => 'Đây là dữ liệu thử của mẫu email.'];
        if (str_starts_with($key, 'order_')) {
            return new OrderNotification($copy, $fields,
                [['name' => 'Sản phẩm thử nghiệm', 'option' => '', 'quantity' => 1, 'price' => 100000, 'total' => 100000]],
                ['subtotal' => 100000, 'shipping' => 0, 'total' => 100000]);
        }

        return new AdminNotification($copy, $fields);
    }

    public function html(string $key, array $draft): string
    {
        return $this->mailable($key, $draft)->render();
    }
}
