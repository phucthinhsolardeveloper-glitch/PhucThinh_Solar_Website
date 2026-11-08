<?php

namespace App\Domain\Commerce;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Records an operator's confirmation of an offline payment, without altering gateway payments. */
class ManualPayment
{
    public function confirm(Order $order, User $actor, string $reference): void
    {
        abort_unless($actor->hasPermission('orders'), 403);
        $reference = trim($reference);
        if ($reference === '' || mb_strlen($reference) > 120) {
            throw ValidationException::withMessages(['reference' => 'Nhập mã tham chiếu giao dịch (tối đa 120 ký tự).']);
        }

        DB::transaction(function () use ($order, $actor, $reference): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_code !== 'manual' || $locked->payment_status !== 'unpaid') {
                throw ValidationException::withMessages(['reference' => 'Chỉ xác nhận đơn thanh toán thủ công đang chưa thanh toán.']);
            }

            $locked->payments()->create([
                'provider' => 'manual', 'reference' => 'admin:'.$locked->id,
                'provider_reference' => $reference, 'amount' => $locked->total,
                'status' => 'paid', 'payload' => ['confirmed_by' => $actor->id],
            ]);
            $locked->update(['payment_status' => 'paid', 'payment_reference' => $reference]);
        });
    }
}
