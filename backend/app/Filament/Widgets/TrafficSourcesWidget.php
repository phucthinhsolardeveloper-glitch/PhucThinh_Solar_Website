<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\PageViews;
use App\Domain\Analytics\Periods;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Where visits start (first page of each visit) and which device they use, last 30 days. */
class TrafficSourcesWidget extends ChartWidget
{
    protected ?string $heading = 'Khách đến từ đâu';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '260px';

    public function getDescription(): ?string
    {
        $devices = DB::table('page_views')->where('day', '>=', now()->subDays(29)->toDateString())->where('source', '!=', 'internal')
            ->selectRaw('device, count(*) as n')->groupBy('device')->pluck('n', 'device');
        $total = max(1, (int) $devices->sum());

        return '30 ngày qua · '.collect(PageViews::DEVICES)->map(fn ($label, $key) => $label.' '.round((int) ($devices[$key] ?? 0) / $total * 100).'%')->implode(' · ');
    }

    protected function getData(): array
    {
        $rows = DB::table('page_views')->where('day', '>=', now()->subDays(29)->toDateString())->where('source', '!=', 'internal')
            ->selectRaw('source, count(*) as n')->groupBy('source')->orderByDesc('n')->pluck('n', 'source');

        return [
            'labels' => $rows->keys()->map(fn ($key) => PageViews::SOURCES[$key] ?? $key)->all(),
            'datasets' => [['data' => $rows->values()->map(fn ($n) => (int) $n)->all(), 'backgroundColor' => array_slice(Periods::PALETTE, 0, $rows->count()), 'borderWidth' => 0]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'right']], 'cutout' => '62%', 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
