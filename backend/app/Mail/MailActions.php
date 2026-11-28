<?php

namespace App\Mail;

use App\Support\SiteSettings;

/** The buttons under the text of an email: the first is the main one. Addresses are absolute because mail is read outside the site. */
final class MailActions
{
    public function __construct(private readonly MailBrand $brand, private readonly SiteSettings $settings) {}

    /**
     * What a customer can do next: follow the order (when there is one), call, or message on Zalo.
     *
     * @return list<array{label: string, href: string}>
     */
    public function customer(string $orderCode = ''): array
    {
        $brand = $this->brand->data();
        $actions = [];
        if (preg_match('/^[A-Z0-9]{4,20}$/', $orderCode)) {
            $actions[] = ['label' => 'Tra cứu đơn hàng', 'href' => $this->absolute('tra-cuu-don-hang/?ma='.$orderCode)];
        }
        $hotline = preg_replace('/[^+\d]/', '', $brand['hotline']) ?? '';
        if ($hotline !== '') {
            $actions[] = ['label' => 'Gọi '.$brand['hotline'], 'href' => 'tel:'.$hotline];
        }
        $zalo = preg_replace('/\D/', '', (string) ($this->settings->options()['zalo'] ?? '')) ?? '';
        if (strlen($zalo) >= 9) {
            $actions[] = ['label' => 'Nhắn Zalo', 'href' => 'https://zalo.me/'.$zalo];
        }

        return $actions;
    }

    /**
     * What staff can do with a new order or lead: open it in Admin, call the customer.
     *
     * @return list<array{label: string, href: string}>
     */
    public function staff(string $adminPath, string $phone = ''): array
    {
        $actions = [['label' => 'Mở trong Admin', 'href' => $this->absolute($adminPath)]];
        $digits = preg_replace('/[^+\d]/', '', $phone) ?? '';
        if (strlen($digits) >= 8) {
            $actions[] = ['label' => 'Gọi khách '.trim($phone), 'href' => 'tel:'.$digits];
        }

        return $actions;
    }

    public function absolute(string $path): string
    {
        return rtrim((string) config('site.canonical_origin'), '/').'/'.ltrim($path, '/');
    }
}
