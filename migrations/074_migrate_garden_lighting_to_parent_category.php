<?php
/**
 * Gom toàn bộ sản phẩm của product_list "Đèn sân vườn" cũ vào danh mục con
 * "Đèn sân vườn" của product_list "Đèn năng lượng mặt trời".
 *
 * URL cấp list cũ vẫn được giữ để source chuyển hướng 301 về URL canonical.
 * Migration chạy lặp lại an toàn: lần chạy sau không còn sản phẩm cần cập nhật.
 *
 * Preview: php migrations/074_migrate_garden_lighting_to_parent_category.php
 * Apply:   php migrations/074_migrate_garden_lighting_to_parent_category.php --apply
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) { fwrite(STDERR, "Không tìm thấy libraries/config.php.\n"); exit(1); }

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';

$db = $config['database'];
$prefix = !empty($db['prefix']) ? $db['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { fwrite(STDERR, "Table prefix không hợp lệ.\n"); exit(1); }
$pdo = new PDO(
    'mysql:host='.$db['host'].';port='.(int)$db['port'].';dbname='.$db['dbname'].';charset=utf8mb4',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$findList = $pdo->prepare("select id, status from `{$prefix}product_list` where slugvi=? and type=? limit 1");
$findList->execute(array('den-nlmt', 'san-pham'));
$parent = $findList->fetch(PDO::FETCH_ASSOC);
$findList->execute(array('den-san-vuon', 'san-pham'));
$legacy = $findList->fetch(PDO::FETCH_ASSOC);
if(!$parent || !$legacy) { fwrite(STDERR, "Không tìm thấy đủ danh mục cha/cũ.\n"); exit(1); }

$findCat = $pdo->prepare("select id, status from `{$prefix}product_cat` where id_list=? and slugvi=? and type=? limit 1");
$findCat->execute(array((int)$parent['id'], 'den-san-vuon', 'san-pham'));
$gardenCat = $findCat->fetch(PDO::FETCH_ASSOC);
if(!$gardenCat) { fwrite(STDERR, "Không tìm thấy product_cat Đèn sân vườn dưới Đèn NLMT.\n"); exit(1); }

$countStmt = $pdo->prepare("select count(*) from `{$prefix}product` where id_list=? and type=?");
$countStmt->execute(array((int)$legacy['id'], 'san-pham'));
$productCount = (int)$countStmt->fetchColumn();

echo 'PREVIEW: chuyển '.$productCount.' sản phẩm từ list '.$legacy['id'].' sang list '.$parent['id'].' / cat '.$gardenCat['id'].".\n";
if(!$apply || $productCount === 0) exit(0);

$pdo->beginTransaction();
try {
    $update = $pdo->prepare("update `{$prefix}product` set id_list=?, id_cat=? where id_list=? and type=?");
    $update->execute(array((int)$parent['id'], (int)$gardenCat['id'], (int)$legacy['id'], 'san-pham'));
    $pdo->commit();
    echo 'APPLY xong: '.$update->rowCount()." sản phẩm đã chuyển.\n";
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
