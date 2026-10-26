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
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'solution_hub.php';
    require_once LIBRARIES.'product_category_urls.php';
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
    $publicImage = static function($filename, $upload) {
        $approved = solutionHubImage($filename, $upload);
        return $approved === '' ? '' : '/'.ltrim($approved, '/');
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_solutions: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
