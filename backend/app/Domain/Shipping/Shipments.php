<?php

namespace App\Domain\Shipping;

use App\Domain\Audit\Audit;
use App\Domain\Commerce\OrderLifecycle;
use App\Domain\Shipping\NhatTin\NtlClient;
use App\Domain\Shipping\NhatTin\NtlException;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Waybills of an order with Nhất Tín: quote, create (once), follow, cancel. Delivery and payment are
 * separate: nothing here changes what the customer has paid.
 */
final class Shipments
{
    public const ACTIVE = ['create_pending', 'draft', 'waiting_pickup', 'in_transit', 'out_for_delivery', 'failed', 'returning', 'unknown'];

    /** Order statuses a first waybill may be made for. */
    public const SHIPPABLE = ['new', 'confirmed'];

    /**
     * Why no waybill can be made for this order now, or null when one can. The single rule for every way in
     * (row button, order page, bulk, service): checked again under the order lock in create().
     *
     * - "Mới" / "Đã xác nhận" with no waybill running: allowed.
     * - "Đang giao" whose every waybill has ended without delivery (cancelled or returned): allowed, to send again.
     * - "Hoàn tất" / "Đã hủy", a delivered waybill, or a waybill still running: refused, with what to do instead.
     */
    public static function blocker(Order $order): ?string
    {
        $shipments = $order->shipments()->get(['id', 'status', 'bill_code', 'ref_code']);
        $running = $shipments->first(fn (Shipment $s) => in_array($s->status, self::ACTIVE, true));
        if ($running !== null) {
            $code = $running->bill_code ?? $running->ref_code;

            return match ($running->status) {
                'unknown', 'create_pending' => 'Vận đơn '.$code.' chưa rõ đã tạo bên Nhất Tín hay chưa. Dùng "Đối chiếu vận đơn chưa rõ" trước khi tạo lại.',
                'failed' => 'Vận đơn '.$code.' đang có sự cố. Liên hệ Nhất Tín hoặc hủy vận đơn đó rồi mới tạo vận đơn mới.',
                'returning' => 'Vận đơn '.$code.' đang hoàn về kho. Chờ hàng về (Đã hoàn) rồi mới gửi lại.',
                default => 'Đơn đã có vận đơn đang hoạt động ('.$code.'). Hủy vận đơn đó trước khi tạo vận đơn mới.',
            };
        }

        return match (true) {
            $order->status === 'cancelled' => 'Đơn đã hủy: không tạo vận đơn.',
            $order->status === 'done' => 'Đơn đã hoàn tất: không tạo vận đơn.',
            $shipments->contains(fn (Shipment $s) => $s->status === 'delivered') => 'Đơn đã có vận đơn giao thành công: không tạo thêm.',
            $order->status === 'shipping' && $shipments->isEmpty() => 'Đơn đang giao nhưng chưa có vận đơn trên hệ thống: kiểm tra lại trước khi tạo.',
            in_array($order->status, [...self::SHIPPABLE, 'shipping'], true) => null,
            default => 'Trạng thái đơn không cho tạo vận đơn.',
        };
    }

    /** The waybill to follow: the running one, otherwise the newest with a bill code. Never an older one when a newer exists. */
    public static function current(Order $order): ?Shipment
    {
        return $order->shipments()->whereIn('status', self::ACTIVE)->first()
            ?? $order->shipments()->whereNotNull('bill_code')->first();
    }

    public function __construct(private readonly NtlClient $client, private readonly SiteSettings $settings) {}

    /**
     * @param  array{weight_kg: float|string, length_cm?: int|string, width_cm?: int|string, height_cm?: int|string, payer_id?: string}  $parcel
     * @return list<array{service_id: string, service: string, fee: int, lead_time: string}>
     */
    public function quote(Order $order, array $parcel): array
    {
        [$sender, $receiver] = $this->places($order);
        $data = $this->client->call('POST', '/v3/bill/calc-fee', array_filter([
            'partner_id' => config('shipping.nhattin.partner_id') ?: null, 'weight' => (float) $parcel['weight_kg'],
            'length' => (int) ($parcel['length_cm'] ?? 0), 'width' => (int) ($parcel['width_cm'] ?? 0), 'height' => (int) ($parcel['height_cm'] ?? 0),
            'payment_method_id' => (int) ($parcel['payer_id'] ?? 10), 'cod_amount' => $this->codFor($order), 'cargo_value' => (int) round((float) $order->subtotal),
            's_province_id' => $sender['province'], 's_ward_id' => $sender['ward'], 'r_province_id' => $receiver['province'], 'r_ward_id' => $receiver['ward'],
        ], fn ($value) => $value !== null));
        $services = (array) config('shipping.nhattin.services');

        return array_values(array_map(fn ($row) => ['service_id' => (string) ($row['service_id'] ?? ''), 'service' => (string) ($row['service_name'] ?? $services[(string) ($row['service_id'] ?? '')] ?? 'Mã '.($row['service_id'] ?? '?')),
            'fee' => (int) round((float) ($row['total_fee'] ?? 0)), 'lead_time' => (string) ($row['lead_time'] ?? '')], array_filter((array) ($data['data'] ?? []), 'is_array')));
    }

