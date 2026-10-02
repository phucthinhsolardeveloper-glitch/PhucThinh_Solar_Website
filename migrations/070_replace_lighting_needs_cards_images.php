<?php
/**
 * Thay ảnh dùng tạm (1 ảnh sản phẩm thật lặp lại 3 lần) ở khối "Chọn đèn
 * đúng nhu cầu" (list id=20, Đèn năng lượng mặt trời) bằng 3 ảnh minh họa
 * riêng cho từng thẻ. Phát hiện 2026-10-02: sếp chỉ ra khối này vẫn còn dùng
 * ảnh cũ dù đã nâng cấp 7 danh mục khác sang ảnh riêng từng thẻ ở migration
 * 069 - khối Đèn NLMT có từ trước phiên làm việc này nên bị bỏ sót khỏi
 * danh sách 23 ảnh ban đầu.
 *
 * Giữ nguyên title/desc/link hiện có (không đổi nội dung), chỉ thay photo.
 *
 * Mặc định chỉ preview:
 *   php migrations/070_replace_lighting_needs_cards_images.php
 *   php migrations/070_replace_lighting_needs_cards_images.php --apply
 *   php migrations/070_replace_lighting_needs_cards_images.php --rollback
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

$uploadDir = rtrim($publicHtml, '/').'/'.UPLOAD_PRODUCT_L;
$oldPhoto = 'jd-t200-05-6091.jpg';

/* Giữ nguyên title/desc/link gốc, chỉ đổi photo */
$newPhotos = array(
    'Đường giao thông' => 'den-nlmt-duong-giao-thong.png',
    'Sân nhà & sân vườn' => 'den-nlmt-san-vuon.png',
    'Khuôn viên & công trình' => 'den-nlmt-cong-trinh.png',
);

$missing = array();
foreach($newPhotos as $photo) if(!is_file($uploadDir.$photo)) $missing[] = $photo;
if(!$rollback && !empty($missing))
{
    echo "Chưa đủ ảnh, còn thiếu: ".implode(', ', $missing)."\n";
    exit(0);
}

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$stmt = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = 20 limit 1");
$stmt->execute();
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$existing)
{
    fwrite(STDERR, "Không tìm thấy content_section cho list id=20.\n");
    exit(1);
}

$decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
$decoded = is_array($decoded) ? $decoded : array();

$changed = false;
foreach($decoded as &$block)
{
    if(!is_array($block) || (($block['type'] ?? '') !== 'cards')) continue;
    foreach($block['items'] as &$item)
    {
        if($rollback)
        {
            if(isset($newPhotos[$item['title']]) && $item['photo'] === $newPhotos[$item['title']])
            {
                $item['photo'] = $oldPhoto;
                $changed = true;
            }
        }
        else
        {
            if(isset($newPhotos[$item['title']]) && $item['photo'] !== $newPhotos[$item['title']])
            {
                $item['photo'] = $newPhotos[$item['title']];
                $changed = true;
            }
        }
    }
    unset($item);
}
unset($block);

if(!$changed)
{
    echo "Không có gì để ".($rollback ? 'rollback' : 'cập nhật').".\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì. content_section id={$existing['id']} sẽ được cập nhật.\n";
    foreach($decoded as $block) if(($block['type'] ?? '') === 'cards') echo json_encode($block, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$json = htmlspecialchars(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
$pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?")
    ->execute(array($json, time(), $existing['id']));
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong.\n";
