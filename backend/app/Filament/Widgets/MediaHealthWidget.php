<?php

namespace App\Filament\Widgets;

use App\Domain\Media\MediaInventory;
use App\Filament\Resources\MediaFiles\MediaFileResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/** Upload folder at a glance: size, files nothing uses, and links to files that are gone. */
class MediaHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Kho media';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return MediaFileResource::canViewAny();
    }

    protected function getStats(): array
    {
        $h = MediaInventory::health();
        $list = MediaFileResource::getUrl();

        return [
            Stat::make('Tệp đã tải lên', Number::format($h['files']))->description(Number::fileSize($h['bytes']))->url($list),
            Stat::make('Không trang nào dùng', Number::format($h['unused']))->description(Number::fileSize($h['unused_bytes']))->color('gray')
                ->url($list.'?tableFilters[used][value]=0'),
            Stat::make('Link ảnh bị hỏng', $h['broken'])->description($h['broken'] > 0 ? 'Trang đang trỏ tới tệp không còn' : 'Không có')
                ->color($h['broken'] > 0 ? 'danger' : 'success')->url($list.'?tableFilters[on_disk][value]=0'),
        ];
    }
}
