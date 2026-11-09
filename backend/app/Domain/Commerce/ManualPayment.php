<?php

namespace App\Domain\Commerce;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Records an operator's confirmation of an offline payment, without altering gateway payments. */
class ManualPayment
{
    public function confirm(Order $order, User $actor, string $reference, ?int $amount = null): void
    {
        abort_unless($actor->canPerform('orders_payment'), 403);
        $reference = trim($reference);
        if ($reference === '' || mb_strlen($reference) > 120) {
            throw ValidationException::withMessages(['reference' => 'Nhập mã tham chiếu giao dịch (tối đa 120 ký tự).']);
        }

        DB::transaction(function () use ($order, $actor, $reference, $amount): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_code !== 'manual' || $locked->payment_status !== 'unpaid') {
                throw ValidationException::withMessages(['reference' => 'Chỉ xác nhận đơn thanh toán thủ công đang chưa thanh toán.']);
            }
            // A short or long payment is not "paid": it needs a person to settle it with the customer.
            if ($amount !== null && $amount !== (int) round((float) $locked->total)) {
                throw ValidationException::withMessages(['amount' => 'Số tiền nhận khác tổng đơn ('.number_format((float) $locked->total, 0, ',', '.').'đ). Liên hệ khách để xử lý phần chênh lệch trước khi xác nhận.']);
            }

            $locked->payments()->create([
                'provider' => 'manual', 'reference' => 'admin:'.$locked->id,
                'provider_reference' => $reference, 'amount' => $locked->total,
                'status' => 'paid', 'payload' => ['confirmed_by' => $actor->id],
            ]);
            OrderLifecycle::movePayment($locked, 'paid', ['payment_reference' => $reference]);
        });
    }

    /** Records an external full refund that staff has already completed. No money is transferred here. */
    public function recordRefund(Order $order, User $actor, string $reference): void
    {
        abort_unless($actor->canPerform('orders_payment'), 403);
        $reference = trim($reference);
        if ($reference === '' || mb_strlen($reference) > 120) {
            throw ValidationException::withMessages(['reference' => 'Nhập mã tham chiếu hoàn tiền (tối đa 120 ký tự).']);
        }

        DB::transaction(function () use ($order, $actor, $reference): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_code !== 'manual' || $locked->payment_status !== 'paid') {
                throw ValidationException::withMessages(['reference' => 'Chỉ ghi nhận hoàn tiền cho đơn thủ công đã thanh toán.']);
            }
            if ($locked->payments()->where('provider', 'manual')->where('status', 'refunded')->exists()) {
                throw ValidationException::withMessages(['reference' => 'Đơn này đã có bản ghi hoàn tiền.']);
            }

            $locked->payments()->create([
                'provider' => 'manual', 'reference' => 'refund:'.$locked->id,
                'provider_reference' => $reference, 'amount' => $locked->total,
                'status' => 'refunded', 'payload' => ['type' => 'external_full_refund', 'recorded_by' => $actor->id],
            ]);
            OrderLifecycle::movePayment($locked, 'refunded');
        });
    }
}
