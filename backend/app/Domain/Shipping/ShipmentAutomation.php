<?php

namespace App\Domain\Shipping;

use App\Domain\Admin\AdminBell;
use App\Domain\Commerce\OrderLifecycle;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * What staff used to do by hand after Nhất Tín moved a parcel:
 * - the order follows its shipment (picked up / on the way -> "Đang giao", delivered -> "Hoàn tất");
 * - a failed or returning parcel rings the bell;
 * - the parcel weight is worked out from the products, so a waybill rarely needs typing.
 * Order changes go through OrderLifecycle (allowed moves only, audit log), never backwards.
 */
final class ShipmentAutomation
{
    private const ON_THE_WAY = ['in_transit', 'out_for_delivery'];

    private const TROUBLE = ['failed', 'returning', 'returned'];

    /** After a shipment status changed (tracking, webhook or create). */
    public function follow(Shipment $shipment): void
    {
        try {
            $order = Order::query()->find($shipment->order_id);
            if (! $order) {
                return;
            }
            $target = match (true) {
                $shipment->status === 'delivered' => 'done',
                in_array($shipment->status, self::ON_THE_WAY, true) => 'shipping',
                default => null,
            };
            if ($target === 'shipping' && $order->status === 'confirmed') {
                app(OrderLifecycle::class)->update($order, 'shipping', $order->notes);
            } elseif ($target === 'done' && in_array($order->status, ['confirmed', 'shipping'], true)) {
                app(OrderLifecycle::class)->update($order, 'done', $order->notes);
            }
            // A cancelled (or finished) order never follows its parcel; a parcel still moving for a cancelled order is flagged instead.
            if ($order->status === 'cancelled' && in_array($shipment->status, [...self::ON_THE_WAY, 'delivered'], true)) {
                AdminBell::ring('orders', 'shipment:'.$shipment->id.':cancelled-order:'.$shipment->status,
                    'Đơn '.$order->code.' đã hủy nhưng vận đơn '.($shipment->bill_code ?? $shipment->ref_code).': '.NtlStatus::LABELS[$shipment->status],
                    'Liên hệ Nhất Tín để dừng/hoàn hàng. Đơn giữ trạng thái Đã hủy.', OrderResource::getUrl('edit', ['record' => $order]),
                    'heroicon-o-exclamation-triangle', 'danger');
            }
            if (in_array($shipment->status, self::TROUBLE, true)) {
                AdminBell::ring('orders', 'shipment:'.$shipment->id.':'.$shipment->status,
                    'Vận đơn '.($shipment->bill_code ?? $shipment->ref_code).': '.NtlStatus::LABELS[$shipment->status],
                    'Đơn '.$order->code.' · '.$order->customer_name.' · '.$order->phone, OrderResource::getUrl('edit', ['record' => $order]),
                    'heroicon-o-exclamation-triangle', 'danger');
            }
        } catch (Throwable $e) {
            // Never lose a tracking update because the order could not follow; staff still see the shipment.
            Log::warning('Order did not follow its shipment', ['shipment' => $shipment->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Parcel weight from the products' "Cân nặng khi đóng gói".
     *
     * @return array{kg: float, known: bool} known = every line had a weight
     */
    public function weight(Order $order, float $fallbackPerItem = 1.0): array
    {
        $lines = DB::table('order_items')->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.order_id', $order->id)->get(['order_items.quantity', 'products.weight_kg']);
        $kg = 0.0;
        $known = $lines->isNotEmpty();
        foreach ($lines as $line) {
            $each = (float) $line->weight_kg;
            if ($each <= 0) {
                $known = false;
                $each = $fallbackPerItem;
            }
            $kg += $each * max(1, (int) $line->quantity);
        }

        return ['kg' => round(max(0.1, $kg), 2), 'known' => $known];
    }
}
