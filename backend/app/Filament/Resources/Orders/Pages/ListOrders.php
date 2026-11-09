<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Commerce\OrderExporter;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportList')->label('Xuất Excel theo bộ lọc')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => app(OrderExporter::class)->list($this->getFilteredTableQuery())),
        ];
    }
}
