<?php

namespace App\Filament\Resources\PageItems\Pages;

use App\Filament\Resources\PageItems\PageItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePageItems extends ManageRecords
{
    protected static string $resource = PageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
