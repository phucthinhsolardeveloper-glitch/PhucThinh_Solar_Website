<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBrands extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
