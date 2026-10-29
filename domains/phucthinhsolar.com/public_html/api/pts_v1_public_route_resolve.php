<?php
/** GET /api/pts_v1_public_route_resolve.php?slug=... — resolve a legacy public slug without rendering a PHP template. */
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

function ptsPublicRouteResolveError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    if(count($_GET) !== 1 || !isset($_GET['slug']) || !is_string($_GET['slug'])) ptsPublicRouteResolveError(400, 'BAD_QUERY');
    $slug = trim($_GET['slug'], " /\t\n\r\0\x0B");
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 120) ptsPublicRouteResolveError(400, 'BAD_SLUG');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'public_route_resolver.php';
    require_once LIBRARIES.'pts_public_route_resolve_api.php';

    $match = publicRouteResolveSlug($d, $slug, 'slugvi', ptsPublicRouteResolveMap());
    if(empty($match))
    {
        echo json_encode(ptsPublicRouteResolveNotFound(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        exit;
    }
    $dto = ptsPublicRouteResolveDto($d, $match, $slug);
    if(!is_array($dto))
    {
        echo json_encode(ptsPublicRouteResolveNotFound(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        exit;
    }
    echo json_encode($dto, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_public_route_resolve: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
?>
