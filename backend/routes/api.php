<?php

use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\ArticleDraftPreviewController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PagesController;
use App\Http\Controllers\Api\V1\ProductDetailController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\NtlWebhookController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// v1: the payloads Nuxt reads (frontend/shared/contracts).
Route::prefix('v1')->group(function () {
    Route::get('article-draft-preview/{revision}', ArticleDraftPreviewController::class)
        ->middleware(['signed:relative', 'throttle:30,1'])->name('article-draft.preview');
    Route::get('about', AboutController::class);
    Route::get('brand-detail', [PagesController::class, 'brandDetail']);
    Route::get('catalog', CatalogController::class);
    Route::get('checkout-wards', [PagesController::class, 'checkoutWards']);
    Route::get('contact', [PagesController::class, 'contact']);
    Route::get('dealer', [PagesController::class, 'dealer']);
    Route::get('gallery', [PagesController::class, 'gallery']);
    Route::get('gallery-detail', [PagesController::class, 'galleryDetail']);
    Route::get('home', HomeController::class);
    Route::get('lead-form', [PagesController::class, 'leadForm']);
    Route::post('lead-form', [PagesController::class, 'submitLead']);
    Route::get('news', [PagesController::class, 'news']);
    Route::get('news-detail', [PagesController::class, 'newsDetail']);
    Route::get('policy-detail', [PagesController::class, 'policyDetail']);
    Route::get('project-detail', [PagesController::class, 'projectDetail']);
    Route::get('product-extras', [PagesController::class, 'productExtras']);
    Route::post('review', [PagesController::class, 'submitReview']);
    Route::get('projects', [PagesController::class, 'projects']);
    Route::get('product-detail', ProductDetailController::class);
    Route::get('solution-detail', [PagesController::class, 'solutionDetail']);
    Route::get('solutions', [PagesController::class, 'solutions']);
    Route::get('video', [PagesController::class, 'video']);
    Route::get('public-route-resolve', [PagesController::class, 'publicRouteResolve']);
    Route::get('redirect', [PagesController::class, 'redirect']);
    Route::get('search', [PagesController::class, 'search']);
    Route::get('site', SiteController::class);

    // Cart and checkout keep state in the visitor's session (cookie).
    Route::middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class])->group(function () {
        Route::get('cart', [ShopController::class, 'cart']);
        Route::post('cart', [ShopController::class, 'mutateCart']);
        Route::get('checkout', [ShopController::class, 'checkout']);
        Route::post('checkout', [ShopController::class, 'placeOrder']);
        Route::post('shipping-quote', [ShopController::class, 'shippingQuote']);
        Route::get('checkout-notice', [ShopController::class, 'notice']);
        Route::get('order-thanks', [ShopController::class, 'thanks']);
        Route::post('order-thanks/transferred', [ShopController::class, 'reportTransfer'])->middleware('throttle:10,1');
        Route::get('payment/vnpay', [ShopController::class, 'vnpay']);
    });
});

// Nhất Tín pushes waybill status here (token set in their portal).
Route::post('integrations/ntl/webhook', NtlWebhookController::class)->middleware('throttle:120,1');
