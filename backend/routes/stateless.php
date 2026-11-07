<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// No session, cookies or CSRF here: these answers are cached by browsers and proxies.

Route::get('/health', HealthController::class)->name('health');

// Uploaded files and on-demand thumbnails of uploads and site pictures.
Route::get('/upload/{path}', [MediaController::class, 'file'])->where('path', '.+');
Route::get('/thumbs/{width}x{height}x{mode}/{path}', [MediaController::class, 'thumb'])
    ->where(['width' => '\d{1,4}', 'height' => '\d{1,4}', 'mode' => '[1-4]', 'path' => '.+']);
