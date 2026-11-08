<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\CreatesPostWithPublicationGate;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use CreatesPostWithPublicationGate;

    protected static string $resource = ProjectResource::class;
}
