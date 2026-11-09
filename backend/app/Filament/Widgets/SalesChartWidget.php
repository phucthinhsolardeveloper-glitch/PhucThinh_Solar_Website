<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Periods;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Orders placed and their value, cancelled orders left out. */
class SalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Đơn hàng và doanh số';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30d';

    public static function canView(): bool
    {
        return OrderResource::canViewAny();
    }

    protected function getFilters(): ?array
    {
        return ['30d' => '30 ngày', '12w' => '12 tuần', '12m' => '12 tháng'];
    }

    public function getDescription(): ?string
    {
        $period = Periods::make((string) $this->filter);
        $now = $this->totals($period['from'], null);
        $before = $this->totals(Periods::previousFrom($period), $period['from']);
        $change = Periods::change($now['total'], $before['total']);

        return $now['orders'].' đơn · '.number_format($now['total'], 0, ',', '.').'đ'.($change !== '' ? ' · '.$change : '');
    }

    protected function getData(): array
    {
        $period = Periods::make((string) $this->filter);
        $rows = DB::table('orders')->where('status', '!=', 'cancelled')->where('created_at', '>=', $period['from'])
            ->selectRaw($period['sql'].' as bucket, count(*) as orders, sum(total) as total')->groupBy('bucket')->get()->keyBy('bucket');

        return [
            'labels' => $period['labels'],
            'datasets' => [
                ['type' => 'line', 'label' => 'Doanh số (triệu đ)', 'yAxisID' => 'money', 'data' => Periods::fill($period, $rows->map(fn ($r) => round((float) $r->total / 1_000_000, 1))->all()),
                    'borderColor' => Periods::GOLD, 'backgroundColor' => Periods::GOLD, 'tension' => 0.3, 'pointRadius' => 2],
                ['label' => 'Số đơn', 'yAxisID' => 'count', 'data' => Periods::fill($period, $rows->map(fn ($r) => (int) $r->orders)->all()),
                    'backgroundColor' => 'rgba(22,54,95,0.75)', 'borderRadius' => 3],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['display' => false], 'count' => ['position' => 'left', 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'money' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]]]];
    }

    /** @return array{orders: int, total: float} */
    private function totals(\DateTimeInterface $from, ?\DateTimeInterface $until): array
    {
        $row = DB::table('orders')->where('status', '!=', 'cancelled')->where('created_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('created_at', '<', $until))->selectRaw('count(*) as orders, coalesce(sum(total), 0) as total')->first();

        return ['orders' => (int) ($row->orders ?? 0), 'total' => (float) ($row->total ?? 0)];
    }
}
