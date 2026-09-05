<?php

/**
 * Đối chiếu và bổ sung ảnh trong thư mục ĐÈN ORIANA vào dữ liệu local.
 *
 * Mặc định chỉ kiểm tra:
 *   php scripts/import-oriana-images-local.php /path/to/ĐÈN\ ORIANA
 *
 * Ghi dữ liệu local sau khi bản kiểm tra sạch:
 *   php scripts/import-oriana-images-local.php /path/to/ĐÈN\ ORIANA --commit
 */

if(PHP_SAPI !== 'cli') exit(1);
if(getenv('APP_ENV') !== 'local') {
    fwrite(STDERR, "Từ chối chạy ngoài APP_ENV=local.\n");
    exit(1);
}

$sourceRoot = isset($argv[1]) ? rtrim((string)$argv[1], DIRECTORY_SEPARATOR) : '';
$commit = in_array('--commit', $argv, true);
if($sourceRoot === '' || !is_dir($sourceRoot)) {
    fwrite(STDERR, "Không tìm thấy thư mục nguồn ĐÈN ORIANA.\n");
    exit(1);
}
$sourceRootReal = realpath($sourceRoot);

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
define('SOURCES', '/var/www/html/administrator/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
$type = 'san-pham';
require SOURCES.'product_bulk.php';

$assignments = array();

function orianaAdd(&$assignments, $source, $codes, $role = 'main', $order = 1)
{
    foreach((array)$codes as $code) {
        $assignments[] = array(
            'source' => $source,
            'code' => $code,
            'role' => $role,
            'order' => (int)$order
        );
    }
}

function orianaAddPrefix(&$assignments, $d, $type, $source, $prefix)
{
    $rows = $d->rawQuery('select code from #_product where type = ? and code like ? order by id', array($type, $prefix.' (%'));
    if(empty($rows)) throw new RuntimeException('Không tìm thấy biến thể sản phẩm cho mã '.$prefix);
    foreach($rows as $row) orianaAdd($assignments, $source, $row['code']);
}

/* Ảnh đại diện có mã model khớp trực tiếp. */
$mainImages = array(
    'DB60/DB60100.jpg' => 'DB60100',
    'DB60/DB60200.jpg' => 'DB60200',
    'DB60/DB60300.jpg' => 'DB60300',
    'DB60/DB60400.jpg' => 'DB60400',
    'DB7/DB 760.jpg' => 'DB-760',
    'DB7/DB 7100.jpg' => 'DB-7100',
    'DB7/DB 7200.jpg' => 'DB-7200',
    'DB7/DB7300.jpg' => 'DB-7300',
    'DB7/DB 7500.jpg' => 'DB-7500',
    'DB7/DB 71000.jpg' => 'DB-71000',
    'DB9/DB 9100.jpg' => 'DB-9100',
    'DB9/DB 9200.jpg' => 'DB-9200',
    'DB9/DB 9300.jpg' => 'DB-9300',
    'DB9/DB 9600.jpg' => 'DB-9600',
    'DB9/DB9800.jpg' => 'DB-9800',
    'DB9/DB 91000.jpg' => 'DB-91000',
    'DBA/DBA100.JPG' => 'DB-A100',
    'DBA/DBA200.JPG' => 'DB-A200',
    'DBA/DBA300.JPG' => 'DB-A300',
    'DBA/DBA500.JPG' => 'DB-A500',
    'DB8/DB 8100P.jpg' => 'DB-8100P',
    'DB8/DB 8100.jpg' => 'DB-8100',
    'DB8/DB 8150.jpg' => 'DB-8150',
    'DB8/DB 8200.JPG' => 'DB-8200',
    'DB8/DB 8300.jpg' => 'DB-8300',
    'DB8/DB8300S.jpg' => 'DB-8300-S',
    'DB8/DB 8500.jpg' => 'DB-8500',
    'DB8/DB 8500S.jpg' => 'DB-8500-S',
    'DB88/DB88-200PRO.jpg' => 'DB88-200PRO',
    'DB88/DB88-400PRO.jpg' => 'DB88-400PRO',
    'DB88/DB88-600PRO.jpg' => 'DB88-600PRO',
    'DB88/DB88-1000PRO.jpg' => 'DB88-1000PRO',
    'DB 3PRO/DB3PRO-200W.jpg' => 'DB3-PRO-200W',
    'DB 3PRO/DB3PRO-400W.jpg' => 'DB3-PRO-400W',
    'DB 3PRO/DB3PRO-800W.jpg' => 'DB3-PRO-800W',
    'DB 3PRO/DB3PRO-1000W.jpg' => 'DB3-PRO-1000W',
    'DB UFO/DB - UFO1000.jpg' => 'DB-UFO1000',
    'DB01-1-ECO/DB01-1-ECO.jpg' => 'DB01-ECO',
    'DB02/DB02-2.jpg' => 'DB02-2',
    'DB369-DB6300-DB6300S/DB 369-01.JPG' => 'DB369',
    'DB369-DB6300-DB6300S/DB6300.JPG' => 'DB6300',
    'DB369-DB6300-DB6300S/DB6300S.JPG' => 'DB6300S'
);
foreach($mainImages as $source => $code) orianaAdd($assignments, $source, $code);

/* Ảnh phụ theo đúng model. */
orianaAdd($assignments, 'DB8/DB 8500-01.jpg', 'DB-8500', 'gallery', 3);
orianaAdd($assignments, 'DB8/DB 8500S-01.jpg', 'DB-8500-S', 'gallery', 3);
$db8Codes = array('DB-8100P','DB-8100','DB-8150','DB-8200','DB-8300','DB-8300-S','DB-8500','DB-8500-S');
orianaAdd($assignments, 'DB8/PHỤ KIỆN LẮP ĐẶT.jpg', $db8Codes, 'gallery', 2);

$db88Codes = array('DB88-200PRO','DB88-400PRO','DB88-600PRO','DB88-1000PRO');
$db88Gallery = array(
    'DB88/z7091059229196_176dd3c4098e80c4f4dbf7b41a7fe9b8.jpg' => 7,
    'DB88/z7091059344038_6273dbb3f479df46b175463c3744cc1c.jpg' => 6,
    'DB88/z7091059388090_9d072d5affa1e0764df3955c8fa56333.jpg' => 5,
    'DB88/z7091059440492_be0279360d3089af830f4ff2e71ed793.jpg' => 4,
    'DB88/z7091059658796_3e98bf04a0d3f746dee4f5ddfd174756.jpg' => 3,
    'DB88/z7091059811691_e42d66c30982fefa5afcf5043daff73a.jpg' => 2
);
foreach($db88Gallery as $source => $order) orianaAdd($assignments, $source, $db88Codes, 'gallery', $order);

$db3Codes = array('DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W');
orianaAdd($assignments, 'DB 3PRO/z7038079241313_8da9da61cc64733711011670eafad5b2.jpg', $db3Codes, 'gallery', 2);
orianaAdd($assignments, 'DB 3PRO/DB3PRO-1000W - 01.jpg', 'DB3-PRO-1000W', 'gallery', 3);

orianaAdd($assignments, 'DB UFO/DB-UFO1000-01.jpg', 'DB-UFO1000', 'gallery', 5);
orianaAdd($assignments, 'DB UFO/DB-UFO1000-02.jpg', 'DB-UFO1000', 'gallery', 4);
orianaAdd($assignments, 'DB UFO/DB-UFO1000-03.jpg', 'DB-UFO1000', 'gallery', 3);
orianaAdd($assignments, 'DB UFO/DB-UFO1000-04.jpg', 'DB-UFO1000', 'gallery', 2);

orianaAdd($assignments, 'DB01-1/DB01-1.jpg', 'DB01-1', 'gallery', 6);
orianaAdd($assignments, 'DB01-1/DB01-1-04.jpg', 'DB01-1', 'gallery', 5);
orianaAdd($assignments, 'DB01-1/DB01-1-02.jpg', 'DB01-1', 'gallery', 4);
orianaAdd($assignments, 'DB01-1/DB01-1-03.jpg', 'DB01-1', 'gallery', 3);
orianaAdd($assignments, 'DB01-1/DB01-1-01.jpg', 'DB01-1', 'gallery', 2);

orianaAdd($assignments, 'DB02/DB 02-2-01.jpg', 'DB02-2', 'gallery', 3);
orianaAdd($assignments, 'DB02/DB02-2-02.jpg', 'DB02-2', 'gallery', 2);

orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB 369.jpg', 'DB369', 'gallery', 2);
orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB 6300.jpg', 'DB6300', 'gallery', 4);
orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB6300-01.jpg', 'DB6300', 'gallery', 3);
orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB6300-02.jpg', 'DB6300', 'gallery', 2);
orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB 6300S.jpg', 'DB6300S', 'gallery', 3);
orianaAdd($assignments, 'DB369-DB6300-DB6300S/DB6300S-01.jpg', 'DB6300S', 'gallery', 2);

/* Mỗi ảnh trụ cổng mô tả mọi kích thước của cùng model. */
$postModels = array(
    'PL-0109','PL-0110','PL-0111','PL-0112','PL-0113','PL-0205','PL-0209','PL-0210','PL-0211','PL-0213',
    'PL-0301','PL-0304','PL-0305','PL-0306','PL-0308','PL-0309','PL-0310','PL-0311','PL-0312','PL-0313',
    'WL-0401','WL-0402','WL-0407','WL-0410','WL-0411','WL-0412'
);
foreach($postModels as $model) orianaAddPrefix($assignments, $d, $type, 'ĐÈN TRỤ CỔNG/'.$model.'.jpg', $model);
/* Tên ảnh gốc dùng PL nhưng mã sản phẩm hiện tại dùng SL. */
orianaAddPrefix($assignments, $d, $type, 'ĐÈN TRỤ CỔNG/PL-0506.jpg', 'SL-0506');
orianaAddPrefix($assignments, $d, $type, 'ĐÈN TRỤ CỔNG/PL-0507.jpg', 'SL-0507');

function orianaValidateSource($path, $sourceRootReal)
{
    $real = realpath($path);
    if(!$real || strpos($real, $sourceRootReal.DIRECTORY_SEPARATOR) !== 0 || !is_file($real) || is_link($path)) return 'Đường dẫn nguồn không an toàn.';
    $extension = strtolower(pathinfo($real, PATHINFO_EXTENSION));
    $allowed = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp');
    if(!isset($allowed[$extension])) return 'Định dạng không được hỗ trợ.';
    $size = filesize($real);
    if($size <= 0 || $size > 15 * 1024 * 1024) return 'Dung lượng ảnh nguồn không hợp lệ.';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($real);
    $image = @getimagesize($real);
    if($mime !== $allowed[$extension] || !$image || $image[0] < 1 || $image[1] < 1 || $image[0] > 12000 || $image[1] > 12000 || $image[0] * $image[1] > 24000000) return 'MIME hoặc kích thước ảnh không hợp lệ.';
    return '';
}

$seenTarget = array();
$sourceHashes = array();
$mappedHashes = array();
$resolved = array();
$errors = array();
$counts = array('main'=>0, 'gallery'=>0, 'already'=>0);

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRootReal, FilesystemIterator::SKIP_DOTS)) as $file) {
    if(!$file->isFile() || !preg_match('/\.(jpe?g|png|webp)$/i', $file->getFilename())) continue;
    $hash = hash_file('sha256', $file->getPathname());
    $sourceHashes[$hash] = true;
}

