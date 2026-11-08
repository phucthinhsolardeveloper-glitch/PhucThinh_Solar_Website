<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    use ManagesPostPublication;

    protected static string $resource = ProjectResource::class;
}
