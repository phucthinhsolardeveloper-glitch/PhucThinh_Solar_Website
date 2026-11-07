<?php

namespace App\Domain\Commerce;

use Illuminate\Support\Facades\DB;

/**
 * Delivery fee quote (commerceShippingQuote): the ward's fee from the Admin table; carriers are listed
 * with their readiness until their adapters are mapped. A zero fee means "to be confirmed".
 */
class Shipping
{
    /** @return array<string, mixed> */
    public function quote(int $cityId, int $wardId, float $subtotal = 0): array
    {
        $carriers = $this->carriers();
        if ($cityId <= 0 || $wardId <= 0) {
            return ['ok' => false, 'error' => 'Vui lòng chọn tỉnh/thành phố và phường/xã.', 'quotes' => [], 'providers' => $carriers];
        }
        $place = (array) (DB::selectOne('select p.id as city_id, p.name as city_name, w.id as ward_id, w.name as ward_name, w.code as ward_code, w.shipping_fee as ship_price
            from wards w inner join provinces p on p.id = w.province_id where w.id = ? and w.province_id = ? and w.is_visible = 1 and p.is_visible = 1 limit 0,1', [$wardId, $cityId]) ?? []);
        if (! $place) {
            return ['ok' => false, 'error' => 'Phường/xã không thuộc tỉnh/thành phố đã chọn.', 'quotes' => [], 'providers' => $carriers];
        }
        $amount = max(0, (float) $place['ship_price']);
        $local = ['ok' => true, 'priced' => $amount > 0, 'provider' => 'local', 'label' => 'Phí theo bảng khu vực', 'amount' => $amount, 'currency' => 'VND',
            'quote_ref' => 'local-'.$place['city_id'].'-'.$place['ward_id'], 'place' => $place, 'source' => 'ward.ship_price'];
        $quotes = [$local];
        foreach ($carriers as $carrier) {
            $quotes[] = ['ok' => false, 'provider' => $carrier['code'], 'label' => $carrier['label'],
                'status' => $carrier['configured'] ? 'mapping_required' : ($carrier['active'] ? 'missing_credentials' : 'disabled'), 'amount' => null, 'currency' => 'VND',
                'message' => $carrier['configured'] ? 'Đã có credential, cần khai báo kho gửi/mã dịch vụ/quy cách kiện hàng.' : 'Chưa sẵn sàng gọi API.'];
        }

        return ['ok' => true, 'subtotal' => $subtotal, 'quotes' => $quotes, 'selected' => $local, 'priced' => $amount > 0, 'providers' => $carriers];
    }

    /** @return array<string, array<string, mixed>> readiness only; credentials never leave the server */
    private function carriers(): array
    {
        $out = [];
        foreach ((array) config('commerce.carriers') as $code => $carrier) {
            $missing = array_keys(array_filter($carrier['required'], fn ($value) => trim((string) $value) === ''));
            $out[$code] = ['code' => $code, 'label' => $carrier['label'], 'active' => (bool) $carrier['active'], 'configured' => $carrier['active'] && ! $missing,
                'missing' => $missing, 'capabilities' => ['quote', 'create', 'tracking', 'webhook'], 'adapter' => $code];
        }

        return $out;
    }
}
