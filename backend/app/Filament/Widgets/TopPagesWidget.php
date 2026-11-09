<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/** Pages people look at most, last 30 days, with a bar for comparison. */
class TopPagesWidget extends Widget
{
    protected string $view = 'filament.widgets.top-pages';

    protected static ?int $sort = 6;

    /** @return list<array{path: string, url: string, views: int, visitors: int, share: float}> */
    public function rows(): array
    {
        $rows = DB::table('page_views')->where('day', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('path, count(*) as views, count(distinct day, visitor) as visitors')->groupBy('path')->orderByDesc('views')->limit(10)->get();
        $max = max(1, (int) ($rows->first()->views ?? 1));
        $base = rtrim((string) config('app.url'), '/');

        return $rows->map(fn ($r) => ['path' => urldecode((string) $r->path), 'url' => $base.$r->path, 'views' => (int) $r->views,
            'visitors' => (int) $r->visitors, 'share' => round((int) $r->views / $max * 100, 1)])->all();
    }
}
