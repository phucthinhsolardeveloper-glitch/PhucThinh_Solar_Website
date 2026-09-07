<?php
/**
 * CLI-only SMTP readiness.
 *
 *   php scripts/smtp-readiness.php             # kiểm tra cấu hình
 *   php scripts/smtp-readiness.php --connect   # kết nối + authenticate
 *   php scripts/smtp-readiness.php --send-test # gửi 1 mail tới mail.admin_to
 *
 * Không in host, username, recipient, password hoặc lỗi thô từ SMTP provider.
 */
if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "FAIL [config]: không tìm thấy libraries/config.php ngoài Git.\n");
    exit(1);
}

if(!defined('LIBRARIES')) define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'mail_config.php';
loadMailConfig($config);
require LIBRARIES.'class/class.Email.php';

$connect = in_array('--connect', $argv, true);
$sendTest = in_array('--send-test', $argv, true);
$mailConfig = isset($config['mail']) && is_array($config['mail']) ? $config['mail'] : array();
$result = Email::readiness($mailConfig, $connect || $sendTest, $sendTest);

if(empty($result['ok']))
{
    $stage = isset($result['stage']) ? $result['stage'] : 'unknown';
    $fields = !empty($result['errors']) ? ' ('.implode(',', $result['errors']).')' : '';
    fwrite(STDERR, 'FAIL ['.$stage.']'.$fields."\n");
    exit(1);
}

$label = $sendTest ? 'send' : ($connect ? 'connect' : 'config');
echo 'PASS ['.$label."]\n";
exit(0);
