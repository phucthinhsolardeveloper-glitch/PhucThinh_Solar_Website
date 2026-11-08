<?php

namespace App\Filament\Resources\Solutions\Pages;

use App\Filament\Resources\Solutions\SolutionResource;
use App\Filament\Support\CreatesPostWithPublicationGate;
use Filament\Resources\Pages\CreateRecord;

class CreateSolution extends CreateRecord
{
    use CreatesPostWithPublicationGate;

    protected static string $resource = SolutionResource::class;
}
