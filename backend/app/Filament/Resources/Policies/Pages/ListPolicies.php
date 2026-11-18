<?php

namespace App\Filament\Resources\Policies\Pages;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Support\AdminActor;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPolicies extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(AdminActor::current()->canPerform('content_publish')), CreateAction::make()];
    }
}
