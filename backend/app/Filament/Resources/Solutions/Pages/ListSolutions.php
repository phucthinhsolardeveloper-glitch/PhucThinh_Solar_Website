<?php

namespace App\Filament\Resources\Solutions\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Filament\Support\AdminActor;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSolutions extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = SolutionResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(AdminActor::current()->canPerform('content_publish')), CreateAction::make()];
    }
}
