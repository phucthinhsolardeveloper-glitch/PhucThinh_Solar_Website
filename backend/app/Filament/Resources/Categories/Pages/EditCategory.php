<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CategoryResource::withDepth($data);
    }

    protected function getHeaderActions(): array
    {
        // A category with sub-categories or products cannot be deleted; move or hide them first.
        return [DeleteAction::make()->hidden(fn (Category $record) => $record->children()->exists() || $record->products()->exists())];
    }
}
