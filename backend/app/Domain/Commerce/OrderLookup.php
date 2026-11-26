<?php

namespace App\Domain\Commerce;

use App\Domain\Leads\LeadSubmission;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * "Tra cứu đơn hàng": a buyer finds an order by its code and the phone number it was placed with. Both must match;
 * a wrong pair gets the same answer as an unknown code. The answer holds no address, e-mail or phone. Shipping
 * steps come only from a real carrier waybill, never from a simulated one.
 */
final class OrderLookup
{
    /** Wrong pairs one visitor may try per hour before being asked to wait. */
    private const MISSES_PER_HOUR = 10;

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(mixed $body, string $ip): array
    {
        if (! is_array($body) || ! FormToken::valid($body['csrfToken'] ?? null)) {
            return [419, ['error' => ['code' => 'INVALID_SESSION', 'message' => 'Phiên tra cứu đã hết hạn. Vui lòng tải lại trang.']]];
        }
        $key = 'order-lookup|'.$ip;
        if (RateLimiter::tooManyAttempts($key, self::MISSES_PER_HOUR)) {
            return [429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'Bạn đã tra cứu sai nhiều lần. Vui lòng thử lại sau ít phút hoặc gọi hotline.']]];
        }
        $code = strtoupper(trim(is_scalar($body['code'] ?? null) ? (string) $body['code'] : ''));
        $phone = LeadSubmission::normalizePhone(is_scalar($body['phone'] ?? null) ? (string) $body['phone'] : '');
        if (! preg_match('/^[A-Z0-9]{4,20}$/', $code) || $phone === null) {
            return [422, ['error' => ['code' => 'VALIDATION_FAILED', 'message' => 'Nhập mã đơn hàng và số điện thoại đã dùng khi đặt hàng.']]];
        }
        $order = DB::table('orders')->where('code', $code)->where('phone', $phone)->first();
        if ($order === null) {
            RateLimiter::hit($key, 3600);

            return [404, ['error' => ['code' => 'ORDER_NOT_FOUND', 'message' => 'Không tìm thấy đơn hàng với mã và số điện thoại này.']]];
        }

        return [200, ['schemaVersion' => 1, 'order' => $this->present($order)]];
    }

    /** @return array<string, mixed> */
    private function present(object $order): array
    {
        $items = DB::table('order_items')->where('order_id', $order->id)->orderBy('id')->limit(50)->get()->map(function ($line) {
            $price = (int) round((float) ($line->sale_price ?: $line->regular_price));

            return ['name' => trim(strip_tags((string) $line->name)), 'option' => trim($line->color.' '.$line->size),
                'quantity' => max(1, (int) $line->quantity), 'price' => $price, 'total' => $price * max(1, (int) $line->quantity)];
        })->values()->all();
        $method = $order->payment_method_id ? DB::table('payment_methods')->where('id', $order->payment_method_id)->value('name') : null;
        $instruction = $order->payment_code === 'manual' && $order->payment_status === 'unpaid' && $order->status !== 'cancelled'
            ? app(BankTransfer::class)->active((int) $order->id) : null;

        return [
            'code' => (string) $order->code,
            'placedAt' => (string) date('c', strtotime((string) $order->created_at)),
            'status' => (string) $order->status,
            'statusLabel' => OrderResource::STATUSES[$order->status] ?? (string) $order->status,
            'paymentStatus' => (string) $order->payment_status,
            'paymentLabel' => OrderResource::PAYMENT[$order->payment_status] ?? (string) $order->payment_status,
            'paymentMethod' => trim((string) $method),
            'items' => $items,
            'subtotal' => (int) round((float) $order->subtotal),
            'shippingFee' => (int) round((float) $order->shipping_fee),
            'total' => (int) round((float) $order->total),
            'shipment' => $this->shipment((int) $order->id),
        ] + ($instruction ? ['transfer' => Shop::transfer($instruction, $order->transfer_reported_at !== null)] : []);
    }

    /** @return array{carrier: string, billCode: string, status: string, statusLabel: string, events: list<array{at: string, label: string}>}|null */
    private function shipment(int $orderId): ?array
    {
        $shipment = DB::table('shipments')->where('order_id', $orderId)->where('environment', 'production')->whereNotNull('bill_code')
            ->whereNotIn('status', ['cancelled'])->orderByDesc('id')->first();
        if ($shipment === null) {
            return null;
        }
        $events = DB::table('shipment_events')->where('shipment_id', $shipment->id)->orderByDesc('happened_at')->orderByDesc('id')->limit(20)->get()
            ->map(fn ($e) => ['at' => $e->happened_at ? (string) date('c', strtotime((string) $e->happened_at)) : '', 'label' => trim((string) ($e->status_name ?: (NtlStatus::LABELS[NtlStatus::normalise($e->status_raw)] ?? '')))])
            ->filter(fn ($e) => $e['label'] !== '')->values()->all();

        return ['carrier' => 'Nhất Tín', 'billCode' => (string) $shipment->bill_code, 'status' => (string) $shipment->status,
            'statusLabel' => NtlStatus::LABELS[$shipment->status] ?? (string) $shipment->status, 'events' => $events];
    }
}
