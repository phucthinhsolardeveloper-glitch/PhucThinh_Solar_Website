<?php

namespace App\Providers;

use App\Legacy\Auth\LegacyAdminUserProvider;
use App\Legacy\Auth\LegacyPasswordVerifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LegacyPasswordVerifier::class, fn () => new LegacyPasswordVerifier(
            (string) config('legacy.admin_password.secret'),
            (string) config('legacy.admin_password.salt'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('legacy-admin', fn ($app) => new LegacyAdminUserProvider($app->make(LegacyPasswordVerifier::class)));
    }
}
