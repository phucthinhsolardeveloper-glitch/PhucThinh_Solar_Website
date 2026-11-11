<?php

namespace App\Filament\Pages;

use App\Domain\Admin\WorkQueue;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/** "Việc cần làm": every waiting order, enquiry, review, draft and broken product in one place, oldest first. */
class WorkQueuePage extends Page
{
    protected static ?string $title = 'Việc cần làm';

    protected static ?string $navigationLabel = 'Việc cần làm';

    protected static ?string $slug = 'viec-can-lam';

    protected static ?int $navigationSort = -1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected string $view = 'filament.pages.work-queue';

    #[Url]
    public bool $mine = false;

    public static function canAccess(): bool
    {
        return auth('admin')->user() instanceof User;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth('admin')->user();
        $count = $user instanceof User ? app(WorkQueue::class)->urgentCount($user) : 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Đơn, liên hệ và bài chờ duyệt đang đợi';
    }

    /** @return list<array<string, mixed>> */
    public function sections(): array
    {
        $user = auth('admin')->user();

        return $user instanceof User ? app(WorkQueue::class)->sections($user, $this->mine) : [];
    }
}
