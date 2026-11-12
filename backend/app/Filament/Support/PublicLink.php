<?php

namespace App\Filament\Support;

use App\Domain\Content\PostRevisions;
use App\Domain\Routing\PublicUrls;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens the public page for a live record, or a short-lived signed revision preview for a Post that is not live.
 */
final class PublicLink
{
    public static function action(string $name = 'viewOnSite'): Action
    {
        return Action::make($name)
            ->label(fn (?Model $record) => self::isPreview($record) ? 'Xem trước bản nháp' : 'Xem trên website')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (?Model $record) => self::url($record), shouldOpenInNewTab: true)
            ->action(function (?Model $record) {
                if (! $record instanceof Post || ! self::isPreview($record)) {
                    return;
                }
                $draft = $record->revisions()->where('state', 'draft')->first()
                    ?? app(PostRevisions::class)->draft($record, AdminActor::current());

                return redirect()->away(app(PostRevisions::class)->previewUrl($draft));
            })
            ->visible(fn (?Model $record) => $record !== null && PublicUrls::path($record) !== null)
            ->tooltip(fn (?Model $record) => $record && ! PublicUrls::isLive($record)
                ? 'Mở bản xem trước có chữ ký, không làm thay đổi trạng thái xuất bản.'
                : 'Mở trang công khai ở tab mới');
    }

    /** Table row: icon only, on every row (greyed out when the record has no page), so row buttons line up. */
    public static function row(): Action
    {
        return self::action()->iconButton()->visible(true)
            ->disabled(fn (?Model $record) => $record === null || PublicUrls::path($record) === null);
    }

    private static function isPreview(?Model $record): bool
    {
        return $record instanceof Post && ! PublicUrls::isLive($record);
    }

    private static function url(?Model $record): ?string
    {
        if (! $record || ! self::isPreview($record)) {
            return $record ? PublicUrls::url($record) : null;
        }

        $draft = $record->revisions()->where('state', 'draft')->first();

        return $draft ? app(PostRevisions::class)->previewUrl($draft) : null;
    }
}
