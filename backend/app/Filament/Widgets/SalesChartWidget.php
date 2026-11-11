<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Periods;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Orders placed and their value, cancelled orders left out. */
class SalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Doanh số';

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
                ['label' => 'Doanh số (triệu đ)', 'data' => Periods::fill($period, $rows->map(fn ($r) => round((float) $r->total / 1_000_000, 2))->all()),
                    'backgroundColor' => Periods::NAVY, 'borderRadius' => 4, 'maxBarThickness' => 18],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        // One measure on one axis: the order count is in the description, never on a second scale.
        return ['plugins' => ['legend' => ['display' => false]], 'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'triệu đồng'], 'grid' => ['color' => 'rgba(128,128,128,0.12)']],
                'x' => ['grid' => ['display' => false], 'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 12]]]];
    }

    /** @return array{orders: int, total: float} */
    private function totals(\DateTimeInterface $from, ?\DateTimeInterface $until): array
    {
        $row = DB::table('orders')->where('status', '!=', 'cancelled')->where('created_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('created_at', '<', $until))->selectRaw('count(*) as orders, coalesce(sum(total), 0) as total')->first();

        return ['orders' => (int) ($row->orders ?? 0), 'total' => (float) ($row->total ?? 0)];
    }
}
