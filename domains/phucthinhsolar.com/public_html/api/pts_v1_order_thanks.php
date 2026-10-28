<?php
/** Session-bound order success DTO for the Nuxt thank-you page. */
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
$token = isset($_GET['token']) && is_scalar($_GET['token']) ? trim((string)$_GET['token']) : '';
if(!preg_match('/^[a-f0-9]{48}$/', $token) || count($_GET) !== 1)
{
    http_response_code(400);
    echo '{"error":{"code":"BAD_QUERY"}}';
    exit;
}
if(empty($_SESSION['order_thanks'][$token]) || !is_array($_SESSION['order_thanks'][$token]))
{
    http_response_code(404);
    echo '{"error":{"code":"NOT_FOUND"}}';
    exit;
}

try
{
    $session = $_SESSION['order_thanks'][$token];
    if(empty($session['created_at']) || time() - (int)$session['created_at'] > 7200)
    {
        unset($_SESSION['order_thanks'][$token]);
        http_response_code(404);
        echo '{"error":{"code":"NOT_FOUND"}}';
        exit;
    }
    $order = $d->rawQueryOne("select code,fullname,total_price from #_order where id=? and code=? limit 1", array((int)$session['id'], (string)$session['code']));
    if(empty($order['code']))
    {
        unset($_SESSION['order_thanks'][$token]);
        http_response_code(404);
        echo '{"error":{"code":"NOT_FOUND"}}';
        exit;
    }
    $fireConversion = !empty($session['conversion_pending']);
    $_SESSION['order_thanks'][$token]['conversion_pending'] = false;
    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'order'=>array(
            'code'=>trim((string)$order['code']),
            'customerName'=>trim(strip_tags((string)$order['fullname'])),
            'totalPrice'=>max(0, (int)round((float)$order['total_price']))
        ),
        'conversionPending'=>$fireConversion
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_order_thanks: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
