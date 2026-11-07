<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

// Uploaded files, shipped site pictures and on-demand thumbnails.
Route::get('/upload/{path}', [MediaController::class, 'file'])->where('path', '.+');
Route::get('/assets/{path}', [MediaController::class, 'file'])->where('path', 'images/site-visuals/.+');
Route::get('/thumbs/{width}x{height}x{mode}/{path}', [MediaController::class, 'thumb'])
    ->where(['width' => '\d{1,4}', 'height' => '\d{1,4}', 'mode' => '[1-4]', 'path' => '.+']);
