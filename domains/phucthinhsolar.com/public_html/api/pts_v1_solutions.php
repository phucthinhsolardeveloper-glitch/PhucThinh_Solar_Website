<?php
/**
 * GET /api/pts_v1_solutions.php (Phase A internal contract; no production
 * routing is moved to Nuxt).
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
    $allowedQuery = array('category_type','category_path','page');
    foreach(array_keys($_GET) as $key) if(!in_array($key, $allowedQuery, true)) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }
    $categoryType = isset($_GET['category_type']) && is_string($_GET['category_type']) ? $_GET['category_type'] : '';
    $categoryPath = isset($_GET['category_path']) && is_string($_GET['category_path']) ? trim($_GET['category_path'], '/') : '';
    $categorySegments = $categoryPath === '' ? array() : explode('/', $categoryPath);
    $page = isset($_GET['page']) && is_string($_GET['page']) && preg_match('/^[1-9][0-9]*$/', $_GET['page']) ? (int)$_GET['page'] : 1;
    if(isset($_GET['page']) && (!is_string($_GET['page']) || !preg_match('/^[1-9][0-9]*$/', $_GET['page']) || $page > 10000)) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }
    if((isset($_GET['category_type']) || isset($_GET['category_path'])) && ($categoryType === '' || !in_array($categoryType, array('list','cat','item','sub'), true) || !$categorySegments || count($categorySegments) > 4)) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }
    if($categoryType !== '' && count($categorySegments) !== array('list'=>1,'cat'=>2,'item'=>3,'sub'=>4)[$categoryType]) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }
    foreach($categorySegments as $segment) if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $segment)) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'solution_hub.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'pts_public_richtext.php';
    require_once LIBRARIES.'pts_public_solutions_api.php';

    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $solutionRows = $loadRows(
        'select id,id_list,namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,photo1,slugvi,slugen,status from #_news where type=? order by numb,id desc',
        array('giai-phap')
    );
    $introRows = $loadRows(
        'select namevi,nameen,title_indexvi,title_indexen,descvi,descen,contentvi,contenten,photo,status from #_news where type=? order by numb,id desc',
        array('solution-intro')
    );
    $ctaRows = $loadRows(
        'select namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,status from #_news where type=? order by numb,id desc',
        array('solution-cta')
    );
    $productRows = $loadRows(
        'select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_product_list where type=? order by numb,id desc',
        array('san-pham')
    );

    // Match source/template image approval: basename, extension and file existence.
    // solutionHubImage() checks is_file($upload.$filename) with no ROOT prefix,
    // which only works when the include's cwd is the document root (true for
    // sources/solution_hub.php, reached via index.php). This script lives in
    // api/, so that relative is_file() check silently failed for every real
    // uploaded photo (fallback site-visuals images still worked only because
    // the caller below checks is_file(ROOT.$path) itself). Verify against ROOT
    // here instead of touching the shared legacy function.
    $publicImage = static function($filename, $upload) {
        if(!$filename || basename($filename) !== $filename) return '';
        if(!preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $filename)) return '';
        if(!is_file(ROOT.$upload.$filename)) return '';
        return '/'.ltrim($upload.$filename, '/');
    };
    $attachNewsImages = static function(array $rows) use ($publicImage) {
        foreach($rows as &$row)
        {
            if(!is_array($row)) continue;
            $row['_pts_public_photo'] = $publicImage(isset($row['photo']) ? $row['photo'] : '', UPLOAD_NEWS_L);
            $row['_pts_public_photo1'] = $publicImage(isset($row['photo1']) ? $row['photo1'] : '', UPLOAD_NEWS_L);
        }
        unset($row);
        return $rows;
    };
    $solutionRows = $attachNewsImages($solutionRows);
    $introRows = $attachNewsImages($introRows);
    $ctaRows = $attachNewsImages($ctaRows);

    foreach($productRows as &$productRow)
    {
        if(!is_array($productRow)) continue;
        $productRow['_pts_public_photo'] = $publicImage(isset($productRow['photo']) ? $productRow['photo'] : '', UPLOAD_PRODUCT_L);
    }
    unset($productRow);

    $fallbackImages = array(
        'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
        'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
        'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg'
    );
    foreach($fallbackImages as $slug => $path)
    {
        if(!is_file(ROOT.$path)) unset($fallbackImages[$slug]);
    }

    if(function_exists('closeSessionWrite')) closeSessionWrite();
    $options = is_array($optsetting) ? $optsetting : array();
    $data = ptsPublicSolutionsPayload(
        $solutionRows,
        $introRows,
        $ctaRows,
        $productRows,
        isset($options['hotline']) ? $options['hotline'] : '',
        $fallbackImages
    );
    if($categoryType !== '')
    {
        $category = ptsPublicSolutionsResolveCategory($d, $categoryType, $categorySegments);
        if($category === null) { http_response_code(404); echo '{"error":{"code":"CATEGORY_NOT_FOUND"}}'; exit; }
        $matchingRows = array_values(array_filter(solutionHubSelect($solutionRows), static function($row) use ($category) { return (int)($row[$category['article_field']] ?? 0) === (int)$category['id']; }));
        $total = count($matchingRows);
        $pageRows = array_slice($matchingRows, ($page - 1) * 12, 12);
        $data = ptsPublicSolutionsCategoryPayload($category, $pageRows, $data, $page, $total);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_solutions: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
