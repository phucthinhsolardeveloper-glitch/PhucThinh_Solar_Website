<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\AdminActor;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(AdminActor::current()->canPerform('content_publish')), CreateAction::make()];
    }
}
