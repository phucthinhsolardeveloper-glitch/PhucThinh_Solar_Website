<?php
/**
 * Soạn khối "Chọn X đúng nhu cầu" (type='cards') cho 4/8 danh mục cấp 1 còn
 * thiếu mà CÓ ảnh sản phẩm thật để dùng tạm: Đèn sân vườn, Quạt năng lượng
 * mặt trời, Tấm pin năng lượng mặt trời, Thiết bị & phụ kiện điện mặt trời.
 * Phát hiện qua rà soát toàn site 2026-10-02 (sếp: "thiếu cái Chọn sản phẩm
 * đúng nhu cầu").
 *
 * KHÔNG xử lý 4 danh mục còn lại (Trụ đèn, Biến tần, Pin lưu trữ, Bơm nước
 * năng lượng mặt trời) - toàn bộ sản phẩm trong 4 danh mục này hiện KHÔNG
 * CÓ ảnh nào trong catalog (cả cột photo lẫn photo_feed đều rỗng, xác nhận
 * qua truy vấn trực tiếp + trang danh mục thật đang hiện "NO IMAGE" cho mọi
 * sản phẩm) - không có ảnh thật nào để dùng tạm, tự chế ảnh không liên quan
 * là đi ngược yêu cầu "không chế cháo" sếp đã nói trước đó. Cần admin upload
 * ảnh sản phẩm thật cho 4 danh mục này trước (vấn đề lớn hơn, ảnh hưởng cả
 * lưới sản phẩm, không riêng khối này) rồi mới bổ sung khối cards sau.
 *
 * Mỗi danh mục dùng tạm 1 ảnh sản phẩm thật đã có sẵn của chính danh mục đó
 * (không mượn ảnh danh mục khác) cho cả 3 thẻ - đổi ảnh riêng từng thẻ qua
 * Admin khi có đủ ảnh.
 *
 * Mặc định chỉ preview:
 *   php migrations/068_add_category_needs_cards.php
 *   php migrations/068_add_category_needs_cards.php --apply
 *   php migrations/068_add_category_needs_cards.php --rollback
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

/* id_list => [label, ảnh tạm (thật, của chính danh mục), tiêu đề, 3 thẻ] */
$cardsByList = array(
    28 => array('Đèn sân vườn', 'pl-0109-7307.png', 'Chọn đèn sân vườn đúng nhu cầu', array(
        array('title' => 'Cổng & tường rào', 'desc' => 'Đảm bảo lối vào sáng rõ, trang trọng'),
        array('title' => 'Sân vườn & lối đi', 'desc' => 'Tạo không gian cảnh quan ấm cúng'),
        array('title' => 'Tiểu cảnh & cây xanh', 'desc' => 'Nhấn điểm cho cây, biển nhỏ'),
    )),
    23 => array('Quạt năng lượng mặt trời', 'blue-fan20w-9997.png', 'Chọn quạt đúng nhu cầu', array(
        array('title' => 'Nhà xưởng & công trình', 'desc' => 'Làm mát không gian rộng, ổn định'),
        array('title' => 'Sân vườn & ngoài trời', 'desc' => 'Thoáng mát cho không gian sinh hoạt'),
        array('title' => 'Nơi chưa có điện lưới', 'desc' => 'Chủ động nguồn điện từ năng lượng mặt trời'),
    )),
    21 => array('Tấm pin năng lượng mặt trời', 'sharp-550w-mono-nu-jd-550-l-8519.png', 'Chọn tấm pin đúng nhu cầu', array(
        array('title' => 'Hộ gia đình', 'desc' => 'Tiết kiệm điện cho mái nhà dân dụng'),
        array('title' => 'Doanh nghiệp & nhà xưởng', 'desc' => 'Giảm chi phí vận hành sản xuất'),
        array('title' => 'Dự án công suất lớn', 'desc' => 'Hệ thống điện mặt trời quy mô lớn'),
    )),
    25 => array('Thiết bị & phụ kiện điện mặt trời', 'product-372-clean-v1-1319.png', 'Chọn thiết bị đúng nhu cầu', array(
        array('title' => 'Hệ thống mới', 'desc' => 'Đầy đủ thiết bị để lắp đặt hoàn chỉnh'),
        array('title' => 'Nâng cấp & mở rộng', 'desc' => 'Bổ sung, thay thế theo nhu cầu'),
        array('title' => 'Bảo trì & sửa chữa', 'desc' => 'Vật tư thay thế, khắc phục sự cố'),
    )),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$selectQuery = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = ? limit 1");
$updateQuery = $pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?");

$planned = array();
foreach($cardsByList as $listId => $def)
{
    list($label, $photo, $title, $items) = $def;
    $cardItems = array();
    foreach($items as $item) $cardItems[] = array('photo' => $photo, 'title' => $item['title'], 'desc' => $item['desc'], 'link' => '');
    $cardsBlock = array('type' => 'cards', 'title' => $title, 'items' => $cardItems);

    $selectQuery->execute(array($listId));
    $existing = $selectQuery->fetch(PDO::FETCH_ASSOC);
    if(!$existing)
    {
        fwrite(STDERR, "SKIP $label: không tìm thấy content_section (list id=$listId).\n");
        continue;
    }

    $decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
    $decoded = is_array($decoded) ? $decoded : array();
    $hasCardsAlready = false;
    foreach($decoded as $block) if(is_array($block) && (($block['type'] ?? '') === 'cards')) { $hasCardsAlready = true; break; }

    if(!$rollback && $hasCardsAlready)
    {
        fwrite(STDERR, "SKIP $label: content_section id={$existing['id']} đã có khối cards, không ghi đè.\n");
        continue;
    }
    if($rollback && !$hasCardsAlready)
    {
        fwrite(STDERR, "SKIP $label: content_section id={$existing['id']} hiện không có cards, không có gì để rollback.\n");
        continue;
    }

    if($rollback)
    {
        $restored = array();
        foreach($decoded as $block) if(!(is_array($block) && (($block['type'] ?? '') === 'cards'))) $restored[] = $block;
        $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => $restored);
    }
    else
    {
        $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => array_merge($decoded, array($cardsBlock)));
    }
}

if(empty($planned))
{
    echo "Không có danh mục nào cần cập nhật.\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($planned as $p) echo "  [{$p['label']}] content_section id={$p['id']}\n";
    echo "Chạy lại với --apply để áp dụng (".count($planned)." danh mục).\n";
    exit(0);
}

$now = time();
$done = 0;
foreach($planned as $p)
{
    $json = htmlspecialchars(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
    $updateQuery->execute(array($json, $now, $p['id']));
    $done++;
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã cập nhật $done danh mục.\n";
