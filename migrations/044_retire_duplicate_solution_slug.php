<?php
/**
 * Thu hồi bản ghi Giải pháp trùng còn sót trên production và cài redirect 301.
 *
 * Bối cảnh (đo ngày 2026-09-17 trên dữ liệu production đã restore lên VPS):
 * đợt làm lại nội dung Giải pháp ngày 15/09 ở local đã ĐỔI TÊN bản ghi id 60
 * thành "Giải pháp chiếu sáng năng lượng mặt trời" (slug
 * chieu-sang-nang-luong-mat-troi) và thêm redirect. Nhưng trên production, lịch
 * sử đi theo hướng khác: id 60 GIỮ NGUYÊN tên/slug cũ và vẫn `hienthi`, còn bản
 * thay thế được tạo mới thành id 134 với slug mới.
 *
 * Hậu quả đo được trên production:
 *   /giai-phap/thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh/  -> 200, tự canonical
 *   /giai-phap/chieu-sang-nang-luong-mat-troi/                       -> 200, tự canonical
 * Hai URL cùng một chủ đề, mỗi URL tự trỏ canonical về chính nó. Bản ghi 60
 * không có cờ `solutionhub` nên KHÔNG hiện trên trang danh sách, nhưng vẫn phục
 * vụ được qua URL trực tiếp và đang giữ 1.369 lượt xem tích luỹ.
 *
 * Việc migration làm:
 *   1. Bỏ cờ `hienthi` của bản ghi 60 để nó thôi phục vụ public (không xoá dữ
 *      liệu, vẫn sửa lại được trong Admin).
 *   2. Thêm redirect 301 từ đường dẫn cũ sang bản ghi 134, giữ giá trị SEO đã
 *      tích luỹ thay vì để URL cũ chết 404.
 *
 * Mặc định chỉ xem trước:
 *   php migrations/044_retire_duplicate_solution_slug.php
 *   php migrations/044_retire_duplicate_solution_slug.php --apply
 *   php migrations/044_retire_duplicate_solution_slug.php --rollback
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
$prefix = isset($config['database']['prefix']) ? $config['database']['prefix'] : 'table_';

$retiredId = 60;
$replacementId = 134;
$oldSlug = 'thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh';
$entityTable = 'news_giai_phap';
/* Resolver nhận cả dạng có và không có tiền tố route, giống 2 dòng đã dùng ở
   local; thêm cả hai để URL cũ vào đường nào cũng được chuyển. */
$oldPaths = array($oldSlug, 'giai-phap/'.$oldSlug);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$fetch = $pdo->prepare("select id, type, slugvi, status from `{$prefix}news` where id = ? limit 1");

$fetch->execute(array($retiredId));
$retired = $fetch->fetch(PDO::FETCH_ASSOC);
$fetch->execute(array($replacementId));
$replacement = $fetch->fetch(PDO::FETCH_ASSOC);

/* Dừng thay vì đoán: nếu dữ liệu không đúng hình trạng đã khảo sát thì mọi
   thao tác ghi đều có thể sai đối tượng. */
if(!$retired || !$replacement)
{
    fwrite(STDERR, "Không tìm thấy bản ghi {$retiredId} hoặc {$replacementId}; dừng.\n");
    exit(1);
}
if($retired['type'] !== 'giai-phap' || $replacement['type'] !== 'giai-phap')
{
    fwrite(STDERR, "Hai bản ghi phải cùng type=giai-phap; dừng.\n");
    exit(1);
}
if($retired['slugvi'] !== $oldSlug)
{
    fwrite(STDERR, "Bản ghi {$retiredId} không còn mang slug cũ (hiện: {$retired['slugvi']}); có thể đã xử lý. Dừng.\n");
    exit($rollback ? 0 : 1);
}

$statusToFlags = function($status) {
    $flags = array_filter(array_map('trim', explode(',', (string)$status)), 'strlen');
    return array_values(array_unique($flags));
};
$currentFlags = $statusToFlags($retired['status']);
$hiddenFlags = array_values(array_diff($currentFlags, array('hienthi')));
$restoredFlags = array_values(array_unique(array_merge($currentFlags, array('hienthi'))));

$countRedirect = $pdo->prepare("select count(*) from `{$prefix}url_redirect` where old_path = ?");

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    echo "  Bản ghi thu hồi : id {$retired['id']} slug {$retired['slugvi']} status \"{$retired['status']}\"\n";
    echo "  Bản thay thế    : id {$replacement['id']} slug {$replacement['slugvi']} status \"{$replacement['status']}\"\n";
    echo "  Sẽ đổi status   : \"".implode(',', $currentFlags)."\" -> \"".implode(',', $hiddenFlags)."\"\n";
    foreach($oldPaths as $path)
    {
        $countRedirect->execute(array($path));
        $has = (int)$countRedirect->fetchColumn();
        echo "  Redirect        : {$path} -> news id {$replacementId} ".($has ? "(đã có, bỏ qua)" : "(sẽ thêm)")."\n";
    }
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$pdo->beginTransaction();
try
{
    if($rollback)
    {
        $upd = $pdo->prepare("update `{$prefix}news` set status = ? where id = ? and type = 'giai-phap'");
        $upd->execute(array(implode(',', $restoredFlags), $retiredId));
        $del = $pdo->prepare("delete from `{$prefix}url_redirect` where old_path = ? and entity_table = ? and entity_id = ?");
        foreach($oldPaths as $path) $del->execute(array($path, $entityTable, $replacementId));
        $pdo->commit();
        echo "ROLLBACK xong: status bản ghi {$retiredId} -> \"".implode(',', $restoredFlags)."\", đã xoá redirect vừa thêm.\n";
        exit(0);
    }

    $upd = $pdo->prepare("update `{$prefix}news` set status = ? where id = ? and type = 'giai-phap'");
    $upd->execute(array(implode(',', $hiddenFlags), $retiredId));

    $ins = $pdo->prepare("insert into `{$prefix}url_redirect` (old_path, entity_table, entity_id, date_created) values (?, ?, ?, ?)");
    $added = 0;
    foreach($oldPaths as $path)
    {
        $countRedirect->execute(array($path));
        if((int)$countRedirect->fetchColumn() > 0) continue;
        $ins->execute(array($path, $entityTable, $replacementId, time()));
        $added++;
    }
    $pdo->commit();
    echo "APPLY xong: status bản ghi {$retiredId} -> \"".implode(',', $hiddenFlags)."\"; thêm {$added} redirect tới news id {$replacementId}.\n";
    echo "Nhớ xoá cache trang sau khi áp dụng.\n";
}
catch(Exception $e)
{
    $pdo->rollBack();
    fwrite(STDERR, "Lỗi, đã rollback transaction: ".$e->getMessage()."\n");
    exit(1);
}
