<?php

use App\Domain\Analytics\PageViews;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lost or stalled outbox mail is re-dispatched; the worker itself retries with backoff.
Schedule::command('pts:outbox --sweep')->everyFiveMinutes()->withoutOverlapping();

// Review uploads left behind by an interrupted submission; the media inventory is refreshed nightly.
Schedule::command('pts:media --prune-staging')->hourly()->withoutOverlapping();
Schedule::command('pts:media --scan')->dailyAt('03:30')->withoutOverlapping();

// Page view rows older than about 13 months are deleted.
Schedule::call(fn () => PageViews::prune())->name('page-views-prune')->dailyAt('03:50')->withoutOverlapping();
// Data-quality counts per day, for the trend on "Chất lượng dữ liệu".
Schedule::command('pts:data-quality --record')->dailyAt('04:10')->withoutOverlapping();
