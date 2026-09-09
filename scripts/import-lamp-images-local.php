<?php

/**
 * Đối chiếu và bổ sung ảnh đèn JINDIAN/Blue Carbon (đèn pha + đèn đường) theo
 * MÃ SẢN PHẨM, cùng khuôn an toàn với scripts/import-oriana-images-local.php:
 * chỉ ghi khi khớp đúng 1 sản phẩm theo code, không đè ảnh đại diện đang có
 * (tự chuyển sang ảnh phụ), transaction có rollback, bắt buộc dry-run trước.
 *
 * Chạy (từ thư mục gốc repo):
 *   php scripts/import-lamp-images-local.php "/path/to/DaChuanHoa"            # xem trước
 *   php scripts/import-lamp-images-local.php "/path/to/DaChuanHoa" --apply    # ghi local
 *
 * Production bắt buộc dry-run trước, backup DB/upload và xác nhận riêng:
 *   php scripts/import-lamp-images-local.php "/path" --public-html=/home/USER/domains/phucthinhsolar.com/public_html
 *   php scripts/import-lamp-images-local.php "/path" --public-html=... --apply --confirm-production=IMPORT-LAMP
 *
 * Tên file đích: import-product-<id>.<ext> (ảnh đại diện) hoặc
 * import-product-<id>-<NN>.<ext> (ảnh phụ, NN tự tính tiếp theo numb lớn
 * nhất đang có trong gallery của đúng sản phẩm đó, không hardcode).
 */

if(PHP_SAPI !== 'cli') exit(1);
set_exception_handler(function($exception) {
    fwrite(STDERR, "FAIL: Importer dừng do lỗi không xử lý; kiểm tra log server và chạy lại dry-run.\n");
    exit(1);
});

$sourceRoot = isset($argv[1]) ? rtrim((string)$argv[1], DIRECTORY_SEPARATOR) : '';
$commit = in_array('--apply', $argv, true) || in_array('--commit', $argv, true);
$isLocal = getenv('APP_ENV') === 'local';
$productionConfirmed = in_array('--confirm-production=IMPORT-LAMP', $argv, true);
$publicHtmlOverride = '';
foreach($argv as $arg) if(strpos($arg, '--public-html=') === 0) $publicHtmlOverride = rtrim(substr($arg, 14), '/');
if(!$isLocal && $commit && !$productionConfirmed) {
    fwrite(STDERR, "Từ chối ghi ngoài local: phải backup, dry-run rồi thêm --confirm-production=IMPORT-LAMP.\n");
    exit(1);
}
if($sourceRoot === '' || !is_dir($sourceRoot)) {
    fwrite(STDERR, "Không tìm thấy thư mục nguồn.\n");
    exit(1);
}
$sourceRootReal = realpath($sourceRoot);

