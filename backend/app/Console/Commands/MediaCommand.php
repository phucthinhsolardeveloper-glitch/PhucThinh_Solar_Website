<?php

namespace App\Console\Commands;

use App\Domain\Media\MediaInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Number;

/** Media inventory: scan uploads and their usage, clear abandoned review uploads, print the health. */
class MediaCommand extends Command
{
    protected $signature = 'pts:media
        {--scan : Re-read the uploads folder and every place that points at a file}
        {--prune-staging : Remove review upload batches that were never saved}
        {--hours=24 : Age after which a review upload batch counts as abandoned}';

    protected $description = 'Media inventory: size, type, dimensions, hash and usage of uploaded files; broken references.';

    public function handle(): int
    {
        $inventory = new MediaInventory;
        if ($this->option('prune-staging')) {
            $this->info('Review upload batches removed: '.$inventory->pruneStaging((int) $this->option('hours')));
        }
        if ($this->option('scan')) {
            $stats = $inventory->scan();
            $this->table(['Tệp', 'Mới', 'Đổi', 'Không dùng', 'Tham chiếu hỏng', 'Đã bỏ'], [array_values($stats)]);
        }
        $h = MediaInventory::health();
        $this->table(['Tệp', 'Dung lượng', 'Không dùng', 'Dung lượng không dùng', 'Tham chiếu hỏng', 'Nhóm trùng', 'Quét lúc'],
            [[$h['files'], Number::fileSize($h['bytes']), $h['unused'], Number::fileSize($h['unused_bytes']), $h['broken'], $h['duplicates'], $h['scanned_at'] ?? '—']]);

        return self::SUCCESS;
    }
}
