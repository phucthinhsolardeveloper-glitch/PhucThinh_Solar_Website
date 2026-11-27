<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Admin\WorkQueue;
use App\Domain\Commerce\OrderExporter;
use App\Filament\Concerns\HasQuickTabs;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    use HasQuickTabs;

    protected static string $resource = OrderResource::class;

    /** The order's journey, left to right, plus the money that is waiting to be checked. */
    public function getTabs(): array
    {
        $status = fn (string $value) => [OrderResource::STATUSES[$value], fn (Builder $query) => $query->where('status', $value)];

        return $this->quickTabs([
            'moi' => ['Mới', fn (Builder $query) => $query->whereIn('orders.id', WorkQueue::newOrders()->select('id'))],
            'bao-ck' => ['Khách báo đã chuyển khoản', fn (Builder $query) => $query->whereNotNull('transfer_reported_at')->where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')],
            'da-xac-nhan' => $status('confirmed'),
            'dang-giao' => $status('shipping'),
            'hoan-tat' => $status('done'),
            'da-huy' => $status('cancelled'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportList')->label('Xuất Excel theo bộ lọc')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => app(OrderExporter::class)->list($this->getFilteredTableQuery())),
        ];
    }
}
