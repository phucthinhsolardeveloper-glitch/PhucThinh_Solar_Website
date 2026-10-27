<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

function ptsCartMutateJson($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
{
    header('Allow: POST');
    ptsCartMutateJson(405, array('error'=>array('code'=>'METHOD_NOT_ALLOWED')));
}

$body = json_decode(file_get_contents('php://input'), true);
if(!is_array($body)) ptsCartMutateJson(400, array('error'=>array('code'=>'BAD_BODY')));
$csrf = isset($body['csrfToken']) && is_scalar($body['csrfToken']) ? (string)$body['csrfToken'] : '';
if(!formCsrfValid($csrf)) ptsCartMutateJson(419, array('error'=>array('code'=>'INVALID_SESSION','message'=>'Phiên giỏ hàng không hợp lệ. Vui lòng tải lại trang.')));

$action = isset($body['action']) && is_scalar($body['action']) ? (string)$body['action'] : '';
if(!in_array($action, array('add','update','delete'), true)) ptsCartMutateJson(400, array('error'=>array('code'=>'BAD_ACTION')));

if(!publicProductPricesVisible())
{
    unset($_SESSION['cart']);
    ptsCartMutateJson(409, array('error'=>array('code'=>'QUOTE_ONLY','message'=>'Sản phẩm hiện nhận báo giá trực tiếp.')));
}

if($action === 'add')
{
    $id = isset($body['productId']) && is_scalar($body['productId']) ? (int)$body['productId'] : 0;
    $quantity = isset($body['quantity']) && is_scalar($body['quantity']) ? (int)$body['quantity'] : 1;
    $color = isset($body['color']) && is_scalar($body['color']) ? (int)$body['color'] : 0;
    $size = isset($body['size']) && is_scalar($body['size']) ? (int)$body['size'] : 0;
    if($id <= 0 || $quantity < 1 || $quantity > 999 || $color < 0 || $size < 0) ptsCartMutateJson(400, array('error'=>array('code'=>'BAD_ITEM')));
    $product = $cart->getProductInfo($id);
    if(empty($product) || publicProductRequiresQuote($product)) ptsCartMutateJson(409, array('error'=>array('code'=>'QUOTE_ONLY','message'=>'Sản phẩm này nhận báo giá trực tiếp.')));
    $cart->addToCart($quantity, $id, $color, $size);
}
elseif($action === 'update')
{
    $id = isset($body['productId']) && is_scalar($body['productId']) ? (int)$body['productId'] : 0;
    $quantity = isset($body['quantity']) && is_scalar($body['quantity']) ? (int)$body['quantity'] : 0;
    $code = isset($body['code']) && is_scalar($body['code']) ? trim((string)$body['code']) : '';
    if($id <= 0 || $quantity < 1 || $quantity > 999 || !preg_match('/^[a-f0-9]{32}$/', $code)) ptsCartMutateJson(400, array('error'=>array('code'=>'BAD_ITEM')));
    $found = false;
    foreach((array)(isset($_SESSION['cart']) ? $_SESSION['cart'] : array()) as &$item)
    {
        if(isset($item['code']) && hash_equals((string)$item['code'], $code))
        {
            if((int)$item['productid'] !== $id) ptsCartMutateJson(409, array('error'=>array('code'=>'ITEM_MISMATCH')));
            $item['qty'] = $quantity;
            $found = true;
            break;
        }
    }
    unset($item);
    if(!$found) ptsCartMutateJson(404, array('error'=>array('code'=>'NOT_FOUND')));
}
else
{
    $code = isset($body['code']) && is_scalar($body['code']) ? trim((string)$body['code']) : '';
    if(!preg_match('/^[a-f0-9]{32}$/', $code)) ptsCartMutateJson(400, array('error'=>array('code'=>'BAD_ITEM')));
    $found = false;
    foreach((array)(isset($_SESSION['cart']) ? $_SESSION['cart'] : array()) as $item)
        if(isset($item['code']) && hash_equals((string)$item['code'], $code)) { $found = true; break; }
    if(!$found) ptsCartMutateJson(404, array('error'=>array('code'=>'NOT_FOUND')));
    $cart->removeProduct($code);
}

ptsCartMutateJson(200, array('ok'=>true,'count'=>count((array)(isset($_SESSION['cart']) ? $_SESSION['cart'] : array()))));
