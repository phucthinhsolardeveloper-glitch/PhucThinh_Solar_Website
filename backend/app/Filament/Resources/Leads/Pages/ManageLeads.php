<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Concerns\HasQuickTabs;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageLeads extends ManageRecords
{
    use HasInlineEditMode;
    use HasQuickTabs;

    protected static string $resource = LeadResource::class;

    public function getTabs(): array
    {
        $status = fn (string $value) => [LeadResource::STATUSES[$value], fn (Builder $query) => $query->where('status', $value)];

        return $this->quickTabs([
            'moi' => $status('new'),
            'qua-han' => ['Quá hạn gọi lại', fn (Builder $query) => $query->whereNotIn('status', ['done', 'spam'])->where('follow_up_at', '<', now())],
            'da-lien-he' => $status('contacted'),
            'da-xong' => $status('done'),
            'rac' => $status('spam'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->inlineEditAction(static::getResource()::canViewAny())];
    }
}
