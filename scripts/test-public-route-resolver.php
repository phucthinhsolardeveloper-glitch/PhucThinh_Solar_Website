<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require LIBRARIES.'public_route_resolver.php';

function routeAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}

$routes = array(
    array('tbl'=>'product_list','field'=>'idl','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product_cat','field'=>'idc','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product_item','field'=>'idi','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product_sub','field'=>'ids','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product_brand','field'=>'idb','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product','field'=>'id','com'=>'san-pham','type'=>'san-pham'),
    array('tbl'=>'product','field'=>'id','com'=>'thu-vien-anh','type'=>'thu-vien-anh'),
    array('tbl'=>'photo','field'=>'id','com'=>'video','type'=>'video'),
    array('tbl'=>'news_list','field'=>'idl','com'=>'tin-tuc','type'=>'tin-tuc'),
    array('tbl'=>'news_cat','field'=>'idc','com'=>'tin-tuc','type'=>'tin-tuc'),
    array('tbl'=>'news_item','field'=>'idi','com'=>'tin-tuc','type'=>'tin-tuc'),
    array('tbl'=>'news_sub','field'=>'ids','com'=>'tin-tuc','type'=>'tin-tuc'),
    array('tbl'=>'news','field'=>'id','com'=>'tin-tuc','type'=>'tin-tuc'),
    array('tbl'=>'news_list','field'=>'idl','com'=>'giai-phap','type'=>'giai-phap'),
    array('tbl'=>'news_cat','field'=>'idc','com'=>'giai-phap','type'=>'giai-phap'),
    array('tbl'=>'news_item','field'=>'idi','com'=>'giai-phap','type'=>'giai-phap'),
    array('tbl'=>'news_sub','field'=>'ids','com'=>'giai-phap','type'=>'giai-phap'),
    array('tbl'=>'news','field'=>'id','com'=>'giai-phap','type'=>'giai-phap'),
    array('tbl'=>'news_list','field'=>'idl','com'=>'du-an','type'=>'du-an'),
    array('tbl'=>'news_cat','field'=>'idc','com'=>'du-an','type'=>'du-an'),
    array('tbl'=>'news_item','field'=>'idi','com'=>'du-an','type'=>'du-an'),
    array('tbl'=>'news_sub','field'=>'ids','com'=>'du-an','type'=>'du-an'),
    array('tbl'=>'news','field'=>'id','com'=>'du-an','type'=>'du-an'),
    array('tbl'=>'news','field'=>'id','com'=>'chinh-sach','type'=>'chinh-sach'),
    array('tbl'=>'static','field'=>'id','com'=>'gioi-thieu','type'=>'gioi-thieu'),
    array('tbl'=>'','field'=>'id','com'=>'lien-he','type'=>''),
);

$routerSource = file_get_contents(LIBRARIES.'router.php');
routeAssert(substr_count($routerSource, 'array("tbl" =>') === count($routes), 'Fixture route test khớp số định nghĩa trong router');
routeAssert(strpos($routerSource, "'@^product/?$'") !== false, 'Alias gốc /product/ dùng regex AltoRouter và giữ redirect 301');
routeAssert(publicRouteIdentifier('slugvi') === 'slugvi' && publicRouteIdentifier('slugvi desc') === '', 'Resolver chỉ nhận identifier SQL an toàn');
routeAssert(publicRouteResolveSlug($d, 'khong-ton-tai-'.bin2hex(random_bytes(4)), 'slugvi', $routes) === array(), 'Slug không tồn tại không sinh route giả');

$queryProbe = new class {
    public $calls = 0;
    public function rawQueryOne($sql, $params)
    {
        $this->calls++;
        return array('id'=>123, 'route_field'=>'id', 'route_com'=>'san-pham');
    }
};
publicRouteResolveSlug($queryProbe, 'slug-kiem-thu', 'slugvi', $routes);
routeAssert($queryProbe->calls === 1, 'Mỗi slug chỉ gọi một query resolver');

// Dựng kết quả chuẩn theo đúng thuật toán cũ: duyệt từng bảng theo thứ tự và giữ match đầu tiên.
$expected = array();
foreach($routes as $route)
{
    $table = publicRouteIdentifier($route['tbl']);
    if($table === '' || in_array($table, array('static', 'photo'), true)) continue;
    $rows = $d->rawQuery("select id, slugvi from #_{$table} where type = ? and slugvi <> '' and find_in_set('hienthi',status)", array($route['type']));
    foreach($rows as $row)
    {
        if(isset($expected[$row['slugvi']])) continue;
        $expected[$row['slugvi']] = array('id'=>(int)$row['id'], 'route_field'=>$route['field'], 'route_com'=>$route['com']);
    }
}
routeAssert(count($expected) > 0, 'Có inventory slug public để đối chứng');

$mismatches = array();
foreach($expected as $slug => $legacyMatch)
{
    $unionMatch = publicRouteResolveSlug($d, $slug, 'slugvi', $routes);
    if(empty($unionMatch)
        || (int)$unionMatch['id'] !== $legacyMatch['id']
        || $unionMatch['route_field'] !== $legacyMatch['route_field']
        || $unionMatch['route_com'] !== $legacyMatch['route_com']) $mismatches[] = $slug;
}
routeAssert(empty($mismatches), 'UNION giữ đúng route legacy cho toàn bộ '.count($expected).' slug public');
echo 'Public route resolver tests completed: '.count($expected)." slug.\n";
?>
