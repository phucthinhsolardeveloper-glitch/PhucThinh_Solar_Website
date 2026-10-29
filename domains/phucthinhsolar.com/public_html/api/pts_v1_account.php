<?php
/** GET-only, session-scoped customer account projection for Nuxt. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

function ptsAccountError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    foreach(array_keys($_GET) as $key) if(!in_array($key, array('action','id'), true)) ptsAccountError(400, 'BAD_QUERY');
    $action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';
    if(!in_array($action, array('login','register','forgot','activate','profile','orders','order-detail','returns','cancellations'), true)) ptsAccountError(400, 'BAD_ACTION');
    $id = 0;
    if(isset($_GET['id']))
    {
        if(!is_string($_GET['id']) || !preg_match('/^[1-9][0-9]{0,9}$/', $_GET['id'])) ptsAccountError(400, 'BAD_QUERY');
        $id = (int)$_GET['id'];
    }
    if(($action === 'activate' || $action === 'order-detail') && $id <= 0) ptsAccountError(400, 'BAD_QUERY');
    if(!in_array($action, array('activate','order-detail'), true) && $id !== 0) ptsAccountError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'form_security.php';
    require_once LIBRARIES.'pts_public_account_api.php';
    $flash = new Flash();
    $payload = ptsAccountPayload($d, $action, $id, $_GET, ptsAccountFlash($flash), formCsrfToken());
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_account: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
