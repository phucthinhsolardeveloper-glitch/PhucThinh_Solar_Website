<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'admin_security.php';
require LIBRARIES.'login_security.php';
require LIBRARIES.'session_security.php';

function readinessAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException("FAIL: {$message}");
    echo "PASS: {$message}\n";
}

function readinessHttp($url, $method = 'GET', $data = array(), $cookie = '')
{
    $headers = array('User-Agent: PTS-Local-Readiness-Test');
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
    $setCookies = array();
    foreach($responseHeaders as $header)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
        if(stripos($header, 'Set-Cookie:') === 0) $setCookies[] = trim(substr($header, 11));
    }
    return array('status' => $status, 'body' => $body, 'cookies' => $setCookies);
}

function readinessSessionCookie($setCookies)
{
    foreach($setCookies as $cookie)
        if(preg_match('/^(PHPSESSID=[^;]+)/', $cookie, $match)) return array($match[1], $cookie);
    return array('', '');
}

$prefix = 'CODEX-RBAC-'.bin2hex(random_bytes(4));
$createdUsers = array();
$createdGroups = array();
$rateKeys = array();

try
{
    $matrix = array(
        'product' => array('product_man_san-pham','product_add_san-pham','product_edit_san-pham','product_delete_san-pham','product_add_photo_san-pham','product_edit_photo_san-pham','product_delete_photo_san-pham'),
        'order' => array('order_man','order_edit'),
        'content' => array('news_man_tin-tuc','news_add_tin-tuc','news_edit_tin-tuc','news_delete_tin-tuc'),
        'viewer' => array('product_man_san-pham')
    );

    foreach($matrix as $roleName => $permissions)
    {
        $d->insert('permission_group', array('name' => $prefix.'-'.$roleName, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
        $groupId = (int)$d->getLastInsertId();
        $createdGroups[] = $groupId;
        foreach($permissions as $permission) $d->insert('permission', array('id_permission_group' => $groupId, 'permission' => $permission));

        $username = strtolower($prefix.'-'.$roleName);
        $passwordPlain = bin2hex(random_bytes(12));
        $passwordHash = $func->encryptPassword($config['website']['secret'], $passwordPlain, $config['website']['salt']);
        $d->insert('user', array('id_permission' => $groupId, 'username' => $username, 'password' => $passwordHash, 'fullname' => 'Local readiness '.$roleName, 'status' => 'hienthi', 'role' => 1, 'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0));
        $userId = (int)$d->getLastInsertId();
        $createdUsers[$roleName] = array('id' => $userId, 'username' => $username, 'password' => $passwordPlain);

        $loaded = adminLoadPermissions($d, $userId);
        readinessAssert(count(array_diff($permissions, $loaded)) === 0, 'Nạp đúng quyền DB cho vai trò '.$roleName);
    }

    readinessAssert(adminPermissionGranted(true, 1, false, 'product_edit_san-pham', adminLoadPermissions($d, $createdUsers['product']['id'])), 'Nhóm sản phẩm được sửa sản phẩm');
    readinessAssert(!adminPermissionGranted(true, 1, false, 'order_edit', adminLoadPermissions($d, $createdUsers['product']['id'])), 'Nhóm sản phẩm không vượt quyền đơn hàng');
    readinessAssert(adminPermissionGranted(true, 1, false, 'order_edit', adminLoadPermissions($d, $createdUsers['order']['id'])), 'Nhóm đơn hàng được cập nhật đơn');
    readinessAssert(!adminPermissionGranted(true, 1, false, 'product_edit_san-pham', adminLoadPermissions($d, $createdUsers['viewer']['id'])), 'Nhóm chỉ xem không có quyền sửa');

    $fakeServer = array('REMOTE_ADDR' => '198.51.100.10', 'HTTP_X_FORWARDED_FOR' => '203.0.113.20');
    readinessAssert(adminLoginClientIp($fakeServer, array()) === '198.51.100.10', 'Login không tin X-Forwarded-For từ proxy chưa cấu hình');
    readinessAssert(adminLoginClientIp($fakeServer, array('198.51.100.10')) === '203.0.113.20', 'Login chỉ nhận forwarded IP từ trusted proxy');

    $rateDescriptors = adminLoginLimitDescriptors('198.51.100.77', $prefix.'-rate', 2, 20);
    foreach($rateDescriptors as $descriptor) $rateKeys[] = $descriptor['key'];
    adminLoginLimitFailure($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) === 0, 'Rate limit chưa khóa trước ngưỡng');
    adminLoginLimitFailure($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) > 0, 'Rate limit khóa theo IP khi đạt ngưỡng');
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time() + 901) === 0, 'Rate limit tự mở và reset bộ đếm khi hết thời gian khóa');
    readinessAssert(adminLoginLimitFailure($d, $rateDescriptors, time() + 901) > 0, 'Lần sai sau khi mở khóa bắt đầu lại từ đầu');
    adminLoginLimitReset($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) === 0, 'Đăng nhập thành công reset rate limit');

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/html/administrator/templates'));
    $postForms = 0;
    $csrfFields = 0;
    foreach($iterator as $file)
    {
        if(!$file->isFile() || substr($file->getFilename(), -4) !== '.php') continue;
        $source = file_get_contents($file->getPathname());
        $postForms += preg_match_all('/<form[^>]*method="post"/i', $source, $unused);
        $csrfFields += substr_count($source, 'adminCsrfField()');
    }
    readinessAssert($postForms === 42 && $csrfFields === $postForms, 'Đủ CSRF field cho 42 form POST Admin');

    $productListTemplate = file_get_contents('/var/www/html/administrator/templates/product/man/mans_tpl.php');
    readinessAssert(strpos($productListTemplate, 'totalByID(') === false && strpos($productListTemplate, '$commentTotals') !== false, 'Danh sách sản phẩm dùng thống kê đánh giá theo batch');

    unset($_REQUEST['id_city'], $_REQUEST['id_district']);
    $emptyDistrict = $func->getAjaxPlace('district');
    $emptyWard = $func->getAjaxPlace('ward');
    readinessAssert(strpos($emptyDistrict, 'id="id_district"') !== false && substr_count($emptyDistrict, '<option') === 1, 'Dropdown quận không truy vấn dữ liệu khi chưa chọn tỉnh');
    readinessAssert(strpos($emptyWard, 'id="id_ward"') !== false && substr_count($emptyWard, '<option') === 1, 'Dropdown phường không truy vấn dữ liệu khi chưa chọn quận');

    $base = 'http://127.0.0.1/administrator/';
    $loginPage = readinessHttp($base.'index.php?com=user&act=login');
    list($oldCookie, $loginSetCookie) = readinessSessionCookie($loginPage['cookies']);
    readinessAssert($loginPage['status'] === 200 && $oldCookie !== '', 'Mở trang login và nhận session cookie');
    readinessAssert(stripos($loginSetCookie, 'HttpOnly') !== false && stripos($loginSetCookie, 'SameSite=Lax') !== false, 'Cookie login có HttpOnly và SameSite=Lax');
    readinessAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $tokenMatch) === 1, 'Trang login phát hành CSRF token');

    $missingCsrf = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['product']['username'], 'password' => $createdUsers['product']['password']), $oldCookie);
    readinessAssert($missingCsrf['status'] === 403, 'Login thiếu CSRF bị từ chối');

    $login = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['product']['username'], 'password' => $createdUsers['product']['password'], 'admin_csrf' => $tokenMatch[1]), $oldCookie);
    list($newCookie, $authenticatedSetCookie) = readinessSessionCookie($login['cookies']);
    $loginJson = json_decode($login['body'], true);
    readinessAssert($login['status'] === 200 && !empty($loginJson['success']), 'Đăng nhập HTTP bằng tài khoản RBAC tạm thành công');
    readinessAssert($newCookie !== '' && $newCookie !== $oldCookie, 'Session ID được regenerate sau đăng nhập');
    readinessAssert(stripos($authenticatedSetCookie, 'HttpOnly') !== false && stripos($authenticatedSetCookie, 'SameSite=Lax') !== false, 'Cookie sau đăng nhập giữ thuộc tính bảo mật');

    $adminForm = readinessHttp($base.'index.php?com=product&act=add&type=san-pham', 'GET', array(), $newCookie);
    readinessAssert($adminForm['status'] === 200 && strpos($adminForm['body'], 'name="admin_csrf"') !== false, 'Form sản phẩm authenticated render CSRF field');
    readinessAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $adminForm['body'], $authenticatedToken) === 1, 'Phiên authenticated phát hành token mới');

    $apiMissing = readinessHttp($base.'api/status.php', 'POST', array('id' => 999999, 'table' => 'product', 'attr' => 'hienthi', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    readinessAssert($apiMissing['status'] === 403, 'API mutation thiếu CSRF trả 403');
    $apiValid = readinessHttp($base.'api/status.php', 'POST', array('admin_csrf' => $authenticatedToken[1], 'id' => 999999, 'table' => 'product', 'attr' => 'hienthi', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    readinessAssert($apiValid['status'] === 200, 'API mutation có CSRF hợp lệ đi qua lớp bảo vệ');

    echo "Admin readiness tests completed.\n";
}
finally
{
    foreach($createdUsers as $user) $d->rawQuery("delete from #_user_log where id_user = ?", array($user['id']));
    foreach($createdUsers as $user) $d->rawQuery("delete from #_user where id = ?", array($user['id']));
    foreach($createdGroups as $groupId) $d->rawQuery("delete from #_permission where id_permission_group = ?", array($groupId));
    foreach($createdGroups as $groupId) $d->rawQuery("delete from #_permission_group where id = ?", array($groupId));
    foreach($rateKeys as $key) $d->rawQuery("delete from #_user_limit where login_ip = ?", array($key));
}
?>
