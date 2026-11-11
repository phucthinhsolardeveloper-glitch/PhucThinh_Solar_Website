<?php

namespace App\Filament\Support;

use App\Domain\Routing\PublicStatus;
use App\Domain\Routing\PublicUrls;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** "Trên website": the real public state of a row (PublicStatus), linking to the public page. */
final class PublicStatusColumn
{
    /** @param bool $besideSwitch true when a "Hiển thị" switch sits next to it: "Đang ẩn" would only repeat the switch */
    public static function make(bool $besideSwitch = false): TextColumn
    {
        return TextColumn::make('public_status')->label('Trên website')->badge()->placeholder('—')
            ->state(fn (Model $record) => $besideSwitch && PublicStatus::of($record)['key'] === PublicStatus::HIDDEN ? null : PublicStatus::of($record)['label'])
            ->icon(fn (Model $record) => PublicStatus::of($record)['key'] !== PublicStatus::HIDDEN && PublicUrls::path($record) !== null ? 'heroicon-m-arrow-top-right-on-square' : null)
            ->iconPosition('after')
            ->color(fn (Model $record) => PublicStatus::of($record)['color'])
            ->tooltip(fn (Model $record) => PublicStatus::of($record)['reason'])
            ->url(fn (Model $record) => PublicUrls::path($record) !== null ? PublicUrls::url($record) : null, shouldOpenInNewTab: true);
    }
}
