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
    require_once LIBRARIES.'solution_hub.php';
    $row = $d->rawQueryOne('select id,id_list,namevi,slugvi,descvi,contentvi,photo,address,tenkhachhang,project_start_date,project_end_date,status,type from #_news where slugvi = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array($slug, 'du-an'));
    if(empty($row)) ptsProjectDetailError(404, 'PROJECT_NOT_FOUND');
    $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = ? and com = ? and act = ? and type = ? limit 0,1', array((int)$row['id'], 'news', 'man', 'du-an'));
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
    $relatedProjectRows = $d->rawQuery('select id,namevi,slugvi,photo,address,status from #_news where id<>? and id_list=? and type=? and find_in_set(\'hienthi\',status) order by numb,id desc limit 0,3', array((int)$row['id'], (int)$row['id_list'], 'du-an'));
    $relatedProjects = array();
    foreach((array)$relatedProjectRows as $relatedProjectRow) { $dto = ptsProjectDetailRelatedProjectDto($relatedProjectRow, ROOT); if($dto !== null) $relatedProjects[] = $dto; }
    $ctaRows = $d->rawQuery('select namevi,title_indexvi,descvi,content1vi,photo,status from #_news where type=? order by numb,id desc', array('project-cta'));
    $trustRows = $d->rawQuery('select namevi,photo,status from #_news where type=? and find_in_set(\'hienthi\',status) order by numb,id desc limit 0,3', array('project-trust'));
    $ctaVisible = solutionHubSelect($ctaRows, '', 1);
    $cta = null;
    if(!$ctaRows || $ctaVisible)
    {
        $ctaRow = $ctaVisible ? $ctaVisible[0] : array();
        $ctaImage = ptsProjectDetailMedia(isset($ctaRow['photo']) ? $ctaRow['photo'] : '', ROOT, 'upload/news/');
        if($ctaImage === '' && is_file(ROOT.'upload/news/anh-quan-gl-4-9449.jpg')) $ctaImage = '/upload/news/anh-quan-gl-4-9449.jpg';
        $cta = array(
            'eyebrow' => solutionHubField($ctaRow, 'title_index', 'vi') ?: 'Đồng hành cùng năng lượng sạch',
            'title' => solutionHubField($ctaRow, 'name', 'vi') ?: 'Bạn có công trình cần tư vấn?',
            'description' => solutionHubField($ctaRow, 'desc', 'vi') ?: 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'buttonLabel' => solutionHubField($ctaRow, 'content1', 'vi') ?: 'Nhận tư vấn miễn phí',
            'image' => $ctaImage,
            'hotline' => isset($optsetting['hotline']) && is_scalar($optsetting['hotline']) ? trim((string)$optsetting['hotline']) : '',
            'trust' => ptsProjectDetailTrustDto((array)$trustRows, ROOT)
        );
    }
    echo json_encode(ptsProjectDetailPayload($row, $gallery, $related, array('project' => 'legacy /'.$slug.'/', 'gallery' => 'news.php detail branch', 'relatedEquipment' => 'product_projects.php', 'relatedProjects' => 'news.php same category', 'cta' => 'project_hub.php project-cta slot'), $relatedProjects, $cta, is_array($seoRow) ? $seoRow : array()), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_project_detail: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
