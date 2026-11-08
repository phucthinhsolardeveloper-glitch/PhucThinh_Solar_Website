<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    use ManagesPostPublication;

    protected static string $resource = PolicyResource::class;
}
