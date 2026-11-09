<?php

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
