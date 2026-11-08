<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Domain\Content\ArticleWorkflow;
use App\Filament\Resources\ArticleDrafts\ArticleDraftResource;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\AdminActor;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    public function getSubheading(): ?string
    {
        return 'Lưu tại đây cập nhật bài public ngay. Dùng “Soạn bản nháp” để xem trước và lưu lịch sử trước khi xuất bản.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('draft')->label('Soạn bản nháp')->icon('heroicon-o-document-duplicate')
                ->action(function (Post $record) {
                    $draft = app(ArticleWorkflow::class)->draft($record, AdminActor::current());

                    return redirect(ArticleDraftResource::getUrl('edit', ['record' => $draft]));
                }),
            DeleteAction::make(),
        ];
    }
}
