<?php
/** GET-only Phase A product catalog contract. */
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

function ptsCatalogError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    $allowed = array('category', 'page', 'per_page');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsCatalogError(400, 'BAD_QUERY');
    foreach($allowed as $key) if(array_key_exists($key, $_GET) && (!is_string($_GET[$key]) || trim($_GET[$key]) === '')) ptsCatalogError(400, 'BAD_QUERY');
    $categoryPath = isset($_GET['category']) && is_scalar($_GET['category']) ? trim((string)$_GET['category'], '/') : '';
    if($categoryPath !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/', $categoryPath)) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['page'])) ptsCatalogError(400, 'BAD_QUERY');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if(isset($_GET['per_page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['per_page'])) ptsCatalogError(400, 'BAD_QUERY');
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    if($page > 10000 || $perPage > 48) ptsCatalogError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';

    $segments = $categoryPath === '' ? array() : explode('/', $categoryPath);
    $resolved = $segments ? publicResolveProductCategoryPath($d, $segments, 'vi') : array();
    if($segments && empty($resolved)) ptsCatalogError(400, 'BAD_QUERY');
    $where = "p.type = ? and find_in_set('hienthi', p.status)";
    $params = array('san-pham');
    $category = array();
    if($resolved)
    {
        $fields = array('idl' => 'id_list', 'idc' => 'id_cat', 'idi' => 'id_item', 'ids' => 'id_sub');
        $last = array_key_last($resolved);
        $where .= ' and p.'.$fields[$last].' = ?';
        $params[] = (int)$resolved[$last];
        $tables = array('idl' => 'product_list', 'idc' => 'product_cat', 'idi' => 'product_item', 'ids' => 'product_sub');
        $row = $d->rawQueryOne("select id,namevi,slugvi,status from #_{$tables[$last]} where id = ? and type = ? and find_in_set('hienthi',status) limit 1", array((int)$resolved[$last], 'san-pham'));
        $category = ptsCatalogCategoryDto(is_array($row) ? $row : array(), $segments);
        if($category === null) ptsCatalogError(400, 'BAD_QUERY');
    }
    $totalRow = $d->rawQueryOne("select count(*) as total from #_product p where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $rows = $d->rawQuery("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status from #_product p where {$where} order by p.id desc limit ".(int)$offset.', '.(int)$perPage, $params);
    $rows = is_array($rows) ? $rows : array();
    foreach($rows as &$row)
    {
        $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo']) ? $row['photo'] : '';
        $row['_pts_public_photo2'] = isset($row['photo2']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo2']) ? $row['photo2'] : '';
    }
    unset($row);
    $categories = $d->rawQuery("select pl.id,pl.namevi,pl.slugvi,pl.status from #_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham', 'san-pham'));
    echo json_encode(ptsCatalogPayload($category ?: array(), is_array($categories) ? $categories : array(), $rows, $page, $perPage, $total), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_catalog: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
