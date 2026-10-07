<?php
/**
 * Đồng bộ khối cards "Chọn đèn đúng nhu cầu" cho danh mục Đèn năng lượng
 * mặt trời (list id 20) khi production chỉ có content_section nhưng thiếu
 * block cards. Chỉ thêm block nếu chưa có; giữ nguyên mọi block hiện hữu.
 *
 * Mặc định chỉ preview:
 *   php migrations/071_restore_lighting_needs_cards.php
 *   php migrations/071_restore_lighting_needs_cards.php --apply
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

$cards = array(
    'type' => 'cards',
    'title' => 'Chọn đèn đúng nhu cầu',
    'items' => array(
        array('photo' => 'den-nlmt-duong-giao-thong.png', 'title' => 'Đường giao thông', 'desc' => 'Đảm bảo chiếu sáng an toàn, ổn định', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=duong-giao-thong'),
        array('photo' => 'den-nlmt-san-vuon.png', 'title' => 'Sân nhà & sân vườn', 'desc' => 'Tạo không gian sáng đẹp, tiện nghi', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=san-nha'),
        array('photo' => 'den-nlmt-cong-trinh.png', 'title' => 'Khuôn viên & công trình', 'desc' => 'Giải pháp chiếu sáng linh hoạt, bền bỉ', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=khuon-vien'),
    ),
);

$db = $config['database'];
$pdo = new PDO(
    'mysql:host='.$db['host'].';port='.(int)$db['port'].';dbname='.$db['dbname'].';charset=utf8mb4',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$stmt = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = ? limit 1");
$stmt->execute(array(20));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row) { fwrite(STDERR, "Không tìm thấy content_section cho list id=20.\n"); exit(1); }

$blocks = json_decode(htmlspecialchars_decode((string)$row['blocksvi'], ENT_QUOTES), true);
$blocks = is_array($blocks) ? $blocks : array();
foreach($blocks as $block)
{
    if(is_array($block) && (($block['type'] ?? '') === 'cards'))
    {
        echo "SKIP: content_section id={$row['id']} đã có block cards.\n";
        exit(0);
    }
}

$next = array_merge($blocks, array($cards));
echo "PREVIEW: content_section id={$row['id']} sẽ thêm 1 block cards, giữ nguyên ".count($blocks)." block hiện hữu.\n";
if(!$apply) exit(0);

$json = htmlspecialchars(json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
$pdo->beginTransaction();
try
{
    $pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?")
        ->execute(array($json, time(), (int)$row['id']));
    $pdo->commit();
    echo "APPLY xong.\n";
}
catch(Throwable $e)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
