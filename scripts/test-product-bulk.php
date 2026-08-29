<?php
$_SERVER['SERVER_NAME'] = 'localhost';
session_start();
define('LIBRARIES', '/var/www/html/libraries/');
define('SOURCES', '/var/www/html/administrator/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'config-type.php';
$type = 'san-pham';
require SOURCES.'product_bulk.php';

function testAssert($condition, $message)
{
    if (!$condition) throw new Exception($message);
    echo "PASS: ", $message, PHP_EOL;
}

function commitRowsForTest($rows, $token)
{
    global $type, $bulkMessage, $bulkResult, $bulkPreview;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-bulk-test-'.$token.'.json';
    file_put_contents($path, json_encode(array('type' => $type, 'created_at' => time(), 'rows' => $rows), JSON_UNESCAPED_UNICODE));
    $_SESSION['product_bulk_preview'][$token] = array('path' => $path, 'type' => $type, 'created_at' => time());
    $_SESSION['product_bulk_csrf'] = 'test-csrf';
    $_POST = array('product_bulk_csrf' => 'test-csrf', 'confirm_bulk' => 'CAP_NHAT', 'preview_token' => $token);
    $bulkMessage = null;
    $bulkResult = null;
    $bulkPreview = null;
    productBulkCommit();
    testAssert(!empty($bulkMessage) && $bulkMessage['type'] === 'success', 'Commit batch thành công');
}

$before = (int) $d->rawQueryOne('select count(*) as total from #_product where type = ?', array($type))['total'];
$code = 'CODEX-BULK-'.date('His');
$createdId = 0;
try {
    $csvPath = tempnam(sys_get_temp_dir(), 'pts-csv-');
    $csv = fopen($csvPath, 'wb');
    fputcsv($csv, array('action', 'code', 'namevi', 'regular_price', 'sale_price'));
    fputcsv($csv, array('create', $code, 'Sản phẩm kiểm thử bulk', '1000', '900'));
    fclose($csv);
    $parsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($csvPath), 'name' => 'test.csv', 'tmp_name' => $csvPath));
    testAssert(empty($parsed['errors']) && count($parsed['rows']) === 1, 'Đọc CSV đúng header và dữ liệu');
    @unlink($csvPath);

    $createRows = array(array('_row_number' => 2, 'action' => 'create', 'code' => $code, 'namevi' => 'Sản phẩm kiểm thử bulk', 'regular_price' => '1000', 'sale_price' => '900', 'status' => 'hienthi', 'descvi' => '<script>alert(1)</script><p>Nội dung hợp lệ</p>'));
    $preview = productBulkValidateRows($createRows, $type);
    testAssert($preview['errors'] === 0 && $preview['counts']['create'] === 1, 'Preview tạo mới hợp lệ');
    commitRowsForTest($createRows, bin2hex(random_bytes(12)));
    $created = $d->rawQueryOne('select * from #_product where type = ? and code = ? limit 0,1', array($type, $code));
    testAssert(!empty($created['id']), 'Sản phẩm thử đã được tạo');
    testAssert(stripos(htmlspecialchars_decode($created['descvi']), '<script') === false, 'HTML nguy hiểm bị loại bỏ');
    $createdId = (int) $created['id'];

    $updateRows = array(array('_row_number' => 2, 'action' => 'update', 'id' => $createdId, 'expected_updated_at' => (int) $created['date_updated'], 'sale_price' => '800'));
    $preview = productBulkValidateRows($updateRows, $type);
    testAssert($preview['errors'] === 0 && $preview['counts']['update'] === 1, 'Preview cập nhật theo ID hợp lệ');
    commitRowsForTest($updateRows, bin2hex(random_bytes(12)));
    $updated = $d->rawQueryOne('select * from #_product where id = ? and type = ?', array($createdId, $type));
    testAssert((float) $updated['sale_price'] === 800.0 && $updated['namevi'] === $created['namevi'], 'Ô trống giữ nguyên và giá được cập nhật');

    $archiveRows = array(array('_row_number' => 2, 'action' => 'archive', 'id' => $createdId, 'expected_updated_at' => (int) $updated['date_updated']));
    $preview = productBulkValidateRows($archiveRows, $type);
    testAssert($preview['errors'] === 0 && $preview['counts']['archive'] === 1, 'Preview archive hợp lệ');
    commitRowsForTest($archiveRows, bin2hex(random_bytes(12)));
    $archived = $d->rawQueryOne('select status from #_product where id = ? and type = ?', array($createdId, $type));
    testAssert(strpos(','.$archived['status'].',', ',hienthi,') === false, 'Archive gỡ trạng thái hiển thị');

    $duplicates = productBulkValidateRows(array(
        array('_row_number' => 2, 'action' => 'create', 'code' => 'DUPLICATE-CODE', 'namevi' => 'Trùng 1'),
        array('_row_number' => 3, 'action' => 'create', 'code' => 'DUPLICATE-CODE', 'namevi' => 'Trùng 2')
    ), $type);
    testAssert($duplicates['errors'] > 0, 'Phát hiện mã trùng trong cùng file');
    $missingId = productBulkValidateRows(array(array('_row_number' => 2, 'action' => 'update', 'namevi' => 'Không có ID')), $type);
    testAssert($missingId['errors'] > 0, 'Từ chối update không có ID');
} finally {
    if ($createdId > 0) {
        $d->rawQuery('delete from #_seo where id_parent = ? and com = ? and type = ?', array($createdId, 'product', $type));
        $d->rawQuery('delete from #_product where id = ? and type = ?', array($createdId, $type));
    }
    $after = (int) $d->rawQueryOne('select count(*) as total from #_product where type = ?', array($type))['total'];
    testAssert($after === $before, 'Dữ liệu thử đã được dọn sạch, tổng sản phẩm không đổi');
}