foreach($assignments as $assignment) {
    $path = $sourceRootReal.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $assignment['source']);
    $validationError = orianaValidateSource($path, $sourceRootReal);
    if($validationError !== '') {
        $errors[] = $assignment['source'].': '.$validationError;
        continue;
    }
    $mappedHashes[hash_file('sha256', $path)] = true;
    $products = $d->rawQuery('select id,code,namevi,photo,date_updated from #_product where type = ? and code = ?', array($type, $assignment['code']));
    if(count($products) !== 1) {
        $errors[] = $assignment['source'].': mã '.$assignment['code'].' khớp '.count($products).' sản phẩm.';
        continue;
    }
    $product = $products[0];
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if($extension === 'jpeg') $extension = 'jpg';
    $filename = 'oriana-product-'.(int)$product['id'].'-'.str_pad((string)$assignment['order'], 2, '0', STR_PAD_LEFT).'.'.$extension;
    $targetKey = (int)$product['id'].'|'.$assignment['role'].'|'.$assignment['order'];
    if(isset($seenTarget[$targetKey])) {
        $errors[] = 'Mapping trùng cho '.$product['code'].' vai trò '.$assignment['role'].' thứ tự '.$assignment['order'].'.';
        continue;
    }
    $seenTarget[$targetKey] = true;
    $already = false;
    if($assignment['role'] === 'main') {
        if(trim((string)$product['photo']) === $filename) $already = true;
        elseif(trim((string)$product['photo']) !== '') $errors[] = 'Sản phẩm '.$product['code'].' đã có ảnh đại diện khác: '.$product['photo'];
    } else {
        $gallery = $d->rawQueryOne('select id from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? and photo = ? limit 0,1', array((int)$product['id'], 'product', $type, 'man', $type, $filename));
        $already = !empty($gallery);
    }
    $targetPath = productBulkImageUploadDirectory().$filename;
    if($already && !is_file($targetPath)) $errors[] = 'DB đã tham chiếu nhưng thiếu file '.$filename.'.';
    if(!$already && is_file($targetPath)) $errors[] = 'File đích đã tồn tại nhưng DB chưa tham chiếu: '.$filename.'.';
    $resolved[] = array_merge($assignment, array('path'=>$path, 'product'=>$product, 'filename'=>$filename, 'target'=>$targetPath, 'extension'=>$extension, 'already'=>$already));
    $counts[$already ? 'already' : $assignment['role']]++;
}

