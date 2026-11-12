<?php

namespace App\Filament\Concerns;

use App\Domain\Content\PostRevisions;
use App\Domain\Routing\PublicUrls;
use App\Filament\Support\AdminActor;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * After "Lưu" on an edit page, the notice says WHAT changed (by the field labels on the form) and offers the public
 * page that shows it, so staff can check the result on the website straight away.
 */
trait ReportsSavedChanges
{
    protected function getSavedNotification(): ?Notification
    {
        $notification = parent::getSavedNotification();
        if ($notification === null) {
            return null;
        }
        $changed = $this->changedFieldLabels();
        $notification->body($changed !== []
            ? 'Đã đổi: '.implode(', ', array_slice($changed, 0, 8)).(count($changed) > 8 ? ' và '.(count($changed) - 8).' mục khác' : '').'.'
            : 'Thông tin chính không đổi; ảnh, SEO và nội dung đi kèm được lưu nếu có sửa.');
        $record = $this->getRecord();
        if ($record instanceof Post && ! PublicUrls::isLive($record)) {
            $draft = $record->revisions()->where('state', 'draft')->first()
                ?? app(PostRevisions::class)->draft($record, AdminActor::current());
            $notification->actions([
                Action::make('previewDraft')->label('Xem trước bản nháp')
                    ->url(app(PostRevisions::class)->previewUrl($draft), shouldOpenInNewTab: true)->button(),
            ])->persistent();
        } elseif (($url = PublicUrls::url($record)) !== null) {
            $notification->actions([
                Action::make('viewOnSite')->label('Xem trên website')
                    ->url($url, shouldOpenInNewTab: true)->button(),
            ])->persistent();
        }

        return $notification;
    }

    /** @return list<string> form labels of the columns this save changed */
    private function changedFieldLabels(): array
    {
        $keys = array_values(array_diff(array_keys($this->getRecord()->getChanges()), ['updated_at', 'created_at']));
        if ($keys === []) {
            return [];
        }
        $labels = [];
        try {
            foreach ($this->form->getFlatFields(withHidden: true) as $field) {
                $labels[$field->getName()] = trim(strip_tags((string) $field->getLabel()));
            }
        } catch (Throwable) {
            // Fall back to the column names.
        }

        return array_values(array_unique(array_map(fn ($key) => ($labels[$key] ?? '') !== '' ? $labels[$key] : $key, $keys)));
    }
}
