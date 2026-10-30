<?php
/** GET-only public Gallery list DTO for Nuxt migration. */
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

function ptsGalleryError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    foreach(array_keys($_GET) as $key) if($key !== 'p') ptsGalleryError(400, 'BAD_QUERY');
    if(isset($_GET['p']) && (!is_string($_GET['p']) || !preg_match('/^[1-9][0-9]*$/', $_GET['p']))) ptsGalleryError(400, 'BAD_QUERY');
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    if($page > 1000000) ptsGalleryError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_media_library_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';

    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $where = "type=? and find_in_set('hienthi',status) and slugvi<>''";
    $count = $d->rawQueryOne("select count(*) as num from #_product where {$where}", array('thu-vien-anh'));
    $total = is_array($count) ? max(0, (int)($count['num'] ?? 0)) : 0;
    $rows = $d->rawQuery(
        "select id,namevi,slugvi,photo,descvi,status from #_product where {$where} order by numb,id desc limit ".(int)$offset.",".(int)$perPage,
        array('thu-vien-anh')
    );
    $albums = array();
    foreach((array)$rows as $row)
    {
        $row['_pts_public_photo'] = !empty($row['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo']) ? $row['photo'] : '';
        $dto = ptsGalleryAlbumDto($row);
        if($dto !== null) $albums[] = $dto;
    }

    $seo = $d->rawQueryOne("select titlevi,descriptionvi,keywordsvi,photo from #_seopage where type=? limit 1", array('thu-vien-anh')) ?: array();
    $seoImage = ptsPublicSeoImage($seo['photo'] ?? '', ROOT, UPLOAD_SEOPAGE_L);
    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'path'=>'/thu-vien-anh/',
        'title'=>'Thư viện ảnh',
        'seo'=>ptsMediaSeoDto($seo, 'Thư viện ảnh', 'Thư viện ảnh', '/thu-vien-anh/', $seoImage),
        'albums'=>$albums,
        'pagination'=>array('page'=>$page,'perPage'=>$perPage,'total'=>$total)
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_gallery: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
