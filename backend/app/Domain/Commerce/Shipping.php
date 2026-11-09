<?php

namespace App\Domain\Commerce;

use Illuminate\Support\Facades\DB;

/**
 * Delivery fee shown at checkout: the ward's fee from the Admin table. A zero fee means "to be
 * confirmed by staff". Nhất Tín quotes are made by staff on the order, never shown to buyers as a price.
 */
class Shipping
{
    /** @return array<string, mixed> */
    public function quote(int $cityId, int $wardId, float $subtotal = 0): array
    {
        if ($cityId <= 0 || $wardId <= 0) {
            return ['ok' => false, 'error' => 'Vui lòng chọn tỉnh/thành phố và phường/xã.', 'quotes' => []];
        }
        $place = (array) (DB::selectOne('select p.id as city_id, p.name as city_name, w.id as ward_id, w.name as ward_name, w.code as ward_code, w.shipping_fee as ship_price
            from wards w inner join provinces p on p.id = w.province_id where w.id = ? and w.province_id = ? and w.is_visible = 1 and p.is_visible = 1 limit 0,1', [$wardId, $cityId]) ?? []);
        if (! $place) {
            return ['ok' => false, 'error' => 'Phường/xã không thuộc tỉnh/thành phố đã chọn.', 'quotes' => []];
        }
        $amount = max(0, (float) $place['ship_price']);
        $local = ['ok' => true, 'priced' => $amount > 0, 'provider' => 'local', 'label' => 'Phí theo bảng khu vực', 'amount' => $amount, 'currency' => 'VND',
            'quote_ref' => 'local-'.$place['city_id'].'-'.$place['ward_id'], 'place' => $place, 'source' => 'ward.ship_price'];
        $quotes = [$local];

        return ['ok' => true, 'subtotal' => $subtotal, 'quotes' => $quotes, 'selected' => $local, 'priced' => $amount > 0];
    }
}
