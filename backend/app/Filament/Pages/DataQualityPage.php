<?php

namespace App\Filament\Pages;

use App\Domain\Admin\DataQuality;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** "Chất lượng dữ liệu": what is wrong or missing on products, posts and SEO, with links to fix each one. */
class DataQualityPage extends Page
{
    protected static ?string $title = 'Chất lượng dữ liệu';

    protected static ?string $slug = 'chat-luong-du-lieu';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected string $view = 'filament.pages.data-quality';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && ($user->hasPermission('catalog') || $user->hasPermission('content'));
    }

    public function getSubheading(): ?string
    {
        return 'Cập nhật 10 phút một lần. Số "7 ngày trước" lấy từ bản ghi hằng đêm. Bấm tên để mở và sửa.';
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('refresh')->label('Kiểm tra lại ngay')->icon('heroicon-o-arrow-path')->color('gray')
            ->action(fn () => app(DataQuality::class)->report(true))];
    }

    /** @return array<string, list<array<string, mixed>>> group => checks, errors first */
    public function groups(): array
    {
        $user = auth('admin')->user();
        $allowed = fn (array $check) => $user instanceof User && (str_starts_with($check['key'], 'product.') ? $user->hasPermission('catalog') : $user->hasPermission('content') || $user->hasPermission('catalog'));
        $order = ['error' => 0, 'warning' => 1, 'info' => 2];

        return collect(app(DataQuality::class)->report())->filter($allowed)
            ->sortBy(fn ($c) => $order[$c['severity']] ?? 3)->groupBy('group')->map(fn ($c) => $c->values()->all())->all();
    }

    /** @return array<string, int> */
    public function weekAgo(): array
    {
        return app(DataQuality::class)->countsAgo(7);
    }
}
