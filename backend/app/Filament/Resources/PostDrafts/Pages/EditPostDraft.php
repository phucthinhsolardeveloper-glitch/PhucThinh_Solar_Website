<?php

namespace App\Filament\Resources\PostDrafts\Pages;

use App\Domain\Content\PostWorkflow;
use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\PostDrafts\PostDraftResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Filament\Support\AdminActor;
use App\Models\PostRevision;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPostDraft extends EditRecord
{
    protected static string $resource = PostDraftResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof PostRevision, 404);

        return app(PostWorkflow::class)->save($record, $data['payload'] ?? [], AdminActor::current());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')->label('Xuất bản')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible(fn () => AdminActor::current()->canPerform('content_publish'))
                ->requiresConfirmation()->modalDescription('Lưu bản nháp trước khi xuất bản. Thao tác này cập nhật nội dung công khai và lưu lịch sử để có thể khôi phục.')
                ->action(function () {
                    $revision = app(PostWorkflow::class)->publish($this->revision(), AdminActor::current());
                    $resource = match ($revision->post?->kind) {
                        'solution' => SolutionResource::class,
                        'project' => ProjectResource::class,
                        'policy' => PolicyResource::class,
                        default => abort(404),
                    };

                    return redirect($resource::getUrl('edit', ['record' => $revision->post_id]));
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
