<?php

namespace App\Filament\Resources\MediaFiles\Pages;

use App\Domain\Media\MediaInventory;
use App\Filament\Resources\MediaFiles\MediaFileResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMediaFiles extends ListRecords
{
    protected static string $resource = MediaFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')->label('Quét lại')->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $s = (new MediaInventory)->scan();
                    Notification::make()->success()->title('Đã quét '.$s['files'].' tệp')
                        ->body("Mới {$s['new']}, đổi {$s['changed']}, không dùng {$s['unused']}, link hỏng {$s['broken']}.")->send();
                }),
        ];
    }
}
