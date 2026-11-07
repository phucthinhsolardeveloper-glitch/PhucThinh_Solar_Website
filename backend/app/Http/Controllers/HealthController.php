<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness plus one read on each connection: the Laravel tables (lv_*) and the PHP-era tables (table_*).
 * It never writes, so it is safe to call from any monitor.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (Throwable) {
            $checks['database'] = 'unavailable';
        }
        try {
            $checks['legacy_products'] = DB::connection('legacy')->table('table_product')->count();
        } catch (Throwable) {
            $checks['legacy_products'] = 'unavailable';
        }
        $healthy = $checks['database'] === 'ok' && is_int($checks['legacy_products']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
