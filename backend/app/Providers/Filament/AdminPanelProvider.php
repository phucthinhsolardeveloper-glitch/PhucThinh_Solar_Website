<?php

namespace App\Providers\Filament;

use App\Domain\Routing\PublicUrls;
use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->authGuard('admin')
            ->resourceEditPageRedirect('index')
            ->brandName('Phúc Thịnh Solar')
            ->brandLogo('/thumbs/480x67x3/upload/photo/logo-phucthinh-solar-wordmark-2026.png')
            ->brandLogoHeight('1.75rem')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.admin-head'))
            // "Xem website" in the header, next to the search box (local: SITE_PUBLIC_URL, production: same host).
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('filament.view-site-link', ['url' => PublicUrls::home()]))
            ->favicon('/admin/favicon.png')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups(['Sản phẩm', 'Nội dung', 'Hình ảnh & video', 'Bán hàng', 'Khách hàng', 'Hệ thống'])
            ->sidebarCollapsibleOnDesktop()
            // Bell in the header: new orders, transfer reports, enquiries and reviews (App\Domain\Admin\AdminBell).
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
