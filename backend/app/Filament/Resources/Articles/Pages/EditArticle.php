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
use Illuminate\Database\Eloquent\Model;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $publisher = AdminActor::current()->canPerform('content_publish');
        if ($record instanceof Post && $record->is_visible) {
            abort_unless($publisher, 403);
        }
        if (! $publisher) {
            $data['is_visible'] = false;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    public function getSubheading(): ?string
    {
        if (! AdminActor::current()->canPerform('content_publish') && $this->isPublished()) {
            return 'Bài đã xuất bản chỉ được sửa qua bản nháp. Chọn “Soạn bản nháp” để đề xuất thay đổi.';
        }

        return 'Lưu tại đây cập nhật bài public ngay. Dùng “Soạn bản nháp” để xem trước và lưu lịch sử trước khi xuất bản.';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->hidden(fn () => $this->isPublished() && ! AdminActor::current()->canPerform('content_publish'));
    }

    private function isPublished(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Post && $record->is_visible;
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
