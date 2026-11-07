<?php

namespace App\Domain\Commerce;

use Illuminate\Support\Facades\DB;

/** Payment methods set in Admin, plus VNPay when configured, and VNPay signing. */
class Payments
{
    public const VNPAY_ID = -1;

    /** @return list<array{id: int, namevi: string, descvi: string, slugvi?: string}> */
    public function visible(): array
    {
        $methods = array_map(fn ($row) => (array) $row, DB::select("select name as namevi, coalesce(description,'') as descvi, id from payment_methods where is_visible = 1 order by sort_order,id desc"));
        if (self::vnpayConfigured()) {
            $methods[] = ['namevi' => 'Thanh toán online qua VNPay', 'descvi' => 'Thanh toán qua ứng dụng ngân hàng, thẻ hoặc ví được VNPay hỗ trợ.', 'id' => self::VNPAY_ID, 'slugvi' => 'vnpay'];
        }

        return $methods;
    }

    /** @return array<string, mixed> the chosen method, or [] when it is hidden or unknown */
    public function find(int $id): array
    {
        if ($id === self::VNPAY_ID) {
            return self::vnpayConfigured() ? ['id' => self::VNPAY_ID, 'namevi' => 'Thanh toán online qua VNPay', 'slugvi' => 'vnpay'] : [];
        }

        return $id > 0 ? (array) (DB::selectOne("select id, name as namevi, coalesce(slug,'') as slugvi from payment_methods where id = ? and is_visible = 1 limit 0,1", [$id]) ?? []) : [];
    }

    public static function vnpayConfigured(): bool
    {
        $c = config('commerce.vnpay');

        return ! empty($c['active']) && trim((string) $c['tmn_code']) !== '' && trim((string) $c['hash_secret']) !== ''
            && filter_var($c['endpoint'], FILTER_VALIDATE_URL) && filter_var($c['return_url'], FILTER_VALIDATE_URL) && filter_var($c['ipn_url'], FILTER_VALIDATE_URL);
    }

    /** Signed redirect to the VNPay payment page (query-string encoding, as VNPay signs it). */
    public static function vnpayUrl(string $orderCode, float $amount, string $info, string $ip): ?string
    {
        if (! self::vnpayConfigured()) {
            return null;
        }
        $c = config('commerce.vnpay');
        $params = ['vnp_Version' => '2.1.0', 'vnp_Command' => 'pay', 'vnp_TmnCode' => (string) $c['tmn_code'], 'vnp_Amount' => (string) round($amount * 100), 'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $orderCode, 'vnp_OrderInfo' => trim($info), 'vnp_OrderType' => 'other', 'vnp_Locale' => 'vn', 'vnp_ReturnUrl' => (string) $c['return_url'],
            'vnp_IpAddr' => $ip, 'vnp_CreateDate' => date('YmdHis')];
        $query = self::query($params);

        return rtrim((string) $c['endpoint'], '?').'?'.$query.'&vnp_SecureHash='.hash_hmac('sha512', $query, (string) $c['hash_secret']);
    }

    public static function vnpayVerify(array $input): bool
    {
        $secret = (string) config('commerce.vnpay.hash_secret');
        if ($secret === '' || empty($input['vnp_SecureHash'])) {
            return false;
        }
        $hash = (string) $input['vnp_SecureHash'];
        $params = array_filter($input, fn ($value, $key) => str_starts_with((string) $key, 'vnp_') && ! in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true) && $value !== '', ARRAY_FILTER_USE_BOTH);

        return hash_equals(strtolower($hash), hash_hmac('sha512', self::query($params), $secret));
    }

    private static function query(array $params): string
    {
        ksort($params);

        return implode('&', array_map(fn ($key, $value) => urlencode((string) $key).'='.urlencode((string) $value), array_keys($params), $params));
    }
}
