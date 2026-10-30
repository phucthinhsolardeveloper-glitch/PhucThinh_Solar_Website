<?php
/** One-time session notice for a PHP checkout redirect rendered by the Nuxt cart. */
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

require_once LIBRARIES.'pts_public_checkout_notice.php';
try
{
    echo json_encode(ptsPublicCheckoutNoticePayload($_SESSION), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_checkout_notice: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
