<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Concerns\HasQuickTabs;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageReviews extends ManageRecords
{
    use HasQuickTabs;

    protected static string $resource = ReviewResource::class;

    public function getTabs(): array
    {
        $status = fn (string $value) => [ReviewResource::STATUSES[$value], fn (Builder $query) => $query->where('status', $value)];

        return $this->quickTabs(['cho-duyet' => $status('pending'), 'dang-hien' => $status('approved'), 'da-an' => $status('hidden')]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
