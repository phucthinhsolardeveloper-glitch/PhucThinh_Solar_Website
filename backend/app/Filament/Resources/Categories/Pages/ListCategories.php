<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tree')->label('Dạng cây (kéo thả)')->icon('heroicon-o-bars-3-bottom-left')->color('gray')
                ->url(CategoryResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
