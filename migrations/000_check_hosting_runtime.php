<?php
/**
 * Kiểm tra chỉ đọc môi trường PHP/cấu hình/dữ liệu tối thiểu trước deploy.
 * Không thử đăng nhập SMTP và không in host, email, mật khẩu hay dữ liệu khách.
 *
 *   php migrations/000_check_hosting_runtime.php
 *   php migrations/000_check_hosting_runtime.php --require-production
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
set_exception_handler(function($exception) {
    fwrite(STDERR, "FAIL: Không thể hoàn tất runtime preflight; kiểm tra config, kết nối DB và log server.\n");
    exit(1);
});

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "FAIL: Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$requireProduction = in_array('--require-production', $argv, true);
$failures = array();
$warnings = array();

function runtimeResult($ok, $label, $detail)
{
    printf("%-5s %-28s %s\n", $ok ? 'PASS' : 'FAIL', $label, $detail);
}

echo "HOSTING RUNTIME PREFLIGHT — CHỈ ĐỌC\n";
echo str_repeat('=', 76), "\n";

$phpOk = PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 80000;
runtimeResult($phpOk, 'PHP', PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.' (mục tiêu đã test: 7.4.x)');
if(!$phpOk) $failures[] = 'PHP phải là 7.4.x cho lần deploy này; PHP 8.x cần đợt kiểm thử riêng.';

$requiredExtensions = array('calendar','curl','exif','fileinfo','gd','json','mbstring','pdo_mysql','zip');
$missingExtensions = array();
foreach($requiredExtensions as $extension) if(!extension_loaded($extension)) $missingExtensions[] = $extension;
runtimeResult(empty($missingExtensions), 'PHP extensions', empty($missingExtensions) ? implode(', ', $requiredExtensions) : 'thiếu '.implode(', ', $missingExtensions));
if(!empty($missingExtensions)) $failures[] = 'Thiếu PHP extension: '.implode(', ', $missingExtensions).'.';

$runtimeDirs = array('upload','thumbs','caches','logs');
$badDirs = array();
foreach($runtimeDirs as $directory)
{
    $path = $publicHtml.'/'.$directory;
    if(!is_dir($path) || !is_writable($path)) $badDirs[] = $directory;
}
runtimeResult(empty($badDirs), 'Thư mục runtime', empty($badDirs) ? 'upload, thumbs, caches, logs ghi được' : 'thiếu/không ghi được: '.implode(', ', $badDirs));
if(!empty($badDirs)) $failures[] = 'Thư mục runtime chưa sẵn sàng: '.implode(', ', $badDirs).'.';

$permissionOk = !empty($config['permission']['active']);
runtimeResult($permissionOk, 'Phân quyền Admin', $permissionOk ? 'đang bật' : 'đang tắt');
if(!$permissionOk) $failures[] = 'Phân quyền Admin đang tắt.';

$debugOff = empty($config['website']['debug-developer']);
$reportingOff = empty($config['website']['error-reporting']);
runtimeResult($debugOff || !$requireProduction, 'debug-developer', $debugOff ? 'đã tắt' : 'đang bật (chỉ chấp nhận ở local)');
runtimeResult($reportingOff || !$requireProduction, 'error-reporting', $reportingOff ? 'đã tắt' : 'đang bật (chỉ chấp nhận ở local)');
if($requireProduction && !$debugOff) $failures[] = 'Production còn bật debug-developer.';
if($requireProduction && !$reportingOff) $failures[] = 'Production còn bật error-reporting.';
if(!$requireProduction && (!$debugOff || !$reportingOff)) $warnings[] = 'Local đang bật chế độ debug; đây là chủ đích của Docker test.';

$setting = $d->rawQueryOne('select options from #_setting limit 0,1');
$options = !empty($setting['options']) ? json_decode($setting['options'], true) : array();
if(!is_array($options)) $options = array();
$mailerType = isset($options['mailertype']) ? (int)$options['mailertype'] : 1;
$smtpKeys = $mailerType === 2
    ? array('host_gmail','port_gmail','secure_gmail','email_gmail','password_gmail')
    : array('ip_host','port_host','secure_host','email_host','password_host');
$smtpConfigured = true;
foreach($smtpKeys as $key) if(!isset($options[$key]) || trim((string)$options[$key]) === '') $smtpConfigured = false;
runtimeResult($smtpConfigured || !$requireProduction, 'Cấu hình SMTP', $smtpConfigured ? 'đủ trường bắt buộc (chưa gửi thử)' : 'còn thiếu (chỉ chấp nhận ở local)');
if($requireProduction && !$smtpConfigured) $failures[] = 'SMTP production chưa đủ cấu hình.';
if(!$requireProduction && !$smtpConfigured) $warnings[] = 'SMTP local chưa cấu hình; form đã có cơ chế không làm vỡ trang khi gửi thất bại.';

$payment = $d->rawQueryOne("select count(*) as n from #_news where type = ? and find_in_set('hienthi', status)", array('hinh-thuc-thanh-toan'));
$paymentCount = isset($payment['n']) ? (int)$payment['n'] : 0;
$paymentOk = $paymentCount > 0;
runtimeResult($paymentOk, 'Phương thức thanh toán', $paymentCount.' phương thức đang hiển thị');
if(!$paymentOk) $failures[] = 'Không có phương thức thanh toán đang hiển thị.';

foreach($warnings as $warning) echo 'WARN: '.$warning."\n";
if(!empty($failures))
{
    foreach($failures as $failure) echo 'BLOCK: '.$failure."\n";
    exit(1);
}
echo $requireProduction
    ? "READY: runtime production đạt các điều kiện tự động; vẫn phải gửi thử email và smoke test trình duyệt.\n"
    : "READY: runtime local đạt điều kiện bắt buộc.\n";
exit(0);
