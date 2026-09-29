<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'product_projects.php';

function linkAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

function linkHttp($url, $method = 'GET', $data = array(), $cookie = '')
{
    $headers = array('User-Agent: PTS-Product-Project-Test');
    if($cookie !== '') $headers[] = 'Cookie: '.$cookie;
    $options = array('http' => array('method' => $method, 'ignore_errors' => true, 'timeout' => 20, 'header' => implode("\r\n", $headers)));
    if($method === 'POST')
    {
        $options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
        $options['http']['content'] = http_build_query($data);
    }
    $body = file_get_contents($url, false, stream_context_create($options));
    $responseHeaders = isset($http_response_header) ? $http_response_header : array();
    $status = 0;
    $cookies = array();
    foreach($responseHeaders as $header)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
        if(stripos($header, 'Set-Cookie:') === 0 && preg_match('/^(?:Set-Cookie:\s*)?(PHPSESSID=[^;]+)/i', $header, $match)) $cookies[] = $match[1];
    }
    return array('status' => $status, 'body' => $body, 'cookies' => $cookies);
}

$suffix = bin2hex(random_bytes(5));
$productId = 0;
$secondProductId = 0;
$projectId = 0;
$userId = 0;
$galleryId = 0;

try {
    linkAssert((bool)$d->rawQueryOne("show tables like 'table_product_project'"), 'Migration 010 đã tạo bảng quan hệ');

    $d->insert('product', array(
        'namevi' => 'Fixture sản phẩm dự án '.$suffix,
        'slugvi' => 'fixture-san-pham-du-an-'.$suffix,
        'code' => 'FIXTURE-PP-'.$suffix,
        'type' => 'san-pham', 'status' => 'hienthi', 'numb' => 1,
        'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 1,
        'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0,
        'date_created' => time(), 'date_updated' => 0, 'view' => 0
    ));
    $productId = (int)$d->getLastInsertId();
    $secondProductData = array(
        'namevi' => 'Fixture sản phẩm dự án thứ hai '.$suffix,
        'slugvi' => 'fixture-san-pham-du-an-thu-hai-'.$suffix,
        'code' => 'FIXTURE-PP2-'.$suffix,
        'type' => 'san-pham', 'status' => 'hienthi', 'numb' => 1,
        'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 2,
        'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0,
        'date_created' => time(), 'date_updated' => 0, 'view' => 0
    );
    $d->insert('product', $secondProductData);
    $secondProductId = (int)$d->getLastInsertId();
    $d->insert('news', array(
        'namevi' => 'Fixture dự án sản phẩm '.$suffix,
        'slugvi' => 'fixture-du-an-san-pham-'.$suffix,
        'type' => 'du-an', 'status' => 'hienthi', 'numb' => 1,
        'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0,
        'date_created' => time(), 'date_updated' => 0, 'view' => 0
    ));
    $projectId = (int)$d->getLastInsertId();
    $existingGallery = $d->rawQueryOne("select photo from #_gallery where com = ? and type = ? and kind = ? and val = ? and photo <> '' limit 0,1", array('news', 'du-an', 'man', 'du-an'));
    if(!empty($existingGallery['photo']))
    {
        $d->insert('gallery', array(
            'id_parent'=>$projectId, 'photo'=>$existingGallery['photo'], 'com'=>'news', 'type'=>'du-an',
            'kind'=>'man', 'val'=>'du-an', 'numb'=>1, 'status'=>'hienthi', 'date_created'=>time()
        ));
        $galleryId = (int)$d->getLastInsertId();
    }

    linkAssert($productId > 0 && $projectId > 0, 'Tạo fixture độc lập cho quan hệ');
    linkAssert(productProjectSync($d, $projectId, array($secondProductId, $productId)), 'Lưu quan hệ bằng transaction');
    linkAssert(productProjectIdsForProject($d, $projectId) === array($secondProductId, $productId), 'Giữ đúng thứ tự sản phẩm theo dự án');

    $username = 'codex-project-link-'.$suffix;
    $password = bin2hex(random_bytes(12));
    $d->insert('user', array(
        'id_permission' => 0, 'username' => $username,
        'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
        'fullname' => 'Local project link test', 'status' => 'hienthi', 'role' => 3,
        'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
    ));
    $userId = (int)$d->getLastInsertId();
    $loginPage = linkHttp('http://127.0.0.1/administrator/index.php?com=user&act=login');
    linkAssert(!empty($loginPage['cookies']) && preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $loginToken) === 1, 'Admin HTTP phát hành cookie và CSRF');
    $login = linkHttp('http://127.0.0.1/administrator/api/login.php', 'POST', array('username'=>$username, 'password'=>$password, 'admin_csrf'=>$loginToken[1]), $loginPage['cookies'][0]);
    $sessionCookie = !empty($login['cookies']) ? $login['cookies'][0] : '';
    linkAssert($login['status'] === 200 && $sessionCookie !== '', 'Đăng nhập Admin fixture để kiểm tra form thật');
    $editForm = linkHttp('http://127.0.0.1/administrator/index.php?com=news&act=edit&type=du-an&id='.$projectId, 'GET', array(), $sessionCookie);
    linkAssert($editForm['status'] === 200 && strpos($editForm['body'], 'id="project-product-picker"') !== false && strpos($editForm['body'], 'id="project-products-sortable"') !== false, 'Form Dự án render bộ chọn và danh sách sắp xếp');
    linkAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $editForm['body'], $editToken) === 1, 'Form Dự án giữ CSRF');
    $save = linkHttp('http://127.0.0.1/administrator/index.php?com=news&act=save&type=du-an', 'POST', array(
        'admin_csrf'=>$editToken[1], 'id'=>$projectId,
        /* Dự án mới bắt buộc địa chỉ hai cấp; dùng fixture địa giới ổn định
           thay vì bỏ trống khiến test bị chặn trước khi kiểm tra thứ tự liên kết. */
        'data'=>array('namevi'=>'Fixture dự án sản phẩm '.$suffix, 'numb'=>1, 'project_city_id'=>64, 'project_ward_id'=>10600),
        'slugvi'=>'fixture-du-an-san-pham-'.$suffix,
        'status'=>array('hienthi'=>'hienthi'),
        'project_products'=>array($productId, $secondProductId),
        'save-here'=>'1'
    ), $sessionCookie);
    linkAssert($save['status'] === 200 && productProjectIdsForProject($d, $projectId) === array($productId, $secondProductId), 'Admin HTTP lưu và đổi đúng thứ tự liên kết');

    $projectPage = linkHttp('http://127.0.0.1/fixture-du-an-san-pham-'.$suffix);
    $productPage = linkHttp('http://127.0.0.1/fixture-san-pham-du-an-'.$suffix);
    linkAssert($projectPage['status'] === 200 && strpos($projectPage['body'], 'Sản phẩm sử dụng trong dự án') !== false && strpos($projectPage['body'], 'fixture-san-pham-du-an-'.$suffix) !== false, 'Trang Dự án render link tới sản phẩm');
    linkAssert($productPage['status'] === 200 && strpos($productPage['body'], 'Dự án thực tế sử dụng sản phẩm') !== false && strpos($productPage['body'], 'fixture-du-an-san-pham-'.$suffix) !== false, 'Trang Sản phẩm render link ngược tới Dự án');
    if($galleryId > 0) linkAssert(strpos($productPage['body'], 'pts-project-media-thumbs') !== false, 'Trang Sản phẩm render ảnh gallery Dự án');
    linkAssert(!productProjectSync($d, $projectId, array(999999999)), 'Từ chối ID sản phẩm không tồn tại');
    linkAssert(productProjectIdsForProject($d, $projectId) === array($productId, $secondProductId), 'Validation lỗi không xóa quan hệ đang có');

    $publicProduct = $d->rawQueryOne("select p.id from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and find_in_set('hienthi',pp.status) and p.type = ? and find_in_set('hienthi',p.status)", array($projectId, 'san-pham'));
    $publicProject = $d->rawQueryOne("select n.id from #_product_project pp inner join #_news n on n.id = pp.id_project where pp.id_product = ? and find_in_set('hienthi',pp.status) and n.type = ? and find_in_set('hienthi',n.status)", array($productId, 'du-an'));
    linkAssert(in_array((int)$publicProduct['id'], array($productId, $secondProductId), true) && (int)$publicProject['id'] === $projectId, 'Public đọc được quan hệ theo cả hai chiều');

    $d->where('id', $productId);
    $d->update('product', array('status' => ''));
    $hiddenProduct = $d->rawQueryOne("select p.id from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and p.id = ? and find_in_set('hienthi',p.status)", array($projectId, $productId));
    linkAssert(empty($hiddenProduct), 'Sản phẩm ẩn không xuất hiện ở Dự án public');
} finally {
    if($projectId > 0) $d->rawQuery('delete from #_product_project where id_project = ?', array($projectId));
    if($galleryId > 0) $d->rawQuery('delete from #_gallery where id = ?', array($galleryId));
    if($productId > 0) $d->rawQuery('delete from #_product where id = ?', array($productId));
    if($secondProductId > 0) $d->rawQuery('delete from #_product where id = ?', array($secondProductId));
    if($projectId > 0) $d->rawQuery('delete from #_news where id = ?', array($projectId));
    if($userId > 0) $d->rawQuery('delete from #_user_log where id_user = ?', array($userId));
    if($userId > 0) $d->rawQuery('delete from #_user where id = ?', array($userId));
}

$orphan = $d->rawQueryOne('select count(*) as total from #_product_project pp left join #_product p on p.id = pp.id_product left join #_news n on n.id = pp.id_project where p.id is null or n.id is null');
linkAssert((int)$orphan['total'] === 0, 'Không còn quan hệ mồ côi sau test');
echo "Product-project link tests completed.\n";
