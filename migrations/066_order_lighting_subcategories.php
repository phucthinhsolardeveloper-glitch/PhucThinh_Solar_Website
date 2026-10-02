<?php
/**
 * 4 danh mục con của "Đèn năng lượng mặt trời" (Đèn Pha/Đèn Đường/Đèn UFO/
 * Đèn Ốp Trần) đều có numb=1 giống hệt nhau - chưa từng được sắp xếp - nên
 * dải tab và sidebar "Loại đèn" tự rơi về sắp theo id giảm dần (Ốp Trần, UFO,
 * Đường, Pha), không theo thứ tự ưu tiên nghiệp vụ nào, lệch với mẫu bố cục
 * đề xuất (Đường → Pha → ... , phát hiện qua rà soát 2026-10-02).
 *
 * Gán numb tăng dần: Đèn Đường(1) → Đèn Pha(2) → Đèn UFO(3) → Đèn Ốp Trần(4)
 * - ưu tiên nhóm phổ biến/khối lượng lớn (đường, pha) trước, nhóm chuyên
 * dụng/trang trí (UFO nhà xưởng, ốp trần dân dụng) sau. Chỉ đổi 4 dòng này,
 * không đụng danh mục khác.
 *
 * Mặc định chỉ preview:
 *   php migrations/066_order_lighting_subcategories.php
 *   php migrations/066_order_lighting_subcategories.php --apply
 *   php migrations/066_order_lighting_subcategories.php --rollback
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$rollback = in_array('--rollback', $argv, true);
if($apply && $rollback)
{
    fwrite(STDERR, "Chỉ được chọn một trong --apply hoặc --rollback.\n");
    exit(1);
}

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

$newNumb = array(20 => 1, 19 => 2, 23 => 3, 24 => 4); // Đường, Pha, UFO, Ốp Trần
$oldNumb = array(20 => 1, 19 => 1, 23 => 1, 24 => 1);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$target = $rollback ? $oldNumb : $newNumb;
$select = $pdo->prepare("select id, numb, namevi from `{$prefix}product_cat` where id = ? limit 1");
$planned = array();
foreach($target as $catId => $numb)
{
    $select->execute(array($catId));
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if(!$row) { fwrite(STDERR, "SKIP: không tìm thấy id_cat=$catId.\n"); continue; }
    if((int)$row['numb'] === $numb) { fwrite(STDERR, "SKIP {$row['namevi']}: numb đã là $numb.\n"); continue; }
    $planned[] = array('id' => $catId, 'name' => $row['namevi'], 'from' => (int)$row['numb'], 'to' => $numb);
}

if(empty($planned)) { echo "Không có gì cần cập nhật.\n"; exit(0); }

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($planned as $p) echo "  [{$p['name']}] numb {$p['from']} -> {$p['to']}\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$update = $pdo->prepare("update `{$prefix}product_cat` set numb = ? where id = ?");
foreach($planned as $p) $update->execute(array($p['to'], $p['id']));
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã cập nhật ".count($planned)." danh mục.\n";
