<?php

namespace App\Filament\Support;

use App\Domain\Content\PostRevisions;
use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Edit screen of a post: publishers may save the public page directly (each save is kept as a
 * version); content-only editors work through a draft. Shared by articles, solutions, projects and policies.
 */
trait ManagesPostPublication
{
    use ReportsSavedChanges;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $publisher = AdminActor::current()->canPerform('content_publish');
        if ($record instanceof Post && $record->is_visible) {
            abort_unless($publisher, 403);
        }
        if (! $publisher) {
            $data['is_visible'] = false;
        }
        if ($record instanceof Post) {
            app(PostRevisions::class)->beforeDirectSave($record);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    /** Runs after the SEO, gallery and product relations are saved, so the version is complete. */
    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if ($record instanceof Post) {
            app(PostRevisions::class)->afterDirectSave($record, AdminActor::current());
        }
    }

    public function getSubheading(): ?string
    {
        if (! AdminActor::current()->canPerform('content_publish') && $this->isPublished()) {
            return 'Nội dung đã xuất bản chỉ được sửa qua bản nháp. Chọn “Soạn bản nháp” để đề xuất thay đổi.';
        }
        if ($this->isPublished()) {
            return 'Lưu tại đây cập nhật trang công khai ngay và giữ lại bản cũ trong lịch sử. Dùng “Soạn bản nháp” để chuẩn bị thay đổi trước khi xuất bản.';
        }

        return null;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->hidden(fn () => $this->isPublished() && ! AdminActor::current()->canPerform('content_publish'));
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $draft = $record instanceof Post ? $record->revisions()->where('state', 'draft')->first() : null;

        return [
            PublicLink::action(),
            Action::make('draft')->label($draft ? 'Mở bản nháp' : 'Soạn bản nháp')->icon('heroicon-o-document-duplicate')
                ->color($draft ? 'warning' : 'primary')
                ->action(function (Post $record) {
                    $draft = app(PostRevisions::class)->draft($record, AdminActor::current());

                    return redirect(PostRevisionResource::getUrl('edit', ['record' => $draft]));
                }),
            Action::make('history')->label('Lịch sử')->icon('heroicon-o-clock')->color('gray')
                ->visible(fn () => $record instanceof Post && $record->revisions()->exists())
                ->url(fn () => PostRevisionResource::getUrl('index', ['tableFilters' => ['post_id' => ['value' => $record->getKey()]]])),
            DeleteAction::make(),
        ];
    }

    private function isPublished(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Post && $record->is_visible;
    }
}