$uncovered = array_diff_key($sourceHashes, $mappedHashes);
if(!empty($uncovered)) $errors[] = 'Còn '.count($uncovered).' nội dung ảnh duy nhất chưa được đối chiếu.';

echo 'Ảnh nguồn: '.count($sourceHashes).' nội dung duy nhất; mapping: '.count($assignments)." lượt gán.\n";
echo 'Sẵn sàng: '.$counts['main'].' ảnh đại diện, '.$counts['gallery'].' ảnh phụ; đã có: '.$counts['already'].".\n";
if(!empty($errors)) {
    foreach($errors as $error) echo 'ERROR: '.$error."\n";
    exit(1);
}
if(!$commit) {
    echo "DRY-RUN OK: chưa thay đổi file hoặc database.\n";
    exit(0);
}

$preparedDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-oriana-prepared-'.bin2hex(random_bytes(8));
if(!mkdir($preparedDir, 0700)) throw new RuntimeException('Không tạo được thư mục chuẩn hóa tạm.');
$prepared = array();
$createdFiles = array();
$committed = false;
register_shutdown_function(function() use (&$committed, &$createdFiles, $preparedDir) {
    if(!$committed) foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
    if(is_dir($preparedDir)) {
        foreach(scandir($preparedDir) as $name) if($name !== '.' && $name !== '..' && is_file($preparedDir.DIRECTORY_SEPARATOR.$name)) @unlink($preparedDir.DIRECTORY_SEPARATOR.$name);
        @rmdir($preparedDir);
    }
});

