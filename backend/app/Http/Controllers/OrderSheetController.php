<?php

namespace App\Http\Controllers;

use App\Domain\Commerce\OrderSheet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The order sheet PDF for signed-in staff. Nothing is written and nothing may be cached. */
class OrderSheetController extends Controller
{
    public function __invoke(Order $order, OrderSheet $sheets, Request $request): Response
    {
        $this->allow('orders');
        // An order placed before sheets were stored is drawn from today's data, and says so.
        $sheet = $sheets->stored((int) $order->id);
        if ($sheet === null) {
            $sheet = $sheets->build((int) $order->id) ?? abort(404);
            $sheet['rebuilt_at'] = now()->format('Y-m-d H:i:s');
        }

        return $this->pdf($sheets, $sheet, $request->boolean('tai'));
    }

    /** A sheet with made-up data, to judge the layout. */
    public function sample(OrderSheet $sheets): Response
    {
        $this->allow('settings');

        return $this->pdf($sheets, $sheets->sample(), false);
    }

    /** These routes sit outside the panel, so a switched-off account is refused here as the panel refuses it. */
    private function allow(string $permission): void
    {
        $user = auth('admin')->user();
        abort_unless($user instanceof User && $user->is_active && $user->hasPermission($permission), 403);
    }

    /** @param array<string, mixed> $sheet */
    private function pdf(OrderSheet $sheets, array $sheet, bool $download): Response
    {
        return response($sheets->pdf($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.OrderSheet::filename($sheet).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
