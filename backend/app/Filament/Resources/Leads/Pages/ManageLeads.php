<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLeads extends ManageRecords
{
    use HasInlineEditMode;

    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny())];
    }
}
