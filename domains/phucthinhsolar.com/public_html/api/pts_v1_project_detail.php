<?php
/** GET /api/pts_v1_project_detail.php — read-only project detail projection. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET'); header('Content-Type: application/json; charset=UTF-8'); http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}'; exit;
}
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
function ptsProjectDetailError($status, $code) { http_response_code($status); echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
try {
    foreach(array_keys($_GET) as $key) if($key !== 'slug') ptsProjectDetailError(400, 'BAD_QUERY');
    if(isset($_GET['slug']) && !is_string($_GET['slug'])) ptsProjectDetailError(400, 'BAD_QUERY');
    $slug = isset($_GET['slug']) ? trim($_GET['slug'], '/') : '';
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsProjectDetailError(400, 'BAD_QUERY');
    define('LIBRARIES', '../libraries/');
    define('THUMBS', 'thumbs');
    define('WATERMARK', 'watermark');
    require_once LIBRARIES.'config.php';
    require_once LIBRARIES.'autoload.php';
    new AutoLoad();
    $d = new PDODb($config['database']);
    require_once LIBRARIES.'pts_public_project_detail_api.php';
    $row = $d->rawQueryOne('select id,id_list,namevi,slugvi,descvi,contentvi,photo,address,tenkhachhang,project_start_date,project_end_date,status,type from #_news where slugvi = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array($slug, 'du-an'));
    if(empty($row)) ptsProjectDetailError(404, 'PROJECT_NOT_FOUND');
    $category = $d->rawQueryOne('select id,namevi,slugvi,status from #_news_list where id = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array((int)$row['id_list'], 'du-an'));
    $row['category_id'] = isset($category['id']) ? (int)$category['id'] : 0;
    $row['category_name'] = isset($category['namevi']) ? $category['namevi'] : '';
    $row['category_slug'] = isset($category['slugvi']) ? $category['slugvi'] : '';
    $row['_pts_cover'] = ptsProjectDetailMedia(isset($row['photo']) ? $row['photo'] : '', ROOT, UPLOAD_NEWS_L);
    $gallery = array();
    $galleryRows = $d->rawQuery('select photo,namevi,link_video,kind,val,status from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val in (?,?) and find_in_set(\'hienthi\',status) order by numb,id desc', array((int)$row['id'], 'news', 'du-an', 'man', 'du-an', 'video'));
    foreach((array)$galleryRows as $galleryRow) { $dto = ptsProjectDetailGalleryDto($galleryRow, ROOT); if($dto !== null) $gallery[] = $dto; }
    $related = array();
    $relatedRows = $d->rawQuery('select p.id,p.namevi,p.slugvi,p.photo,p.code,p.status from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and find_in_set(\'hienthi\',pp.status) and p.type = ? and find_in_set(\'hienthi\',p.status) order by pp.numb,pp.id', array((int)$row['id'], 'san-pham'));
    foreach((array)$relatedRows as $relatedRow) { $dto = ptsProjectDetailProductDto($relatedRow, ROOT); if($dto !== null) $related[] = $dto; }
    echo json_encode(ptsProjectDetailPayload($row, $gallery, $related, array('project' => 'legacy /'.$slug.'/', 'gallery' => 'news.php detail branch', 'relatedEquipment' => 'product_projects.php')), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_project_detail: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
