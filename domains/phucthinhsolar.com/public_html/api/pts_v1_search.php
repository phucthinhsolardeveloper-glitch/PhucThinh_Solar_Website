<?php
/** GET-only public search DTO for the Nuxt migration. Mirrors sources/search.php matching rules. */
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

function ptsSearchError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    $allowed = array('keyword','page','per_page','brand','price');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsSearchError(400, 'BAD_QUERY');

    $rawKeyword = isset($_GET['keyword']) && is_scalar($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';
    if($rawKeyword === '' || mb_strlen($rawKeyword, 'UTF-8') > 100 || preg_match('/[\x00-\x1F\x7F]/', $rawKeyword)) ptsSearchError(400, 'BAD_QUERY');
    if(isset($_GET['page']) && (!is_string($_GET['page']) || !preg_match('/^[1-9][0-9]*$/', $_GET['page']))) ptsSearchError(400, 'BAD_QUERY');
    if(isset($_GET['per_page']) && (!is_string($_GET['per_page']) || !preg_match('/^[1-9][0-9]*$/', $_GET['per_page']))) ptsSearchError(400, 'BAD_QUERY');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    if($page > 10000 || $perPage > 48) ptsSearchError(400, 'BAD_QUERY');
    if(isset($_GET['brand']) && (!is_string($_GET['brand']) || !preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/', $_GET['brand']))) ptsSearchError(400, 'BAD_QUERY');
    if(isset($_GET['price']) && (!is_string($_GET['price']) || !preg_match('/^[123](?:,[123])*$/', $_GET['price']))) ptsSearchError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'product_price_filter.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';

    $normalized = $func->changeTitle(htmlspecialchars($rawKeyword, ENT_QUOTES, 'UTF-8'));
    if($normalized === '') ptsSearchError(400, 'BAD_QUERY');

    $rawPattern = '%'.$rawKeyword.'%';
    $normalizedPattern = '%'.$normalized.'%';
    $clauses = array();
    $params = array('san-pham');

    foreach(array(
        'p.namevi','p.nameen','p.code','p.pdp_profile',
        'p.descvi','p.descen','p.highlightsvi','p.highlightsen',
        'p.applicationsvi','p.applicationsen','p.installationvi','p.installationen',
        'p.contentvi','p.contenten','p.additional_specs','p.options',
        'p.thongsokythuatvi','p.thongsokythuaten','p.warranty',
        'p.battery_capacity','p.panel_power','p.light_source','p.lamp_dimensions',
        'p.color_temperature','p.light_color','p.material','p.charging_time',
        'p.lighting_time','p.waterproof_rating','p.led_chip','p.lighting_area',
        'p.installation_height','p.panel_dimensions','p.package_contents',
        'p.carton_quantity','p.carton_dimensions','p.khoanggia'
    ) as $field)
    {
        $clauses[] = $field.' LIKE ?';
        $params[] = $rawPattern;
    }
    $clauses[] = 'p.slugvi LIKE ?'; $params[] = $normalizedPattern;
    $clauses[] = 'p.slugen LIKE ?'; $params[] = $normalizedPattern;

    $brandClauses = array();
    foreach(array('pb.namevi','pb.nameen','pb.slugvi','pb.slugen') as $field)
    {
        $brandClauses[] = $field.' LIKE ?';
        $params[] = strpos($field, 'slug') !== false ? $normalizedPattern : $rawPattern;
    }
    $clauses[] = 'exists (select 1 from #_product_brand pb where pb.id = p.id_brand and ('.implode(' or ', $brandClauses).'))';

    $compactKeyword = preg_replace('/[^a-z0-9]/', '', strtolower($normalized));
    if($compactKeyword !== '')
    {
        $compactPattern = '%'.$compactKeyword.'%';
        foreach(array('p.code','p.slugvi','p.slugen','p.pdp_profile','p.options','p.additional_specs','p.thongsokythuatvi','p.thongsokythuaten','p.contentvi','p.contenten','p.descvi','p.descen') as $field)
        {
            $expr = 'LOWER('.$field.')';
            foreach(array('-', ' ', '_', '/', '.') as $separator) $expr = "REPLACE($expr, '$separator', '')";
            $clauses[] = $expr.' LIKE ?';
            $params[] = $compactPattern;
        }
    }

    $where = "p.type = ? and (".implode(' or ', $clauses).") and find_in_set('hienthi',p.status)";
    $baseWhere = $where;
    $baseParams = $params;
    $brandIds = isset($_GET['brand']) ? array_values(array_unique(array_map('intval', explode(',', $_GET['brand'])))) : array();
    $priceKeys = isset($_GET['price']) && publicProductPricesVisible() ? array_values(array_unique(explode(',', $_GET['price']))) : array();
    if($brandIds)
    {
        $where .= ' and p.id_brand in ('.implode(',', array_fill(0, count($brandIds), '?')).')';
        $params = array_merge($params, $brandIds);
    }
    if($priceKeys)
    {
        list($priceWhere, $priceParams) = productPriceFilterSql(implode(',', $priceKeys));
        $where .= $priceWhere;
        $params = array_merge($params, $priceParams);
    }
    $totalRow = $d->rawQueryOne("select count(*) total from #_product p where $where", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $rows = $d->rawQuery(
        "select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from #_product p where $where".publicProductDefaultOrder('p')." limit ".(int)$offset.", ".(int)$perPage,
        $params
    );
    $products = array();
    foreach((array)$rows as $row)
    {
        $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo']) ? $row['photo'] : '';
        $row['_pts_public_photo2'] = isset($row['photo2']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo2']) ? $row['photo2'] : '';
        $dto = ptsCatalogProductDto($row, publicProductPricesVisible());
        if($dto !== null) $products[] = $dto;
    }

    /* Match the legacy search sidebar: all currently published product brands,
       plus price bands only while public pricing is enabled. */
    $brandRows = $cache->get("select id,namevi from #_product_brand where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    $brands = array();
    foreach((array)$brandRows as $brandRow)
    {
        if(!is_array($brandRow)) continue;
        $brandDto = ptsCatalogBrandDto($brandRow);
        if($brandDto !== null) $brands[] = $brandDto;
    }
    $priceRanges = publicProductPricesVisible() ? ptsCatalogPriceRangeDto() : array();

    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'keyword'=>ptsCatalogPlainText($rawKeyword),
        'products'=>$products,
        'pagination'=>array('page'=>$page,'perPage'=>$perPage,'total'=>$total),
        'filters'=>array('brands'=>$brands,'priceRanges'=>$priceRanges),
        'selected'=>array('brandIds'=>$brandIds,'priceKeys'=>$priceKeys)
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_search: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
