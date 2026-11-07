<?php

namespace App\Filament\Widgets;

use App\Domain\Catalog\Health\CatalogHealth;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/** Admin home: how many products have each problem, refreshed every ten minutes. */
class CatalogHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Sức khỏe dữ liệu sản phẩm';

    protected function getStats(): array
    {
        $issues = Cache::remember('catalog-health', 600, fn () => app(CatalogHealth::class)->run());
        $stats = [];
        foreach (CatalogHealth::CHECKS as $key => [$severity, $label]) {
            $count = count($issues[$key]);
            $stats[] = Stat::make($label, $count)
                ->color($count === 0 ? 'success' : ($severity === 'error' ? 'danger' : ($severity === 'warning' ? 'warning' : 'gray')))
                ->description($count === 0 ? 'Không có' : 'Cần xử lý');
        }

        return $stats;
    }
}
