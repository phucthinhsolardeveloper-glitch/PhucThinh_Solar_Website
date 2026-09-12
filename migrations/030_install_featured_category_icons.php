<?php
/**
 * Cài bộ icon đồng nhất cho danh mục sản phẩm nổi bật theo slug ổn định.
 *
 * Mặc định chỉ xem trước. Script chỉ điền danh mục đang trống hoặc phục hồi
 * file khi DB đã tham chiếu đúng tên; không ghi đè ảnh khác đã có.
 *
 * Migration lịch sử cho cây 12 danh mục trước migration 033. Bộ ảnh nguồn đã
 * được lưu ngoài repo; khi thật sự cần phục hồi phải truyền --assets rõ ràng.
 *
 *   php migrations/030_install_featured_category_icons.php --assets=/path/to/icons
 *   php migrations/030_install_featured_category_icons.php --assets=/path/to/icons --apply
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
set_exception_handler(function($exception) {
    fwrite(STDERR, "FAIL: Không thể hoàn tất kiểm tra icon; chưa xác nhận thay đổi nào thành công.\n");
    exit(1);
});

$apply = in_array('--apply', $argv, true);
$assetDir = '';
foreach($argv as $arg)
{
    if(strpos($arg, '--assets=') === 0) $assetDir = rtrim(substr($arg, 9), '/');
}
if($assetDir === '')
{
    fwrite(STDERR, "FAIL: Thiếu --assets=/path/to/icons; bộ icon nguồn không còn nằm trong repo.\n");
    exit(1);
}
$assetDirReal = realpath($assetDir);
if(!$assetDirReal || !is_dir($assetDirReal))
{
    fwrite(STDERR, "FAIL: Không tìm thấy thư mục icon nguồn: ".$assetDir."\n");
    exit(1);
}

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "FAIL: Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);

$icons = array(
    'den-nlmt' => array('01-den-nang-luong-mat-troi.png', 'featured-category-01-den-nang-luong-mat-troi.png'),
    'bien-tan-inverter' => array('02-bien-tan-inverter.png', 'featured-category-02-bien-tan-inverter.png'),
    'tam-pin-nang-luong-mat-troi' => array('03-tam-pin-nang-luong-mat-troi.png', 'featured-category-03-tam-pin-nang-luong-mat-troi.png'),
    'tru-den' => array('04-tru-den.png', 'featured-category-04-tru-den.png'),
    'den-san-vuon' => array('05-den-san-vuon.png', 'featured-category-05-den-san-vuon.png'),
    'quat-nang-luong-mat-troi' => array('06-quat-nang-luong-mat-troi.png', 'featured-category-06-quat-nang-luong-mat-troi.png'),
    'bien-tan-bom' => array('07-bien-tan-bom.png', 'featured-category-07-bien-tan-bom.png'),
    'thiet-bi-dien-nang-luong-mat-troi' => array('08-thiet-bi-dien-nang-luong-mat-troi.png', 'featured-category-08-thiet-bi-dien-nang-luong-mat-troi.png'),
    'he-thong-khung-nhom' => array('09-he-thong-khung-nhom.png', 'featured-category-09-he-thong-khung-nhom.png'),
    'he-thong-dien' => array('10-he-thong-dien.png', 'featured-category-10-he-thong-dien.png'),
    'pin-luu-tru' => array('11-pin-luu-tru.png', 'featured-category-11-pin-luu-tru.png'),
    'den-ac' => array('12-den-ac.png', 'featured-category-12-den-ac.png')
);

$uploadDir = $publicHtml.'/upload/product/';
if(!is_dir($uploadDir) || !is_writable($uploadDir))
{
    fwrite(STDERR, "FAIL: upload/product không tồn tại hoặc không ghi được.\n");
    exit(1);
}

$plan = array();
$errors = array();
$preserved = 0;
$already = 0;
foreach($icons as $slug => $spec)
{
    $source = $assetDirReal.'/'.$spec[0];
    $targetName = $spec[1];
    $sourceReal = realpath($source);
    $mime = $sourceReal && is_file($sourceReal) ? (new finfo(FILEINFO_MIME_TYPE))->file($sourceReal) : '';
    $size = $sourceReal && is_file($sourceReal) ? filesize($sourceReal) : 0;
    $image = $sourceReal && is_file($sourceReal) ? @getimagesize($sourceReal) : false;
    if(!$sourceReal || strpos($sourceReal, $assetDirReal.DIRECTORY_SEPARATOR) !== 0 || $mime !== 'image/png'
        || !$image || $size < 1 || $size > 5 * 1024 * 1024 || $image[0] > 2000 || $image[1] > 2000)
    {
        $errors[] = $slug.': icon nguồn không hợp lệ.';
        continue;
    }

    $rows = $d->rawQuery(
        'select id, namevi, photo, date_updated from #_product_list where type = ? and slugvi = ?',
        array('san-pham', $slug)
    );
    if(count($rows) !== 1)
    {
        $errors[] = $slug.': khớp '.count($rows).' danh mục, mong đợi đúng 1.';
        continue;
    }
    $row = $rows[0];
    $currentPhoto = trim((string)$row['photo']);
    if($currentPhoto !== '' && $currentPhoto !== $targetName)
    {
        echo 'PRESERVE: '.$slug.' đang có ảnh khác; không ghi đè '.$currentPhoto."\n";
        $preserved++;
        continue;
    }

    $target = $uploadDir.$targetName;
    if(is_file($target) && !hash_equals(hash_file('sha256', $sourceReal), hash_file('sha256', $target)))
    {
        $errors[] = $slug.': file đích '.$targetName.' đã tồn tại nhưng khác nội dung.';
        continue;
    }
    if($currentPhoto === $targetName && is_file($target))
    {
        $already++;
        continue;
    }
    $plan[] = array(
        'id' => (int)$row['id'], 'slug' => $slug, 'name' => $row['namevi'],
        'current' => $currentPhoto, 'date_updated' => (int)$row['date_updated'],
        'source' => $sourceReal, 'target' => $target,
        'target_name' => $targetName, 'copy' => !is_file($target)
    );
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n" : "CHẾ ĐỘ XEM TRƯỚC\n";
echo 'Đã đúng: '.$already.' | sẽ cài/khôi phục: '.count($plan).' | giữ ảnh khác: '.$preserved."\n";
foreach($plan as $item) echo '  '.$item['slug'].' -> '.$item['target_name'].($item['copy'] ? ' [copy + DB]' : ' [DB]')."\n";
if(!empty($errors))
{
    foreach($errors as $error) echo 'ERROR: '.$error."\n";
    exit(1);
}
if(!$apply)
{
    echo "DRY-RUN OK: chưa thay đổi file hoặc database.\n";
    exit(0);
}

$createdFiles = array();
$changedRows = array();
try
{
    foreach($plan as $item)
    {
        if($item['copy'])
        {
            $temporary = $item['target'].'.tmp-'.bin2hex(random_bytes(5));
            if(!copy($item['source'], $temporary)) throw new RuntimeException('Không copy được icon '.$item['slug']);
            @chmod($temporary, 0644);
            if(!rename($temporary, $item['target']))
            {
                @unlink($temporary);
                throw new RuntimeException('Không đưa được icon vào upload '.$item['slug']);
            }
            $createdFiles[] = $item['target'];
        }

        $changedRows[] = array('id' => $item['id'], 'photo' => $item['current'], 'date_updated' => $item['date_updated']);
        $d->where('id', $item['id']);
        $d->where('type', 'san-pham');
        $d->where('photo', $item['current']);
        if(!$d->update('product_list', array('photo' => $item['target_name'], 'date_updated' => time())))
            throw new RuntimeException('Không cập nhật được DB cho '.$item['slug']);
        $verified = $d->rawQueryOne(
            'select photo from #_product_list where id = ? and type = ? and slugvi = ? limit 0,1',
            array($item['id'], 'san-pham', $item['slug'])
        );
        if(empty($verified) || (string)$verified['photo'] !== $item['target_name'])
            throw new RuntimeException('Không xác minh được DB sau cập nhật '.$item['slug']);
    }
    $cache->delete();
    echo 'APPLY OK: đã cài/khôi phục '.count($plan)." icon; không ghi đè ảnh danh mục khác.\n";
}
catch(Exception $e)
{
    foreach(array_reverse($changedRows) as $row)
        $d->rawQuery('update #_product_list set photo = ?, date_updated = ? where id = ?', array($row['photo'], $row['date_updated'], $row['id']));
    foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
    fwrite(STDERR, 'ROLLBACK: '.$e->getMessage()."\n");
    exit(1);
}
