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

require_once __DIR__.'/../libraries/product_sort.php';

function ptsCatalogError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    $allowed = array('category', 'page', 'per_page', 'brand', 'price', 'subcategory', 'sort', 'keyword', 'is_need');
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
    if(isset($_GET['keyword']) && (function_exists('mb_strlen') ? mb_strlen(trim($_GET['keyword']), 'UTF-8') : strlen(trim($_GET['keyword']))) > 100) ptsCatalogError(400, 'BAD_QUERY');
    $needKeyPattern = '(?:du-an-cong-trinh|chieu-sang-gia-dinh|san-vuon-canh-quan)';
    if(isset($_GET['is_need']) && !preg_match('/^'.$needKeyPattern.'(?:,'.$needKeyPattern.')*$/', $_GET['is_need'])) ptsCatalogError(400, 'BAD_QUERY');
    if(isset($_GET['is_need']) && count(array_unique(explode(',', $_GET['is_need']))) !== count(explode(',', $_GET['is_need']))) ptsCatalogError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'product_price_filter.php';
    require_once LIBRARIES.'product_content_blocks.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';

    $segments = $categoryPath === '' ? array() : explode('/', $categoryPath);
    $resolved = $segments ? publicResolveProductCategoryPath($d, $segments, 'vi') : array();
    if($segments && empty($resolved)) ptsCatalogError(404, 'CATEGORY_NOT_FOUND');
    $baseWhere = "p.type = ? and find_in_set('hienthi', p.status)";
    $baseParams = array('san-pham');
    $category = array();
    $selectedCategoryRow = array();
    $resolvedListId = 0;
    if($resolved)
    {
        $fields = array('idl' => 'id_list', 'idc' => 'id_cat', 'idi' => 'id_item', 'ids' => 'id_sub');
        $last = array_key_last($resolved);
        $baseWhere .= ' and p.'.$fields[$last].' = ?';
        $baseParams[] = (int)$resolved[$last];
        $tables = array('idl' => 'product_list', 'idc' => 'product_cat', 'idi' => 'product_item', 'ids' => 'product_sub');
        $categoryExtraColumns = $last === 'idc' ? ',id_list' : '';
        $row = $d->rawQueryOne("select id,namevi,slugvi,status,photo{$categoryExtraColumns} from #_{$tables[$last]} where id = ? and type = ? and find_in_set('hienthi',status) limit 1", array((int)$resolved[$last], 'san-pham'));
        $selectedCategoryRow = is_array($row) ? $row : array();
        $category = ptsCatalogCategoryDto(is_array($row) ? $row : array(), $segments);
        if($category === null) ptsCatalogError(404, 'CATEGORY_NOT_FOUND');
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
    $needKeys = isset($_GET['is_need']) ? array_values(array_unique(explode(',', $_GET['is_need']))) : array();
    $keywordRaw = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $keyword = $keywordRaw !== '' ? $func->changeTitle($keywordRaw) : '';

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
    if($needKeys)
    {
        list($needWhere, $needParams) = productNeedFilterSql(implode(',', $needKeys));
        $where .= $needWhere;
        $params = array_merge($params, $needParams);
    }
    if($keyword !== '')
    {
        $where .= ' and (p.namevi like ? or p.code like ? or p.slugvi like ? or p.slugen like ?)';
        $keywordLike = '%'.$keyword.'%';
        $params = array_merge($params, array($keywordLike, $keywordLike, $keywordLike, $keywordLike));
    }

    $totalRow = $d->rawQueryOne("select count(*) as total from #_product p where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $orderBy = ptsCatalogSortOrderBy($sortKey, publicProductPricesVisible());
    $defaultOrder = publicProductDefaultOrder('p', publicProductBossCategoryOrder('p'));
    $orderBySql = $orderBy !== null ? $orderBy : preg_replace('/^\s*order\s+by\s+/i', '', $defaultOrder);
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

    $extras = array('root' => ROOT);
    $extras['canonicalPath'] = !empty($category['url']) ? $category['url'] : '/san-pham/';
    $extras['seoFallbackTitle'] = !empty($selectedCategoryRow['namevi']) ? $selectedCategoryRow['namevi'] : 'Sản phẩm';
    if($resolved)
    {
        /* Match sources/product.php SEO actions for the resolved entity depth. */
        $seoActions = array('idl' => 'man_list', 'idc' => 'man_cat', 'idi' => 'man_item', 'ids' => 'man_sub');
        $seoDepth = array_key_last($resolved);
        $seoAction = isset($seoActions[$seoDepth]) ? $seoActions[$seoDepth] : '';
        if($seoAction !== '' && !empty($selectedCategoryRow['id'])) {
            $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 1', array((int)$selectedCategoryRow['id'], 'product', $seoAction, 'san-pham'));
            $extras['seoRow'] = is_array($seoRow) ? $seoRow : array();
        }
    }
    else
    {
        /* Root catalog metadata is managed on #_seopage for type san-pham. */
        $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seopage where type = ? limit 1', array('san-pham'));
        $extras['seoRow'] = is_array($seoRow) ? $seoRow : array();
    }
    $currentPath = $categoryPath === '' ? 'san-pham' : 'danh-muc-san-pham/'.$categoryPath;
    $heroRows = $cache->get("select namevi,link,photo,status from #_photo where type=? order by numb,id desc", array('product-category-hero'), 'result', 7200);
    $heroConfigured = false;
    foreach((array)$heroRows as $heroRow) {
        if(trim(parse_url((string)($heroRow['link'] ?? ''), PHP_URL_PATH), '/') !== $currentPath) continue;
        $heroConfigured = true;
        if(ptsCatalogPublished($heroRow['status'] ?? '') && !empty($heroRow['photo'])) $extras['hero'] = array('photo' => $heroRow['photo'], 'upload' => 'upload/photo/', 'alt' => $heroRow['namevi'] ?? '');
        break;
    }
    /* Keep the source-confirmed lighting fallbacks available when Admin has
       not configured a hero; a hidden Admin record must continue to disable it. */
    $heroFallbacks = array(
        'danh-muc-san-pham/den-nlmt' => array('catalog-lighting-overview-20261008.png', 'Tổng quan giải pháp chiếu sáng năng lượng mặt trời'),
        'danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình')
    );
    if(empty($extras['hero']) && !$heroConfigured && isset($heroFallbacks[$currentPath]) && is_file(ROOT.'assets/images/site-visuals/'.$heroFallbacks[$currentPath][0])) {
        $heroFallback = $heroFallbacks[$currentPath];
        $extras['hero'] = array('photo' => $heroFallback[0], 'upload' => 'assets/images/site-visuals/', 'alt' => $heroFallback[1]);
    }
    if(empty($extras['hero']) && !empty($selectedCategoryRow['photo'])) $extras['hero'] = array('photo' => $selectedCategoryRow['photo'], 'upload' => 'upload/product/', 'alt' => $selectedCategoryRow['namevi'] ?? '');
    if(empty($extras['hero']) && $categoryPath === '' && is_file(ROOT.'assets/images/site-visuals/catalog-products-hero-20261007-v2.png')) $extras['hero'] = array('photo' => 'catalog-products-hero-20261007-v2.png', 'upload' => 'assets/images/site-visuals/', 'alt' => 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar');

    $sectionScope = '';
    $sectionId = 0;
    if(isset($resolved['idl'])) { $sectionScope = 'list'; $sectionId = (int)$resolved['idl']; }
    elseif(isset($resolved['idc'])) { $sectionScope = 'cat'; $sectionId = (int)$resolved['idc']; }
    $blocks = $sectionScope !== '' ? productCategorySectionBlocks($d, $sectionScope, $sectionId, 'vi') : array();
    $split = productSplitFaqBlocks($blocks);
    $cardSplit = productSplitCardsBlocks($split['rest']);
    $extras['faq'] = $split['faq'];
    $extras['cards'] = $cardSplit['cards'];
    if($sectionScope === 'cat' && (empty($extras['faq']) || empty($extras['cards'])) && !empty($selectedCategoryRow['id_list'])) {
        $parentBlocks = productCategorySectionBlocks($d, 'list', (int)$selectedCategoryRow['id_list'], 'vi');
        $parentFaq = productSplitFaqBlocks($parentBlocks);
        $parentCards = productSplitCardsBlocks($parentFaq['rest']);
        if(empty($extras['faq'])) $extras['faq'] = $parentFaq['faq'];
        if(empty($extras['cards'])) $extras['cards'] = $parentCards['cards'];
    }
    if($sectionScope === 'list' && !empty($selectedCategoryRow['slugvi']) && $selectedCategoryRow['slugvi'] === 'den-nlmt' && (empty($extras['faq']) || empty($extras['cards']))) {
        $fallback = productLightingFallbackBlocks();
        if(empty($extras['faq'])) $extras['faq'] = $fallback['faq'];
        if(empty($extras['cards'])) $extras['cards'] = $fallback['cards'];
    }

    $supportRows = $cache->get("select namevi,descvi,photo,link,status from #_photo where type=? order by numb,id desc", array('catalog-campaign'), 'result', 7200);
    foreach((array)$supportRows as $supportRow) {
        if(empty($supportRow['photo']) || !ptsCatalogPublished($supportRow['status'] ?? '')) continue;
        $extras['support'] = array('name' => $supportRow['namevi'] ?? '', 'desc' => $supportRow['descvi'] ?? '', 'photo' => $supportRow['photo'], 'link' => $supportRow['link'] ?? 'lien-he/');
        break;
    }
    if(!$supportRows) $extras['support'] = array('name' => 'Cần hỗ trợ chọn sản phẩm phù hợp?', 'desc' => 'Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp.', 'photo' => 'catalog-equipment-system.jpg', 'link' => 'lien-he/');

    echo json_encode(ptsCatalogPayload(
        $category ?: array(),
        is_array($categories) ? $categories : array(),
        $rows, $page, $perPage, $total, publicProductPricesVisible(),
        array(
            'brands' => is_array($brandOptionRows) ? $brandOptionRows : array(),
            'subcategories' => is_array($subcategoryOptionRows) ? $subcategoryOptionRows : array(),
            'needsEnabled' => strpos($categoryPath, 'den-') !== false || strpos($categoryPath, 'tru-den') !== false
        ),
        array('brandIds' => $brandIds, 'priceKeys' => $priceParam !== '' ? explode(',', $priceParam) : array(), 'subcategoryIds' => $subcategoryIds, 'sort' => $sortKey, 'needKeys' => $needKeys),
        $extras
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_catalog: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
