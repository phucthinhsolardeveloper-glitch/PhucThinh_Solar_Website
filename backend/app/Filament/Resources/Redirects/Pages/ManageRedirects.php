<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Redirects\RedirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRedirects extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny()), CreateAction::make()->label('Thêm chuyển hướng')->mutateDataUsing(fn (array $data) => $data + ['source' => 'manual'])];
    }
}
