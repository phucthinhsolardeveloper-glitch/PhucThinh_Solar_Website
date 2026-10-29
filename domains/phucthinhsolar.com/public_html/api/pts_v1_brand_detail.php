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
    $allowed = array('slug', 'page', 'per_page', 'sort', 'price');
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
    if(isset($_GET['price']) && (!is_string($_GET['price']) || !preg_match('/^[123](?:,[123])*$/', $_GET['price']))) ptsBrandDetailError(400, 'BAD_QUERY');
    if(isset($_GET['price']) && count(array_unique(explode(',', $_GET['price']))) !== count(explode(',', $_GET['price']))) ptsBrandDetailError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'product_price_filter.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';
    require_once LIBRARIES.'pts_public_brand_detail_api.php';

    $row = $d->rawQueryOne("select id,namevi,slugvi,status from #_product_brand where slugvi = ? and type = ? and find_in_set('hienthi',status) limit 1", array($slug, 'san-pham'));
    if(empty($row)) ptsBrandDetailError(404, 'BRAND_NOT_FOUND');

    $sortKey = ptsCatalogSortKey(isset($_GET['sort']) ? $_GET['sort'] : '');
    $orderBy = ptsCatalogSortOrderBy($sortKey, publicProductPricesVisible());
    $orderBySql = $orderBy !== null ? $orderBy : 'p.id desc';

    $where = "p.id_brand = ? and p.type = ? and find_in_set('hienthi', p.status)";
    $params = array((int)$row['id'], 'san-pham');
    $priceKeys = publicProductPricesVisible() && isset($_GET['price']) ? array_values(array_unique(explode(',', $_GET['price']))) : array();
    if($priceKeys)
    {
        list($priceWhere, $priceParams) = productPriceFilterSql(implode(',', $priceKeys));
        $where .= $priceWhere;
        $params = array_merge($params, $priceParams);
    }
    $totalRow = $d->rawQueryOne("select count(*) as total from #_product p where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $rows = $d->rawQuery("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from #_product p where {$where} order by {$orderBySql} limit ".(int)$offset.', '.(int)$perPage, $params);
    $rows = is_array($rows) ? $rows : array();
    $productIds = array();
    foreach($rows as $productRow)
    {
        $productId = isset($productRow['id']) ? (int)$productRow['id'] : 0;
        if($productId > 0 && !in_array($productId, $productIds, true)) $productIds[] = $productId;
    }
    $galleryByProduct = array();
    if($productIds)
    {
        $idPlaceholders = implode(',', array_fill(0, count($productIds), '?'));
        $galleryRows = $d->rawQuery(
            "select id_parent,photo,status from #_gallery where id_parent in ({$idPlaceholders}) and com = ? and kind = ? and val = ? and find_in_set('hienthi',status) order by numb,id",
            array_merge($productIds, array('product', 'man', 'san-pham'))
        );
        foreach(is_array($galleryRows) ? $galleryRows : array() as $galleryRow)
        {
            $productId = isset($galleryRow['id_parent']) ? (int)$galleryRow['id_parent'] : 0;
            $filename = isset($galleryRow['photo']) ? trim((string)$galleryRow['photo']) : '';
            if($productId <= 0 || !in_array($productId, $productIds, true) || !ptsCatalogPublished($galleryRow['status'] ?? '') || $filename === '' || basename($filename) !== $filename) continue;
            if(ptsCatalogPublicMedia($filename) === '' || !is_file(ROOT.UPLOAD_PRODUCT_L.$filename)) continue;
            if(!isset($galleryByProduct[$productId])) $galleryByProduct[$productId] = array();
            if(!in_array($filename, $galleryByProduct[$productId], true)) $galleryByProduct[$productId][] = $filename;
        }
    }
    foreach($rows as &$productRow)
    {
        $productRow['_pts_public_photo'] = isset($productRow['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$productRow['photo']) ? $productRow['photo'] : '';
        $productRow['_pts_public_photo2'] = isset($productRow['photo2']) && is_file(ROOT.UPLOAD_PRODUCT_L.$productRow['photo2']) ? $productRow['photo2'] : '';
        $productId = isset($productRow['id']) ? (int)$productRow['id'] : 0;
        $productRow['_pts_public_gallery'] = isset($galleryByProduct[$productId]) ? $galleryByProduct[$productId] : array();
    }
    unset($productRow);

    $brandOptions = $d->rawQuery("select namevi,slugvi,id,type,status from #_product_brand where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'));
    $filterOptions = array(
        'brands' => is_array($brandOptions) ? $brandOptions : array(),
        'priceRanges' => publicProductPricesVisible() ? ptsCatalogPriceRangeDto() : array()
    );
    $payload = ptsBrandDetailPayload($row, $rows, $page, $perPage, $total, publicProductPricesVisible(), $sortKey, $filterOptions, array('priceKeys' => $priceKeys));
    if($payload === null) ptsBrandDetailError(404, 'BRAND_NOT_FOUND');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_brand_detail: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
