<?php
/** GET /api/pts_v1_news.php — internal read-only news contract. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

function ptsNewsError($status, $code) {
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $allowed = array('q', 'p', 'category', 'category_type', 'category_path');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsNewsError(400, 'BAD_QUERY');
    foreach($allowed as $key) if(array_key_exists($key, $_GET) && (!is_string($_GET[$key]) || (in_array($key, array('p', 'category'), true) && trim($_GET[$key]) === ''))) ptsNewsError(400, 'BAD_QUERY');
    $query = isset($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 120, 'UTF-8') : '';
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $categorySlug = isset($_GET['category']) ? trim((string)$_GET['category'], '/') : '';
    $categoryType = isset($_GET['category_type']) ? (string)$_GET['category_type'] : ($categorySlug !== '' ? 'list' : '');
    $categorySegments = array();
    if(isset($_GET['category_path'])) {
        if(!isset($_GET['category_type']) || !is_string($_GET['category_path'])) ptsNewsError(400, 'BAD_QUERY');
        $categorySegments = explode('/', trim($_GET['category_path'], '/'));
        if(in_array('', $categorySegments, true) || count($categorySegments) > 4) ptsNewsError(400, 'BAD_QUERY');
        if($categorySlug !== '' && end($categorySegments) !== $categorySlug) ptsNewsError(400, 'BAD_QUERY');
        $categorySlug = (string)end($categorySegments);
    } elseif($categorySlug !== '') $categorySegments = array($categorySlug);
    if($categoryType !== '' && !in_array($categoryType, array('list','cat','item','sub'), true)) ptsNewsError(400, 'BAD_QUERY');
    if($categoryType !== '' && $categorySlug === '') ptsNewsError(400, 'BAD_QUERY');
    if(isset($_GET['p']) && !preg_match('/^[1-9][0-9]*$/', $_GET['p'])) ptsNewsError(400, 'BAD_QUERY');
    if($page > 1000000 || ($categorySlug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $categorySlug))) ptsNewsError(400, 'BAD_QUERY');
    foreach($categorySegments as $segment) if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $segment)) ptsNewsError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_news_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';
    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $categoryRows = $loadRows("select id,namevi,descvi,slugvi,photo,status from #_news_list where type=? and find_in_set('hienthi',status) and exists(select 1 from #_news n where n.id_list=#_news_list.id and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'') order by numb,id desc", array('tin-tuc', 'tin-tuc'));
    $categories = array(); $categoryMap = array(); $selectedCategory = null;
    foreach($categoryRows as $row) {
        if(!is_array($row)) continue;
        $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
        $categories[] = $row;
        if($categorySlug !== '' && (string)($row['slugvi'] ?? '') === $categorySlug) $selectedCategory = $row;
    }
    $categoryResolution = null;
    if($categorySlug !== '') {
        $categoryResolution = ptsNewsResolveCategoryHierarchy($d, $categoryType, $categorySegments);
        if($categoryResolution === null) ptsNewsError(404, 'CATEGORY_NOT_FOUND');
        $selectedCategory = $categoryResolution['row'];
        $seoActions = array('list' => 'man_list', 'cat' => 'man_cat', 'item' => 'man_item', 'sub' => 'man_sub');
        $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 1', array((int)$selectedCategory['id'], 'news', $seoActions[$categoryType], 'tin-tuc'));
        $categoryMetadata = is_array($selectedCategory['_pts_public_category_metadata'] ?? null) ? $selectedCategory['_pts_public_category_metadata'] : array();
        $categoryMetadata['seo'] = ptsPublicSeoDto(is_array($seoRow) ? $seoRow : array(), ROOT, UPLOAD_NEWS_L, $selectedCategory['photo'] ?? '');
        $selectedCategory['_pts_public_category_metadata'] = $categoryMetadata;
    }
    $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi,photo from #_seopage where type=? limit 0,1', array('tin-tuc'));
    $seo = ptsPublicSeoDto(is_array($seoRow) ? $seoRow : array(), ROOT, UPLOAD_SEOPAGE_L);

    $fields = 'id,id_list,namevi,slugvi,photo,descvi,contentvi,date_created,status';
    $where = "type=? and find_in_set('hienthi',status) and slugvi<>''";
    $params = array('tin-tuc'); $featuredRows = array(); $heroEnabled = true;
    $introRows = $loadRows('select namevi,descvi,status from #_news where type=? order by numb,id desc', array('news-intro'));
    $introConfigured = (bool)$introRows; $intro = array();
    foreach($introRows as $row) if(is_array($row) && ptsNewsPublished($row['status'] ?? '')) { $intro = $row; break; }
    if($introConfigured) $heroEnabled = ptsNewsHasStatus($intro['status'] ?? '', 'hero');
    if(!$categorySlug && $query === '' && $heroEnabled) {
        $selection = $d->rawQueryOne("select count(*) as num from #_news where type=? and find_in_set('newshub',status)", array('tin-tuc'));
        $hasNewshub = !empty($selection['num']);
        $heroWhere = $where.($hasNewshub ? " and find_in_set('newshub',status)" : '');
        $featuredRows = $loadRows('select '.$fields.' from #_news where '.$heroWhere.' order by numb,date_created desc,id desc limit 3', $params);
        if($featuredRows) { $where .= ' and id not in ('.implode(',', array_fill(0, count($featuredRows), '?')).')'; foreach($featuredRows as $row) $params[] = (int)$row['id']; }
    }
    if($selectedCategory) { $where .= ' and '.$categoryResolution['article_field'].'=?'; $params[] = (int)$selectedCategory['id']; }
    if($query !== '') {
        $pattern = '%'.strtr($query, array('!' => '!!', '%' => '!%', '_' => '!_')).'%';
        $where .= " and (namevi like ? escape '!' or descvi like ? escape '!' or contentvi like ? escape '!')";
        array_push($params, $pattern, $pattern, $pattern);
    }
    $countRow = $d->rawQueryOne('select count(*) as num from #_news where '.$where, $params);
    $total = is_array($countRow) ? (int)($countRow['num'] ?? 0) : 0;
    $pages = max(1, (int)ceil($total / 6)); $page = min($page, $pages);
    $rows = $loadRows('select '.$fields.' from #_news where '.$where.' order by date_created desc,id desc limit '.(($page - 1) * 6).',6', $params);
    foreach($rows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : ''; unset($row);
    foreach($featuredRows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : ''; unset($row);
    $ctaRows = $loadRows('select namevi,descvi,content1vi,photo,status from #_news where type=? order by numb,id desc', array('news-cta'));
    foreach($ctaRows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : ''; unset($row);
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    echo json_encode(ptsNewsPayload($categories, $rows, $featuredRows, $intro, $introConfigured, $ctaRows, (bool)$ctaRows, $page, $total, $query, $selectedCategory, is_array($optsetting) && isset($optsetting['hotline']) ? $optsetting['hotline'] : '', $heroEnabled, $seo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
    error_log('pts_v1_news: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
