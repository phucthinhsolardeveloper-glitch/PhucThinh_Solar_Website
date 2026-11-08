<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// No session, cookies or CSRF here: these answers are cached by browsers and proxies.

Route::get('/health', HealthController::class)->name('health');

Route::get('/admin/favicon.png', fn () => response()->file(public_path('favicon.png'), [
    'Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400',
]));

// Uploaded files and on-demand thumbnails of uploads and site pictures.
Route::get('/upload/{path}', [MediaController::class, 'file'])->where('path', '.+');
Route::get('/thumbs/{width}x{height}x{mode}/{path}', [MediaController::class, 'thumb'])
    ->where(['width' => '\d{1,4}', 'height' => '\d{1,4}', 'mode' => '[1-4]', 'path' => '.+']);

// Canonical addresses of every public page, for search engines.
Route::get('/sitemap.xml', SitemapController::class);
