<?php

namespace App\Filament\Support;

use App\Domain\Content\PostWorkflow;
use App\Filament\Resources\PostDrafts\PostDraftResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

trait ManagesPostPublication
{
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
            return 'Nội dung đã xuất bản chỉ được sửa qua bản nháp. Chọn “Soạn bản nháp” để đề xuất thay đổi.';
        }

        return 'Lưu tại đây cập nhật nội dung công khai ngay. Dùng “Soạn bản nháp” để lưu lịch sử và duyệt trước khi xuất bản.';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->hidden(fn () => $this->isPublished() && ! AdminActor::current()->canPerform('content_publish'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('draft')->label('Soạn bản nháp')->icon('heroicon-o-document-duplicate')
                ->action(function (Post $record) {
                    $draft = app(PostWorkflow::class)->draft($record, AdminActor::current());

                    return redirect(PostDraftResource::getUrl('edit', ['record' => $draft]));
                }),
            DeleteAction::make(),
        ];
    }

    private function isPublished(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Post && $record->is_visible;
    }
}
