<?php
/** GET-only public ward list scoped to one visible city. */
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET')
{
    header('Allow: GET');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}

$city = isset($_GET['city']) && is_scalar($_GET['city']) ? (int)$_GET['city'] : 0;
if($city <= 0 || count($_GET) !== 1)
{
    http_response_code(400);
    echo '{"error":{"code":"BAD_QUERY"}}';
    exit;
}

try
{
    $cityRow = $d->rawQueryOne("select id from #_city where id=? and find_in_set('hienthi',status) limit 1", array($city));
    if(empty($cityRow['id'])) { http_response_code(404); echo '{"error":{"code":"NOT_FOUND"}}'; exit; }

    $wards = array();
    foreach((array)$d->rawQuery("select id,name from #_ward where id_city=? and id_district=0 and find_in_set('hienthi',status) order by numb asc,id asc", array($city)) as $row)
    {
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $name = isset($row['name']) && is_scalar($row['name']) ? trim(strip_tags((string)$row['name'])) : '';
        if($id > 0 && $name !== '') $wards[] = array('id'=>$id,'name'=>$name);
    }
    echo json_encode(array('schemaVersion'=>1,'locale'=>'vi','cityId'=>$city,'wards'=>$wards), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_checkout_wards: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
