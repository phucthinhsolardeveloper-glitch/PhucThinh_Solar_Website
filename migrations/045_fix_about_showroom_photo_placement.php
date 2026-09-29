<?php
/**
 * Đưa ảnh trang Giới thiệu về đúng bản ghi như thiết kế (bản local).
 *
 * Bối cảnh (đo 2026-09-17 sau khi restore dữ liệu production lên VPS):
 * migration 043 đổi ảnh của bản ghi `lich-su` có photo = 2354937...n-6638.png
 * thành ảnh showroom mới. Nhưng trên production ảnh showroom đã được nhập bằng
 * tay qua Admin vào SAI bản ghi:
 *
 *   id 81 "Lịch sử hình thành"            -> about-phuc-thinh-showroom-20260916.jpg
 *   id 82 "Về Chúng Tôi Phúc Thịnh Solar" -> 2354937...n-6638.png
 *
 * Trong khi thiết kế (local) là ngược lại:
 *
 *   id 81 -> z453730262355794910a4cdaeda2f2e99d9ef20740636d-scaled-2225.jpg
 *   id 82 -> about-phuc-thinh-showroom-20260916.jpg
 *
 * Vì cả hai dòng đều khớp điều kiện tìm của 043 (một dòng mang ảnh cũ, một dòng
 * mang ảnh mới) nên 043 đếm ra 2 kết quả và TỰ DỪNG - đúng như nó được thiết kế
 * để không ghi nhầm. Migration này xử lý riêng tình trạng lệch đó.
 *
 * đã chốt theo đặc tả ngày 2026-09-17: lấy theo bản local.
 *
 * Mặc định chỉ xem trước:
 *   php migrations/045_fix_about_showroom_photo_placement.php
 *   php migrations/045_fix_about_showroom_photo_placement.php --apply
 *   php migrations/045_fix_about_showroom_photo_placement.php --rollback
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

$recordType = 'lich-su';
$showroom = 'about-phuc-thinh-showroom-20260916.jpg';
$legacyAbout = '2354937183308875654238531382722883947765860n-6638.png';
$historyPhoto = 'z453730262355794910a4cdaeda2f2e99d9ef20740636d-scaled-2225.jpg';
$uploadDir = $publicHtml.'/upload/news';

/* id => array(trang thai production hien tai, trang thai dich theo local) */
$plan = array(
    81 => array('from' => $showroom,     'to' => $historyPhoto),
    82 => array('from' => $legacyAbout,  'to' => $showroom),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$fetch = $pdo->prepare("select id, type, namevi, photo from `{$prefix}news` where id = ? limit 1");
$rows = array();
foreach($plan as $id => $step)
{
    $fetch->execute(array($id));
    $row = $fetch->fetch(PDO::FETCH_ASSOC);
    if(!$row)
    {
        fwrite(STDERR, "Không tìm thấy bản ghi {$id}; dừng.\n");
        exit(1);
    }
    if($row['type'] !== $recordType)
    {
        fwrite(STDERR, "Bản ghi {$id} không phải type={$recordType}; dừng.\n");
        exit(1);
    }
    $rows[$id] = $row;
}

/* File đích phải có thật trong upload/news, nếu không trang sẽ hiện ảnh lỗi. */
foreach($plan as $id => $step)
{
    if(!is_file($uploadDir.'/'.$step['to']))
    {
        fwrite(STDERR, "Thiếu file đích trong upload/news: {$step['to']}; dừng.\n");
        exit(1);
    }
}

$expectedKey = $rollback ? 'to' : 'from';
$targetKey = $rollback ? 'from' : 'to';
foreach($plan as $id => $step)
{
    if($rows[$id]['photo'] === $step[$targetKey]) continue;
    if($rows[$id]['photo'] !== $step[$expectedKey])
    {
        fwrite(STDERR, "Bản ghi {$id} đang dùng ảnh ngoài dự kiến ({$rows[$id]['photo']}); dừng để không ghi đè dữ liệu biên tập.\n");
        exit(1);
    }
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($plan as $id => $step)
    {
        $now = $rows[$id]['photo'];
        $to = $step['to'];
        echo "  id {$id} \"{$rows[$id]['namevi']}\"\n";
        echo "      hiện tại : {$now}\n";
        echo "      sẽ thành : {$to}".($now === $to ? "  (đã đúng, bỏ qua)" : "")."\n";
    }
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$pdo->beginTransaction();
try
{
    $update = $pdo->prepare("update `{$prefix}news` set photo = ? where id = ? and type = ? and photo = ?");
    $changed = 0;
    foreach($plan as $id => $step)
    {
        $want = $rollback ? $step['from'] : $step['to'];
        $have = $rollback ? $step['to'] : $step['from'];
        if($rows[$id]['photo'] === $want) continue;
        $update->execute(array($want, $id, $recordType, $have));
        if($update->rowCount() !== 1) throw new RuntimeException("Bản ghi {$id} đã thay đổi trong lúc chạy.");
        $changed++;
    }
    $pdo->commit();
    echo ($rollback ? "ROLLBACK" : "APPLY")." xong: đã cập nhật {$changed} bản ghi.\n";
    echo "Nhớ xoá cache trang sau khi áp dụng.\n";
}
catch(Exception $e)
{
    $pdo->rollBack();
    fwrite(STDERR, "Lỗi, đã rollback transaction: ".$e->getMessage()."\n");
    exit(1);
}
