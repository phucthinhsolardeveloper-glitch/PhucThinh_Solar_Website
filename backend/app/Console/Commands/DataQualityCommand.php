<?php

namespace App\Console\Commands;

use App\Domain\Admin\DataQuality;
use Illuminate\Console\Command;

/** Prints the data-quality report; --record also stores today's counts for the trend on the Admin page (nightly). */
class DataQualityCommand extends Command
{
    protected $signature = 'pts:data-quality {--record : Store today\'s counts}';

    protected $description = 'Data-quality report of products, posts and SEO (read-only unless --record).';

    public function handle(DataQuality $quality): int
    {
        if ($this->option('record')) {
            $this->info('Đã ghi '.$quality->record().' số liệu cho hôm nay.');
        }
        $this->table(['Nhóm', 'Kiểm tra', 'Mức', 'Số lượng'], array_map(fn ($c) => [$c['group'], $c['label'], $c['severity'], $c['count']], $quality->report()));

        return self::SUCCESS;
    }
}
