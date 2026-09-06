<?php
/**
 * Regression test cho URL public khi chạy sau HTTPS reverse proxy/Cloudflare.
 * Chạy trong web container: php /tmp/test-proxy-https.php
 */

$_SERVER['SERVER_NAME'] = 'internal-web';
$_SERVER['HTTP_HOST'] = 'preview.example.test';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/du-an/?p=2';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https,http';

define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'class/class.Functions.php';

$functions = new Functions(null, null);
$failures = 0;

function proxyHttpsAssert($condition, $message)
{
    global $failures;
    echo ($condition ? 'PASS: ' : 'FAIL: '), $message, PHP_EOL;
    if (!$condition) $failures++;
}

$configBase = 'https://preview.example.test/';
$functions->checkHTTP('https://', array(), $configBase, 'preview.example.test/');
proxyHttpsAssert($configBase === 'https://preview.example.test/', 'Không hạ configBase HTTPS do reverse proxy xuống HTTP');
proxyHttpsAssert($functions->getPageURL() === 'https://preview.example.test/du-an/?p=2', 'URL đầy đủ nhận scheme và host public');
proxyHttpsAssert($functions->getCurrentPageURL() === 'https://preview.example.test/du-an/', 'Canonical nhận HTTPS và bỏ tham số phân trang');

unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/san-pham/';
proxyHttpsAssert($functions->getCurrentPageURL() === 'http://localhost:8080/san-pham/', 'HTTP local trực tiếp vẫn giữ nguyên host và cổng');

echo PHP_EOL, $failures ? 'Proxy HTTPS regression: FAIL' : 'Proxy HTTPS regression: PASS', PHP_EOL;
exit($failures ? 1 : 0);
