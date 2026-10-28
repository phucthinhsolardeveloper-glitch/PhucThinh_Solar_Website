<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET')
{
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(array('error'=>array('code'=>'METHOD_NOT_ALLOWED')), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit();
}

require_once LIBRARIES.'pts_public_cart_api.php';

try
{
    $items = array();
    $pricesVisible = publicProductPricesVisible();
    $root = defined('ROOT') ? ROOT : dirname(__DIR__).DIRECTORY_SEPARATOR;
    $upload = defined('UPLOAD_PRODUCT_L') ? UPLOAD_PRODUCT_L : 'upload/product/';

    foreach((array)(isset($_SESSION['cart']) ? $_SESSION['cart'] : array()) as $cartItem)
    {
        if(!is_array($cartItem) || empty($cartItem['productid'])) continue;
        $product = $cart->getProductInfo((int)$cartItem['productid']);
        if(empty($product)) continue;
        $statuses = array_filter(array_map('trim', explode(',', isset($product['status']) ? (string)$product['status'] : '')));
        if(!in_array('hienthi', $statuses, true)) continue;

        $color = !empty($cartItem['color']) ? $cart->getProductColor((int)$cartItem['color']) : '';
        $size = !empty($cartItem['size']) ? $cart->getProductSize((int)$cartItem['size']) : '';
        $item = ptsCartItemDto($cartItem, $product, $color, $size, $root, $upload, $pricesVisible);
        if($item !== null) $items[] = $item;
        if(count($items) >= 100) break;
    }

    $payload = ptsCartPayload($items);
    $payload['csrfToken'] = formCsrfToken();
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_cart: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
