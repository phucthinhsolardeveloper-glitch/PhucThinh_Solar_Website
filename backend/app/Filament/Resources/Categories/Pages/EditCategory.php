<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\PublicLink;
use App\Models\Category;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use ReportsSavedChanges, SavesAndStays;

    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // "Thuộc danh mục" is locked (not sent) while the category has children: keep its current parent.
        return CategoryResource::withDepth($data + ['parent_id' => $this->getRecord()->getAttribute('parent_id')]);
    }

    protected function getHeaderActions(): array
    {
        // A category with sub-categories or products cannot be deleted; move or hide them first.
        return [PublicLink::action(), ActionGroup::make([DeleteAction::make()->hidden(fn (Category $record) => $record->children()->exists() || $record->products()->exists())])->label('Thêm')->icon('heroicon-m-ellipsis-horizontal')->button()->color('gray')];
    }
}
