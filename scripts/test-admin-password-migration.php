<?php
/** HTTP integration test cho nâng hash Admin không reset mật khẩu hiện hữu. */
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'login_security.php';

$base = 'http://127.0.0.1/administrator/';
$passes = 0; $failures = 0;
function passwordOk($condition, $message)
{
    global $passes, $failures;
    if($condition) { ++$passes; echo 'PASS: '.$message.PHP_EOL; }
    else { ++$failures; echo 'FAIL: '.$message.PHP_EOL; }
}
function passwordRequest($url, $cookie = '', $post = null)
{
    $headers = "User-Agent: PTS-Password-Migration\r\nX-PTS-Permission-Test: enforce\r\n";
    if($cookie !== '') $headers .= 'Cookie: '.$cookie."\r\n";
    $options = array('http'=>array(
        'ignore_errors'=>true, 'timeout'=>20, 'follow_location'=>0,
        'method'=>$post === null ? 'GET' : 'POST', 'header'=>$headers
    ));
    if($post !== null)
    {
        $options['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = http_build_query($post);
    }
    $body = @file_get_contents($url, false, stream_context_create($options));
    $status = 0; $newCookie = '';
    foreach((array)$http_response_header as $line)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $match)) $status = (int)$match[1];
        if(stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $match)) $newCookie = $match[1];
    }
    return array('status'=>$status, 'body'=>(string)$body, 'cookie'=>$newCookie);
}
function passwordLogin($base, $username, $password)
{
    $page = passwordRequest($base.'index.php?com=user&act=login');
    $cookie = $page['cookie'];
    $page = passwordRequest($base.'index.php?com=user&act=login', $cookie);
    if(!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $match)) return array('ok'=>false, 'cookie'=>$cookie);
    $response = passwordRequest($base.'api/login.php', $cookie, array(
        'username'=>$username, 'password'=>$password, 'admin_csrf'=>$match[1]
    ));
    $payload = json_decode($response['body'], true);
    return array('ok'=>!empty($payload['success']), 'cookie'=>$response['cookie'] !== '' ? $response['cookie'] : $cookie);
}

$suffix = bin2hex(random_bytes(4));
$username = 'hash-migration-'.$suffix;
$password = bin2hex(random_bytes(12));
$legacyHash = adminLegacyPasswordHash($password, $config['website']['secret'], $config['website']['salt']);
$id = 0;
register_shutdown_function(function () use (&$id, $d) {
    if($id)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($id));
        $d->rawQuery('delete from #_user where id = ?', array($id));
    }
});

$d->insert('user', array(
    'id_permission'=>0, 'username'=>$username, 'password'=>$legacyHash,
    'fullname'=>'Password migration test', 'status'=>'hienthi', 'role'=>3,
    'secret_key'=>bin2hex(random_bytes(16)), 'numb'=>0
));
$id = (int)$d->getLastInsertId();
passwordOk((bool)preg_match('/^[a-f0-9]{32}$/', $legacyHash), 'Fixture bắt đầu bằng hash MD5 legacy');
passwordOk(adminPasswordVerify($password, $legacyHash, $config['website']['secret'], $config['website']['salt']), 'Helper chấp nhận đúng mật khẩu hash legacy');
passwordOk(!adminPasswordVerify($password.'-wrong', $legacyHash, $config['website']['secret'], $config['website']['salt']), 'Helper từ chối sai mật khẩu hash legacy');

$firstLogin = passwordLogin($base, $username, $password);
passwordOk($firstLogin['ok'], 'Tài khoản MD5 hiện hữu đăng nhập thành công');
$after = $d->rawQueryOne('select password from #_user where id = ? limit 1', array($id));
$newHash = isset($after['password']) ? (string)$after['password'] : '';
passwordOk($newHash !== $legacyHash && !empty(password_get_info($newHash)['algo']), 'Đăng nhập cũ tự nâng DB sang password_hash');
passwordOk(password_verify($password, $newHash), 'Hash mới xác minh đúng mật khẩu gốc');

$secondLogin = passwordLogin($base, $username, $password);
passwordOk($secondLogin['ok'], 'Tài khoản đã nâng hash tiếp tục đăng nhập được');
$wrongLogin = passwordLogin($base, $username, $password.'-wrong');
passwordOk(!$wrongLogin['ok'], 'Hash mới từ chối mật khẩu sai');
$final = $d->rawQueryOne('select password from #_user where id = ? limit 1', array($id));
passwordOk(isset($final['password']) && hash_equals($newHash, (string)$final['password']), 'Đăng nhập sai không thay đổi hash đã nâng cấp');

echo PHP_EOL.'Kết quả: '.$passes.' pass, '.$failures.' fail'.PHP_EOL;
exit($failures ? 1 : 0);
