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
    $allowed = array('category', 'page', 'per_page', 'brand', 'price', 'subcategory', 'sort');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsCatalogError(400, 'BAD_QUERY');
    foreach($allowed as $key) if(array_key_exists($key, $_GET) && (!is_string($_GET[$key]) || trim($_GET[$key]) === '')) ptsCatalogError(400, 'BAD_QUERY');
    $categoryPath = isset($_GET['category']) && is_scalar($_GET['category']) ? trim((string)$_GET['category'], '/') : '';
    if($categoryPath !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/', $categoryPath)) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['page'])) ptsCatalogError(400, 'BAD_QUERY');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if(isset($_GET['per_page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['per_page'])) ptsCatalogError(400, 'BAD_QUERY');
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    if($page > 10000 || $perPage > 48) ptsCatalogError(400, 'BAD_QUERY');
    /*
     * brand/subcategory: danh sach ID duong, phan cach dau phay, khong cho
     * phan tu rong (chat hon regex legacy '/^[0-9,]+$/' o sources/product.php
     * vi day la code moi, SQL phai parameterized). price: chi nhan 3 khoa
     * that cua productPriceFilterRanges(). sort: whitelist qua ptsCatalogSortKey().
     */
    if(isset($_GET['brand']) && !preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/', $_GET['brand'])) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['subcategory']) && !preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/', $_GET['subcategory'])) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['price']) && !preg_match('/^[123](?:,[123])*$/', $_GET['price'])) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['sort']) && !preg_match('/^[a-z-]+$/', $_GET['sort'])) ptsCatalogError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'product_price_filter.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';

    $segments = $categoryPath === '' ? array() : explode('/', $categoryPath);
    $resolved = $segments ? publicResolveProductCategoryPath($d, $segments, 'vi') : array();
    if($segments && empty($resolved)) ptsCatalogError(400, 'BAD_QUERY');
    $baseWhere = "p.type = ? and find_in_set('hienthi', p.status)";
    $baseParams = array('san-pham');
    $category = array();
    $resolvedListId = 0;
    if($resolved)
    {
        $fields = array('idl' => 'id_list', 'idc' => 'id_cat', 'idi' => 'id_item', 'ids' => 'id_sub');
        $last = array_key_last($resolved);
        $baseWhere .= ' and p.'.$fields[$last].' = ?';
        $baseParams[] = (int)$resolved[$last];
        $tables = array('idl' => 'product_list', 'idc' => 'product_cat', 'idi' => 'product_item', 'ids' => 'product_sub');
        $row = $d->rawQueryOne("select id,namevi,slugvi,status from #_{$tables[$last]} where id = ? and type = ? and find_in_set('hienthi',status) limit 1", array((int)$resolved[$last], 'san-pham'));
        $category = ptsCatalogCategoryDto(is_array($row) ? $row : array(), $segments);
        if($category === null) ptsCatalogError(400, 'BAD_QUERY');
        /*
         * "Loai san pham" (subcategory pill/checkbox) chi co y nghia khi dang
         * dung o dung mot danh muc cap 1 (product_list) - khop nhanh is_cat
         * trong sources/product.php chi duoc ap dung o nhanh $idl!=''. O cap
         * sau (cat/item/sub) hoac trang goc /san-pham/, khong gop subcategory
         * cua nhieu list khac nhau lam mot danh sach vo nghia.
         */
        if($last === 'idl') $resolvedListId = (int)$resolved[$last];
    }

    /* brand: ID duong da validate o tren, parameterized - khong noi chuoi truc tiep nhu legacy. */
    $brandIds = array();
    if(isset($_GET['brand']) && $_GET['brand'] !== '') $brandIds = array_values(array_unique(array_map('intval', explode(',', $_GET['brand']))));
    $subcategoryIds = array();
    if($resolvedListId > 0 && isset($_GET['subcategory']) && $_GET['subcategory'] !== '') $subcategoryIds = array_values(array_unique(array_map('intval', explode(',', $_GET['subcategory']))));
    $priceParam = isset($_GET['price']) && publicProductPricesVisible() ? (string)$_GET['price'] : '';
    $sortKey = ptsCatalogSortKey(isset($_GET['sort']) ? $_GET['sort'] : '');

    $where = $baseWhere;
    $params = $baseParams;
    if($brandIds)
    {
        $where .= ' and p.id_brand in ('.implode(',', array_fill(0, count($brandIds), '?')).')';
        $params = array_merge($params, $brandIds);
    }
    if($subcategoryIds)
    {
        $where .= ' and p.id_cat in ('.implode(',', array_fill(0, count($subcategoryIds), '?')).')';
        $params = array_merge($params, $subcategoryIds);
    }
    if($priceParam !== '')
    {
        /*
         * productPriceFilterSql() tra ve ten cot tho (sale_price/regular_price/
         * status), khong alias. An toan vi truy van nay chi co #_product p
         * trong FROM, khong join bang nao khac cung ten cot nen khong mo ho -
         * khong can tien to lai.
         */
        list($priceWhere, $priceParams) = productPriceFilterSql($priceParam);
        $where .= $priceWhere;
        $params = array_merge($params, $priceParams);
    }

    $totalRow = $d->rawQueryOne("select count(*) as total from #_product p where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $orderBy = ptsCatalogSortOrderBy($sortKey, publicProductPricesVisible());
    $orderBySql = $orderBy !== null ? $orderBy : 'p.id desc';
    $rows = $d->rawQuery("select p.id,p.namevi,p.slugvi,p.photo,p.photo2,p.status,p.regular_price,p.sale_price,p.cong_suat from #_product p where {$where} order by {$orderBySql} limit ".(int)$offset.', '.(int)$perPage, $params);
    $rows = is_array($rows) ? $rows : array();
    foreach($rows as &$row)
    {
        $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo']) ? $row['photo'] : '';
        $row['_pts_public_photo2'] = isset($row['photo2']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo2']) ? $row['photo2'] : '';
    }
    unset($row);
    $categories = $d->rawQuery("select pl.id,pl.namevi,pl.slugvi,pl.status from #_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham', 'san-pham'));

    /*
     * Brand options: quet theo dung baseWhere (category thuan, CHUA ap brand/
     * price/subcategory) - khop productContextualBrands() o sources/product.php,
     * de checkbox khong tu rung xuong rong khi khach da tick mot nhan hieu.
     */
    $brandOptionRows = $d->rawQuery("select distinct pb.id,pb.namevi from #_product p inner join #_product_brand pb on pb.id = p.id_brand and pb.type = p.type and find_in_set('hienthi',pb.status) where {$baseWhere} and p.id_brand > 0 order by pb.numb,pb.id desc", $baseParams);
    $subcategoryOptionRows = array();
    if($resolvedListId > 0) $subcategoryOptionRows = $d->rawQuery("select id,namevi,slugvi,status from #_product_cat where id_list = ? and type = ? and find_in_set('hienthi',status) order by numb,id desc", array($resolvedListId, 'san-pham'));

    echo json_encode(ptsCatalogPayload(
        $category ?: array(),
        is_array($categories) ? $categories : array(),
        $rows, $page, $perPage, $total, publicProductPricesVisible(),
        array('brands' => is_array($brandOptionRows) ? $brandOptionRows : array(), 'subcategories' => is_array($subcategoryOptionRows) ? $subcategoryOptionRows : array()),
        array('brandIds' => $brandIds, 'priceKeys' => $priceParam !== '' ? explode(',', $priceParam) : array(), 'subcategoryIds' => $subcategoryIds, 'sort' => $sortKey)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_catalog: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
