<?php
/** GET-only public Gallery detail DTO for Nuxt migration. */
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

function ptsGalleryDetailError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try
{
    if(count($_GET) !== 1 || !isset($_GET['slug']) || !is_string($_GET['slug'])) ptsGalleryDetailError(400, 'BAD_QUERY');
    $slug = trim($_GET['slug'], '/');
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsGalleryDetailError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_media_library_api.php';

    $row = $d->rawQueryOne(
        "select id,namevi,slugvi,photo,descvi,status from #_product where slugvi=? and type=? and find_in_set('hienthi',status) limit 1",
        array($slug, 'thu-vien-anh')
    );
    if(empty($row)) ptsGalleryDetailError(404, 'GALLERY_NOT_FOUND');
    $row['_pts_public_photo'] = !empty($row['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$row['photo']) ? $row['photo'] : '';
    $album = ptsGalleryAlbumDto($row);
    if($album === null) ptsGalleryDetailError(404, 'GALLERY_NOT_FOUND');

    $imageRows = $d->rawQuery(
        "select id,photo,namevi,status from #_gallery where id_parent=? and com='product' and type=? and kind='man' and val=? and find_in_set('hienthi',status) order by numb,id desc",
        array((int)$row['id'], 'thu-vien-anh', 'thu-vien-anh')
    );
    $images = array();
    foreach((array)$imageRows as $imageRow)
    {
        $imageRow['_pts_public_photo'] = !empty($imageRow['photo']) && is_file(ROOT.UPLOAD_PRODUCT_L.$imageRow['photo']) ? $imageRow['photo'] : '';
        $dto = ptsGalleryImageDto($imageRow);
        if($dto !== null) $images[] = $dto;
        if(count($images) >= 120) break;
    }

    $seo = $d->rawQueryOne(
        "select titlevi,descriptionvi,keywordsvi from #_seo where id_parent=? and com=? and act=? and type=? limit 1",
        array((int)$row['id'],'product','man','thu-vien-anh')
    ) ?: array();
    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'album'=>$album,
        'images'=>$images,
        'seo'=>ptsMediaSeoDto($seo, $album['title'], $album['description'], '/'.$album['slug'].'/')
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_gallery_detail: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
