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
    require_once LIBRARIES.'pts_public_richtext.php';

    $row = $d->rawQueryOne(
        "select id,namevi,slugvi,descvi,contentvi,date_created,status from #_news where slugvi=? and type=? and find_in_set('hienthi',status) limit 1",
        array($slug, 'chinh-sach')
    );
    if(empty($row))
    {
        http_response_code(404);
        echo '{"error":{"code":"POLICY_NOT_FOUND"}}';
        exit;
    }
    $title = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$row['namevi'], ENT_QUOTES, 'UTF-8'))));
    if($title === '')
    {
        http_response_code(404);
        echo '{"error":{"code":"POLICY_NOT_FOUND"}}';
        exit;
    }
    $description = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)($row['descvi'] ?? ''), ENT_QUOTES, 'UTF-8'))));
    $content = ptsPublicRichTextSanitize(isset($row['contentvi']) ? $row['contentvi'] : '');
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
        'date'=>!empty($row['date_created']) ? date('Y-m-d',(int)$row['date_created']) : '',
        'content'=>array('format'=>'sanitized-html','html'=>$content,'sanitizedBy'=>'pts_public_richtext','sanitizerVersion'=>1),
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
