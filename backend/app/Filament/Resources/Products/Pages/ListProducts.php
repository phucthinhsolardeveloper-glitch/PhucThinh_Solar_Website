<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Pages\ProductBulkPage;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * True while the pencil is on: the list shows editable boxes that save on the spot. It comes from the address (?sua=1),
     * because the table is built before any button runs; the filters and search are kept by the session.
     */
    #[Url(as: 'sua')]
    public bool $inlineEdit = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inlineEdit')->icon('heroicon-o-pencil-square')
                ->label(fn () => $this->inlineEdit ? 'Xong sửa' : 'Sửa trên bảng')
                ->tooltip('Sửa tên, danh mục, thương hiệu, giá ngay trên danh sách, không cần mở từng sản phẩm')
                ->color(fn () => $this->inlineEdit ? 'primary' : 'gray')
                ->visible(fn () => ProductResource::canBulkImport())
                ->url(fn () => static::getResource()::getUrl('index', $this->inlineEdit ? [] : ['sua' => 1])),
            Action::make('bulk')->label('Nhập / xuất Excel')->icon('heroicon-o-table-cells')->color('gray')
                ->visible(fn () => ProductResource::canBulkImport())->url(ProductBulkPage::getUrl()),
            CreateAction::make(),
        ];
    }
}
