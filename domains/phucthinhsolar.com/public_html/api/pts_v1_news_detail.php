<?php
/** GET /api/pts_v1_news_detail.php?slug=... — published, read-only news detail. */
function ptsNewsDetailError($status, $code)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    ptsNewsDetailError(405, 'METHOD_NOT_ALLOWED');
}

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug'], " /\t\n\r\0\x0B") : '';
if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsNewsDetailError(400, 'BAD_SLUG');
foreach(array_keys($_GET) as $key) if($key !== 'slug') ptsNewsDetailError(400, 'BAD_QUERY');

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

try {
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_news_detail_api.php';
    $row = $d->rawQueryOne("select id,id_list,id_user,namevi,slugvi,photo,descvi,contentvi,content1vi,date_created,date_updated,status,type from #_news where slugvi=? and type=? and find_in_set('hienthi',status) limit 1", array($slug, 'tin-tuc'));
    if(empty($row)) ptsNewsDetailError(404, 'ARTICLE_NOT_FOUND');
    $row['_pts_public_photo'] = !empty($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';

    $category = array();
    if((int)$row['id_list'] > 0) {
        $categoryRow = $d->rawQueryOne("select id,namevi,descvi,slugvi,status from #_news_list where id=? and type=? and find_in_set('hienthi',status) limit 1", array((int)$row['id_list'], 'tin-tuc'));
        if(!empty($categoryRow) && ptsNewsDetailSlug($categoryRow['slugvi'] ?? '') !== '') {
            $category = array('id'=>(int)$categoryRow['id'], 'name'=>ptsNewsDetailText($categoryRow['namevi'] ?? ''), 'slug'=>ptsNewsDetailSlug($categoryRow['slugvi']), 'url'=>'/'.ptsNewsDetailSlug($categoryRow['slugvi']).'/');
        }
    }
    $author = array();
    if((int)($row['id_user'] ?? 0) > 0) $author = $d->rawQueryOne("select fullname from #_user where id=? limit 1", array((int)$row['id_user'])) ?: array();
    $photos = $d->rawQuery("select photo,namevi from #_gallery where id_parent=? and com=? and type=? and kind=? and val=? and find_in_set('hienthi',status) order by numb,id desc", array((int)$row['id'], 'news', 'tin-tuc', 'man', 'tin-tuc'));
    $videos = $d->rawQuery("select namevi,link_video from #_gallery where id_parent=? and com=? and type=? and kind=? and val=? and find_in_set('hienthi',status) order by numb,id desc", array((int)$row['id'], 'news', 'tin-tuc', 'man', 'video'));
    $relatedRows = $d->rawQuery("select n.id,n.namevi,n.slugvi,n.photo,n.descvi,n.date_created,n.status,u.fullname as _pts_public_author from #_news n left join #_user u on u.id=n.id_user where n.id<>? and n.id_list=? and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'' order by n.numb,n.id desc limit 12", array((int)$row['id'], (int)$row['id_list'], 'tin-tuc'));
    foreach($relatedRows as &$relatedRow) $relatedRow['_pts_public_photo'] = !empty($relatedRow['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$relatedRow['photo']) ? $relatedRow['photo'] : ''; unset($relatedRow);
    $ctaRows = $d->rawQuery('select namevi,descvi,content1vi,photo,status from #_news where type=? order by numb,id desc', array('news-cta'));
    foreach($ctaRows as &$ctaRow) $ctaRow['_pts_public_photo'] = !empty($ctaRow['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$ctaRow['photo']) ? $ctaRow['photo'] : ''; unset($ctaRow);
    $seo = $d->rawQueryOne("select titlevi,descriptionvi,keywordsvi from #_seo where id_parent=? and com=? and act=? and type=? limit 1", array((int)$row['id'], 'news', 'man', 'tin-tuc')) ?: array();
    $article = ptsNewsDetailArticleDto($row, $category, $author, ptsNewsDetailMediaDto($photos, $videos), $seo);
    if($article === null) ptsNewsDetailError(404, 'ARTICLE_NOT_FOUND');
    echo json_encode(array(
        'article'=>$article,
        'related'=>ptsNewsDetailRelatedDto($relatedRows),
        'cta'=>ptsNewsDetailCtaDto($ctaRows, isset($optsetting['hotline']) ? $optsetting['hotline'] : '')
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
    error_log('pts_v1_news_detail: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
