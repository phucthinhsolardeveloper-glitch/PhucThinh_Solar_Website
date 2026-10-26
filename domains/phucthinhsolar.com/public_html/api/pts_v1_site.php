<?php
/**
 * GET /api/pts_v1_site.php (Phase A internal contract; no production routing
 * is moved to Nuxt). Uses the existing published-category rule in allpage.php.
 */
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

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_site_api.php';

    $productLists = $cache->get(
        "select pl.id, pl.namevi, pl.slugvi from #_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc",
        array('san-pham', 'san-pham'),
        'result',
        7200
    );
    if(function_exists('closeSessionWrite')) closeSessionWrite();

    $data = ptsPublicSitePayload(
        is_array($setting) ? $setting : array(),
        is_array($optsetting) ? $optsetting : array(),
        is_array($productLists) ? $productLists : array()
    );
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_site: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
