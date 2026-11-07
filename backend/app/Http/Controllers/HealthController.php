<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness plus one database read. It never writes, so it is safe to call from any monitor.
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
            $checks['products'] = DB::table('products')->count();
        } catch (Throwable) {
            $checks['products'] = 'unavailable';
        }
        $healthy = $checks['database'] === 'ok' && is_int($checks['products']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
