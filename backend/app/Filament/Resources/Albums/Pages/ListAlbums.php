<?php

namespace App\Filament\Resources\Albums\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Albums\AlbumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlbums extends ListRecords
{
    use HasInlineEditMode;

    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
