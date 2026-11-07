<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Health\CatalogHealth;
use Illuminate\Console\Command;

class DataHealthCommand extends Command
{
    protected $signature = 'pts:data-health {--json : Print the full report as JSON} {--limit=5 : Examples shown per check}';

    protected $description = 'Report catalog data a visitor would see as broken (read-only).';

    public function handle(CatalogHealth $health): int
    {
        $issues = $health->run();
        if ($this->option('json')) {
            $this->line((string) json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $rows = [];
        foreach (CatalogHealth::CHECKS as $key => [$severity, $label]) {
            $examples = array_slice($issues[$key], 0, (int) $this->option('limit'));
            $rows[] = [$severity, $label, count($issues[$key]), implode('; ', array_map(fn ($r) => '#'.$r['id'].' '.mb_strimwidth($r['name'], 0, 40, '…'), $examples))];
        }
        $this->table(['Mức', 'Kiểm tra', 'Số lượng', 'Ví dụ'], $rows);

        $errors = array_sum(array_map(fn ($key) => CatalogHealth::CHECKS[$key][0] === 'error' ? count($issues[$key]) : 0, array_keys($issues)));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
