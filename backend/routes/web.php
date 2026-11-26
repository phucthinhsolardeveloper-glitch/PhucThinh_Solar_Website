<?php

use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\OrderSheetController;
use Illuminate\Support\Facades\Route;

// Pages served by Laravel itself are the Filament admin (registered by its panel provider).
// Media and the health check live in routes/stateless.php, outside the session.

Route::middleware('web')->get('/admin/orders/in-nhieu', [OrderPrintController::class, 'many'])->name('order.print-many');
Route::middleware('web')->get('/admin/orders/{order}/in', OrderPrintController::class)->name('order.print');
Route::middleware('web')->get('/admin/orders/{order}/phieu.pdf', OrderSheetController::class)->name('order.sheet');
Route::middleware('web')->get('/admin/phieu-don-hang-mau.pdf', [OrderSheetController::class, 'sample'])->name('order.sheet-sample');