    /** @param array<string, mixed> $input weight_kg, sizes, service_id, payer_id, cod_amount, note */
    public function create(Order $order, array $input, User $actor): Shipment
    {
        abort_unless($actor->hasPermission('orders'), 403);
        if (app()->environment('production') && $this->client->mode() === 'mock') {
            throw ValidationException::withMessages(['shipment' => 'Nhất Tín đang ở chế độ mô phỏng (NTL_MODE=mock): không tạo vận đơn giả cho đơn thật.']);
        }
        [$sender, $receiver] = $this->places($order, codes: true);
        $shipment = DB::transaction(function () use ($order, $input, $actor) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (($reason = self::blocker($locked)) !== null) {
                throw ValidationException::withMessages(['shipment' => $reason]);
            }
            $attempt = $locked->shipments()->count() + 1;

            return $locked->shipments()->create([
                'provider' => 'nhattin', 'environment' => $this->client->mode(), 'ref_code' => $locked->code.'-'.$attempt, 'status' => 'create_pending',
                'service_id' => (string) $input['service_id'], 'payer_id' => (string) ($input['payer_id'] ?? '10'),
                'weight_kg' => (float) $input['weight_kg'], 'length_cm' => (int) ($input['length_cm'] ?? 0), 'width_cm' => (int) ($input['width_cm'] ?? 0),
                'height_cm' => (int) ($input['height_cm'] ?? 0), 'cod_amount' => max(0, (int) ($input['cod_amount'] ?? 0)),
                'cargo_value' => (int) round((float) $locked->subtotal), 'note' => $input['note'] ?? null, 'created_by' => $actor->id,
            ]);
        });
        $request = [
            'ref_code' => $shipment->ref_code, 'package_no' => 1, 'weight' => (float) $shipment->weight_kg, 'length' => (int) $shipment->length_cm,
            'width' => (int) $shipment->width_cm, 'height' => (int) $shipment->height_cm, 'cargo_content' => mb_substr($this->content($order), 0, 250),
            'cargo_value' => (int) $shipment->cargo_value, 'cargo_type_id' => 2, 'service_id' => (int) $shipment->service_id,
            'payment_method_id' => (int) $shipment->payer_id, 'cod_amount' => (int) $shipment->cod_amount, 'note' => (string) $shipment->note,
            's_name' => $sender['name'], 's_phone' => $sender['phone'], 's_address' => $sender['address'], 's_province_code' => $sender['province'], 's_ward_code' => $sender['ward'],
            'r_name' => (string) $order->customer_name, 'r_phone' => (string) $order->phone, 'r_address' => (string) $order->address,
            'r_province_code' => $receiver['province'], 'r_ward_code' => $receiver['ward'], 'is_draft' => 1,
        ];
        $shipment->update(['request' => $request]);
        try {
            $data = (array) ($this->client->call('POST', '/v3/bill/create', $request)['data'] ?? []);
        } catch (NtlException $error) {
            // Unknown outcome: never retried automatically, so one order cannot end up with two waybills.
            $shipment->update(['status' => $error->unknown ? 'unknown' : 'cancelled', 'error' => mb_substr($error->getMessage(), 0, 255)]);
            Audit::record('shipment.create_failed', 'Order', $order->id, null, ['ref_code' => $shipment->ref_code, 'error' => $error->getMessage()]);
            throw ValidationException::withMessages(['shipment' => $error->getMessage().($error->unknown ? ' Kiểm tra trên cổng Nhất Tín với mã '.$shipment->ref_code.' trước khi tạo lại.' : '')]);
        }
        $shipment->update(['bill_code' => $data['bill_code'] ?? null, 'status_raw' => (string) ($data['status_id'] ?? ''),
            'status' => NtlStatus::normalise($data['status_id'] ?? null), 'fee' => isset($data['total_fee']) ? (int) round((float) $data['total_fee']) : null,
            'response' => $data, 'error' => null]);
        Audit::record('shipment.create', 'Order', $order->id, null, ['bill_code' => $shipment->bill_code, 'environment' => $shipment->environment]);
        // Sending a parcel means the order was accepted.
        $fresh = $order->fresh();
        if ($fresh && $fresh->status === 'new') {
            app(OrderLifecycle::class)->update($fresh, 'confirmed', $fresh->notes);
        }

