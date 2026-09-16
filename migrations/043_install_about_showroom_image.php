<?php
/**
 * Cài ảnh showroom thật cho phần giới thiệu Phúc Thịnh Solar.
 *
 * Ảnh gốc trong upload luôn được giữ lại để làm fallback. Hai biến thể WebP
 * được đóng gói cùng migration vì thư mục upload là dữ liệu runtime và không
 * nằm trong gói release mã nguồn.
 *
 * Mặc định chỉ xem trước:
 *   php migrations/043_install_about_showroom_image.php
 *   php migrations/043_install_about_showroom_image.php --apply
 *   php migrations/043_install_about_showroom_image.php --rollback
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
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();

$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

$recordId = 0;
$recordType = 'lich-su';
$oldPhoto = '2354937183308875654238531382722883947765860n-6638.png';
$newPhoto = 'about-phuc-thinh-showroom-20260916.jpg';
$assetDir = __DIR__.'/assets/043-about-showroom';
$uploadDir = $publicHtml.'/upload/news';
$assets = array(
    $newPhoto => array('mime' => 'image/jpeg', 'width' => 1920, 'height' => 1020),
    $newPhoto.'.webp' => array('mime' => 'image/webp', 'width' => 1920, 'height' => 1020),
    'about-phuc-thinh-showroom-20260916-960.webp' => array('mime' => 'image/webp', 'width' => 960, 'height' => 510)
);

function aboutShowroomValidateImage($path, array $spec)
{
    if(!is_file($path) || filesize($path) < 1024 || filesize($path) > 8 * 1024 * 1024) return false;
    $image = @getimagesize($path);
    if(!$image || (int)$image[0] !== $spec['width'] || (int)$image[1] !== $spec['height']) return false;
    $mime = isset($image['mime']) ? $image['mime'] : '';
    if($mime !== $spec['mime']) return false;
    if($mime === 'image/webp')
    {
        $header = @file_get_contents($path, false, null, 0, 12);
        if(strlen((string)$header) !== 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP') return false;
    }
    return true;
}

foreach($assets as $filename => $spec)
{
    if(!aboutShowroomValidateImage($assetDir.'/'.$filename, $spec))
    {
        fwrite(STDERR, 'Asset không hợp lệ: '.$filename."\n");
        exit(1);
    }
}
if(!is_dir($uploadDir))
{
    fwrite(STDERR, "Không tìm thấy upload/news.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$locate = $pdo->prepare("select id, type, photo from `{$prefix}news` where type = ? and photo in (?, ?) order by id");
$locate->execute(array($recordType, $oldPhoto, $newPhoto));
$matches = $locate->fetchAll(PDO::FETCH_ASSOC);
if(count($matches) !== 1)
{
    fwrite(STDERR, "Không xác định duy nhất bản ghi Giới thiệu theo type và tên ảnh; dừng để không cập nhật nhầm.\n");
    exit(1);
}
$row = $matches[0];
$recordId = (int)$row['id'];
$select = $pdo->prepare("select id, type, photo from `{$prefix}news` where id = ? and type = ? limit 1");
if($row['photo'] !== $oldPhoto && $row['photo'] !== $newPhoto)
{
    fwrite(STDERR, "Ảnh hiện tại đã khác ảnh cũ/ảnh mới dự kiến; dừng để không ghi đè dữ liệu biên tập.\n");
    exit(1);
}

if($rollback)
{
    if($row['photo'] === $oldPhoto)
    {
        echo "ROLLBACK: DB đã dùng ảnh cũ, không cần thay đổi. Các asset mới được giữ lại an toàn.\n";
        exit(0);
    }
    if(!is_file($uploadDir.'/'.$oldPhoto))
    {
        fwrite(STDERR, "Không thể rollback: file ảnh cũ không còn trong upload/news.\n");
        exit(1);
    }
    $update = $pdo->prepare("update `{$prefix}news` set photo = ? where id = ? and type = ? and photo = ?");
    $pdo->beginTransaction();
    try
    {
        $update->execute(array($oldPhoto, $recordId, $recordType, $newPhoto));
        if($update->rowCount() !== 1) throw new RuntimeException('Bản ghi đã thay đổi trong lúc rollback.');
        $pdo->commit();
    }
    catch(Throwable $exception)
    {
        if($pdo->inTransaction()) $pdo->rollBack();
        fwrite(STDERR, 'ROLLBACK FAIL: '.$exception->getMessage()."\n");
        exit(1);
    }
    try { (new Cache(null))->delete(); } catch(Throwable $exception) { fwrite(STDERR, "Cảnh báo: chưa xóa được cache.\n"); }
    echo "ROLLBACK OK: DB đã trở về ảnh cũ; không xóa file WebP/JPEG mới.\n";
    exit(0);
}

$copies = array();
foreach($assets as $filename => $spec)
{
    $source = $assetDir.'/'.$filename;
    $target = $uploadDir.'/'.$filename;
    if(is_file($target))
    {
        if(!aboutShowroomValidateImage($target, $spec) || hash_file('sha256', $target) !== hash_file('sha256', $source))
        {
            fwrite(STDERR, 'File đích đã tồn tại nhưng khác asset đóng gói: '.$filename."\n");
            exit(1);
        }
    }
    else $copies[] = array('source' => $source, 'target' => $target, 'name' => $filename, 'spec' => $spec);
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để thực hiện)\n";
echo 'Bản ghi: #'.$recordId.' / '.$recordType."\n";
echo 'Ảnh hiện tại: '.$row['photo']."\n";
echo 'Asset cần copy: '.count($copies).'/'.count($assets)."\n";
echo $row['photo'] === $newPhoto ? "DB đã trỏ tới ảnh mới.\n" : "DB sẽ đổi sang: ".$newPhoto."\n";
if(!$apply)
{
    echo "Chưa ghi file hoặc DB.\n";
    exit(0);
}

$createdFiles = array();
try
{
    foreach($copies as $copy)
    {
        $temporary = $copy['target'].'.tmp-'.bin2hex(random_bytes(5));
        if(!copy($copy['source'], $temporary)) throw new RuntimeException('Không copy được '.$copy['name']);
        @chmod($temporary, 0644);
        if(!aboutShowroomValidateImage($temporary, $copy['spec']) || hash_file('sha256', $temporary) !== hash_file('sha256', $copy['source']))
        {
            @unlink($temporary);
            throw new RuntimeException('Không xác minh được file tạm '.$copy['name']);
        }
        if(!rename($temporary, $copy['target']))
        {
            @unlink($temporary);
            throw new RuntimeException('Không đưa được asset vào upload '.$copy['name']);
        }
        $createdFiles[] = $copy['target'];
    }

    if($row['photo'] === $oldPhoto)
    {
        $update = $pdo->prepare("update `{$prefix}news` set photo = ? where id = ? and type = ? and photo = ?");
        $pdo->beginTransaction();
        $update->execute(array($newPhoto, $recordId, $recordType, $oldPhoto));
        if($update->rowCount() !== 1) throw new RuntimeException('Bản ghi đã thay đổi trong lúc cập nhật.');
        $pdo->commit();
    }
}
catch(Throwable $exception)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    foreach(array_reverse($createdFiles) as $file) if(is_file($file)) @unlink($file);
    fwrite(STDERR, 'APPLY FAIL: '.$exception->getMessage()."\nĐã rollback thay đổi của lần chạy này.\n");
    exit(1);
}

$select->execute(array($recordId, $recordType));
$verified = $select->fetch(PDO::FETCH_ASSOC);
if(!$verified || $verified['photo'] !== $newPhoto)
{
    fwrite(STDERR, "APPLY FAIL: không xác minh được DB sau cập nhật.\n");
    exit(1);
}
foreach($assets as $filename => $spec)
{
    if(!aboutShowroomValidateImage($uploadDir.'/'.$filename, $spec))
    {
        fwrite(STDERR, 'APPLY FAIL: không xác minh được '.$filename." sau cập nhật.\n");
        exit(1);
    }
}
try { (new Cache(null))->delete(); } catch(Throwable $exception) { fwrite(STDERR, "Cảnh báo: cập nhật thành công nhưng chưa xóa được cache.\n"); }
echo "APPLY OK: ảnh JPEG fallback và hai biến thể WebP đã sẵn sàng; DB trỏ đúng ảnh mới.\n";
