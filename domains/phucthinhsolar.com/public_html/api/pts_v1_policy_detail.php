<?php
/** GET-only published support/policy detail for Nuxt. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}
$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug'], " /\t\n\r\0\x0B") : '';
if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || count($_GET) !== 1)
{
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(400);
    echo '{"error":{"code":"BAD_QUERY"}}';
    exit;
}
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_news_detail_api.php';

    $row = $d->rawQueryOne(
        "select id,id_list,id_user,namevi,slugvi,photo,descvi,contentvi,date_created,status from #_news where slugvi=? and type=? and find_in_set('hienthi',status) limit 1",
        array($slug, 'chinh-sach')
    );
    if(empty($row))
    {
        http_response_code(404);
        echo '{"error":{"code":"POLICY_NOT_FOUND"}}';
        exit;
    }
    $row['_pts_public_photo'] = !empty($row['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
    $title = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$row['namevi'], ENT_QUOTES, 'UTF-8'))));
    if($title === '')
    {
        http_response_code(404);
        echo '{"error":{"code":"POLICY_NOT_FOUND"}}';
        exit;
    }
    $description = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)($row['descvi'] ?? ''), ENT_QUOTES, 'UTF-8'))));
    $content = ptsPublicRichTextSanitize(isset($row['contentvi']) ? $row['contentvi'] : '');
    $author = array();
    if((int)($row['id_user'] ?? 0) > 0) $author = $d->rawQueryOne("select fullname from #_user where id=? limit 1", array((int)$row['id_user'])) ?: array();
    $relatedRows = $d->rawQuery(
        "select id,namevi,slugvi,photo,descvi,date_created,status from #_news where id<>? and id_list=? and type=? and find_in_set('hienthi',status) and slugvi<>'' order by numb,id desc limit 12",
        array((int)$row['id'], (int)$row['id_list'], 'chinh-sach')
    );
    foreach($relatedRows as &$relatedRow) $relatedRow['_pts_public_photo'] = !empty($relatedRow['photo']) && is_file(ROOT.UPLOAD_NEWS_L.$relatedRow['photo']) ? $relatedRow['photo'] : '';
    unset($relatedRow);
    $seo = $d->rawQueryOne("select titlevi,descriptionvi,keywordsvi from #_seo where id_parent=? and com=? and act=? and type=? limit 1", array((int)$row['id'],'news','man','chinh-sach')) ?: array();
    $plain = function($value) {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8'))));
    };

    echo json_encode(array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'id'=>(int)$row['id'],
        'title'=>$title,
        'slug'=>$slug,
        'canonical'=>'/ho-tro/'.$slug.'/',
        'description'=>$description,
        'cover'=>ptsNewsDetailImage(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
        'date'=>!empty($row['date_created']) ? date('Y-m-d',(int)$row['date_created']) : '',
        'author'=>array('name'=>ptsNewsDetailAuthor($author['fullname'] ?? '')),
        'content'=>array('format'=>'sanitized-html','html'=>$content,'sanitizedBy'=>'pts_public_richtext','sanitizerVersion'=>1),
        'related'=>ptsNewsDetailRelatedDto($relatedRows),
        'seo'=>array(
            'title'=>$plain($seo['titlevi'] ?? '') ?: $title,
            'description'=>$plain($seo['descriptionvi'] ?? '') ?: $description,
            'keywords'=>$plain($seo['keywordsvi'] ?? ''),
            'canonical'=>'/ho-tro/'.$slug.'/'
        )
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_policy_detail: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
