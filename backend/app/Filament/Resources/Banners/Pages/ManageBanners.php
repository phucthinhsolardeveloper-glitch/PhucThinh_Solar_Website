<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Pages\PageLayoutsPage;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\PageItems\PageItemResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageBanners extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = BannerResource::class;

    /** Placements that belong to the dealer page, which is edited in "Nội dung các trang", not in a layout. */
    private const DEALER = ['dealer-hero' => 'dealer.intro', 'hinh-anh-dai-ly' => 'dealer.overview'];

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), Action::make('layout')->label('Bố cục trang')->icon(Heroicon::OutlinedQueueList)->color('gray')
            ->visible(fn () => PageLayoutsPage::canAccess() && ! isset(self::DEALER[$this->tableFilters['placement']['value'] ?? '']))
            ->url(fn () => PageLayoutsPage::getUrl(['page' => str_starts_with((string) ($this->tableFilters['placement']['value'] ?? ''), 'about-') ? 'about' : 'home'])),
            Action::make('dealerText')->label('Nội dung trang Đại lý')->icon(Heroicon::OutlinedRectangleGroup)->color('gray')
                ->visible(fn () => PageItemResource::canViewAny() && isset(self::DEALER[$this->tableFilters['placement']['value'] ?? '']))
                ->url(fn () => PageItemResource::getUrl('index', ['filters' => ['section' => ['value' => self::DEALER[$this->tableFilters['placement']['value'] ?? ''] ?? 'dealer.intro']]])),
            CreateAction::make()];
    }
}
