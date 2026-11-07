<?php

use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PagesController;
use App\Http\Controllers\Api\V1\ProductDetailController;
use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

// v1 mirrors the PHP endpoints Nuxt reads today (frontend/shared/contracts). Read-only.
Route::prefix('v1')->group(function () {
    Route::get('about', AboutController::class);
    Route::get('catalog', CatalogController::class);
    Route::get('contact', [PagesController::class, 'contact']);
    Route::get('dealer', [PagesController::class, 'dealer']);
    Route::get('home', HomeController::class);
    Route::get('news', [PagesController::class, 'news']);
    Route::get('news-detail', [PagesController::class, 'newsDetail']);
    Route::get('policy-detail', [PagesController::class, 'policyDetail']);
    Route::get('project-detail', [PagesController::class, 'projectDetail']);
    Route::get('projects', [PagesController::class, 'projects']);
    Route::get('product-detail', ProductDetailController::class);
    Route::get('site', SiteController::class);
});
