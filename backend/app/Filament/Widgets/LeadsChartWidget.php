<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Periods;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Contact, consultation and dealer requests per day or week, spam left out. */
class LeadsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Khách để lại liên hệ';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30d';

    public static function canView(): bool
    {
        return LeadResource::canViewAny();
    }

    protected function getFilters(): ?array
    {
        return ['30d' => '30 ngày', '12w' => '12 tuần', '12m' => '12 tháng'];
    }

    public function getDescription(): ?string
    {
        $period = Periods::make((string) $this->filter);
        $count = fn ($from, $until = null) => DB::table('leads')->where('status', '!=', 'spam')->where('created_at', '>=', $from)
            ->when($until, fn ($q) => $q->where('created_at', '<', $until))->count();
        $now = $count($period['from']);
        $change = Periods::change($now, $count(Periods::previousFrom($period), $period['from']));

        return $now.' yêu cầu'.($change !== '' ? ' · '.$change : '');
    }

    protected function getData(): array
    {
        $period = Periods::make((string) $this->filter);
        $rows = DB::table('leads')->where('status', '!=', 'spam')->where('created_at', '>=', $period['from'])
            ->selectRaw($period['sql'].' as bucket, kind, count(*) as n')->groupBy('bucket', 'kind')->get();
        $datasets = [];
        foreach (array_keys(LeadResource::KINDS) as $i => $kind) {
            $datasets[] = ['label' => LeadResource::KINDS[$kind], 'backgroundColor' => Periods::PALETTE[$i], 'borderRadius' => 3,
                'data' => Periods::fill($period, $rows->where('kind', $kind)->mapWithKeys(fn ($r) => [$r->bucket => (int) $r->n])->all())];
        }

        return ['labels' => $period['labels'], 'datasets' => $datasets];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
