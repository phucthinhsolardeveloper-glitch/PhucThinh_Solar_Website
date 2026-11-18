<?php

namespace App\Domain\Analytics;

use Illuminate\Database\Query\Builder;

/**
 * Which orders the dashboard counts as sales. A cancelled order is out. So is an online (VNPay) order that has not been paid:
 * a failed or abandoned payment stays "new / unpaid" and would otherwise add its total to the revenue and to the "to process" count.
 * Bank-transfer and cash orders count from the moment they are placed, as before.
 */
final class CountedOrders
{
    public static function apply(Builder $query, string $prefix = ''): Builder
    {
        return $query->where($prefix.'status', '!=', 'cancelled')
            ->where(fn ($q) => $q->where($prefix.'payment_code', '!=', 'vnpay')->orWhereNull($prefix.'payment_code')->orWhere($prefix.'payment_status', 'paid'));
    }
}
