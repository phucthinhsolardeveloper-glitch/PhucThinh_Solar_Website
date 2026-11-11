<?php

namespace App\Filament\Support;

use App\Domain\Routing\PublicUrls;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * "Xem trên website": opens the public page that shows this record, in a new tab. Used as a table row action and
 * as an edit-page header action. A hidden record still gets the link, with a note that the page will say "not found".
 */
final class PublicLink
{
    public static function action(string $name = 'viewOnSite'): Action
    {
        return Action::make($name)
            ->label('Xem trên website')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (?Model $record) => $record ? PublicUrls::url($record) : null, shouldOpenInNewTab: true)
            ->visible(fn (?Model $record) => $record !== null && PublicUrls::path($record) !== null)
            ->tooltip(fn (?Model $record) => $record && ! PublicUrls::isLive($record)
                ? 'Mục này đang ẩn (hoặc danh mục chưa có sản phẩm hiện) nên trang sẽ báo không tìm thấy.'
                : 'Mở trang công khai ở tab mới');
    }

    /** Table row: icon only, on every row (greyed out when the record has no page), so row buttons line up. */
    public static function row(): Action
    {
        return self::action()->iconButton()->visible(true)
            ->disabled(fn (?Model $record) => $record === null || PublicUrls::path($record) === null);
    }
}
