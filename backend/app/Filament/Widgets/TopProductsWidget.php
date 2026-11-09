<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Periods;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/** Products ordered most (by quantity) in the last 90 days, cancelled orders left out. */
class TopProductsWidget extends ChartWidget
{
    protected ?string $heading = 'Sản phẩm bán chạy';

    protected ?string $description = '90 ngày qua, theo số lượng đặt';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return OrderResource::canViewAny();
    }

    protected function getData(): array
    {
        $rows = DB::table('order_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
            ->where('o.status', '!=', 'cancelled')->where('o.created_at', '>=', now()->subDays(90))
            ->selectRaw('max(i.name) as name, sum(i.quantity) as qty')
            ->groupByRaw('coalesce(i.product_id, i.name)')->orderByDesc('qty')->limit(8)->get();

        return [
            'labels' => $rows->map(fn ($r) => mb_strimwidth((string) $r->name, 0, 70, '…'))->all(),
            'datasets' => [['label' => 'Số lượng', 'data' => $rows->map(fn ($r) => (int) $r->qty)->all(), 'backgroundColor' => Periods::NAVY, 'borderRadius' => 3]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
