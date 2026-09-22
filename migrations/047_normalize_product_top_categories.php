<?php
/**
 * Chuẩn hoá cây sản phẩm còn 9 danh mục cấp 1 theo quyết định ngày 2026-09-22.
 *
 * - Đặt lại tên/thứ tự 9 danh mục cấp 1 (numb 1..9).
 * - Ẩn "Hệ khung & phụ kiện tấm pin" khỏi cấp 1, không xoá dữ liệu.
 * - Chuyển ba danh mục cấp 2 Thanh ray/Kẹp tấm pin/Kẹp mái sang dưới
 *   "Tấm pin năng lượng mặt trời" và chuyển 12 sản phẩm theo cùng id_list.
 * - Giữ nguyên id/slug danh mục cấp 2, SEO và content_section của chúng.
 * - Cài redirect cho URL cấp 1/cấp 2 cũ để không làm chết link đã chia sẻ.
 * - Nếu campaign từng áp theo danh mục Hệ khung, đổi scope sang ba danh mục
 *   cấp 2 vừa chuyển để không vô tình áp cho toàn bộ Tấm pin.
 *
 * Không tạo danh mục cấp 3. Bản ghi cấp 1 cũ chỉ bị ẩn để có đường khôi phục.
 * Mặc định chỉ xem trước; phải backup DB trước khi chạy --apply.
 *
 * Chạy:
 *   php migrations/047_normalize_product_top_categories.php
 *   php migrations/047_normalize_product_top_categories.php --apply
 *
 * Rollback: khôi phục DB từ backup tạo ngay trước khi --apply.
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
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

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$prefix = isset($config['database']['prefix']) ? $config['database']['prefix'] : 'table_';
$type = 'san-pham';
$now = time();

$topPlan = array(
    'den-nlmt' => array('name' => 'Đèn năng lượng mặt trời', 'numb' => 1),
    'den-san-vuon' => array('name' => 'Đèn sân vườn', 'numb' => 2),
    'tru-den' => array('name' => 'Trụ đèn', 'numb' => 3),
    'quat-nang-luong-mat-troi' => array('name' => 'Quạt năng lượng mặt trời', 'numb' => 4),
    'tam-pin-nang-luong-mat-troi' => array('name' => 'Tấm pin năng lượng mặt trời', 'numb' => 5),
    'bien-tan-inverter' => array('name' => 'Biến tần (Inverter)', 'numb' => 6),
    'pin-luu-tru' => array('name' => 'Pin lưu trữ', 'numb' => 7),
    'bien-tan-bom' => array('name' => 'Bơm nước năng lượng mặt trời', 'numb' => 8),
    'thiet-bi-dien-nang-luong-mat-troi' => array('name' => 'Thiết bị & phụ kiện điện mặt trời', 'numb' => 9),
);
$retiredSlug = 'he-thong-khung-nhom';
$panelSlug = 'tam-pin-nang-luong-mat-troi';
$movedCats = array(
    'thanh-ray' => 1,
    'kep-tam-pin' => 2,
    'kep-mai-kliplock-seamlock' => 3,
);

$flags = function($status) {
    $parts = array_filter(array_map('trim', explode(',', (string)$status)), 'strlen');
    return array_values(array_unique($parts));
};
$withFlag = function($status, $flag) use ($flags) {
    $parts = $flags($status);
    if(!in_array($flag, $parts, true)) $parts[] = $flag;
    return implode(',', $parts);
};
$withoutFlags = function($status, array $remove) use ($flags) {
    return implode(',', array_values(array_diff($flags($status), $remove)));
};

$listStmt = $pdo->prepare("select id,namevi,slugvi,numb,status from `{$prefix}product_list` where type=?");
$listStmt->execute(array($type));
$listsBySlug = array();
foreach($listStmt->fetchAll(PDO::FETCH_ASSOC) as $row) $listsBySlug[$row['slugvi']] = $row;

$requiredSlugs = array_merge(array_keys($topPlan), array($retiredSlug));
$missing = array();
foreach($requiredSlugs as $slug) if(empty($listsBySlug[$slug])) $missing[] = $slug;
if($missing)
{
    fwrite(STDERR, "Thiếu danh mục cấp 1 bắt buộc: ".implode(', ', $missing)."; dừng để tránh gán nhầm.\n");
    exit(1);
}

$retired = $listsBySlug[$retiredSlug];
$panel = $listsBySlug[$panelSlug];
$catStmt = $pdo->prepare("select id,id_list,namevi,slugvi,numb,status from `{$prefix}product_cat` where type=? and slugvi=?");
$catRows = array();
foreach($movedCats as $slug => $numb)
{
    $catStmt->execute(array($type, $slug));
    $rows = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    if(count($rows) !== 1)
    {
        fwrite(STDERR, "Danh mục cấp 2 slug {$slug} phải có đúng 1 bản ghi, hiện có ".count($rows)."; dừng.\n");
        exit(1);
    }
    $catRows[$slug] = $rows[0];
    $parentId = (int)$rows[0]['id_list'];
    if($parentId !== (int)$retired['id'] && $parentId !== (int)$panel['id'])
    {
        fwrite(STDERR, "Danh mục cấp 2 {$slug} đang thuộc id_list={$parentId}, không phải nguồn/đích dự kiến; dừng.\n");
        exit(1);
    }
}

$catIds = array_map(function($row) { return (int)$row['id']; }, array_values($catRows));
$catPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
$productCheck = $pdo->prepare(
    "select id,id_list,id_cat,id_item,id_sub,namevi from `{$prefix}product`
     where type=? and (id_list=? or id_cat in ({$catPlaceholders}))"
);
$productCheck->execute(array_merge(array($type, (int)$retired['id']), $catIds));
$affectedProducts = $productCheck->fetchAll(PDO::FETCH_ASSOC);
foreach($affectedProducts as $product)
{
    if(!in_array((int)$product['id_cat'], $catIds, true) || (int)$product['id_item'] !== 0 || (int)$product['id_sub'] !== 0)
    {
        fwrite(STDERR, "Sản phẩm #{$product['id']} không đúng cấu trúc 2 cấp dự kiến; dừng.\n");
        exit(1);
    }
    if((int)$product['id_list'] !== (int)$retired['id'] && (int)$product['id_list'] !== (int)$panel['id'])
    {
        fwrite(STDERR, "Sản phẩm #{$product['id']} có id_list không khớp cha danh mục; dừng.\n");
        exit(1);
    }
}

$deeperStmt = $pdo->prepare(
    "select
       (select count(*) from `{$prefix}product_item` where type=? and id_list=?) item_count,
       (select count(*) from `{$prefix}product_sub` where type=? and id_list=?) sub_count"
);
$deeperStmt->execute(array($type, (int)$retired['id'], $type, (int)$retired['id']));
$deeper = $deeperStmt->fetch(PDO::FETCH_ASSOC);
if((int)$deeper['item_count'] !== 0 || (int)$deeper['sub_count'] !== 0)
{
    fwrite(STDERR, "Danh mục Hệ khung đang có cấp 3/cấp 4; migration này cố ý không tạo cấp 3 nên dừng.\n");
    exit(1);
}

$visibleCountStmt = $pdo->prepare("select count(*) from `{$prefix}product_list` where type=? and find_in_set('hienthi',status)");
$visibleCountStmt->execute(array($type));
$visibleBefore = (int)$visibleCountStmt->fetchColumn();
$alreadyMoved = true;
foreach($catRows as $row) if((int)$row['id_list'] !== (int)$panel['id']) $alreadyMoved = false;
foreach($affectedProducts as $row) if((int)$row['id_list'] !== (int)$panel['id']) $alreadyMoved = false;
$retiredVisible = in_array('hienthi', $flags($retired['status']), true);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "XEM TRƯỚC — chưa ghi dữ liệu\n\n";
echo "Danh mục cấp 1 đang hiển thị: {$visibleBefore}\n";
foreach($topPlan as $slug => $plan)
{
    $row = $listsBySlug[$slug];
    echo sprintf("  %d. %s (id %d; hiện: %s, numb %d)\n", $plan['numb'], $plan['name'], $row['id'], $row['namevi'], $row['numb']);
}
echo "Ẩn cấp 1: {$retired['namevi']} (id {$retired['id']})\n";
echo "Chuyển ".count($catRows)." danh mục cấp 2 và ".count($affectedProducts)." sản phẩm sang {$panel['namevi']} (id {$panel['id']}).\n";
foreach($catRows as $slug => $row) echo "  - {$row['namevi']} (id {$row['id']}) -> numb {$movedCats[$slug]}\n";

if(!$apply)
{
    if($alreadyMoved && !$retiredVisible) echo "Dữ liệu phân cấp đã ở trạng thái đích; chạy --apply chỉ chuẩn hoá lại tên/thứ tự/redirect.\n";
    else echo "Chạy lại với --apply sau khi đã backup DB.\n";
    exit(0);
}

try
{
    $updateList = $pdo->prepare(
        "update `{$prefix}product_list` set namevi=?, numb=?, status=?, date_updated=? where id=? and type=?"
    );
    foreach($topPlan as $slug => $plan)
    {
        $row = $listsBySlug[$slug];
        $newStatus = $withFlag($row['status'], 'hienthi');
        $updateList->execute(array($plan['name'], $plan['numb'], $newStatus, $now, (int)$row['id'], $type));
    }

    $retiredStatus = $withoutFlags($retired['status'], array('hienthi','noibat','major','solutionhub'));
    $updateList->execute(array($retired['namevi'], 10, $retiredStatus, $now, (int)$retired['id'], $type));

    $moveCat = $pdo->prepare(
        "update `{$prefix}product_cat` set id_list=?, numb=?, date_updated=? where id=? and type=?"
    );
    foreach($catRows as $slug => $row)
        $moveCat->execute(array((int)$panel['id'], $movedCats[$slug], $now, (int)$row['id'], $type));

    $moveProducts = $pdo->prepare(
        "update `{$prefix}product` set id_list=?, date_updated=? where type=? and id_cat in ({$catPlaceholders})"
    );
    $moveProducts->execute(array_merge(array((int)$panel['id'], $now, $type), $catIds));

    /* Giữ phạm vi campaign cũ ở đúng 3 nhóm Hệ khung, không mở rộng sang
       toàn bộ danh mục Tấm pin. */
    $scopeStmt = $pdo->prepare(
        "select id_campaign from `{$prefix}product_campaign_scope` where scope_type='list' and scope_id=?"
    );
    $scopeStmt->execute(array((int)$retired['id']));
    $campaignIds = array_map('intval', $scopeStmt->fetchAll(PDO::FETCH_COLUMN));
    $insertScope = $pdo->prepare(
        "insert ignore into `{$prefix}product_campaign_scope` (id_campaign,scope_type,scope_id) values (?,'cat',?)"
    );
    foreach($campaignIds as $campaignId)
        foreach($catIds as $catId) $insertScope->execute(array($campaignId, $catId));
    $deleteScope = $pdo->prepare(
        "delete from `{$prefix}product_campaign_scope` where scope_type='list' and scope_id=?"
    );
    $deleteScope->execute(array((int)$retired['id']));

    /* Chỉ sửa SEO title chắc chắn là tên cũ/lỗi chính tả; không ghi đè nội
       dung marketing đã được biên tập riêng. */
    $seoUpdate = $pdo->prepare(
        "update `{$prefix}seo` set titlevi=? where com='product' and act='man_list' and type=? and id_parent=? and (trim(titlevi)=? or trim(titlevi)=?)"
    );
    $seoUpdate->execute(array('Tấm pin năng lượng mặt trời', $type, (int)$panel['id'], 'Tấm pin năng lượng măt trời', ''));
    $seoUpdate->execute(array('Bơm nước năng lượng mặt trời', $type, (int)$listsBySlug['bien-tan-bom']['id'], 'Biến tần bơm', ''));

    $redirect = $pdo->prepare(
        "insert into `{$prefix}url_redirect` (old_path,entity_table,entity_id,date_created)
         values (?,?,?,?) on duplicate key update entity_table=values(entity_table),entity_id=values(entity_id)"
    );
    foreach(array('danh-muc-san-pham/austdoor','danh-muc-san-pham/'.$retiredSlug) as $oldPath)
        $redirect->execute(array($oldPath, 'product_list', (int)$panel['id'], $now));
    foreach($catRows as $slug => $row)
        $redirect->execute(array('danh-muc-san-pham/'.$retiredSlug.'/'.$slug, 'product_cat', (int)$row['id'], $now));
}
catch(Exception $exception)
{
    fwrite(STDERR, "Lỗi khi áp dụng: ".$exception->getMessage()."\nDB dùng MyISAM nên hãy restore backup vừa tạo trước migration.\n");
    exit(1);
}

