<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Pages\ProductBulkPage;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulk')->label('Nhập / xuất Excel')->icon('heroicon-o-table-cells')->color('gray')
                ->visible(fn () => ProductResource::canBulkImport())->url(ProductBulkPage::getUrl()),
            CreateAction::make(),
        ];
    }
}
