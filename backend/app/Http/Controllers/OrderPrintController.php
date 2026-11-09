<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;

/** Printable order sheet for staff; the browser's print dialog also saves it as PDF. */
class OrderPrintController extends Controller
{
    public function __invoke(Order $order, SiteSettings $settings): View
    {
        $user = auth('admin')->user();
        abort_unless($user instanceof User && $user->hasPermission('orders'), 403);

        return view('order-print', ['order' => $order->load(['items', 'province', 'ward', 'paymentMethod']), 'settings' => $settings]);
    }
}
