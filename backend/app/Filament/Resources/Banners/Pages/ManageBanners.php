<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()];
    }
}
