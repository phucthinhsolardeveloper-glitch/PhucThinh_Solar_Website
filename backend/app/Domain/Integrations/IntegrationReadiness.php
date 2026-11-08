<?php

namespace App\Domain\Integrations;

use App\Domain\Commerce\Payments;

final class IntegrationReadiness
{
    /** @return list<array{label:string,state:string,detail:string}> */
    public function all(): array
    {
        $vnpay = (array) config('commerce.vnpay', []);
        $vnpayMissing = $this->missing($vnpay, ['tmn_code', 'hash_secret', 'return_url', 'ipn_url']);
        $recaptcha = (array) config('site.recaptcha', []);
        $recaptchaMissing = $this->missing($recaptcha, ['site_key', 'secret']);
        $mailer = (string) config('mail.default', 'log');
        $mailMissing = $this->missing(['from' => config('mail.from.address'), 'admin_to' => config('mail.admin_to')], ['from', 'admin_to']);

        $items = [
            ['label' => 'VNPay', 'state' => ! ($vnpay['active'] ?? false) ? 'Tắt' : ($vnpayMissing !== [] ? 'Thiếu cấu hình'
                : (Payments::vnpayConfigured() ? 'Cần kiểm thử' : 'URL không hợp lệ')),
                'detail' => ! ($vnpay['active'] ?? false) ? 'Thanh toán VNPay đang tắt.' : ($vnpayMissing !== []
                    ? 'Thiếu: '.implode(', ', $vnpayMissing).'.'
                    : (! Payments::vnpayConfigured() ? 'Kiểm tra endpoint, return URL và IPN URL trước khi thử callback.'
                        : (str_contains((string) ($vnpay['endpoint'] ?? ''), 'sandbox') ? 'Đang dùng sandbox.' : 'Kiểm tra endpoint và callback ở staging trước khi mở thật.')))],
            ['label' => 'reCAPTCHA', 'state' => ! ($recaptcha['active'] ?? false) ? 'Tắt' : ($recaptchaMissing === [] ? 'Cần kiểm thử' : 'Thiếu cấu hình'),
                'detail' => ! ($recaptcha['active'] ?? false) ? 'Xác minh biểu mẫu đang tắt.' : ($recaptchaMissing === []
                    ? 'Khóa công khai và khóa bí mật đã có; cần thử biểu mẫu ở staging.' : 'Thiếu: '.implode(', ', $recaptchaMissing).'.')],
            ['label' => 'Email', 'state' => $mailer === 'log' ? 'Ghi log' : ($mailMissing === [] ? 'Cần kiểm thử' : 'Thiếu cấu hình'),
                'detail' => $mailer === 'log' ? 'Email chỉ được ghi log.' : ($mailMissing === []
                    ? 'Đã có địa chỉ gửi và nhận; cần gửi thử vào mailbox staging.' : 'Thiếu: '.implode(', ', $mailMissing).'.')],
        ];

        foreach ((array) config('commerce.carriers', []) as $carrier) {
            $active = (bool) ($carrier['active'] ?? false);
            $missing = $this->missing((array) ($carrier['required'] ?? []), array_keys((array) ($carrier['required'] ?? [])));
            $items[] = [
                'label' => (string) ($carrier['label'] ?? 'Hãng vận chuyển'),
                'state' => ! $active ? 'Tắt' : ($missing === [] ? 'Cần mapping' : 'Thiếu cấu hình'),
                'detail' => ! $active ? 'Chưa bật hãng vận chuyển.' : ($missing === []
                    ? 'Đã có credential; chưa có kho gửi, mã dịch vụ, quy cách kiện hàng và adapter tính phí.'
                    : 'Thiếu: '.implode(', ', $missing).'. Chưa thể gọi API hãng.'),
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $values @param list<string> $keys @return list<string> */
    private function missing(array $values, array $keys): array
    {
        return array_values(array_filter($keys, fn (string $key) => trim((string) ($values[$key] ?? '')) === ''));
    }
}
