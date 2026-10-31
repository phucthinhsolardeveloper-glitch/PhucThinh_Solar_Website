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

foreach(array_keys($_GET) as $queryKey)
{
    if($queryKey !== 'category')
    {
        http_response_code(400);
        echo '{"error":{"code":"BAD_QUERY"}}';
        exit;
    }
}
if(isset($_GET['category']) && (!is_string($_GET['category']) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $_GET['category'])))
{
    http_response_code(400);
    echo '{"error":{"code":"BAD_QUERY"}}';
    exit;
}
$categorySlug = isset($_GET['category']) ? $_GET['category'] : '';

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'solution_hub.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'pts_public_richtext.php';
    require_once LIBRARIES.'pts_public_seo_api.php';
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
    $categoryRows = $loadRows(
        "select nl.id,nl.namevi,nl.descvi,nl.slugvi,nl.photo,nl.status from #_news_list nl where nl.type=? and find_in_set('hienthi',nl.status) and exists(select 1 from #_news n where n.id_list=nl.id and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'') order by nl.numb,nl.id desc",
        array('giai-phap', 'giai-phap')
    );
    // Match the active legacy /giai-phap/ SEO lookup in sources/news.php.
    $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi,photo from #_seopage where type = ? limit 1', array('giai-phap'));
    $seo = ptsPublicSeoDto(is_array($seoRow) ? $seoRow : array(), ROOT, UPLOAD_SEOPAGE_L);

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
        $fallbackImages,
        'assets/images/site-visuals/catalog-street-lighting.jpg',
        $seo
    );
    if($categorySlug !== '')
    {
        $selectedCategory = null;
        foreach($categoryRows as $categoryRow)
        {
            if(is_array($categoryRow) && isset($categoryRow['slugvi']) && $categoryRow['slugvi'] === $categorySlug)
            {
                $selectedCategory = $categoryRow;
                break;
            }
        }
        $categoryDto = null;
        if($selectedCategory)
        {
            // The legacy taxonomy SEO row is keyed by news/man_list/giai-phap.
            // ptsPublicSeoDto expects the raw filename and validates it against ROOT.
            $categorySeoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 1', array((int)$selectedCategory['id'], 'news', 'man_list', 'giai-phap'));
            $categorySeo = ptsPublicSeoDto(
                is_array($categorySeoRow) ? $categorySeoRow : array(),
                ROOT,
                UPLOAD_NEWS_L,
                isset($selectedCategory['photo']) ? $selectedCategory['photo'] : ''
            );
            $categoryDto = ptsPublicSolutionCategoryDto($selectedCategory, $categoryRows, $solutionRows, $categorySeo);
        }
        if($categoryDto === null)
        {
            http_response_code(404);
            echo '{"error":{"code":"CATEGORY_NOT_FOUND"}}';
            exit;
        }
        $data['category'] = $categoryDto;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_solutions: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
