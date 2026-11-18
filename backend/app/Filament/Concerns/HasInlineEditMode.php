<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Livewire\Attributes\Url;

/**
 * The pencil on a list page: while it is on, the table swaps some columns for boxes that save on the spot.
 * It travels in the address (?sua=1) because Filament builds the table before any button of the page runs;
 * filters and search stay, kept by the session.
 */
trait HasInlineEditMode
{
    #[Url(as: 'sua')]
    public bool $inlineEdit = false;

    protected function inlineEditAction(bool $allowed): Action
    {
        return Action::make('inlineEdit')->icon('heroicon-o-pencil-square')
            ->label(fn () => $this->inlineEdit ? 'Xong sửa' : 'Sửa trên bảng')
            ->tooltip('Sửa ngay trên danh sách, không cần mở từng dòng')
            ->color(fn () => $this->inlineEdit ? 'primary' : 'gray')
            ->visible($allowed)
            ->url(fn () => static::getResource()::getUrl('index', $this->inlineEdit ? [] : ['sua' => 1]));
    }
}
