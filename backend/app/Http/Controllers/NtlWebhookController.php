<?php

namespace App\Http\Controllers;

use App\Domain\Shipping\Shipments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Status updates pushed by Nhất Tín. Authorised by the token set in their portal; updates delivery only. */
class NtlWebhookController extends Controller
{
    public function __invoke(Request $request, Shipments $shipments): JsonResponse
    {
        $expected = (string) config('shipping.nhattin.webhook_token');
        $given = trim((string) preg_replace('/^Bearer\s+/i', '', (string) $request->header('Authorization')));
        if ($expected === '') {
            return response()->json(['success' => false, 'message' => 'disabled'], 503);
        }
        if (! hash_equals($expected, $given)) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }
        if (strlen((string) $request->getContent()) > 65536 || $request->json()->all() === []) {
            return response()->json(['success' => false, 'message' => 'bad request'], 400);
        }
        $known = $shipments->webhook($request->json()->all());

        return response()->json(['success' => true, 'known' => $known]);
    }
}
