<?php
/** GET /api/pts_v1_dealer.php — internal read-only dealer contract. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { header('Allow: GET'); header('Content-Type: application/json; charset=UTF-8'); http_response_code(405); echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}'; exit; }
header('Content-Type: application/json; charset=UTF-8'); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
try {
    if($_GET) { http_response_code(400); echo '{"error":{"code":"BAD_QUERY"}}'; exit; }
    require_once __DIR__.'/config.php'; require_once LIBRARIES.'pts_public_dealer_api.php';
    $load = static function($sql, array $params) use ($cache) { $rows = $cache->get($sql, $params, 'result', 7200); return is_array($rows) ? $rows : array(); };
    $static = $load('select type,namevi,descvi,status from #_static where type in (?,?,?) order by id desc', array('dai-ly-1','dai-ly-2','dai-ly-3'));
    $photos = $load('select namevi,link,photo,status from #_photo where type=? order by numb,id desc', array('hinh-anh-dai-ly'));
    $hero = $load('select namevi,photo,status from #_photo where type=? order by numb,id desc', array('dealer-hero'));
    $support = $load('select namevi,descvi,photo,status from #_news where type=? order by numb,id desc limit 0,4', array('cam-ket-dai-ly'));
    $testimonials = $load('select namevi,descvi,contentvi,photo,status from #_news where type=? order by numb,id desc limit 0,3', array('phan-hoi-dai-ly'));
    $stats = $load('select namevi,solieu,status from #_news where type=? order by numb,id desc limit 0,3', array('so-lieu-thong-ke-dai-ly'));
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    echo json_encode(ptsDealerPayload($static,$photos,$hero,$support,$testimonials,$stats,ROOT,isset($optsetting['hotline']) ? $optsetting['hotline'] : ''), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_dealer: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
