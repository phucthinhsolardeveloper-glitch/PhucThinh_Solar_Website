<?php

namespace App\Filament\Resources\Provinces\Pages;

use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Resources\Pages\EditRecord;

class EditProvince extends EditRecord
{
    use SavesAndStays;

    protected static string $resource = ProvinceResource::class;
}