foreach($resolved as $row) {
    if($row['already']) continue;
    $hash = hash_file('sha256', $row['path']);
    if(isset($prepared[$hash])) continue;
    $preparedPath = $preparedDir.DIRECTORY_SEPARATOR.$hash.'.'.$row['extension'];
    if(!copy($row['path'], $preparedPath)) throw new RuntimeException('Không sao chép được ảnh nguồn '.$row['source']);
    if(!productBulkImageFinalizeFile($preparedPath, $row['extension'])) throw new RuntimeException('Không chuẩn hóa được ảnh '.$row['source']);
    $finalError = productBulkImageValidateFile($preparedPath, $row['extension']);
    if($finalError !== '') throw new RuntimeException('Ảnh sau chuẩn hóa không hợp lệ '.$row['source'].': '.$finalError);
    $prepared[$hash] = $preparedPath;
}

$d->startTransaction();
try {
    foreach($resolved as $row) {
        if($row['already']) continue;
        $preparedPath = $prepared[hash_file('sha256', $row['path'])];
        if(!copy($preparedPath, $row['target'])) throw new RuntimeException('Không lưu được '.$row['filename']);
        @chmod($row['target'], 0644);
        $createdFiles[] = $row['target'];
        if($row['role'] === 'main') {
            $current = $d->rawQueryOne('select photo,date_updated from #_product where id = ? and type = ? limit 0,1', array((int)$row['product']['id'], $type));
            if(empty($current) || trim((string)$current['photo']) !== '' || (int)$current['date_updated'] !== (int)$row['product']['date_updated']) throw new RuntimeException('Sản phẩm '.$row['code'].' đã thay đổi sau bước kiểm tra.');
            $d->where('id', (int)$row['product']['id']);
            $d->where('type', $type);
            $d->where('photo', '');
            if(!$d->update('product', array('photo'=>$row['filename'], 'date_updated'=>time()))) throw new RuntimeException('Không cập nhật được ảnh đại diện '.$row['code']);
        } else {
            if(!$d->insert('gallery', array(
                'id_parent'=>(int)$row['product']['id'], 'photo'=>$row['filename'], 'namevi'=>'', 'numb'=>(int)$row['order'],
                'type'=>$type, 'com'=>'product', 'kind'=>'man', 'val'=>$type, 'status'=>'hienthi', 'date_created'=>time(), 'date_updated'=>0
            ))) throw new RuntimeException('Không thêm được ảnh phụ '.$row['code']);
        }
    }
    $d->commit();
    $committed = true;
    $cache->delete();
    echo 'COMMIT OK: đã thêm '.$counts['main'].' ảnh đại diện và '.$counts['gallery']." ảnh phụ.\n";
} catch(Exception $e) {
    $d->rollback();
    foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
    $createdFiles = array();
    fwrite(STDERR, 'ROLLBACK: '.$e->getMessage()."\n");
    exit(1);
}

