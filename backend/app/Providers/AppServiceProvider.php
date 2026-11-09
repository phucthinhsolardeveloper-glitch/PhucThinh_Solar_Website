<?php

namespace App\Providers;

use App\Auth\AdminUserProvider;
use App\Auth\PasswordVerifier;
use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Support\SiteSettings;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One copy per request: the tree is read once and shared by every API part.
        $this->app->scoped(CategoryTree::class);
        $this->app->scoped(SiteSettings::class);
        $this->app->singleton(PasswordVerifier::class, fn () => new PasswordVerifier(
            (string) config('site.admin_password.secret'),
            (string) config('site.admin_password.salt'),
        ));
        $this->app->bind(ProductMediaUrls::class, fn () => new ProductMediaUrls(
            rtrim((string) config('site.upload_path'), '/'),
            rtrim((string) config('site.assets_path'), '/'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('admin-users', fn ($app) => new AdminUserProvider($app['hash'], $app->make(PasswordVerifier::class)));
        // Long Vietnamese names wrap instead of widening the table and pushing the row buttons off screen.
        TextColumn::configureUsing(fn (TextColumn $column) => $column->wrap());
        // Vietnamese day/month order everywhere, not the browser's US mm/dd/yyyy.
        DatePicker::configureUsing(fn (DatePicker $field) => $field->native(false)->displayFormat('d/m/Y')->firstDayOfWeek(1));
        DateTimePicker::configureUsing(fn (DateTimePicker $field) => $field->native(false)->displayFormat('d/m/Y H:i')->firstDayOfWeek(1));
    }
}
