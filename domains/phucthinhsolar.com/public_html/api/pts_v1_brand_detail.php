<?php
/** GET-only read bridge for the Nuxt brand detail preview (/thuong-hieu/{slug}/). */
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

function ptsBrandDetailError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    $allowed = array('slug', 'page', 'per_page', 'sort');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsBrandDetailError(400, 'BAD_QUERY');
    if(!isset($_GET['slug']) || !is_string($_GET['slug'])) ptsBrandDetailError(400, 'BAD_QUERY');
    $slug = trim($_GET['slug'], '/');
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsBrandDetailError(400, 'BAD_QUERY');
    if(isset($_GET['page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['page'])) ptsBrandDetailError(400, 'BAD_QUERY');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if(isset($_GET['per_page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['per_page'])) ptsBrandDetailError(400, 'BAD_QUERY');
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    if($page > 10000 || $perPage > 48) ptsBrandDetailError(400, 'BAD_QUERY');
    if(isset($_GET['sort']) && !preg_match('/^[a-z-]+$/', $_GET['sort'])) ptsBrandDetailError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';
    require_once LIBRARIES.'pts_public_brand_detail_api.php';

    $row = $d->rawQueryOne("select id,namevi,slugvi,status from #_product_brand where slugvi = ? and type = ? and find_in_set('hienthi',status) limit 1", array($slug, 'san-pham'));
    if(empty($row)) ptsBrandDetailError(404, 'BRAND_NOT_FOUND');

    $sortKey = ptsCatalogSortKey(isset($_GET['sort']) ? $_GET['sort'] : '');
    $orderBy = ptsCatalogSortOrderBy($sortKey, publicProductPricesVisible());
    $orderBySql = $orderBy !== null ? $orderBy : 'p.id desc';

    $where = "p.id_brand = ? and p.type = ? and find_in_set('hienthi', p.status)";
    $params = array((int)$row['id'], 'san-pham');
    $totalRow = $d->rawQueryOne("select count(*) as total from #_product p where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $rows = $d->rawQuery("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from #_product p where {$where} order by {$orderBySql} limit ".(int)$offset.', '.(int)$perPage, $params);
    $rows = is_array($rows) ? $rows : array();
    foreach($rows as &$productRow)
    {
        $productRow['_pts_public_photo'] = isset($productRow['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$productRow['photo']) ? $productRow['photo'] : '';
        $productRow['_pts_public_photo2'] = isset($productRow['photo2']) && is_file(ROOT.UPLOAD_PRODUCT_L.$productRow['photo2']) ? $productRow['photo2'] : '';
    }
    unset($productRow);

    $payload = ptsBrandDetailPayload($row, $rows, $page, $perPage, $total, publicProductPricesVisible(), $sortKey);
    if($payload === null) ptsBrandDetailError(404, 'BRAND_NOT_FOUND');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_brand_detail: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
