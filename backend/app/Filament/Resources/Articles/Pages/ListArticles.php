<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Concerns\HasQuickTabs;
use App\Filament\Pages\EditorialCalendarPage;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\AdminActor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListArticles extends ListRecords
{
    use HasInlineEditMode;
    use HasQuickTabs;

    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->inlineEditAction(AdminActor::current()->canPerform('content_publish')),
            Action::make('calendar')->label('Lịch bài viết')->icon('heroicon-o-calendar-days')->color('gray')->url(fn () => EditorialCalendarPage::getUrl()),
            CreateAction::make(),
        ];
    }

    /** The same words as "Lịch bài viết": on the website, waiting for its hour, switched off. */
    public function getTabs(): array
    {
        return $this->quickTabs([
            'dang-hien' => ['Đang hiện', fn (Builder $query) => $query->where('is_visible', true)->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))],
            'hen-gio' => ['Hẹn giờ', fn (Builder $query) => $query->where('is_visible', true)->where('published_at', '>', now())],
            'dang-an' => ['Đang ẩn', fn (Builder $query) => $query->where('is_visible', false)],
        ]);
    }
}
