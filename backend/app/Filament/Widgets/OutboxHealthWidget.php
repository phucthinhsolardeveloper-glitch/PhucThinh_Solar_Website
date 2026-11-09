<?php

namespace App\Filament\Widgets;

use App\Domain\Outbox\Outbox;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Mail to customers and staff that has not gone out yet, so a mail outage is noticed. */
class OutboxHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Email thông báo';

    protected function getStats(): array
    {
        $h = Outbox::health();

        return [
            Stat::make('Đang chờ gửi', $h['pending'])->color($h['pending'] > 0 ? 'warning' : 'success'),
            Stat::make('Đang thử lại', $h['retrying'])->color($h['retrying'] > 0 ? 'warning' : 'success'),
            Stat::make('Gửi thất bại', $h['failed'] + $h['failed_jobs'])
                ->description($h['failed'] + $h['failed_jobs'] > 0 ? 'Kiểm tra cấu hình mail trên server' : 'Không có')
                ->color($h['failed'] + $h['failed_jobs'] > 0 ? 'danger' : 'success'),
        ];
    }
}
