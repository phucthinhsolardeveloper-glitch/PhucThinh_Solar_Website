<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The public API keeps query values exactly as sent, like the PHP endpoints it replaces:
        // "?q=" stays an empty string and spaces are not trimmed before validation.
        $isApi = fn (Request $request) => $request->is('api/*');
        $middleware->convertEmptyStringsToNull(except: [$isApi]);
        $middleware->trimStrings(except: [$isApi]);
        // Caddy and the Nuxt server sit in front; the visitor IP (rate limits) comes from X-Forwarded-For.
        $middleware->trustProxies(at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1'))));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
