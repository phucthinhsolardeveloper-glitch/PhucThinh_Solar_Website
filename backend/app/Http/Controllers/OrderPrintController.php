<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Printable order sheet for staff; the browser's print dialog also saves it as PDF. */
class OrderPrintController extends Controller
{
    public function __invoke(Order $order, SiteSettings $settings): View
    {
        $user = auth('admin')->user();
        abort_unless($user instanceof User && $user->hasPermission('orders'), 403);

        return view('order-print', ['order' => $order->load(['items', 'province', 'ward', 'paymentMethod']), 'settings' => $settings]);
    }

    /** Several orders in one print (?ids=1,2,3), each on its own page, in the order given. */
    public function many(Request $request, SiteSettings $settings): View
    {
        $user = auth('admin')->user();
        abort_unless($user instanceof User && $user->hasPermission('orders'), 403);
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))))), 0, 100);
        abort_if($ids === [], 404);
        $orders = Order::query()->whereKey($ids)->with(['items', 'province', 'ward', 'paymentMethod'])->get()
            ->sortBy(fn (Order $o) => array_search($o->id, $ids, true))->values();
        abort_if($orders->isEmpty(), 404);

        return view('order-print', ['orders' => $orders, 'order' => $orders->first(), 'settings' => $settings]);
    }
}
