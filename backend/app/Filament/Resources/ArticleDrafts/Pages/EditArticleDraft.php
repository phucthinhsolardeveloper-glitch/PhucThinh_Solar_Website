<?php

namespace App\Filament\Resources\ArticleDrafts\Pages;

use App\Domain\Content\ArticleWorkflow;
use App\Filament\Resources\ArticleDrafts\ArticleDraftResource;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditArticleDraft extends EditRecord
{
    protected static string $resource = ArticleDraftResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof PostRevision, 404);

        return app(ArticleWorkflow::class)->save($record, $data['payload'] ?? [], AdminActor::current());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Xem trước trên Nuxt')->icon('heroicon-o-eye')
                ->url(fn () => app(ArticleWorkflow::class)->previewUrl($this->revision()))->openUrlInNewTab(),
            Action::make('publish')->label('Xuất bản')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible(fn () => AdminActor::current()->canPerform('content_publish'))
                ->requiresConfirmation()->modalDescription('Lưu bản nháp trước khi xuất bản. Thao tác này cập nhật bài viết công khai và lưu lịch sử để có thể khôi phục.')
                ->action(function () {
                    $revision = app(ArticleWorkflow::class)->publish($this->revision(), AdminActor::current());

                    return redirect(ArticleResource::getUrl('edit', ['record' => $revision->post_id]));
                }),
        ];
    }

    private function revision(): PostRevision
    {
        $record = $this->getRecord();
        abort_unless($record instanceof PostRevision, 404);

        return $record;
    }
}
