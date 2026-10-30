<?php
/** Session-aware PDP supplemental read model for Nuxt. */
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

function ptsPdpExtrasError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function ptsPdpExtrasText($value)
{
    if(!is_scalar($value)) return '';
    return trim(preg_replace('/\s+/u',' ',strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))));
}
function ptsPdpExtrasVideo($value)
{
    if(!is_scalar($value)) return '';
    $parts = parse_url(trim(htmlspecialchars_decode((string)$value, ENT_QUOTES)));
    if(!is_array($parts) || empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $id = '';
    if(in_array($host,array('youtu.be','www.youtu.be'),true)) $id = trim(isset($parts['path']) ? $parts['path'] : '','/');
    elseif(in_array($host,array('youtube.com','www.youtube.com','m.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com'),true))
    {
        if(!empty($parts['query'])) { parse_str($parts['query'],$query); $id = isset($query['v']) ? $query['v'] : ''; }
        if($id === '' && !empty($parts['path']) && preg_match('~/(?:embed|shorts)/([A-Za-z0-9_-]{11})~',$parts['path'],$m)) $id=$m[1];
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/',$id) ? 'https://www.youtube-nocookie.com/embed/'.$id : '';
}
function ptsPdpExtrasPhoto($value)
{
    $value = is_scalar($value) ? basename(trim((string)$value)) : '';
    if($value === '' || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i',$value)) return '';
    return is_file(ROOT.UPLOAD_PHOTO_L.$value) ? '/'.trim(UPLOAD_PHOTO_L,'/\\').'/'.$value : '';
}
function ptsPdpExtrasReviewVideo($row, $config)
{
    if(!is_array($row)) return null;
    $filename = isset($row['video']) && is_scalar($row['video']) ? basename(trim((string)$row['video'])) : '';
    $allowed = isset($config['website']['video']['extension']) && is_array($config['website']['video']['extension'])
        ? array_map('strtolower', $config['website']['video']['extension']) : array();
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $directory = defined('UPLOAD_VIDEO_L') ? UPLOAD_VIDEO_L : 'upload/video/';
    if($filename === '' || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $filename)
        || !in_array($extension, $allowed, true) || !is_file(ROOT.$directory.$filename)) return null;
    $poster = ptsPdpExtrasPhoto(isset($row['photo']) ? $row['photo'] : '');
    return array('url'=>'/'.trim($directory,'/\\').'/'.$filename, 'poster'=>$poster);
}

if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    ptsPdpExtrasError(405,'METHOD_NOT_ALLOWED');
}
if(!isset($_GET['slug']) || !is_string($_GET['slug'])
    || array_diff(array_keys($_GET), array('slug','reviewOffset','replyParent','replyOffset'))) ptsPdpExtrasError(400,'BAD_QUERY');
$slug=trim($_GET['slug'],'/');
if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)) ptsPdpExtrasError(400,'BAD_QUERY');
$reviewOffset=0;
if(isset($_GET['reviewOffset']))
{
    if(!is_string($_GET['reviewOffset']) || !preg_match('/^(?:0|[1-9][0-9]{0,5})$/',$_GET['reviewOffset'])) ptsPdpExtrasError(400,'BAD_QUERY');
    $reviewOffset=(int)$_GET['reviewOffset'];
}
$replyParent=0;
$replyOffset=0;
if(isset($_GET['replyParent']) || isset($_GET['replyOffset']))
{
    if(!isset($_GET['replyParent'],$_GET['replyOffset']) || !is_string($_GET['replyParent']) || !preg_match('/^[1-9][0-9]{0,9}$/',$_GET['replyParent'])
        || !is_string($_GET['replyOffset']) || !preg_match('/^(?:0|[1-9][0-9]{0,5})$/',$_GET['replyOffset'])) ptsPdpExtrasError(400,'BAD_QUERY');
    $replyParent=(int)$_GET['replyParent'];
    $replyOffset=(int)$_GET['replyOffset'];
    if(isset($_GET['reviewOffset'])) ptsPdpExtrasError(400,'BAD_QUERY');
}

