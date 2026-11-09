<?php

namespace App\Domain\Shipping;

use App\Domain\Shipping\NhatTin\NtlClient;
use App\Domain\Shipping\NhatTin\NtlException;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Nhất Tín fee shown at checkout and kept on the order. Worked out on the server from the cart's
 * packed weight; a mock price says so. Returns null when Nhất Tín is not in use at all.
 */
final class CheckoutFee
{
    /** Heavier than this goes to staff for a freight quote. */
    public const MANUAL_OVER_KG = 50;

    /** Service offered at checkout: Tiết kiệm. */
    private const SERVICE = '91';

    public function __construct(private readonly NtlClient $client, private readonly SiteSettings $settings) {}

    /**
     * @param  array{kg: float, known: bool}  $parcel
     * @return array{ok: true, priced: bool, provider: string, label: string, amount: float, note: string, quote_ref: string, source: string}|null
     */
    public function quote(int $wardId, array $parcel, float $subtotal): ?array
    {
        $mode = $this->client->mode();
        // Real customers only ever see a real Nhất Tín price; otherwise the area table applies.
        if (! config('shipping.nhattin.checkout_quotes') || (app()->environment('production') && $mode !== 'production')) {
            return null;
        }
        if (! $this->client->readiness()['ready'] || ($mode !== 'mock' && ! config('shipping.nhattin.locations_verified'))) {
            return null;
        }
        $manual = fn (string $note) => ['ok' => true, 'priced' => false, 'provider' => 'nhattin', 'label' => 'Nhất Tín', 'amount' => 0.0,
            'note' => $note, 'quote_ref' => '', 'source' => 'nhattin.manual'];
        $kg = $parcel['kg'];
        if (! $parcel['known']) {
            if ($mode !== 'mock') {
                return $manual('Nhân viên báo phí giao hàng sau khi xác nhận kiện hàng.');
            }
            $kg = max($kg, 1.0); // mock only: a product without weight counts as 1 kg so the flow can be tried
        }
        if ($kg > self::MANUAL_OVER_KG) {
            return $manual('Hàng cồng kềnh: nhân viên báo giá vận chuyển riêng.');
        }
        $receiver = $this->place($wardId);
        $sender = $this->place((int) $this->settings->get('shipping_sender_ward_id', 0)) ?? ($mode === 'mock' ? $receiver : null);
        if ($receiver === null || $sender === null) {
            return $manual('Nhân viên báo phí giao hàng sau.');
        }
        $key = 'ntl:fee:'.$mode.':'.md5(json_encode([$sender, $receiver, $kg, (int) $subtotal]));
        try {
            $row = Cache::remember($key, 600, function () use ($sender, $receiver, $kg, $subtotal) {
                $data = $this->client->call('POST', '/v3/bill/calc-fee', array_filter(['partner_id' => config('shipping.nhattin.partner_id') ?: null,
                    'weight' => $kg, 'payment_method_id' => 10, 'cargo_value' => (int) round($subtotal), 'service_id' => (int) self::SERVICE,
                    's_province_id' => $sender['province'], 's_ward_id' => $sender['ward'], 'r_province_id' => $receiver['province'], 'r_ward_id' => $receiver['ward']], fn ($v) => $v !== null));
                $rows = array_values(array_filter((array) ($data['data'] ?? []), 'is_array'));
                $pick = collect($rows)->firstWhere('service_id', (int) self::SERVICE) ?? collect($rows)->sortBy('total_fee')->first();

                return $pick ? ['fee' => (int) round((float) $pick['total_fee']), 'lead_time' => (string) ($pick['lead_time'] ?? ''), 'at' => now()->format('H:i d/m')] : null;
            });
        } catch (NtlException) {
            return $manual('Chưa tính được phí Nhất Tín lúc này, nhân viên sẽ báo phí sau.');
        }
        if (! is_array($row) || $row['fee'] <= 0) {
            return $manual('Nhân viên báo phí giao hàng sau.');
        }
        $mock = $mode === 'mock';

        return ['ok' => true, 'priced' => true, 'provider' => 'nhattin', 'label' => 'Nhất Tín · Tiết kiệm'.($mock ? ' (mô phỏng)' : ''),
            'amount' => (float) $row['fee'],
            'note' => $mock ? 'Giá mô phỏng để thử quy trình, không phải báo giá của Nhất Tín.'
                : 'Cước Nhất Tín tính lúc '.$row['at'].($row['lead_time'] !== '' ? ', giao '.$row['lead_time'] : '').'.',
            'quote_ref' => 'ntl-'.$mode.'-'.substr($key, -12), 'source' => 'nhattin.calc-fee'];
    }

    /** @return array{province: string, ward: string}|null */
    private function place(int $wardId): ?array
    {
        $row = $wardId > 0 ? DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')->where('w.id', $wardId)->first(['p.code as province', 'w.tax_code as ward']) : null;

        return $row && $row->province && $row->ward ? ['province' => (string) $row->province, 'ward' => (string) $row->ward] : null;
    }
}
