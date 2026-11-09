<?php

namespace App\Domain\Shipping\NhatTin;

/** Nhất Tín status codes mapped to ours. Codes not listed are kept raw and shown as "unknown". */
final class NtlStatus
{
    public const MAP = [
        '1' => 'failed', '2' => 'waiting_pickup', '3' => 'in_transit', '4' => 'delivered', '6' => 'cancelled', '7' => 'failed',
        '9' => 'returning', '10' => 'returned', '11' => 'failed', '12' => 'draft', '13' => 'out_for_delivery', '15' => 'in_transit',
        '16' => 'returning', '17' => 'failed',
    ];

    public const LABELS = [
        'create_pending' => 'Đang gửi yêu cầu', 'draft' => 'Nháp', 'waiting_pickup' => 'Chờ lấy hàng', 'in_transit' => 'Đang vận chuyển',
        'out_for_delivery' => 'Đang giao', 'delivered' => 'Đã giao', 'failed' => 'Có sự cố', 'returning' => 'Đang hoàn', 'returned' => 'Đã hoàn',
        'cancelled' => 'Đã hủy', 'unknown' => 'Chưa rõ kết quả',
    ];

    /** A later update may not move a shipment out of these. */
    public const TERMINAL = ['delivered', 'returned', 'cancelled'];

    public static function normalise(mixed $raw): string
    {
        return self::MAP[(string) $raw] ?? 'unknown';
    }
}
