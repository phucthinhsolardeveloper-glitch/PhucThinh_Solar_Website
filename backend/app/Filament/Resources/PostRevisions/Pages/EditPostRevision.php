<?php

namespace App\Filament\Resources\PostRevisions\Pages;

use App\Domain\Content\PostRevisions;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPostRevision extends EditRecord
{
    use SavesAndStays;

    protected static string $resource = PostRevisionResource::class;

    public function getTitle(): string
    {
        $record = $this->revision();

        return 'Bản nháp: '.($record->post->title ?? '');
    }

    public function getSubheading(): ?string
    {
        $kind = PostRevisionResource::KIND_LABELS[$this->revision()->post?->kind] ?? '';

        return $kind.' · bản #'.$this->revision()->version.'. Nhấn “Lưu” để giữ bản nháp; “Xuất bản” mới thay đổi trang công khai.';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof PostRevision, 404);

        return app(PostRevisions::class)->save($record, $data['payload'] ?? [], AdminActor::current());
    }

    protected function getHeaderActions(): array
    {
        return [
            PostRevisionResource::compareAction(),
            PostRevisionResource::previewAction(),
            Action::make('publish')->label('Xuất bản')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible(fn () => AdminActor::current()->canPerform('content_publish'))
                ->requiresConfirmation()->modalDescription('Thay đổi chưa lưu sẽ được lưu trước. Trang công khai được cập nhật và phiên bản cũ vẫn nằm trong lịch sử.')
                ->action(function () {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $revision = app(PostRevisions::class)->publish($this->revision()->refresh(), AdminActor::current());
                    $resource = PostRevisionResource::ownerResource($revision->post->kind);

                    return redirect($resource::getUrl('edit', ['record' => $revision->post_id]));
                }),
            DeleteAction::make()->label('Bỏ bản nháp')->modalDescription('Bản nháp bị xóa; trang công khai không đổi.')
                ->successRedirectUrl(PostRevisionResource::getUrl()),
        ];
    }

    private function revision(): PostRevision
    {
        $record = $this->getRecord();
        abort_unless($record instanceof PostRevision, 404);

        return $record;
    }
}
