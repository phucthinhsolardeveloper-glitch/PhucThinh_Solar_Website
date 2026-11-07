<?php

namespace App\Filament\Resources\StoreLocations\Pages;

use App\Filament\Resources\StoreLocations\StoreLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStoreLocations extends ManageRecords
{
    protected static string $resource = StoreLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
