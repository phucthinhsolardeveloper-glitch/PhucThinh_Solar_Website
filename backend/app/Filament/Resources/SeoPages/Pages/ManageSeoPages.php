<?php

namespace App\Filament\Resources\SeoPages\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\SeoPages\SeoPageResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSeoPages extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = SeoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny())];
    }
}
