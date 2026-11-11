<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    use ManagesPostPublication;
    use SavesAndStays;

    protected static string $resource = PolicyResource::class;
}
