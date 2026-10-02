<?php
/**
 * Chuyển khối "Chọn đèn đúng nhu cầu" từ hardcode trong template
 * (templates/product/product_tpl.php, đã gỡ) sang khối type='cards' thật
 * trong content_section của danh mục "Đèn năng lượng mặt trời" (list id 20)
 * - dùng đúng cơ chế composer chung (Admin > Khối nội dung mô tả) vừa thêm
 * loại "Lưới thẻ", để admin tự sửa tiêu đề/thẻ/ảnh qua UI thay vì phải nhờ
 * sửa code mỗi khi đổi nội dung.
 *
 * Ảnh tạm dùng chung 1 ảnh sản phẩm đèn đường thật đang có sẵn trong
 * upload/product/ (renderer đọc UPLOAD_PRODUCT, không phải UPLOAD_PHOTO) -
 * đúng như đã thống nhất với sếp 2026-10-02, đổi ảnh thật qua Admin khi có.
 *
 * Mặc định chỉ preview:
 *   php migrations/064_add_lighting_needs_cards_block.php
 *   php migrations/064_add_lighting_needs_cards_block.php --apply
 *   php migrations/064_add_lighting_needs_cards_block.php --rollback
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

$listId = 20; // Đèn năng lượng mặt trời

$cardsBlock = array(
    'type' => 'cards',
    'title' => 'Chọn đèn đúng nhu cầu',
    'items' => array(
        array('photo' => 'jd-t200-05-6091.jpg', 'title' => 'Đường giao thông', 'desc' => 'Đảm bảo chiếu sáng an toàn, ổn định', 'link' => '?is_need=duong-giao-thong'),
        array('photo' => 'jd-t200-05-6091.jpg', 'title' => 'Sân nhà & sân vườn', 'desc' => 'Tạo không gian sáng đẹp, tiện nghi', 'link' => '?is_need=san-nha'),
        array('photo' => 'jd-t200-05-6091.jpg', 'title' => 'Khuôn viên & công trình', 'desc' => 'Giải pháp chiếu sáng linh hoạt, bền bỉ', 'link' => '?is_need=khuon-vien'),
    ),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$row = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = ? limit 1");
$row->execute(array($listId));
$existing = $row->fetch(PDO::FETCH_ASSOC);

if(!$existing)
{
    fwrite(STDERR, "Không tìm thấy content_section cho list id $listId - chạy migration 063 trước.\n");
    exit(1);
}

$decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
$decoded = is_array($decoded) ? $decoded : array();
$hasCardsAlready = false;
foreach($decoded as $block) if(is_array($block) && (($block['type'] ?? '') === 'cards')) { $hasCardsAlready = true; break; }

if(!$rollback && $hasCardsAlready)
{
    echo "SKIP: content_section id={$existing['id']} đã có khối cards, không ghi đè.\n";
    exit(0);
}
if($rollback && !$hasCardsAlready)
{
    echo "SKIP: content_section id={$existing['id']} hiện không có khối cards, không có gì để rollback.\n";
    exit(0);
}

if($rollback)
{
    $next = array();
    foreach($decoded as $block) if(!(is_array($block) && (($block['type'] ?? '') === 'cards'))) $next[] = $block;
}
else
{
    $next = array_merge($decoded, array($cardsBlock));
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    echo "  content_section id={$existing['id']}: sẽ có ".count($next)." khối (hiện có ".count($decoded).").\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$json = htmlspecialchars(json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
$pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?")->execute(array($json, time(), $existing['id']));
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong.\n";
