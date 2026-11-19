<?php

namespace App\Filament\Resources\StoreLocations\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\StoreLocations\StoreLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStoreLocations extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = StoreLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
