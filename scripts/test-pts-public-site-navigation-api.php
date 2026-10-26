<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_category_urls.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_navigation_api.php';

function navCheck($condition, $message) { if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
$result = ptsPublicSiteNavigationPayload(array(
    'setting' => array('namevi' => 'PTS', 'private' => 'no'),
    'options' => array('hotline' => '0900 123', 'fanpage' => 'https://facebook.com/pts', 'youtube' => 'javascript:alert(1)', 'secret' => 'no'),
    'asset' => array('logo' => '/upload/photo/logo.webp', 'logoFooter' => ''),
    'footer' => array('namevi' => 'PTS <b>Solar</b>', 'contentvi' => '<p>Địa chỉ <script>x</script><br>Hotline</p>'),
    'policy' => array(array('id'=>2,'namevi'=>'Bảo hành','slugvi'=>'bao-hanh'), array('id'=>9,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'no')),
    'locations' => array(array('id'=>3,'namevi'=>'Trụ sở','address'=>'Hà Nội','ggmap'=>'https://maps.example/pts')),
    'productLists' => array(array('id'=>1,'namevi'=>'Đèn','slugvi'=>'den'), array('id'=>8,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'no')),
    'productCats' => array(array('id'=>2,'id_list'=>1,'namevi'=>'Đèn pha','slugvi'=>'den-pha')),
    'productItems' => array(array('id'=>3,'id_cat'=>2,'namevi'=>'Đèn pha 100W','slugvi'=>'den-pha-100w')),
    'productSubs' => array(array('id'=>4,'id_item'=>3,'namevi'=>'Mẫu A','slugvi'=>'mau-a')),
    'solutionRows' => array(array('id'=>5,'namevi'=>'Giải pháp','title_indexvi'=>'Giải pháp thật','slugvi'=>'giai-phap-that','status'=>'hienthi,solutionhub')),
    'newsLists' => array(), 'newsCats' => array(), 'newsItems' => array(), 'newsSubs' => array()
));
navCheck($result['navigationSchemaVersion'] === 1, 'navigation schema version');
navCheck(!array_key_exists('schemaVersion', $result), 'site API v1 schema version remains unchanged');
navCheck(count($result['navigation']) === 6, 'exact top-level navigation');
navCheck($result['navigation'][2]['children'][1]['label'] === 'Giải pháp thật', 'published solution navigation');
navCheck($result['productCategoryTree'][0]['children'][0]['children'][0]['children'][0]['name'] === 'Mẫu A', 'product hierarchy');
navCheck(strpos($result['footer']['contactText'], '<script>') === false, 'footer text sanitized');
navCheck(count($result['footer']['socialLinks']) === 1, 'unsafe social URL omitted');
navCheck(strpos(json_encode($result), 'private') === false && strpos(json_encode($result), 'secret') === false, 'private fields absent');
echo "PASS: PTS public navigation DTO (7 assertions)\n";
