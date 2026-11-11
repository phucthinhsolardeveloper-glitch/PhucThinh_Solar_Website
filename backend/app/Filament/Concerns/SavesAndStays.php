<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * The panel sends "Lưu" back to the list (resourceEditPageRedirect('index')). This adds a second button
 * that saves the same way but keeps the page open, and "← Mục trước / Mục sau →" to walk the list in its
 * default order (newest first) without going back to it. They ignore the list's filters and sort (those live in
 * the list component's session state); the panel's unsaved-changes alert guards leaving with edits.
 */
trait SavesAndStays
{
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Lưu'),
            Action::make('saveAndStay')->label('Lưu, ở lại trang')->color('gray')
                ->action(fn () => $this->save(shouldRedirect: false)),
            $this->getCancelFormAction(),
            Action::make('previousRecord')->label('← Mục trước')->color('gray')->link()
                ->tooltip('Mục đứng trước trong danh sách (mới hơn). Thứ tự mặc định (mới nhất trước), không theo bộ lọc đang chọn ở danh sách. Có thay đổi chưa lưu thì trang sẽ hỏi trước khi rời.')
                ->visible(fn () => $this->neighbour('>') !== null)
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->neighbour('>')])),
            Action::make('nextRecord')->label('Mục sau →')->color('gray')->link()
                ->tooltip('Mục đứng sau trong danh sách (cũ hơn). Thứ tự mặc định (mới nhất trước), không theo bộ lọc đang chọn ở danh sách. Có thay đổi chưa lưu thì trang sẽ hỏi trước khi rời.')
                ->visible(fn () => $this->neighbour('<') !== null)
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->neighbour('<')])),
        ];
    }

    /** Id of the closest record above (">") or below ("<") this one, within what the resource lists. */
    private function neighbour(string $direction): ?Model
    {
        $id = $this->getRecord()->getKey();

        return static::getResource()::getEloquentQuery()->where('id', $direction, $id)
            ->orderBy('id', $direction === '>' ? 'asc' : 'desc')->first();
    }
}
