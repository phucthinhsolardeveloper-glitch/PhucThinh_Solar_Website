<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Support\PublicLink;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    use ReportsSavedChanges, SavesAndStays;

    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [PublicLink::action(), ActionGroup::make([DeleteAction::make()])->label('Thêm')->icon('heroicon-m-ellipsis-horizontal')->button()->color('gray')];
    }
}
