<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PublishGate;
use App\Filament\Support\TypedSpecsForm;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return TypedSpecsForm::save($data, null);
    }

    protected function afterCreate(): void
    {
        PublishGate::afterSave($this->getRecord(), false);
    }
}
