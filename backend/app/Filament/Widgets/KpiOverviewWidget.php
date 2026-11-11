<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\WorkQueuePage;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The four numbers the owner looks for first, each against the same days of the period before, with a
 * 14-day sparkline. Cancelled orders and spam are left out everywhere.
 */
class KpiOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = [];
        $monthStart = now()->startOfMonth();
        $sameDayLastMonth = now()->subMonthNoOverflow();
        if (OrderResource::canViewAny()) {
            $revenue = $this->sum('orders', 'total', $monthStart, now(), fn ($q) => $q->where('status', '!=', 'cancelled'));
            $before = $this->sum('orders', 'total', $sameDayLastMonth->copy()->startOfMonth(), $sameDayLastMonth, fn ($q) => $q->where('status', '!=', 'cancelled'));
            $stats[] = Stat::make('Doanh số tháng '.now()->format('n'), number_format($revenue, 0, ',', '.').'đ')
                ->description($this->versus($revenue, $before, 'cùng kỳ tháng trước'))->descriptionIcon($revenue >= $before ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenue >= $before ? 'success' : 'danger')->chart($this->daily('orders', 'sum(total)', fn ($q) => $q->where('status', '!=', 'cancelled')))
                ->url(OrderResource::getUrl());
            $orders = $this->count('orders', now()->subDays(29)->startOfDay(), now(), fn ($q) => $q->where('status', '!=', 'cancelled'));
            $waiting = DB::table('orders')->where('status', 'new')->count();
            $stats[] = Stat::make('Đơn hàng 30 ngày', number_format($orders, 0, ',', '.'))
                ->description($waiting > 0 ? $waiting.' đơn mới đang chờ xử lý' : 'Không có đơn chờ xử lý')
                ->descriptionIcon($waiting > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')->color($waiting > 0 ? 'warning' : 'success')
                ->chart($this->daily('orders', 'count(*)', fn ($q) => $q->where('status', '!=', 'cancelled')))
                ->url($waiting > 0 ? OrderResource::getUrl().'?tableFilters[status][value]=new' : OrderResource::getUrl());
        }
        if (LeadResource::canViewAny()) {
            $leads = $this->count('leads', now()->subDays(29)->startOfDay(), now(), fn ($q) => $q->where('status', '!=', 'spam'));
            $uncalled = DB::table('leads')->where('status', 'new')->count();
            $stats[] = Stat::make('Khách để lại liên hệ 30 ngày', number_format($leads, 0, ',', '.'))
                ->description($uncalled > 0 ? $uncalled.' khách chưa được gọi lại' : 'Đã gọi lại hết')
                ->descriptionIcon($uncalled > 0 ? 'heroicon-m-phone' : 'heroicon-m-check-circle')->color($uncalled > 0 ? 'warning' : 'success')
                ->chart($this->daily('leads', 'count(*)', fn ($q) => $q->where('status', '!=', 'spam')))
                ->url(LeadResource::getUrl().($uncalled > 0 ? '?tableFilters[status][value]=new' : ''));
        }
        $views = (int) DB::table('page_views')->where('day', '>=', now()->subDays(6)->toDateString())->count();
        $viewsBefore = (int) DB::table('page_views')->whereBetween('day', [now()->subDays(13)->toDateString(), now()->subDays(7)->toDateString()])->count();
        $visitors = (int) (DB::table('page_views')->where('day', '>=', now()->subDays(6)->toDateString())->selectRaw('count(distinct day, visitor) as n')->value('n') ?? 0);
        $stats[] = Stat::make('Lượt xem website 7 ngày', number_format($views, 0, ',', '.'))
            ->description(number_format($visitors, 0, ',', '.').' người xem · '.$this->versus($views, $viewsBefore, '7 ngày trước'))
            ->descriptionIcon('heroicon-m-eye')->color('gray')
            ->chart($this->dailyViews());

        $user = auth('admin')->user();
        if ($user instanceof User && ($todo = WorkQueuePage::getNavigationBadge()) !== null) {
            $stats[] = Stat::make('Việc đang chờ', $todo)->description('Đơn, liên hệ, bài chờ duyệt · bấm để mở')
                ->descriptionIcon('heroicon-m-inbox-stack')->color('warning')->url(WorkQueuePage::getUrl());
        }

        return $stats;
    }

    protected function getColumns(): int|array
    {
        return ['@sm' => 2, '@xl' => count($this->getCachedStats()) > 4 ? 5 : 4];
    }

    private function versus(float $now, float $before, string $what): string
    {
        if ($before <= 0) {
            return $now > 0 ? 'chưa có số '.$what : 'chưa có số liệu';
        }
        $pct = (int) round(($now - $before) / $before * 100);

        return ($pct >= 0 ? '+' : '').$pct.'% so với '.$what;
    }

    private function sum(string $table, string $column, Carbon $from, Carbon $until, callable $scope): float
    {
        return (float) DB::table($table)->whereBetween('created_at', [$from, $until])->tap($scope)->sum($column);
    }

    private function count(string $table, Carbon $from, Carbon $until, callable $scope): int
    {
        return DB::table($table)->whereBetween('created_at', [$from, $until])->tap($scope)->count();
    }

    /** @return list<float> last 14 days, oldest first */
    private function daily(string $table, string $aggregate, callable $scope): array
    {
        $rows = DB::table($table)->where('created_at', '>=', now()->subDays(13)->startOfDay())->tap($scope)
            ->selectRaw("date(created_at) as d, {$aggregate} as v")->groupBy('d')->pluck('v', 'd');

        return array_map(fn ($i) => (float) ($rows[now()->subDays(13 - $i)->toDateString()] ?? 0), range(0, 13));
    }

    /** @return list<float> */
    private function dailyViews(): array
    {
        $rows = DB::table('page_views')->where('day', '>=', now()->subDays(13)->toDateString())->selectRaw('day as d, count(*) as v')->groupBy('d')->pluck('v', 'd');

        return array_map(fn ($i) => (float) ($rows[now()->subDays(13 - $i)->toDateString()] ?? 0), range(0, 13));
    }
}
