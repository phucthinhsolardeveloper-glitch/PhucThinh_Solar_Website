<?php
/**
 * Chuẩn hoá title/description trang chủ để Google không phải tự rút gọn tên
 * pháp lý viết hoa toàn bộ thành một title thương hiệu thiếu nhất quán.
 *
 * Mặc định chỉ preview:
 *   php migrations/046_normalize_homepage_brand_seo.php
 *   php migrations/046_normalize_homepage_brand_seo.php --apply
 *   php migrations/046_normalize_homepage_brand_seo.php --rollback
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

$oldTitle = 'CÔNG TY TNHH CÔNG NGHỆ - THƯƠNG MẠI VÀ DỊCH VỤ PHÚC THỊNH SOLAR';
$oldDescription = 'Phúc Thịnh Solar tự hào là ngọn cờ đầu trong lĩnh vực năng lượng mặt trời tại Việt Nam, ra đời với sứ mệnh cao cả &quot;Mang Lại Sự Phồn Thịnh Cho Khách Hàng&quot;. ';
$newTitle = 'Phúc Thịnh Solar | Thiết bị & giải pháp năng lượng mặt trời';
$newDescription = 'Phúc Thịnh Solar cung cấp thiết bị và giải pháp năng lượng mặt trời chính hãng cho gia đình, doanh nghiệp và công trình trên toàn quốc.';

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$query = $pdo->prepare("select id,titlevi,descriptionvi from `{$prefix}seo` where com=? and act=? and type=?");
$query->execute(array('setting', 'update', 'setting'));
$rows = $query->fetchAll(PDO::FETCH_ASSOC);
if(count($rows) !== 1)
{
    fwrite(STDERR, "Cần đúng một bản ghi SEO trang chủ; hiện có ".count($rows).".\n");
    exit(1);
}
$row = $rows[0];
$targetTitle = $rollback ? $oldTitle : $newTitle;
$targetDescription = $rollback ? $oldDescription : $newDescription;
$expectedTitle = $rollback ? $newTitle : $oldTitle;
$expectedDescription = $rollback ? $newDescription : $oldDescription;

if($row['titlevi'] === $targetTitle && $row['descriptionvi'] === $targetDescription)
{
    echo "SKIP: SEO trang chủ đã ở trạng thái đích.\n";
    exit(0);
}
if($row['titlevi'] !== $expectedTitle || $row['descriptionvi'] !== $expectedDescription)
{
    fwrite(STDERR, "Dữ liệu SEO hiện tại nằm ngoài trạng thái đã khảo sát; dừng để không ghi đè nội dung Admin.\n");
    if(!$apply && !$rollback)
    {
        fwrite(STDERR, "  title hiện tại: ".$row['titlevi']."\n");
        fwrite(STDERR, "  description hiện tại: ".$row['descriptionvi']."\n");
    }
    exit(1);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    echo "  title: {$row['titlevi']}\n      -> {$newTitle}\n";
    echo "  description: {$row['descriptionvi']}\n      -> {$newDescription}\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$update = $pdo->prepare("update `{$prefix}seo` set titlevi=?,descriptionvi=? where id=? and titlevi=? and descriptionvi=?");
$update->execute(array($targetTitle, $targetDescription, (int)$row['id'], $expectedTitle, $expectedDescription));
if($update->rowCount() !== 1)
{
    fwrite(STDERR, "Bản ghi đã thay đổi trong lúc chạy; không cập nhật.\n");
    exit(1);
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã chuẩn hoá SEO trang chủ.\n";
