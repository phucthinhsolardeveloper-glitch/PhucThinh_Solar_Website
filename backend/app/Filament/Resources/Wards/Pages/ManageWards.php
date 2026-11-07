<?php

namespace App\Filament\Resources\Wards\Pages;

use App\Filament\Resources\Wards\WardResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWards extends ManageRecords
{
    protected static string $resource = WardResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
