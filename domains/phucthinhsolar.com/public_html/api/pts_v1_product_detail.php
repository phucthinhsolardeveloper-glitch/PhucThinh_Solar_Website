<?php
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET'); header('Content-Type: application/json; charset=UTF-8'); http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}'; exit;
}
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

function ptsPdpError($status, $code) { http_response_code($status); echo json_encode(array('error'=>array('code'=>$code)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

try {
    foreach(array_keys($_GET) as $key) if($key !== 'slug') ptsPdpError(400, 'BAD_QUERY');
    if(isset($_GET['slug']) && !is_string($_GET['slug'])) ptsPdpError(400, 'BAD_QUERY');
    $slug = isset($_GET['slug']) ? trim($_GET['slug'], '/') : '';
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsPdpError(400, 'BAD_QUERY');
    /* Read-only bootstrap: do not start the legacy session or instantiate Cart. */
    define('LIBRARIES', '../libraries/');
    define('THUMBS', 'thumbs');
    define('WATERMARK', 'watermark');
    require_once LIBRARIES.'config.php';
    require_once LIBRARIES.'autoload.php';
    new AutoLoad();
    $d = new PDODb($config['database']);
    /*
     * publicProductPricesVisible() doc global $optsetting. Khong goi
     * api/config.php (co startSecureSession() + khoi tao Cart) vi bootstrap
     * nay chu dinh read-only; tu lay options tu #_setting bang truy van don,
     * khong qua lop Cache (vi Cache can $d da co nhung object Cache khong
     * duoc khoi tao trong bootstrap toi gian nay).
     */
    $settingRow = $d->rawQueryOne('select options from #_setting limit 1');
    $optsetting = !empty($settingRow['options']) ? json_decode($settingRow['options'], true) : null;
    require_once LIBRARIES.'public_route_resolver.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'product_pdp_profiles.php';
    require_once LIBRARIES.'product_specs.php';
    require_once LIBRARIES.'product_content_blocks.php';
    require_once LIBRARIES.'public_pricing.php';
    require_once LIBRARIES.'pts_public_product_detail_api.php';

    $fields = array('id','namevi','slugvi','code','descvi','photo','photo2','status','type','id_list','id_cat','id_item','id_sub','id_brand','pdp_profile','additional_specs','regular_price','sale_price');
    $specFields = array('cong_suat','material','waterproof_rating','warranty','package_contents','light_source','lamp_dimensions','color_temperature','light_color','battery_capacity','panel_power','charging_time','lighting_time','led_chip','lighting_area','installation_height','panel_dimensions','carton_quantity','carton_dimensions','weight');
    $fields = array_values(array_unique(array_merge($fields, $specFields)));
    $row = $d->rawQueryOne('select '.implode(',', $fields).' from #_product where slugvi = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array($slug, 'san-pham'));
    if(empty($row)) ptsPdpError(404, 'PRODUCT_NOT_FOUND');
    $list = $d->rawQueryOne('select id,slugvi,pdp_profile from #_product_list where id = ? and type = ? and find_in_set(\'hienthi\',status) limit 1', array((int)$row['id_list'], 'san-pham'));
    $row['list_slugvi'] = isset($list['slugvi']) ? $list['slugvi'] : '';
    $row['list_pdp_profile'] = isset($list['pdp_profile']) ? $list['pdp_profile'] : '';
    $row['resolved_profile'] = productPdpResolveProfile($row, $list ?: array());

    $media = array();
    foreach(array($row['photo'], $row['photo2']) as $photo) { $url = ptsPdpMediaUrl($photo, ROOT, UPLOAD_PRODUCT_L); if($url !== '') $media[] = $url; }
    $gallery = $d->rawQuery('select photo from #_gallery where id_parent = ? and com = \'product\' and kind = \'man\' and val = ? and find_in_set(\'hienthi\',status) order by numb,id desc', array((int)$row['id'], 'san-pham'));
    foreach((array)$gallery as $item) { $url = ptsPdpMediaUrl(isset($item['photo']) ? $item['photo'] : '', ROOT, UPLOAD_PRODUCT_L); if($url !== '') $media[] = $url; }
    $canonical = publicProductEntityPath($d, 'product', (int)$row['id'], 'vi');
    /* Dùng đúng resolver kế thừa của PDP legacy: product -> brand/cat/list,
       gồm mode own/both/hide/inherit và inherit_source. Chỉ đọc DB. */
    $resolvedSections = productResolveSections($d, $row, 'vi');
    $content = ptsPdpContentDto($resolvedSections, ROOT);
    $documentRows = $d->rawQuery("select document_type,titlevi as title,file_path from #_product_document where id_product=? and find_in_set('hienthi',status) and file_path<>'' order by numb,id", array((int)$row['id']));
    $documents = ptsPdpDocumentsDto((array)$documentRows, ROOT);
    $payload = ptsPdpPayload($row, $media, ptsPdpSpecDto($row), $canonical, array('product' => 'publicProductEntityPath(product)', 'routeResolver' => 'public_route_resolver.php', 'categoryUrls' => 'product_category_urls.php'), publicProductPricesVisible(), $content, $documents);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_product_detail: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
