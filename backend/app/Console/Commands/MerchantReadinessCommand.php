<?php

namespace App\Console\Commands;

use App\Domain\Catalog\MerchantFeed;
use App\Domain\Catalog\MerchantReadinessReport;
use Illuminate\Console\Command;

/**
 * Read-only Merchant Center readiness report: per visible product, why it is outside the legacy/verified feed and
 * which facts (code, price, brand, verified availability, GTIN/MPN) staff still have to supply from real sources.
 * Nothing is written to the database.
 */
class MerchantReadinessCommand extends Command
{
    protected $signature = 'pts:merchant-readiness
        {--csv= : Write every product row to this CSV file (UTF-8 with BOM, opens in Excel)}
        {--only-blocked : List only products that still need data}';

    protected $description = 'Merchant feed readiness per product (read-only).';

    public function handle(MerchantFeed $feed): int
    {
        $report = $feed->readiness((string) config('site.canonical_origin'));
        $rows = $this->option('only-blocked') ? array_values(array_filter($report, fn (array $r) => $r['needs'] !== [])) : $report;

        $count = fn (string $need) => count(array_filter($report, fn (array $r) => in_array($need, $r['needs'], true)));
        $legacyIn = count(array_filter($report, fn (array $r) => $r['legacy'] === null));
        $verifiedIn = count(array_filter($report, fn (array $r) => $r['verified'] === null));
        $this->info('Sản phẩm hiển thị: '.count($report).' | feed hiện hành tính lại từ DB: '.$legacyIn.' | feed đã xác minh: '.$verifiedIn
            .' (ngưỡng phát hành '.(int) config('services.merchant_feed.verified_minimum_items', 286).'). Bản public có thể đang là cache tối đa 1 giờ.');
        $this->table(['Cần bổ sung', 'Số sản phẩm'], array_map(fn (string $need) => [MerchantReadinessReport::NEEDS[$need], $count($need)], array_keys(MerchantReadinessReport::NEEDS)));

        $path = (string) $this->option('csv');
        if ($path !== '') {
            $out = fopen($path, 'wb');
            if ($out === false) {
                $this->error('Không ghi được '.$path);

                return self::FAILURE;
            }
            MerchantReadinessReport::write($out, $rows);
            fclose($out);
            $this->info('Đã ghi '.count($rows).' dòng vào '.$path);
        }

        return self::SUCCESS;
    }
}
