<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

/**
 * The panel sends "Lưu" back to the list (resourceEditPageRedirect('index')). This adds a second button
 * that saves the same way but keeps the page open, for staff who are still editing.
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
        ];
    }
}
