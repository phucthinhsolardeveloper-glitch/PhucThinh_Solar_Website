<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Support\CreatesPostWithPublicationGate;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicy extends CreateRecord
{
    use CreatesPostWithPublicationGate;

    protected static string $resource = PolicyResource::class;
}