$_SERVER['SERVER_NAME'] = 'localhost';
$publicHtml = $publicHtmlOverride !== '' ? $publicHtmlOverride : __DIR__.'/../domains/phucthinhsolar.com/public_html';
if($publicHtmlOverride === '' && !is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) {
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}
define('LIBRARIES', $publicHtml.'/libraries/');
define('SOURCES', $publicHtml.'/administrator/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
$type = 'san-pham';
require SOURCES.'product_bulk.php';

/* Mỗi mục: source (đường dẫn tương đối trong thư mục nguồn), code (mã sản
 * phẩm chính xác), role ('main' nếu sản phẩm chưa có ảnh đại diện, 'gallery'
 * nếu đã có - đối chiếu tay từng file với DB, không suy đoán những trường
 * hợp mơ hồ). */
$assignments = array(
	array('source' => 'Đèn Pha Blue Carbon/BCT - WW1.0.jpg', 'code' => 'BCT-WW1.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT - WW2.0.jpg', 'code' => 'BCT-WW2.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT - WW3.0.jpg', 'code' => 'BCT-WW3.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT - WW5.0 - MONO.jpg', 'code' => 'BCT-WW5.0-MONO', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WR2.0.jpg', 'code' => 'BCT-WR2.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WR3.0.jpg', 'code' => 'BCT-WR3.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WR5.0.jpg', 'code' => 'BCT-WR5.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WR6.0.jpg', 'code' => 'BCT-WR6.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WW4.0.jpg', 'code' => 'BCT-WW4.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WW5.0.jpg', 'code' => 'BCT-WW5.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT WW6.0-MONO.jpg', 'code' => 'BCT-WW6.0-MONO', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT-WW6.0.jpg', 'code' => 'BCT-WW6.0', 'role' => 'main'),
	array('source' => 'Đèn Pha Blue Carbon/BCT FLRP2.0.jpg', 'code' => 'BCT-FLRP2.0', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD V100.jpg', 'code' => 'JD-V100', 'role' => 'main'),
	array('source' => 'Đèn pha JINDIAN/JD V100-01.jpg', 'code' => 'JD-V100', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD V200.jpg', 'code' => 'JD-V200', 'role' => 'main'),
	array('source' => 'Đèn pha JINDIAN/JD V300.jpg', 'code' => 'JD-V300', 'role' => 'main'),
	array('source' => 'Đèn pha JINDIAN/JD V500.jpg', 'code' => 'JD-V500', 'role' => 'main'),
	array('source' => 'Đèn pha JINDIAN/JD V500-01.jpg', 'code' => 'JD-V500', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8200L.jpg', 'code' => 'JD-8200L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8200L-01.jpg', 'code' => 'JD-8200L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8200L-2.jpg', 'code' => 'JD-8200L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8300L.jpg', 'code' => 'JD-8300L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8500L.jpg', 'code' => 'JD-8500L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8800L.jpg', 'code' => 'JD-8800L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD 8860L.jpg', 'code' => 'JD-8860L', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD-T100.jpg', 'code' => 'JD-T100', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD-T200.jpg', 'code' => 'JD-T200', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD-T300.jpg', 'code' => 'JD-T300', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD T300-01.jpg', 'code' => 'JD-T300', 'role' => 'gallery'),
	array('source' => 'Đèn pha JINDIAN/JD T300-02.jpg', 'code' => 'JD-T300', 'role' => 'gallery'),
	array('source' => 'Đèn đường Blue Carbon/BCT - OLF - 140W.jpg', 'code' => 'BCT-OLF-140W', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT -OLMJ 1.0.jpg', 'code' => 'BCT-OLMJ1.0', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT OLK2.0P -M105.jpg', 'code' => 'BCT-OLK2.0P-M105', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT OLK2.0P -P120.jpg', 'code' => 'BCT-OLK2.0P-P120', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT OLK2.0P-M135.jpg', 'code' => 'BCT-OLK2.0P-M135', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLF100W.jpg', 'code' => 'BCT-OLF-100W', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLK3.0P- NEW.jpg', 'code' => 'BCT-OLK3.0P-NEW', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLK3.0P.jpg', 'code' => 'BCT-OLK3.0P', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT -OLK 2.0 36W.jpg', 'code' => 'BCT-OLK2.0', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT OLK2.0P NEW MONO.jpg', 'code' => 'BCT-OLK2.0P-NEW', 'role' => 'main'),
	array('source' => 'Đèn đường Blue Carbon/BCT OLK2.0PNEW MONO (LỰA CHỌN 2).jpg', 'code' => 'BCT-OLK2.0P-NEW', 'role' => 'gallery'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLF-80W.jpg', 'code' => 'BCT-OLF-80W', 'role' => 'gallery'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLJ 1.0.jpg', 'code' => 'BCT-OLJ1.0', 'role' => 'gallery'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLJ 2.0.jpg', 'code' => 'BCT-OLJ2.0', 'role' => 'gallery'),
	array('source' => 'Đèn đường Blue Carbon/BCT-OLK 3.0.jpg', 'code' => 'BCT-OLK3.0', 'role' => 'gallery'),
	array('source' => 'Đèn đường JINDIAN/JD E6300.jpg', 'code' => 'JDE-6300', 'role' => 'main'),
	array('source' => 'Đèn đường JINDIAN/JD E6500.jpg', 'code' => 'JDE-6500', 'role' => 'main'),
	array('source' => 'Đèn đường JINDIAN/JD L1000 NEW.jpg', 'code' => 'JD-L1000-NEW', 'role' => 'main'),
	array('source' => 'Đèn đường JINDIAN/JD L1000.jpg', 'code' => 'JD-L1000', 'role' => 'main'),
	array('source' => 'Đèn đường JINDIAN/JD 369.jpg', 'code' => 'JD-369-300W', 'role' => 'main'),
	array('source' => 'Đèn đường JINDIAN/JD L500..jpg', 'code' => 'JD-L500', 'role' => 'main'),
);

function lampValidateSource($path, $sourceRootReal)
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
$nextGalleryNumb = array(); // id_parent => số numb tiếp theo sẽ dùng trong lượt chạy này

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRootReal, FilesystemIterator::SKIP_DOTS)) as $file) {
    if(!$file->isFile() || !preg_match('/\.(jpe?g|png|webp)$/i', $file->getFilename())) continue;
    $hash = hash_file('sha256', $file->getPathname());
    $sourceHashes[$hash] = true;
}

foreach($assignments as $assignment) {
    $path = $sourceRootReal.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $assignment['source']);
    $validationError = lampValidateSource($path, $sourceRootReal);
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
    $productId = (int)$product['id'];

    if($assignment['role'] === 'main') {
        $filename = 'import-product-'.$productId.'.'.$extension;
        $targetKey = $productId.'|main|0';
    } else {
        if(!isset($nextGalleryNumb[$productId])) {
            $maxNumb = $d->rawQueryOne('select max(numb) as m from #_gallery where id_parent = ? and com = ? and kind = ?', array($productId, 'product', 'man'));
            $nextGalleryNumb[$productId] = !empty($maxNumb['m']) ? (int)$maxNumb['m'] + 1 : 1;
        }
        $order = $nextGalleryNumb[$productId]++;
        $filename = 'import-product-'.$productId.'-'.str_pad((string)$order, 2, '0', STR_PAD_LEFT).'.'.$extension;
        $targetKey = $productId.'|gallery|'.$order;
        $assignment['order'] = $order;
    }
    if(isset($seenTarget[$targetKey])) {
        $errors[] = 'Mapping trùng cho '.$product['code'].' vai trò '.$assignment['role'].'.';
        continue;
    }
    $seenTarget[$targetKey] = true;
    $already = false;
    if($assignment['role'] === 'main') {
        if(trim((string)$product['photo']) === $filename) $already = true;
        elseif(trim((string)$product['photo']) !== '') $errors[] = 'Sản phẩm '.$product['code'].' đã có ảnh đại diện khác: '.$product['photo'].' - kiểm tra lại vai trò (nên là gallery).';
    } else {
        $gallery = $d->rawQueryOne('select id from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? and photo = ? limit 0,1', array($productId, 'product', $type, 'man', $type, $filename));
        $already = !empty($gallery);
    }
    $targetPath = productBulkImageUploadDirectory().$filename;
    if($already && !is_file($targetPath)) $errors[] = 'DB đã tham chiếu nhưng thiếu file '.$filename.'.';
    if(!$already && is_file($targetPath)) $errors[] = 'File đích đã tồn tại nhưng DB chưa tham chiếu: '.$filename.'.';
    $resolved[] = array_merge($assignment, array('path'=>$path, 'product'=>$product, 'filename'=>$filename, 'target'=>$targetPath, 'extension'=>$extension, 'already'=>$already));
    $counts[$already ? 'already' : $assignment['role']]++;
}

$uncovered = array_diff_key($sourceHashes, $mappedHashes);
if(!empty($uncovered)) $errors[] = 'Còn '.count($uncovered).' nội dung ảnh duy nhất trong thư mục nguồn chưa được đối chiếu trong mapping (không nằm trong phạm vi lượt chạy này).';

echo 'Ảnh nguồn: '.count($sourceHashes).' nội dung duy nhất; mapping: '.count($assignments)." lượt gán.\n";
echo 'Sẵn sàng: '.$counts['main'].' ảnh đại diện, '.$counts['gallery'].' ảnh phụ; đã có: '.$counts['already'].".\n";
if(!empty($errors)) {
    foreach($errors as $error) echo 'ERROR: '.$error."\n";
    exit(1);
}

echo "\nChi tiết:\n";
foreach($resolved as $row) {
    if($row['already']) continue;
    printf("  #%-4d %-20s %-8s %s -> %s\n", $row['product']['id'], $row['code'], $row['role'], $row['source'], $row['filename']);
}

if(!$commit) {
    echo "\nDRY-RUN OK: chưa thay đổi file hoặc database.\n";
    exit(0);
}

$preparedDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-lamp-prepared-'.bin2hex(random_bytes(8));
if(!mkdir($preparedDir, 0700)) throw new RuntimeException('Không tạo được thư mục chuẩn hóa tạm.');
$prepared = array();
$createdFiles = array();
$changedProducts = array();
$createdGalleryIds = array();
$committed = false;
register_shutdown_function(function() use (&$committed, &$createdFiles, &$changedProducts, &$createdGalleryIds, $preparedDir, $d) {
    if(!$committed) {
        foreach(array_reverse($createdGalleryIds) as $id) $d->rawQuery('delete from #_gallery where id = ?', array((int)$id));
        foreach(array_reverse($changedProducts) as $row)
            $d->rawQuery('update #_product set photo = ?, date_updated = ? where id = ?', array($row['photo'], $row['date_updated'], $row['id']));
        foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
    }
    if(is_dir($preparedDir)) {
        foreach(scandir($preparedDir) as $name) if($name !== '.' && $name !== '..' && is_file($preparedDir.DIRECTORY_SEPARATOR.$name)) @unlink($preparedDir.DIRECTORY_SEPARATOR.$name);
        @rmdir($preparedDir);
    }
});

foreach($resolved as $row) {
    if($row['already']) continue;
    $hash = hash_file('sha256', $row['path']);
    $cacheKey = $hash.'|'.$row['filename'];
    if(isset($prepared[$cacheKey])) continue;
    $preparedPath = $preparedDir.DIRECTORY_SEPARATOR.$hash.'-'.substr(md5($row['filename']),0,8).'.'.$row['extension'];
    if(!copy($row['path'], $preparedPath)) throw new RuntimeException('Không sao chép được ảnh nguồn '.$row['source']);
    if(!productBulkImageFinalizeFile($preparedPath, $row['extension'])) throw new RuntimeException('Không chuẩn hóa được ảnh '.$row['source']);
    $finalError = productBulkImageValidateFile($preparedPath, $row['extension']);
    if($finalError !== '') throw new RuntimeException('Ảnh sau chuẩn hóa không hợp lệ '.$row['source'].': '.$finalError);
    $prepared[$cacheKey] = $preparedPath;
}

$d->startTransaction();
try {
    foreach($resolved as $row) {
        if($row['already']) continue;
        $preparedPath = $prepared[hash_file('sha256', $row['path']).'|'.$row['filename']];
        if(!copy($preparedPath, $row['target'])) throw new RuntimeException('Không lưu được '.$row['filename']);
        @chmod($row['target'], 0644);
        $createdFiles[] = $row['target'];
        if($row['role'] === 'main') {
            $current = $d->rawQueryOne('select photo,date_updated from #_product where id = ? and type = ? limit 0,1', array((int)$row['product']['id'], $type));
            if(empty($current) || trim((string)$current['photo']) !== '' || (int)$current['date_updated'] !== (int)$row['product']['date_updated']) throw new RuntimeException('Sản phẩm '.$row['code'].' đã thay đổi sau bước kiểm tra.');
            $changedProducts[] = array('id'=>(int)$row['product']['id'], 'photo'=>$current['photo'], 'date_updated'=>(int)$current['date_updated']);
            $d->where('id', (int)$row['product']['id']);
            $d->where('type', $type);
            $d->where('photo', '');
            $d->where('date_updated', (int)$current['date_updated']);
            if(!$d->update('product', array('photo'=>$row['filename'], 'date_updated'=>time()))) throw new RuntimeException('Không cập nhật được ảnh đại diện '.$row['code']);
            $verified = $d->rawQueryOne('select photo from #_product where id = ? and type = ? limit 0,1', array((int)$row['product']['id'], $type));
            if(empty($verified) || (string)$verified['photo'] !== $row['filename']) throw new RuntimeException('Không xác minh được ảnh đại diện '.$row['code'].' sau cập nhật.');
        } else {
            if(!$d->insert('gallery', array(
                'id_parent'=>(int)$row['product']['id'], 'photo'=>$row['filename'], 'namevi'=>'', 'numb'=>(int)$row['order'],
                'type'=>$type, 'com'=>'product', 'kind'=>'man', 'val'=>$type, 'status'=>'hienthi', 'date_created'=>time(), 'date_updated'=>0
            ))) throw new RuntimeException('Không thêm được ảnh phụ '.$row['code']);
            $createdGalleryIds[] = (int)$d->getLastInsertId();
        }
    }
    $d->commit();
    $committed = true;
    $cache->delete();
    echo "\nCOMMIT OK: đã thêm ".$counts['main'].' ảnh đại diện và '.$counts['gallery']." ảnh phụ.\n";
} catch(Exception $e) {
    $d->rollback();
    foreach(array_reverse($createdGalleryIds) as $id) $d->rawQuery('delete from #_gallery where id = ?', array((int)$id));
    foreach(array_reverse($changedProducts) as $row)
        $d->rawQuery('update #_product set photo = ?, date_updated = ? where id = ?', array($row['photo'], $row['date_updated'], $row['id']));
    $createdGalleryIds = array();
    $changedProducts = array();
    foreach($createdFiles as $file) if(is_file($file)) @unlink($file);
    $createdFiles = array();
    fwrite(STDERR, 'ROLLBACK: '.$e->getMessage()."\n");
    exit(1);
}