        return $shipment->refresh();
    }

    public function refresh(Shipment $shipment): Shipment
    {
        if ($shipment->bill_code === null) {
            return $shipment;
        }
        $data = (array) ($this->client->call('GET', '/v3/bill/tracking', ['bill_code' => $shipment->bill_code])['data'][0] ?? []);
        foreach ((array) ($data['histories'] ?? []) as $history) {
            $this->record($shipment, 'tracking', (string) ($history['status_id'] ?? ''), (string) ($history['status_name'] ?? ''), (int) ($history['time'] ?? 0), $history);
        }
        $this->move($shipment, (string) ($data['bill_status_id'] ?? ''));

        return $shipment->refresh();
    }

    public function cancel(Shipment $shipment, User $actor): void
    {
        abort_unless($actor->hasPermission('orders'), 403);
        if (! in_array($shipment->status, ['draft', 'waiting_pickup', 'failed'], true) || $shipment->bill_code === null) {
            throw ValidationException::withMessages(['shipment' => 'Chỉ hủy được vận đơn nháp hoặc đang chờ lấy hàng.']);
        }
        $this->client->call('POST', '/v3/bill/destroy', ['bill_code' => [$shipment->bill_code]]);
        $shipment->update(['status' => 'cancelled', 'status_raw' => '6']);
        Audit::record('shipment.cancel', 'Order', $shipment->order_id, null, ['bill_code' => $shipment->bill_code]);
    }

    /**
     * Settles a create whose outcome is not known (timeout, or a request left half-way). Staff look the
     * ref code up on the Nhất Tín portal: with the bill code found there the waybill is attached and
     * followed as usual; without one it is closed so the order can be sent again.
     */
    public function reconcile(Shipment $shipment, ?string $billCode, User $actor): Shipment
    {
        abort_unless($actor->hasPermission('orders'), 403);
        $billCode = trim((string) $billCode);
        // Decided on the locked row, from the database and not from the model the screen loaded: of two people
        // settling the same waybill only the first changes it; the second finds it settled and changes nothing.
        $attached = DB::transaction(function () use ($shipment, $billCode): bool {
            $locked = Shipment::query()->whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $stuck = $locked->bill_code === null && ($locked->status === 'unknown'
                || ($locked->status === 'create_pending' && $locked->updated_at?->lt(now()->subMinutes(2))));
            if (! $stuck) {
                throw ValidationException::withMessages(['shipment' => 'Vận đơn này đã được đối chiếu hoặc không ở trạng thái chưa rõ kết quả.']);
            }
            if ($billCode === '') {
                $locked->update(['status' => 'cancelled', 'error' => 'Đối chiếu: không có trên Nhất Tín']);
                Audit::record('shipment.reconcile', 'Order', $locked->order_id, null, ['ref_code' => $locked->ref_code, 'found' => false]);

                return false;
            }
            if (! preg_match('/^[A-Za-z0-9-]{4,40}$/', $billCode)
                || Shipment::query()->where('bill_code', $billCode)->where('provider', $locked->provider)->where('environment', $locked->environment)->exists()) {
                throw ValidationException::withMessages(['shipment' => 'Mã vận đơn không hợp lệ hoặc đã gắn với đơn khác.']);
            }
            try {
                $locked->update(['bill_code' => $billCode, 'status' => 'draft', 'error' => null]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['shipment' => 'Mã vận đơn không hợp lệ hoặc đã gắn với đơn khác.']);
            }
            Audit::record('shipment.reconcile', 'Order', $locked->order_id, null, ['ref_code' => $locked->ref_code, 'bill_code' => $billCode, 'found' => true]);

            return true;
        });
        $shipment->refresh();
        if (! $attached) {
            return $shipment;
        }
        try {
            $this->refresh($shipment);
        } catch (NtlException) {
            // Attached anyway; the journey is fetched again with "Cập nhật hành trình".
        }

        return $shipment->refresh();
    }

    /** A status pushed by Nhất Tín. Repeats are ignored; a finished shipment is not moved back. */
    public function webhook(array $payload): bool
    {
        $billCode = (string) ($payload['bill_no'] ?? $payload['bill_code'] ?? '');
        $shipment = $billCode !== '' ? Shipment::query()->where('bill_code', $billCode)->where('environment', $this->client->mode())->first() : null;
        if ($shipment === null) {
            return false;
        }
        $fresh = $this->record($shipment, 'webhook', (string) ($payload['status_id'] ?? ''), (string) ($payload['status_name'] ?? ''), (int) ($payload['status_time'] ?? 0), $payload);
        if ($fresh) {
            $this->move($shipment, (string) ($payload['status_id'] ?? ''));
            if (isset($payload['shipping_fee']) && is_numeric($payload['shipping_fee'])) {
                $shipment->update(['fee' => (int) round((float) $payload['shipping_fee'])]);
            }
        }

        return true;
    }

    /** @return bool false when the same event was already stored */
    private function record(Shipment $shipment, string $source, string $raw, string $name, int $time, array $payload): bool
    {
        $fingerprint = hash('sha256', $shipment->id.'|'.$raw.'|'.$time.'|'.$name);

        return DB::table('shipment_events')->insertOrIgnore(['shipment_id' => $shipment->id, 'source' => $source, 'status_raw' => $raw, 'status_name' => mb_substr($name, 0, 255),
            'happened_at' => $time > 0 ? date('Y-m-d H:i:s', $time) : now(), 'fingerprint' => $fingerprint,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'created_at' => now()]) > 0;
    }

    private function move(Shipment $shipment, string $raw): void
    {
        $status = NtlStatus::normalise($raw);
        if ($raw === '' || in_array($shipment->status, NtlStatus::TERMINAL, true)) {
            return;
        }
        $shipment->update(['status' => $status, 'status_raw' => $raw]);
        app(ShipmentAutomation::class)->follow($shipment);
    }

    /** Cash-on-delivery orders not yet paid have Nhất Tín collect the order total; other orders collect nothing. */
    public function codFor(Order $order): int
    {
        $kind = (string) DB::table('payment_methods')->where('id', $order->payment_method_id)->value('kind');

        return $kind === 'cod' && $order->payment_status === 'unpaid' ? (int) round((float) $order->total) : 0;
    }

    /** @return array{0: array<string, string>, 1: array<string, string>} sender and receiver place codes */
    private function places(Order $order, bool $codes = false): array
    {
        if ($this->client->mode() !== 'mock' && ! config('shipping.nhattin.locations_verified')) {
            throw ValidationException::withMessages(['shipment' => 'Chưa đối chiếu mã tỉnh/phường với danh mục của Nhất Tín (MAPPING_REQUIRED).']);
        }
        $senderWard = (int) $this->settings->get('shipping_sender_ward_id', 0);
        $sender = $this->place($senderWard);
        $receiver = $this->place((int) $order->ward_id);
        if ($sender === null) {
            throw ValidationException::withMessages(['shipment' => 'Chưa khai kho gửi hàng (Thông tin website → Kho gửi hàng).']);
        }
        if ($receiver === null) {
            throw ValidationException::withMessages(['shipment' => 'Phường/xã của khách chưa có mã hành chính.']);
        }

        return [$sender + ['name' => (string) $this->settings->get('shipping_sender_name', $this->settings->siteName()),
            'phone' => (string) $this->settings->get('shipping_sender_phone', $this->settings->get('hotline', '')),
            'address' => (string) $this->settings->get('shipping_sender_address', $this->settings->get('address', ''))], $receiver];
    }

    /** @return array{province: string, ward: string}|null */
    private function place(int $wardId): ?array
    {
        $row = $wardId > 0 ? DB::table('wards as w')->join('provinces as p', 'p.id', '=', 'w.province_id')->where('w.id', $wardId)->first(['p.code as province', 'w.tax_code as ward']) : null;

        return $row && $row->province && $row->ward ? ['province' => (string) $row->province, 'ward' => (string) $row->ward] : null;
    }

    private function content(Order $order): string
    {
        return $order->items()->get(['name', 'quantity'])->map(fn ($item) => $item->getAttribute('quantity').' x '.$item->getAttribute('name'))->implode('; ');
    }
}
