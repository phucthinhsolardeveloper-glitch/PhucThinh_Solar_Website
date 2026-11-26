<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Pages\PageLayoutsPage;
use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageBanners extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), Action::make('layout')->label('Bố cục trang')->icon(Heroicon::OutlinedQueueList)->color('gray')
            ->visible(fn () => PageLayoutsPage::canAccess())
            ->url(fn () => PageLayoutsPage::getUrl(['page' => str_starts_with((string) ($this->tableFilters['placement']['value'] ?? ''), 'about-') ? 'about' : 'home'])),
            CreateAction::make()];
    }
}