/* Hậu kiểm trực tiếp từ DB. */
$visibleRows = $pdo->prepare(
    "select id,namevi,slugvi,numb from `{$prefix}product_list`
     where type=? and find_in_set('hienthi',status) order by numb,id"
);
$visibleRows->execute(array($type));
$visibleRows = $visibleRows->fetchAll(PDO::FETCH_ASSOC);
if(count($visibleRows) !== 9)
{
    fwrite(STDERR, "Hậu kiểm lỗi: cần đúng 9 danh mục cấp 1 hiển thị, hiện có ".count($visibleRows).". Hãy restore backup.\n");
    exit(1);
}
foreach(array_values($topPlan) as $index => $plan)
{
    if($visibleRows[$index]['namevi'] !== $plan['name'] || (int)$visibleRows[$index]['numb'] !== $plan['numb'])
    {
        fwrite(STDERR, "Hậu kiểm lỗi ở vị trí ".($index + 1).". Hãy restore backup.\n");
        exit(1);
    }
}
$mismatch = $pdo->prepare(
    "select count(*) from `{$prefix}product` p join `{$prefix}product_cat` c on c.id=p.id_cat
     where p.type=? and p.id_cat in ({$catPlaceholders}) and (p.id_list<>? or c.id_list<>?)"
);
$mismatch->execute(array_merge(array($type), $catIds, array((int)$panel['id'], (int)$panel['id'])));
if((int)$mismatch->fetchColumn() !== 0)
{
    fwrite(STDERR, "Hậu kiểm lỗi: sản phẩm/danh mục con còn lệch id_list. Hãy restore backup.\n");
    exit(1);
}

echo "\nAPPLY xong: đúng 9 danh mục cấp 1; đã chuyển ".count($affectedProducts)." sản phẩm Hệ khung sang Tấm pin; không tạo cấp 3.\n";
echo "Cần xoá cache ứng dụng để header/listview nhận thứ tự mới.\n";

