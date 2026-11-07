<?php

namespace App\Providers;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Legacy\Auth\LegacyAdminUserProvider;
use App\Legacy\Auth\LegacyPasswordVerifier;
use App\Legacy\Catalog\ProductImageResolver;
use App\Legacy\Settings\LegacySettings;
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
        $this->app->singleton(LegacySettings::class);
        $this->app->bind(ProductMediaUrls::class, fn () => new ProductMediaUrls(rtrim((string) config('legacy.upload_path'), '/')));
        $this->app->bind(ProductImageResolver::class, fn () => new ProductImageResolver(
            rtrim((string) config('legacy.upload_path'), '/').'/product',
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
