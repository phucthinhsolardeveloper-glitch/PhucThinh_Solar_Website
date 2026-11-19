<?php

namespace App\Filament\Resources\PageItems\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\PageItems\PageItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePageItems extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = PageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
