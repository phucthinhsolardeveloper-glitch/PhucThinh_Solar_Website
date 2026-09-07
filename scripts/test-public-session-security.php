<?php
/** Session cookie regression dùng cùng bootstrap với frontend và API public. */
$_SERVER['HTTPS'] = 'on';
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
require '/var/www/html/libraries/session_security.php';
startSecureSession();

$failures = 0;
function publicSessionOk($condition, $message)
{
    global $failures;
    if($condition) echo 'PASS: '.$message.PHP_EOL;
    else { ++$failures; echo 'FAIL: '.$message.PHP_EOL; }
}

$params = session_get_cookie_params();
publicSessionOk(ini_get('session.use_only_cookies') === '1', 'Public session chỉ dùng cookie');
publicSessionOk(ini_get('session.use_strict_mode') === '1', 'Public session bật strict mode');
publicSessionOk(!empty($params['secure']), 'HTTPS phát cookie Secure');
publicSessionOk(!empty($params['httponly']), 'Cookie public có HttpOnly');
publicSessionOk(isset($params['samesite']) && $params['samesite'] === 'Lax', 'Cookie public có SameSite=Lax');
publicSessionOk(isset($params['path']) && $params['path'] === '/', 'Cookie dùng chung đúng path toàn website');

$firstId = session_id();
startSecureSession();
publicSessionOk(session_id() === $firstId, 'Gọi bootstrap lặp lại không tạo session cạnh tranh');
session_write_close();

$sourceFiles = array('/var/www/html/index.php', '/var/www/html/api/config.php', '/var/www/html/libraries/class/class.Flash.php');
foreach($sourceFiles as $sourceFile)
{
    $source = file_get_contents($sourceFile);
    publicSessionOk(strpos($source, 'startSecureSession();') !== false, basename($sourceFile).' dùng bootstrap session chung');
}

echo $failures ? 'Public session security test failed.'.PHP_EOL : 'Public session security tests completed.'.PHP_EOL;
exit($failures ? 1 : 0);
