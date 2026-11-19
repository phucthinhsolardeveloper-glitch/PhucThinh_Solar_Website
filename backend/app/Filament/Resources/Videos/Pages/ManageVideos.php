<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Videos\VideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVideos extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
