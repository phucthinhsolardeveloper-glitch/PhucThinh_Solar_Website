<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Support\PublicLink;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    use ReportsSavedChanges;

    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [PublicLink::action(), DeleteAction::make()];
    }
}
