<?php
/** GET /api/pts_v1_projects.php — internal read-only projects contract. */
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

function ptsProjectsError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    $allowed = array('category', 'page', 'per_page');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowed, true)) ptsProjectsError(400, 'BAD_QUERY');
    foreach($allowed as $key) if(array_key_exists($key, $_GET) && (!is_string($_GET[$key]) || trim($_GET[$key]) === '')) ptsProjectsError(400, 'BAD_QUERY');
    $categorySlug = isset($_GET['category']) ? trim((string)$_GET['category'], '/') : '';
    if($categorySlug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $categorySlug)) ptsProjectsError(400, 'BAD_QUERY');
    if(isset($_GET['page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['page'])) ptsProjectsError(400, 'BAD_QUERY');
    if(isset($_GET['per_page']) && !preg_match('/^[1-9][0-9]*$/', $_GET['per_page'])) ptsProjectsError(400, 'BAD_QUERY');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
    if($page > 10000 || $perPage > 48) ptsProjectsError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_projects_api.php';
    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $categoryRows = $loadRows("select id,namevi,descvi,photo,slugvi,status from #_news_list where type=? and find_in_set('hienthi',status) order by numb,id desc", array('du-an'));
    $categories = array();
    $selectedCategory = null;
    foreach($categoryRows as $row)
    {
        if(!is_array($row)) continue;
        $categories[] = $row;
        if($categorySlug !== '' && (string)($row['slugvi'] ?? '') === $categorySlug) $selectedCategory = $row;
    }
    if($categorySlug !== '' && $selectedCategory === null) ptsProjectsError(404, 'CATEGORY_NOT_FOUND');

    $where = "type=? and find_in_set('hienthi',status)";
    $params = array('du-an');
    if($selectedCategory !== null) { $where .= ' and id_list=?'; $params[] = (int)$selectedCategory['id']; }
    $totalRow = $d->rawQueryOne("select count(*) as total from #_news where {$where}", $params);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    $offset = ($page - 1) * $perPage;
    $rows = $loadRows("select id,id_list,namevi,slugvi,photo,descvi,address,status from #_news where {$where} order by numb,id desc limit ".(int)$offset.', '.(int)$perPage, $params);
    // project_hub.php chooses featured from the already paginated first page;
    // do not scan later rows for a featured flag that PHP would not see.
    $allRows = $page === 1 && $selectedCategory === null ? $rows : array();
    $featuredRow = array();
    if($allRows)
    {
        foreach($allRows as $index => $row) if(is_array($row) && ptsProjectsPublished($row['status'] ?? '') && in_array('projecthub', array_map('trim', explode(',', (string)($row['status'] ?? ''))), true)) { $featuredRow = $row; unset($allRows[$index]); break; }
        if(!$featuredRow && $allRows) { $featuredRow = reset($allRows); unset($allRows[key($allRows)]); }
        $rows = array_values($allRows);
    }
    foreach($categoryRows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
    unset($row);
    foreach($rows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
    unset($row);
    if($featuredRow) $featuredRow['_pts_public_photo'] = isset($featuredRow['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$featuredRow['photo']) ? $featuredRow['photo'] : '';
    $ctaRows = $loadRows('select namevi,descvi,content1vi,photo,status from #_news where type=? order by numb,id desc', array('project-cta'));
    foreach($ctaRows as &$row) $row['_pts_public_photo'] = isset($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
    unset($row);
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    echo json_encode(ptsProjectsPayload($categoryRows, $rows, $featuredRow, $ctaRows, $page, $perPage, $total, $selectedCategory ? ptsProjectsCategoryDto($selectedCategory) : null, is_array($optsetting) && isset($optsetting['hotline']) ? $optsetting['hotline'] : ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_projects: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
