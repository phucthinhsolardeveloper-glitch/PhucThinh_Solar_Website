<?php

namespace App\Domain\Commerce;

use App\Domain\Audit\Audit;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place order and payment states change. Each change is checked against the allowed
 * transitions, taken under a row lock and written to the audit log.
 */
final class OrderLifecycle
{
    /** status => statuses it may move to */
    public const STATUS_TRANSITIONS = [
        'new' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'done', 'cancelled'],
        'shipping' => ['done', 'cancelled'],
        'done' => [],
        'cancelled' => [],
    ];

    public const PAYMENT_TRANSITIONS = [
        'unpaid' => ['paid'],
        'paid' => ['refunded'],
        'refunded' => [],
    ];

    public static function canMoveStatus(string $from, string $to): bool
    {
        return $from === $to || in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }

    public static function canMovePayment(string $from, string $to): bool
    {
        return in_array($to, self::PAYMENT_TRANSITIONS[$from] ?? [], true);
    }

    /** @return array<string, string> the current status and the ones it may move to, for the Admin picker */
    public static function statusChoices(string $current, array $labels): array
    {
        return array_intersect_key($labels, array_flip([$current, ...(self::STATUS_TRANSITIONS[$current] ?? [])]));
    }

    /** Admin changes the handling status and/or internal notes of an order. */
    public function update(Order $order, string $status, ?string $notes): void
    {
        DB::transaction(function () use ($order, $status, $notes) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! self::canMoveStatus((string) $locked->status, $status)) {
                throw ValidationException::withMessages(['data.status' => 'Không thể chuyển đơn từ "'.$locked->status.'" sang "'.$status.'".']);
            }
            $before = ['status' => $locked->status, 'notes' => $locked->notes];
            $locked->forceFill(['status' => $status, 'notes' => $notes])->save();
            [$from, $to] = Audit::diff($before, ['status' => $status, 'notes' => $notes]);
            if ($to !== []) {
                Audit::record(isset($to['status']) ? 'order.status' : 'order.notes', 'Order', (int) $locked->id, $from, $to);
            }
        });
        $order->refresh();
    }

    /**
     * Moves the payment state of a locked order row; callers hold the transaction and the lock.
     *
     * @param  array<string, mixed>  $extra  other order columns written with the change (payment_reference…)
     */
    public static function movePayment(object $lockedOrder, string $to, array $extra = [], ?string $systemActor = null): void
    {
        $from = (string) $lockedOrder->payment_status;
        if (! self::canMovePayment($from, $to)) {
            throw ValidationException::withMessages(['payment_status' => 'Không thể chuyển thanh toán từ "'.$from.'" sang "'.$to.'".']);
        }
        DB::table('orders')->where('id', $lockedOrder->id)->update(['payment_status' => $to] + $extra + ['updated_at' => now()]);
        Audit::record('payment.'.$to, 'Order', (int) $lockedOrder->id, ['payment_status' => $from], ['payment_status' => $to] + $extra, $systemActor);
    }
}
