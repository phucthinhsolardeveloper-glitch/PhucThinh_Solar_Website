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
    require_once LIBRARIES.'public_route_resolver.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'product_pdp_profiles.php';
    require_once LIBRARIES.'product_specs.php';
    require_once LIBRARIES.'pts_public_product_detail_api.php';

    $fields = array('id','namevi','slugvi','code','descvi','photo','photo2','status','type','id_list','id_cat','id_item','id_sub','id_brand','pdp_profile','additional_specs');
    $specFields = array('cong_suat','material','waterproof_rating','warranty','package_contents','light_source','lamp_dimensions','color_temperature','light_color','battery_capacity','panel_power','charging_time','lighting_time','led_chip','lighting_area','installation_height','panel_dimensions','cell_type','efficiency','open_circuit_voltage','short_circuit_current','max_power_voltage','max_power_current','weight','connector_type','inverter_type','ac_power','phase_count','mppt_count','max_dc_voltage','mppt_voltage_range','max_mppt_current','battery_voltage_range','backup_power','monitoring','battery_chemistry','nominal_capacity','usable_capacity','battery_voltage','max_charge_current','max_discharge_current','depth_of_discharge','cycle_life','communication','fan_type','blade_size','airflow','speed_levels','runtime','motor_power','input_voltage','output_voltage','max_current','solar_input_range','control_mode','protections','pump_type','max_flow','max_head','outlet_size','working_voltage','accessory_type','rated_voltage','rated_current','compatibility','cable_section','standard','dimensions_weight');
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
    $payload = ptsPdpPayload($row, $media, ptsPdpSpecDto($row), $canonical, array('product' => 'publicProductEntityPath(product)', 'routeResolver' => 'public_route_resolver.php', 'categoryUrls' => 'product_category_urls.php'));
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_product_detail: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
