<?php

namespace App\Filament\Resources\Solutions\Pages;

use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Filament\Support\ManagesPostPublication;
use Filament\Resources\Pages\EditRecord;

class EditSolution extends EditRecord
{
    use ManagesPostPublication;
    use SavesAndStays;

    protected static string $resource = SolutionResource::class;
}
