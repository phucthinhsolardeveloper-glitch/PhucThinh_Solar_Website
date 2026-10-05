<?php
if (PHP_SAPI !== 'cli') exit(1);

define('ROOT', is_dir('/var/www/html/administrator') ? '/var/www/html/' : dirname(__DIR__).'/domains/phucthinhsolar.com/public_html/');
$loginAdmin = 'pts-asset-check';
$_SESSION = array($loginAdmin => array('active' => true));
$adminLeanAssetScreen = false;
unset($template);

set_error_handler(function($severity, $message) {
    throw new ErrorException($message, 0, $severity);
});

try {
    ob_start();
    include ROOT.'administrator/templates/layout/css.php';
    $html = ob_get_clean();
    if (strpos($html, 'assets/css/adminlte.css') === false) throw new RuntimeException('Admin CSS không được render.');
    echo "PASS: Admin CSS render không phát sinh warning khi route chưa đặt template.\n";
} catch (Throwable $exception) {
    if (ob_get_level()) ob_end_clean();
    fwrite(STDERR, 'FAIL: '.$exception->getMessage()."\n");
    exit(1);
} finally {
    restore_error_handler();
}
