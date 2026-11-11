<?php

namespace App\Filament\Resources\PostRevisions\Pages;

use App\Domain\Content\PostReview;
use App\Domain\Content\PostRevisions;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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

        $review = match ($this->revision()->review_state) {
            'waiting' => ' Đang chờ duyệt'.($this->revision()->review_note ? ' · ghi chú: '.$this->revision()->review_note : '').'.',
            'returned' => ' Bị trả lại: '.$this->revision()->review_note,
            default => '',
        };

        return $kind.' · bản #'.$this->revision()->version.'. Nhấn “Lưu” để giữ bản nháp; “Xuất bản” mới thay đổi trang công khai.'.$review;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof PostRevision, 404);

        return app(PostRevisions::class)->save($record, $data['payload'] ?? [], AdminActor::current());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestReview')->label('Gửi duyệt')->icon('heroicon-o-clipboard-document-check')->color('warning')
                ->visible(fn () => $this->revision()->review_state !== 'waiting')
                ->schema([Textarea::make('note')->label('Ghi chú cho người duyệt')->rows(2)])
                ->modalDescription('Lưu bản nháp rồi báo cho người có quyền xuất bản. Bài chỉ lên website khi được xuất bản.')
                ->action(function (array $data) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    app(PostReview::class)->request($this->revision()->refresh(), AdminActor::current(), $data['note'] ?? null);
                    Notification::make()->success()->title('Đã gửi duyệt')->send();
                }),
            Action::make('returnDraft')->label('Trả lại')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                ->visible(fn () => $this->revision()->review_state === 'waiting' && AdminActor::current()->canPerform('content_publish'))
                ->schema([Textarea::make('note')->label('Cần sửa gì')->required()->rows(3)])
                ->action(function (array $data) {
                    app(PostReview::class)->returnToWriter($this->revision(), AdminActor::current(), (string) $data['note']);
                    Notification::make()->success()->title('Đã trả lại cho người viết')->send();

                    return redirect(PostRevisionResource::getUrl());
                }),
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
