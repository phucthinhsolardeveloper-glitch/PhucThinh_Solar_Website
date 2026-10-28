<?php
/** GET-only checkout metadata for the Nuxt UI; order mutation remains in sources/order.php. */
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET')
{
    header('Allow: GET');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}

require_once LIBRARIES.'pts_public_cart_api.php';
if(!defined('SOURCES')) define('SOURCES', dirname(__DIR__).DIRECTORY_SEPARATOR.'sources'.DIRECTORY_SEPARATOR);
if(!defined('CHECKOUT_ATOMICITY_FUNCTIONS_ONLY')) define('CHECKOUT_ATOMICITY_FUNCTIONS_ONLY', true);
require_once SOURCES.'order.php';

try
{
    $cities = array();
    foreach((array)$d->rawQuery("select id,name from #_city where find_in_set('hienthi',status) order by numb asc,id asc") as $row)
    {
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $name = ptsCartText(isset($row['name']) ? $row['name'] : '');
        if($id > 0 && $name !== '') $cities[] = array('id'=>$id,'name'=>$name);
    }

    $payments = array();
    foreach((array)checkoutVisiblePaymentMethods($d, 'vi') as $row)
    {
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        if($id === 0) continue;
        $name = ptsCartText(isset($row['namevi']) ? $row['namevi'] : '');
        if($name === '') continue;
        $description = ptsCartText(isset($row['descvi']) ? str_replace(array('<br>','<br/>','<br />'), "\n", $row['descvi']) : '');
        $payments[] = array(
            'id'=>$id,
            'name'=>$name,
            'description'=>$description,
            'code'=>$id === -1 ? 'vnpay' : 'legacy'
        );
    }

    $cartReady = !empty($_SESSION['cart']) && publicProductPricesVisible();
    if($cartReady)
    {
        foreach((array)$_SESSION['cart'] as $cartItem)
        {
            $product = !empty($cartItem['productid']) ? $cart->getProductInfo((int)$cartItem['productid']) : null;
            if(empty($product) || publicProductRequiresQuote($product)) { $cartReady = false; break; }
        }
    }

    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'csrfToken'=>formCsrfToken(),
        'checkoutToken'=>checkoutIdempotencyToken(),
        'shippingEnabled'=>!empty($config['order']['ship']),
        'cartReady'=>$cartReady,
        'cities'=>$cities,
        'payments'=>$payments
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_checkout: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
