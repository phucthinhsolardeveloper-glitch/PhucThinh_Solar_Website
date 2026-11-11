<?php

namespace App\Filament\Support;

use App\Domain\Routing\PublicStatus;
use App\Domain\Routing\PublicUrls;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** "Trên website": the real public state of a row (PublicStatus), linking to the public page. */
final class PublicStatusColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('public_status')->label('Trên website')->badge()
            ->state(fn (Model $record) => PublicStatus::of($record)['label'])
            ->color(fn (Model $record) => PublicStatus::of($record)['color'])
            ->tooltip(fn (Model $record) => PublicStatus::of($record)['reason'])
            ->url(fn (Model $record) => PublicUrls::path($record) !== null ? PublicUrls::url($record) : null, shouldOpenInNewTab: true);
    }
}
