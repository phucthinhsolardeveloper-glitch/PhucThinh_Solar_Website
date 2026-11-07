<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Commerce\Shop;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cart, checkout and payment callbacks. These routes run with the visitor's session cookie. */
class ShopController extends Controller
{
    public function cart(Request $request, Shop $shop): JsonResponse
    {
        return PagesController::json($shop->cartPayload());
    }

    public function mutateCart(Request $request, Shop $shop): JsonResponse
    {
        return self::reply($shop->mutate(json_decode($request->getContent(), true)));
    }

    public function checkout(Shop $shop): JsonResponse
    {
        return PagesController::json($shop->checkoutPayload());
    }

    /**
     * JSON from the new checkout, or the classic form post of the current checkout page
     * (form_csrf, checkout_token, dataOrder[...]); the form gets redirects, like sources/order.php gave.
     */
    public function placeOrder(Request $request, Shop $shop): Response
    {
        if (! $request->isJson()) {
            $data = $request->post('dataOrder');
            $result = $shop->placeOrder(['csrfToken' => $request->post('form_csrf'), 'checkoutToken' => $request->post('checkout_token'), 'data' => is_array($data) ? $data : []], (string) $request->ip());

            return redirect($shop->formRedirect($result), 303);
        }

        return self::reply($shop->placeOrder(json_decode($request->getContent(), true), (string) $request->ip()));
    }

    public function shippingQuote(Request $request, Shop $shop): JsonResponse
    {
        return self::reply($shop->shippingQuote(json_decode($request->getContent(), true) ?? $request->post()));
    }

    public function thanks(Request $request, Shop $shop): JsonResponse
    {
        return self::reply($shop->thanks($request->query()));
    }

    public function notice(Shop $shop): JsonResponse
    {
        return PagesController::json($shop->notice());
    }

    public function vnpay(Request $request, Shop $shop): Response
    {
        $result = $shop->vnpayCallback($request->query(), $request->query('mode') === 'ipn');
        if ($result[0] === 302) {
            return redirect($result[2] ?? '/');
        }

        return is_array($result[1]) ? response()->json($result[1], $result[0]) : response($result[1], $result[0])->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /** @param array{0: int, 1: array<string, mixed>} $result */
    private static function reply(array $result): JsonResponse
    {
        return response()->json($result[1], $result[0], ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
