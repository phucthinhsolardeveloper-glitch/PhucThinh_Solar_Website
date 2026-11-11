<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Periods;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Pages seen and people visiting the public site, from the storefront's own counter. */
class TrafficChartWidget extends ChartWidget
{
    protected ?string $heading = 'Lượt truy cập website';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $filter = '7d';

    protected function getFilters(): ?array
    {
        return ['7d' => '7 ngày', '30d' => '30 ngày', '90d' => '90 ngày', '12w' => '12 tuần', '12m' => '12 tháng'];
    }

    public function getDescription(): ?string
    {
        $period = Periods::make((string) $this->filter, 'day');
        $now = $this->totals($period['from']->toDateString(), null);
        $before = $this->totals(Periods::previousFrom($period)->toDateString(), $period['from']->toDateString());
        $change = Periods::change($now['visitors'], $before['visitors']);

        return number_format($now['views'], 0, ',', '.').' lượt xem trang · '.number_format($now['visitors'], 0, ',', '.').' người xem'.($change !== '' ? ' · '.$change : '');
    }

    protected function getData(): array
    {
        $period = Periods::make((string) $this->filter, 'day');
        $rows = DB::table('page_views')->where('day', '>=', $period['from']->toDateString())
            ->selectRaw($period['sql'].' as bucket, count(*) as views, count(distinct day, visitor) as visitors')
            ->groupBy('bucket')->get()->keyBy('bucket');

        return [
            'labels' => $period['labels'],
            'datasets' => [
                ['label' => 'Người xem', 'data' => Periods::fill($period, $rows->map(fn ($r) => (int) $r->visitors)->all()),
                    'borderColor' => Periods::NAVY, 'backgroundColor' => Periods::NAVY, 'borderWidth' => 2, 'tension' => 0, 'pointRadius' => 3],
                ['label' => 'Lượt xem trang', 'data' => Periods::fill($period, $rows->map(fn ($r) => (int) $r->views)->all()),
                    'borderColor' => Periods::GOLD, 'backgroundColor' => Periods::GOLD, 'borderWidth' => 2, 'tension' => 0, 'pointRadius' => 3],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true]]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(128,128,128,0.12)']], 'x' => ['grid' => ['display' => false]]],
            'interaction' => ['mode' => 'index', 'intersect' => false]];
    }

    /** @return array{views: int, visitors: int} */
    private function totals(string $from, ?string $until): array
    {
        $row = DB::table('page_views')->where('day', '>=', $from)->when($until, fn ($q) => $q->where('day', '<', $until))
            ->selectRaw('count(*) as views, count(distinct day, visitor) as visitors')->first();

        return ['views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
    }
}
