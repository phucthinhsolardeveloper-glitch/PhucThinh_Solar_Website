<?php
/**
 * Kiểm thử API trạng thái hàng loạt theo filter báo đúng số dòng thực sự đổi.
 * Fixture có keyword riêng; script tự dọn sản phẩm và tài khoản kể cả khi lỗi.
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$base = 'http://127.0.0.1/administrator/';
$passes = 0;
$failures = 0;
function bulkStatusOk($condition, $message)
{
    global $passes, $failures;
    if($condition) { $passes++; echo 'PASS: '.$message.PHP_EOL; }
    else { $failures++; echo 'FAIL: '.$message.PHP_EOL; }
}
function bulkStatusRequest($url, $cookie = '', $post = null)
{
    $header = "User-Agent: PTS-Bulk-Status-Test\r\n";
    if($cookie !== '') $header .= 'Cookie: '.$cookie."\r\n";
    $options = array('http' => array('ignore_errors' => true, 'timeout' => 20, 'method' => $post === null ? 'GET' : 'POST', 'follow_location' => 0));
    if($post !== null)
    {
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = http_build_query($post);
    }
    $options['http']['header'] = $header;
    $body = @file_get_contents($url, false, stream_context_create($options));
    $status = 0; $newCookie = '';
    foreach((array)$http_response_header as $line)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $match)) $status = (int)$match[1];
        if(stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $match)) $newCookie = $match[1];
    }
    return array('status' => $status, 'body' => (string)$body, 'cookie' => $newCookie);
}

$suffix = strtoupper(bin2hex(random_bytes(4)));
$code = 'BULKSTATUS-'.$suffix;
$username = 'bulkstatus-'.strtolower($suffix);
$password = bin2hex(random_bytes(12));
$userId = 0;
$productId = 0;
register_shutdown_function(function() use (&$userId, &$productId, $d, $code) {
    if($productId) $d->rawQuery('delete from #_product where id=?', array($productId));
    $d->rawQuery('delete from #_product where code=?', array($code));
    if($userId)
    {
        $d->rawQuery('delete from #_user_log where id_user=?', array($userId));
        $d->rawQuery('delete from #_user where id=?', array($userId));
    }
});

$d->insert('user', array(
    'id_permission' => 0, 'username' => $username,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Bulk status reporting test', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$userId = (int)$d->getLastInsertId();
$d->insert('product', array('type' => 'san-pham', 'code' => $code, 'namevi' => $code, 'slugvi' => strtolower($code), 'status' => '', 'date_created' => time()));
$productId = (int)$d->getLastInsertId();

$page = bulkStatusRequest($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = bulkStatusRequest($base.'index.php?com=user&act=login', $cookie);
if(!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrfLogin)) exit(1);
$login = bulkStatusRequest($base.'api/login.php', $cookie, array('username'=>$username, 'password'=>$password, 'admin_csrf'=>$csrfLogin[1]));
if($login['cookie'] !== '') $cookie = $login['cookie'];
bulkStatusOk(!empty(json_decode($login['body'], true)['success']), 'Đăng nhập tài khoản fixture');

$admin = bulkStatusRequest($base.'index.php?com=product&act=man&type=san-pham', $cookie);
if(!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $admin['body'], $csrfAdmin)) exit(1);
$post = array(
    'table'=>'product', 'attr'=>'hienthi', 'value'=>'1', 'type'=>'san-pham', 'com'=>'product',
    'scope'=>'filtered', 'keyword'=>$code, 'admin_csrf'=>$csrfAdmin[1]
);

$first = json_decode(bulkStatusRequest($base.'api/status.php', $cookie, $post)['body'], true);
bulkStatusOk(!empty($first['success']) && (int)$first['updated'] === 1, 'Lượt đầu báo đúng 1 sản phẩm thực sự bật');
$row = $d->rawQueryOne('select status from #_product where id=?', array($productId));
bulkStatusOk(strpos(','.(string)$row['status'].',', ',hienthi,') !== false, 'Lượt đầu cập nhật trạng thái thật');

$second = json_decode(bulkStatusRequest($base.'api/status.php', $cookie, $post)['body'], true);
bulkStatusOk(!empty($second['success']) && (int)$second['updated'] === 0, 'Lặp lại cùng trạng thái báo 0 thay vì tổng bộ lọc');

$post['value'] = '0';
$third = json_decode(bulkStatusRequest($base.'api/status.php', $cookie, $post)['body'], true);
bulkStatusOk(!empty($third['success']) && (int)$third['updated'] === 1, 'Tắt trạng thái báo đúng 1 sản phẩm thực sự đổi');

echo PHP_EOL.'Kết quả: '.$passes.' pass, '.$failures.' fail'.PHP_EOL;
exit($failures ? 1 : 0);

