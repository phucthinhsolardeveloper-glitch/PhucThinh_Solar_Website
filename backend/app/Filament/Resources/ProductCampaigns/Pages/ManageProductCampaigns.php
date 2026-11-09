<?php

namespace App\Filament\Resources\ProductCampaigns\Pages;

use App\Filament\Resources\ProductCampaigns\ProductCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProductCampaigns extends ManageRecords
{
    protected static string $resource = ProductCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm chiến dịch')];
    }
}
