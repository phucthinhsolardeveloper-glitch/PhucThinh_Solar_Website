<?php
/** GET-only public Video list DTO for Nuxt migration. */
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

function ptsVideoError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    foreach(array_keys($_GET) as $key) if($key !== 'p') ptsVideoError(400, 'BAD_QUERY');
    if(isset($_GET['p']) && (!is_string($_GET['p']) || !preg_match('/^[1-9][0-9]*$/', $_GET['p']))) ptsVideoError(400, 'BAD_QUERY');
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    if($page > 1000000) ptsVideoError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_media_library_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';

    $rows = $d->rawQuery(
        "select id,namevi,link_video,status from #_photo where type=? and act<>? and find_in_set('hienthi',status) order by numb,id desc",
        array('video','photo_static')
    );
    $safe = array();
    foreach((array)$rows as $row)
    {
        $dto = ptsVideoDto($row);
        if($dto !== null) $safe[] = $dto;
    }
    $perPage = 10;
    $total = count($safe);
    $videos = array_slice($safe, ($page - 1) * $perPage, $perPage);

    $seo = $d->rawQueryOne("select titlevi,descriptionvi,keywordsvi,photo from #_seopage where type=? limit 1", array('video')) ?: array();
    $seoImage = ptsPublicSeoImage($seo['photo'] ?? '', ROOT, UPLOAD_SEOPAGE_L);
    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'path'=>'/video/',
        'title'=>'Video',
        'seo'=>ptsMediaSeoDto($seo, 'Video', 'Video', '/video/', $seoImage),
        'videos'=>$videos,
        'pagination'=>array('page'=>$page,'perPage'=>$perPage,'total'=>$total)
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_video: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
