<?php
/**
 * Đặt số hotline kỹ thuật (options.hotline_technical trong bảng setting) khi production chưa có.
 * Footer và thanh liên hệ chỉ hiện thẻ "Hỗ trợ kỹ thuật" khi khóa này có giá trị; Admin chưa có ô nhập cho khóa này.
 * Không ghi đè nếu đã có giá trị.
 *
 * Preview: php migrations/078_set_technical_hotline.php
 * Apply:   php migrations/078_set_technical_hotline.php --apply
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) { fwrite(STDERR, "Không tìm thấy libraries/config.php.\n"); exit(1); }

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';

$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { fwrite(STDERR, "Table prefix không hợp lệ.\n"); exit(1); }

$db = $config['database'];
$pdo = new PDO(
    'mysql:host='.$db['host'].';port='.(int)$db['port'].';dbname='.$db['dbname'].';charset=utf8mb4',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$technicalHotline = '0945 536 899';
$row = $pdo->query("select id, options from `{$prefix}setting` order by id limit 1")->fetch(PDO::FETCH_ASSOC);
if(!$row) { fwrite(STDERR, "Không tìm thấy bản ghi setting.\n"); exit(1); }
$options = json_decode((string)$row['options'], true);
if(!is_array($options)) { fwrite(STDERR, "options của setting không phải JSON hợp lệ; dừng, không ghi.\n"); exit(1); }
if(trim((string)($options['hotline_technical'] ?? '')) !== '') { echo "SKIP: hotline kỹ thuật đã có ({$options['hotline_technical']}).\n"; exit(0); }

echo "PREVIEW: setting id={$row['id']} sẽ có hotline_technical = {$technicalHotline}; các khóa khác giữ nguyên.\n";
if(!$apply) { echo "Chưa ghi gì. Backup DB rồi chạy lại kèm --apply.\n"; exit(0); }

$options['hotline_technical'] = $technicalHotline;
$update = $pdo->prepare("update `{$prefix}setting` set options = ? where id = ?");
$update->execute(array(json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $row['id']));
echo "Đã đặt hotline kỹ thuật.\n";
