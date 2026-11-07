<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ProductDetailController;
use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

// v1 mirrors the PHP endpoints Nuxt reads today (frontend/shared/contracts). Read-only.
Route::prefix('v1')->group(function () {
    Route::get('catalog', CatalogController::class);
    Route::get('home', HomeController::class);
    Route::get('product-detail', ProductDetailController::class);
    Route::get('site', SiteController::class);
});
