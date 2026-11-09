<?php

use App\Http\Controllers\OrderPrintController;
use Illuminate\Support\Facades\Route;

// Pages served by Laravel itself are the Filament admin (registered by its panel provider).
// Media and the health check live in routes/stateless.php, outside the session.

Route::middleware('web')->get('/admin/orders/{order}/in', OrderPrintController::class)->name('order.print');
