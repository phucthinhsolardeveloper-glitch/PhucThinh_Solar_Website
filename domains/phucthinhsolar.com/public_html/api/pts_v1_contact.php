<?php
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}
if(!empty($_GET))
{
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(400);
    echo '{"error":{"code":"BAD_REQUEST"}}';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_contact_api.php';
    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $loadOne = static function($sql, array $params) use ($cache) {
        $row = $cache->get($sql, $params, 'fetch', 7200);
        return is_array($row) ? $row : array();
    };

    $seo = $loadOne('select titlevi,descriptionvi from #_seopage where type=? limit 0,1', array('lien-he'));
    $static = $loadOne('select contentvi from #_static where type=? limit 0,1', array('lienhe'));
    $locations = $loadRows("select namevi,address,id,map_iframe from #_news where type=? and find_in_set('hienthi',status) order by case when namevi like 'Trụ sở%' then 0 when namevi like 'Kho Gia Lai%' then 1 else 2 end,numb,id desc", array('he-thong-cua-hang'));
    $visualRows = $loadRows('select namevi,descvi,link,photo,status from #_photo where type=? order by numb,id desc', array('contact-visual'));

    $options = is_array($optsetting ?? null) ? $optsetting : array();
    $visual = null;
    foreach($visualRows as $row)
    {
        if(!is_array($row) || empty($row['photo']) || strpos(','.(string)($row['status'] ?? '').',', ',hienthi,') === false) continue;
        $image = ptsContactAsset($row['photo'], UPLOAD_PHOTO_L, ROOT);
        if($image === '') continue;
        $visual = array(
            'name' => ptsContactText($row['namevi'] ?? '') ?: 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng',
            'description' => ptsContactText($row['descvi'] ?? ''),
            'href' => ptsContactHref($row['link'] ?? '', '#yeu-cau-tu-van'),
            'image' => $image
        );
        break;
    }
    if(!$visualRows)
    {
        $fallback = ptsContactAsset('contact-consultation.jpg', 'assets/images/site-visuals', ROOT);
        if($fallback !== '') $visual = array(
            'name' => 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng',
            'description' => 'Trao đổi cùng đội ngũ Phúc Thịnh Solar để chọn cấu hình phù hợp cho gia đình, cửa hàng hoặc công trình.',
            'href' => '#yeu-cau-tu-van',
            'image' => $fallback
        );
    }

    $locationDto = array();
    foreach($locations as $row)
    {
        $name = ptsContactText($row['namevi'] ?? '');
        $address = ptsContactText($row['address'] ?? '');
        if((int)($row['id'] ?? 0) <= 0 || $name === '' || $address === '') continue;
        $locationDto[] = array('id' => (int)$row['id'], 'name' => $name, 'address' => $address, 'mapUrl' => ptsContactMapUrl($row['map_iframe'] ?? ''));
    }
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    echo json_encode(ptsPublicContactPayload(array(
        'seo' => array('title' => $seo['titlevi'] ?? '', 'description' => $seo['descriptionvi'] ?? ''),
        'title' => 'Liên hệ Phúc Thịnh Solar',
        'intro' => $static['contentvi'] ?? '',
        'visual' => $visual,
        'contact' => array(
            'company' => is_array($setting ?? null) ? ($setting['namevi'] ?? '') : '',
            'hotline' => $options['hotline'] ?? '', 'email' => $options['email'] ?? '',
            'address' => $options['address'] ?? '', 'workingHours' => $options['thoigianlamviec'] ?? '',
            'technicalHotline' => $options['hotline_technical'] ?? ''
        ),
        'locations' => $locationDto
    )), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_contact: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
