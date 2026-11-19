<?php

namespace App\Filament\Support;

use App\Filament\Concerns\HasInlineEditMode;
use Filament\Tables\Table;

/** One question for every list: is the pencil on for this page and may this user see the resource? */
final class InlineEdit
{
    /** @param class-string $resource */
    public static function on(Table $table, string $resource): bool
    {
        $page = $table->getLivewire();

        return in_array(HasInlineEditMode::class, class_uses_recursive($page), true) && $page->inlineEdit && $resource::canViewAny();
    }
}
