<?php
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { header('Allow: GET'); header('Content-Type: application/json; charset=UTF-8'); http_response_code(405); echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}'; exit; }
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
function ptsSolutionDetailError($status, $code) { http_response_code($status); echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
try {
    foreach(array_keys($_GET) as $key) if($key !== 'slug') ptsSolutionDetailError(400, 'BAD_QUERY');
    if(isset($_GET['slug']) && !is_string($_GET['slug'])) ptsSolutionDetailError(400, 'BAD_QUERY');
    $slug = isset($_GET['slug']) ? trim($_GET['slug'], '/') : '';
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsSolutionDetailError(400, 'BAD_QUERY');
    define('LIBRARIES', '../libraries/');
    define('THUMBS', 'thumbs');
    define('WATERMARK', 'watermark');
    require_once LIBRARIES.'config.php';
    require_once LIBRARIES.'autoload.php'; new AutoLoad();
    $d = new PDODb($config['database']);
    require_once LIBRARIES.'solution_hub.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'pts_public_solution_detail_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';
    $row = $d->rawQueryOne('select id,namevi,nameen,descvi,descen,contentvi,contenten,content1vi,content1en,photo,photo1,slugvi,slugen,status from #_news where slugvi = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array($slug, 'giai-phap'));
    if(empty($row)) ptsSolutionDetailError(404, 'SOLUTION_NOT_FOUND');
    /* News detail SEO is stored with com=news, act=man and the record type. */
    $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 1', array((int)$row['id'], 'news', 'man', 'giai-phap'));
    $seo = ptsPublicSeoDto(is_array($seoRow) ? $seoRow : array(), ROOT, UPLOAD_NEWS_L, $row['photo'] ?? '');
    $load = static function($sql, array $params) use ($d) { $rows = $d->rawQuery($sql, $params); return is_array($rows) ? $rows : array(); };
    $solutionRows = $load('select id,namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,photo1,slugvi,slugen,status from #_news where type=? order by numb,id desc', array('giai-phap'));
    $productRows = $load('select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_product_list where type=? order by numb,id desc', array('san-pham'));
    $ctaRows = $load('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,status from #_news where type=? order by numb,id desc', array('solution-cta'));
    $publicImage = static function($filename, $upload) { $url = ptsSolutionDetailMediaUrl($filename, ROOT, $upload); return $url; };
    foreach($solutionRows as &$item) { $item['_pts_public_photo'] = $publicImage($item['photo'] ?? '', UPLOAD_NEWS_L); $item['_pts_public_photo1'] = $publicImage($item['photo1'] ?? '', UPLOAD_NEWS_L); } unset($item);
    foreach($productRows as &$item) $item['_pts_public_photo'] = $publicImage($item['photo'] ?? '', UPLOAD_PRODUCT_L); unset($item);
    foreach($ctaRows as &$item) $item['_pts_public_photo'] = $publicImage($item['photo'] ?? '', UPLOAD_NEWS_L); unset($item);
    $gallery = $load('select photo from #_gallery where id_parent = ? and com=\'news\' and type = ? and kind=\'man\' and val = ? and find_in_set(\'hienthi\',status) order by numb,id desc', array((int)$row['id'], 'giai-phap', 'giai-phap'));
    $safeGallery = array(); foreach($gallery as $item) { $image = $publicImage($item['photo'] ?? '', UPLOAD_NEWS_L); if($image !== '') $safeGallery[] = array('photo' => $image); }
    $row['_pts_public_photo'] = $publicImage($row['photo'] ?? '', UPLOAD_NEWS_L); $row['_pts_public_photo1'] = $publicImage($row['photo1'] ?? '', UPLOAD_NEWS_L);
    // Exact source fallback map from sources/solution_hub.php, only when CMS has no image.
    $sourceFallbackImages = array(
        'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
        'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
        'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg'
    );
    if($row['_pts_public_photo1'] === '' && $row['_pts_public_photo'] === ''
        && isset($sourceFallbackImages[$slug]) && is_file(ROOT.$sourceFallbackImages[$slug]))
        $row['_pts_public_photo'] = '/'.$sourceFallbackImages[$slug];
    $hotline = isset($optsetting['hotline']) && is_scalar($optsetting['hotline']) ? trim((string)$optsetting['hotline']) : '';
    echo json_encode(ptsSolutionDetailPayload($row, $safeGallery, $solutionRows, $productRows, $ctaRows, $hotline, $seo), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_solution_detail: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
