<?php
/**
 * Thay ảnh đại diện 4 bài dự án bằng bản retouch từ chính ảnh công trình thật.
 *
 * Mặc định chỉ preview:
 *   php migrations/062_refresh_project_cover_images.php
 *   php migrations/062_refresh_project_cover_images.php --apply
 *   php migrations/062_refresh_project_cover_images.php --rollback
 *
 * Bản rollback chỉ phục hồi khi trường photo/options vẫn đang trỏ tới bản mới,
 * tránh ghi đè nếu Admin đã chọn ảnh khác trong lúc chờ triển khai.
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

/* id => [ảnh cũ, options cũ, ảnh mới, options mới] — giữ slug và nội dung. */
$plan = array(
    73 => array('010-8474.png', '{"p":"010-8474.png","w":1280,"h":854,"m":"image/png"}', 'du-an-thu-hien-enhanced-20261002.png'),
    74 => array('1-1-1-7215.png', '{"p":"1-1-1-7215.png","w":956,"h":1276,"m":"image/png"}', 'du-an-thu-hong-enhanced-20261002.png'),
    75 => array('anh-quan-gl-4-9449.jpg', '{"p":"anh-quan-gl-4-9449.jpg","w":1280,"h":960,"m":"image/jpeg"}', 'du-an-anh-quan-enhanced-20261002.png'),
    124 => array('anh-hoa-thanh-an-2-8612.jpg', '{"p":"anh-hoa-thanh-an-2-8612.jpg","w":1148,"h":861,"m":"image/jpeg"}', 'du-an-hoa-thanh-an-enhanced-20261002.png'),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$select = $pdo->prepare("select id, photo, options, namevi from `{$prefix}news` where id = ? and type = 'du-an' limit 1");
$update = $pdo->prepare("update `{$prefix}news` set photo = ?, options = ?, date_updated = ? where id = ? and type = 'du-an' and photo = ?");
$rollbackUpdate = $pdo->prepare("update `{$prefix}news` set photo = ?, options = ?, date_updated = ? where id = ? and type = 'du-an' and photo = ?");

$total = 0;
foreach($plan as $id => $pair)
{
    $oldPhoto = $pair[0];
    $oldOptions = $pair[1];
    $newPhoto = $pair[2];
    $select->execute(array($id));
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if(!$row)
    {
        fwrite(STDERR, "SKIP id=$id: không tìm thấy bài dự án.\n");
        continue;
    }

    $targetPhoto = $rollback ? $oldPhoto : $newPhoto;
    $expectedPhoto = $rollback ? $newPhoto : $oldPhoto;
    $targetOptions = $rollback ? $oldOptions : json_encode(array('p' => $targetPhoto, 'w' => 1448, 'h' => 1086, 'm' => 'image/png'), JSON_UNESCAPED_SLASHES);

    if((string)$row['photo'] !== $expectedPhoto)
    {
        echo "SKIP id=$id — ảnh hiện tại là ".((string)$row['photo'] !== '' ? $row['photo'] : '(trống)')."; không phải $expectedPhoto.\n";
        continue;
    }

    echo ($rollback ? 'ROLLBACK' : ($apply ? 'APPLY' : 'PREVIEW'))." id=$id ".(string)$row['namevi']."\n";
    echo "  ".$expectedPhoto." -> ".$targetPhoto."\n";
    if(!$apply && !$rollback) continue;

    $now = time();
    ($rollback ? $rollbackUpdate : $update)->execute(array($targetPhoto, $targetOptions, $now, $id, $expectedPhoto));
    if(($rollback ? $rollbackUpdate : $update)->rowCount() !== 1)
    {
        fwrite(STDERR, "SKIP id=$id: bản ghi đã thay đổi trong lúc chạy.\n");
        continue;
    }
    $total++;
}

if(!$apply && !$rollback) echo "Preview xong: chạy lại với --apply để cập nhật.\n";
else echo "Hoàn tất: $total bản ghi đã ".($rollback ? 'rollback' : 'cập nhật').".\n";
