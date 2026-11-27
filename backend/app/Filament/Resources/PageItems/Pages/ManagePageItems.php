<?php

namespace App\Filament\Resources\PageItems\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Pages\PageLayoutsPage;
use App\Filament\Resources\PageItems\PageItemResource;
use App\Filament\Support\PageSectionSources;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ManagePageItems extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = PageItemResource::class;

    /** On a list filtered to one section: where it shows, which rows are used, and the block's other editors. */
    public function getSubheading(): ?Htmlable
    {
        $help = PageSectionSources::sectionHelp($this->section());

        return $help === null ? null : new HtmlString(view('filament.pages.page-section-source', $help + ['newTab' => false])->render());
    }

    private function section(): string
    {
        return (string) ($this->tableFilters['section']['value'] ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()),
            Action::make('byPage')->label('Chọn theo trang')->icon(Heroicon::OutlinedMap)->color('gray')
                ->slideOver()->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
                ->modalContent(fn () => view('filament.pages.page-directory', ['pages' => PageSectionSources::directory()])),
            Action::make('layout')->label('Bố cục trang')->icon(Heroicon::OutlinedQueueList)->color('gray')
            // Only Home and About have a layout screen.
                ->visible(fn () => PageLayoutsPage::canAccess() && ($this->section() === '' || preg_match('/^(home|about)\./', $this->section()) === 1))
                ->url(fn () => PageLayoutsPage::getUrl(['page' => str_starts_with($this->section(), 'about') ? 'about' : 'home'])),
            CreateAction::make()];
    }
}
