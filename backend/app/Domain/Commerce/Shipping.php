<?php

namespace App\Domain\Commerce;

use App\Domain\Shipping\CheckoutFee;
use Illuminate\Support\Facades\DB;

/**
 * Delivery fee shown at checkout: the ward's fee from the Admin table. A zero fee means "to be
 * confirmed by staff". Nhất Tín quotes are made by staff on the order, never shown to buyers as a price.
 */
class Shipping
{
    /** @return array<string, mixed> */
    /** @param array{kg: float, known: bool}|null $parcel */
    public function quote(int $cityId, int $wardId, float $subtotal = 0, ?array $parcel = null): array
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
        $ntl = $parcel !== null ? app(CheckoutFee::class)->quote($wardId, $parcel, $subtotal) : null;
        if ($ntl !== null && $ntl['priced']) {
            $ntl += ['place' => $place, 'currency' => 'VND'];

            return ['ok' => true, 'subtotal' => $subtotal, 'quotes' => [$ntl, $local], 'selected' => $ntl, 'priced' => true];
        }
        // Nhất Tín could not price it (bulky, weight missing, codes unchecked): the area table, or staff confirm.
        if ($ntl !== null && $amount <= 0) {
            $local['note'] = $ntl['note'];
        }

        return ['ok' => true, 'subtotal' => $subtotal, 'quotes' => [$local], 'selected' => $local, 'priced' => $amount > 0];
    }
}