try
{
    require_once LIBRARIES.'product_pdp_profiles.php';
    require_once LIBRARIES.'product_sort.php';
    require_once LIBRARIES.'pts_public_catalog_api.php';

    $row=$d->rawQueryOne("select id,namevi,slugvi,id_list,pdp_profile,video_url,warranty,chinhsachmuahangvi,status,type from #_product where slugvi=? and type=? and find_in_set('hienthi',status) limit 1",array($slug,'san-pham'));
    if(empty($row)) ptsPdpExtrasError(404,'PRODUCT_NOT_FOUND');
    $list=$d->rawQueryOne("select id,slugvi,pdp_profile from #_product_list where id=? and type=? limit 1",array((int)$row['id_list'],'san-pham'));
    $profileKey=productPdpResolveProfile($row,$list ?: array());
    $profile=productPdpProfile($profileKey);

    $relatedRows=$d->rawQuery(
        "select id,namevi,slugvi,photo,photo2,status,regular_price,sale_price,cong_suat from #_product where id<>? and id_list=? and type=? and find_in_set('hienthi',status)".publicProductDefaultOrder()." limit 8",
        array((int)$row['id'],(int)$row['id_list'],'san-pham')
    );
    $related=array();
    foreach((array)$relatedRows as $relatedRow)
    {
        foreach(array('photo','photo2') as $field)
            $relatedRow['_pts_public_'.$field]=!empty($relatedRow[$field]) && is_file(ROOT.UPLOAD_PRODUCT_L.$relatedRow[$field]) ? $relatedRow[$field] : '';
        $dto=ptsCatalogProductDto($relatedRow,publicProductPricesVisible());
        if($dto!==null) $related[]=$dto;
    }

    $policyOverride=ptsPdpExtrasText(isset($row['chinhsachmuahangvi']) ? $row['chinhsachmuahangvi'] : '');
    $policyLinks=array();
    if($policyOverride==='')
    {
        foreach((array)$d->rawQuery("select namevi,slugvi,status from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc",array('chinh-sach')) as $policy)
        {
            $name=ptsPdpExtrasText(isset($policy['namevi'])?$policy['namevi']:'');
            $policySlug=isset($policy['slugvi']) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$policy['slugvi']) ? $policy['slugvi'] : '';
            if($name!=='' && $policySlug!=='' && mb_stripos($name,'bảo hành',0,'UTF-8')!==false)
                $policyLinks[]=array('label'=>$name,'href'=>'/ho-tro/'.$policySlug.'/');
        }
    }

    $reviewTotalRow=$d->rawQueryOne("select count(id) total,coalesce(sum(star),0) total_star from #_comment where id_parent=0 and id_variant=? and type=? and find_in_set('hienthi',status)",array((int)$row['id'],'san-pham'));
    $reviewTotal=(int)($reviewTotalRow['total'] ?? 0);
    $starCounts=array(1=>0,2=>0,3=>0,4=>0,5=>0);
    foreach((array)$d->rawQuery("select star,count(id) num from #_comment where id_parent=0 and id_variant=? and type=? and find_in_set('hienthi',status) group by star",array((int)$row['id'],'san-pham')) as $starRow)
    {
        $star=(int)$starRow['star']; if($star>=1 && $star<=5) $starCounts[$star]=(int)$starRow['num'];
    }
    $reviewRows=$d->rawQuery("select id,title,content,fullname,star,status,date_posted from #_comment where id_parent=0 and id_variant=? and type=? and find_in_set('hienthi',status) order by date_posted desc limit ".$reviewOffset.",10",array((int)$row['id'],'san-pham'));
    $reviews=array();
    foreach((array)$reviewRows as $reviewRow)
    {
        $photos=array();
        foreach((array)$d->rawQuery("select photo from #_comment_photo where id_parent=? limit 3",array((int)$reviewRow['id'])) as $photo)
        {
            $url=ptsPdpExtrasPhoto($photo['photo'] ?? ''); if($url!=='') $photos[]=$url;
        }
        $replies=array();
        $replyTotalRow=$d->rawQueryOne("select count(id) total from #_comment where id_parent=? and id_variant=? and type=? and find_in_set('hienthi',status)",array((int)$reviewRow['id'],(int)$row['id'],'san-pham'));
        $replyTotal=(int)($replyTotalRow['total'] ?? 0);
        foreach((array)$d->rawQuery("select fullname,content,poster,date_posted from #_comment where id_parent=? and id_variant=? and type=? and find_in_set('hienthi',status) order by date_posted desc limit 5",array((int)$reviewRow['id'],(int)$row['id'],'san-pham')) as $reply)
        {
            $name=ptsPdpExtrasText($reply['fullname'] ?? '');
            $content=ptsPdpExtrasText($reply['content'] ?? '');
            if($content!=='') $replies[]=array('name'=>$name,'content'=>$content,'isAdmin'=>($reply['poster'] ?? '')==='admin','date'=>(int)($reply['date_posted'] ?? 0));
        }
        $reviews[]=array(
            'id'=>(int)$reviewRow['id'],
            'title'=>ptsPdpExtrasText($reviewRow['title'] ?? ''),
            'content'=>ptsPdpExtrasText($reviewRow['content'] ?? ''),
            'name'=>ptsPdpExtrasText($reviewRow['fullname'] ?? ''),
            'star'=>max(1,min(5,(int)$reviewRow['star'])),
            'verified'=>strpos(','.($reviewRow['status'] ?? '').',',',verified-purchase,')!==false,
            'date'=>(int)($reviewRow['date_posted'] ?? 0),
            'photos'=>$photos,
            'video'=>ptsPdpExtrasReviewVideo($d->rawQueryOne("select photo,video from #_comment_video where id_parent=? limit 1",array((int)$reviewRow['id'])), $config),
            'replies'=>$replies,
            'repliesTotal'=>$replyTotal
        );
    }

    $replyPage=null;
    if($replyParent>0)
    {
        $parent=$d->rawQueryOne("select id from #_comment where id=? and id_parent=0 and id_variant=? and type=? and find_in_set('hienthi',status) limit 1",array($replyParent,(int)$row['id'],'san-pham'));
        if(empty($parent)) ptsPdpExtrasError(404,'REVIEW_NOT_FOUND');
        $replyTotalRow=$d->rawQueryOne("select count(id) total from #_comment where id_parent=? and id_variant=? and type=? and find_in_set('hienthi',status)",array($replyParent,(int)$row['id'],'san-pham'));
        $replyTotal=(int)($replyTotalRow['total'] ?? 0);
        $replyRows=$d->rawQuery("select fullname,content,poster,date_posted from #_comment where id_parent=? and id_variant=? and type=? and find_in_set('hienthi',status) order by date_posted desc limit ".$replyOffset.",5",array($replyParent,(int)$row['id'],'san-pham'));
        $replyItems=array();
        foreach((array)$replyRows as $reply)
        {
            $name=ptsPdpExtrasText($reply['fullname'] ?? '');
            $content=ptsPdpExtrasText($reply['content'] ?? '');
            if($content!=='') $replyItems[]=array('name'=>$name,'content'=>$content,'isAdmin'=>($reply['poster'] ?? '')==='admin','date'=>(int)($reply['date_posted'] ?? 0));
        }
        $replyPage=array('parentId'=>$replyParent,'offset'=>$replyOffset,'total'=>$replyTotal,'hasMore'=>$replyOffset+count($replyItems)<$replyTotal,'items'=>$replyItems);
    }

    $commitments=array();
    foreach((array)$d->rawQuery("select namevi,photo,status from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc limit 8",array('cam-ket')) as $commitment)
    {
        $name=ptsPdpExtrasText($commitment['namevi'] ?? '');
        $photo=ptsPdpExtrasPhoto($commitment['photo'] ?? '');
        if($name!=='') $commitments[]=array('name'=>$name,'image'=>$photo);
    }

    $needs=array();
    foreach((array)($profile['needs'] ?? array()) as $code=>$label)
        if(preg_match('/^[a-z0-9_-]{1,64}$/',(string)$code) && ptsPdpExtrasText($label)!=='') $needs[]=array('code'=>(string)$code,'label'=>ptsPdpExtrasText($label));

    $payload=array(
        'schemaVersion'=>1,
        'locale'=>'vi',
        'productId'=>(int)$row['id'],
        'profileKey'=>$profileKey,
        'csrfToken'=>formCsrfToken(),
        'videoEmbedUrl'=>ptsPdpExtrasVideo($row['video_url'] ?? ''),
        'warranty'=>array('summary'=>ptsPdpExtrasText($row['warranty'] ?? ''),'override'=>$policyOverride,'policies'=>$policyLinks),
        'related'=>$related,
        'reviews'=>array(
            'total'=>$reviewTotal,
            'average'=>$reviewTotal>0 ? round(((float)($reviewTotalRow['total_star'] ?? 0))/$reviewTotal,1) : 0,
            'counts'=>$starCounts,
            'requireOrderCode'=>isset($optsetting['review_require_order_code']) && (string)$optsetting['review_require_order_code']==='1',
            'offset'=>$reviewOffset,
            'hasMore'=>$reviewOffset+count($reviews)<$reviewTotal,
            'items'=>$reviews
        ),
        'consultation'=>array('needs'=>$needs),
        'commitments'=>$commitments
    );
    if($replyPage!==null) $payload['replyPage']=$replyPage;
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_product_extras: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
