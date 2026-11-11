<?php

namespace App\Domain\Content;

use App\Domain\Admin\AdminBell;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Models\PostRevision;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Editorial review of drafts: a writer sends a draft for review ("Chờ duyệt"), a publisher either publishes it
 * (PostRevisions::publish, which clears the review) or returns it with a note ("Trả lại"). Both sides hear it
 * through the Admin bell.
 */
final class PostReview
{
    public const LABELS = ['waiting' => 'Chờ duyệt', 'returned' => 'Bị trả lại'];

    public function request(PostRevision $revision, User $actor, ?string $note = null): void
    {
        $this->assertDraft($revision);
        $checks = app(PublishChecks::class)->post(['kind' => $revision->post->kind] + $revision->payload + PublishChecks::postData($revision->post), (int) $revision->post_id);
        if ($checks['errors'] !== []) {
            throw ValidationException::withMessages(['review' => 'Chưa gửi duyệt được: '.implode('; ', $checks['errors']).'.']);
        }
        $revision->update(['review_state' => 'waiting', 'review_note' => $note ?: null, 'review_requested_by' => $actor->id,
            'review_requested_at' => now(), 'reviewed_by' => null]);
        AdminBell::ring('content', 'review:'.$revision->id.':'.now()->timestamp, 'Bài chờ duyệt: '.$revision->post->title,
            $actor->name.' gửi bản nháp #'.$revision->version.($note ? ' · '.$note : ''), PostRevisionResource::getUrl('edit', ['record' => $revision]),
            'heroicon-o-clipboard-document-check', 'warning', fn (User $user) => $user->id !== $actor->id && $user->canPerform('content_publish'));
    }

    public function returnToWriter(PostRevision $revision, User $actor, string $note): void
    {
        $this->assertDraft($revision);
        abort_unless($actor->canPerform('content_publish'), 403);
        $revision->update(['review_state' => 'returned', 'review_note' => $note, 'reviewed_by' => $actor->id]);
        $writer = $revision->requester ?? $revision->editor;
        if ($writer) {
            Notification::make()->title('Bài bị trả lại: '.$revision->post->title)->body($actor->name.': '.$note)
                ->icon('heroicon-o-arrow-uturn-left')->iconColor('danger')
                ->actions([Action::make('open')->label('Mở')->url(PostRevisionResource::getUrl('edit', ['record' => $revision]))->markAsRead()])
                ->sendToDatabase($writer);
        }
    }

    private function assertDraft(PostRevision $revision): void
    {
        if ($revision->state !== 'draft') {
            throw ValidationException::withMessages(['review' => 'Chỉ bản nháp mới gửi hoặc trả lại được.']);
        }
    }
}
