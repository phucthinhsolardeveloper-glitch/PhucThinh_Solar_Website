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

if (!empty($argv[1]) && $argv[1] === '--export-workbook') {
    $_GET['format'] = 'xlsx';
    productBulkExport(false);
}

if (!empty($argv[1]) && $argv[1] === '--inspect-workbook' && !empty($argv[2])) {
    require_once LIBRARIES.'PHPExcel.php';
    require_once LIBRARIES.'PHPExcel/IOFactory.php';
    $workbook = PHPExcel_IOFactory::load($argv[2]);
    $productSheet = $workbook->getSheetByName('SanPham');
    $systemSheet = $workbook->getSheetByName('_HeThong');
    testAssert($productSheet !== null && $systemSheet !== null, 'Workbook có sheet sản phẩm và sheet hệ thống');
    testAssert($productSheet->getCell('A1')->getValue() === 'ID sản phẩm', 'Sheet sản phẩm dùng tiêu đề nghiệp vụ tiếng Việt');
    testAssert($systemSheet->getSheetState() === PHPExcel_Worksheet::SHEETSTATE_VERYHIDDEN, 'Sheet hệ thống được ẩn hoàn toàn');
    testAssert($systemSheet->getCell('A2')->getValue() !== '', 'Sheet hệ thống chứa snapshot để so sánh xung đột');
    $headerColumns = array();
    foreach ($productSheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) $headerColumns[$cell->getValue()] = $cell->getColumn();
    testAssert(!empty($headerColumns['Danh mục cấp 1']) && $productSheet->getCell($headerColumns['Danh mục cấp 1'].'2')->getDataValidation()->getType() === PHPExcel_Cell_DataValidation::TYPE_LIST, 'Danh mục có danh sách chọn trong Excel');
    $parsedWorkbook = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($argv[2]), 'name' => 'san-pham.xlsx', 'tmp_name' => $argv[2]));
    testAssert(empty($parsedWorkbook['errors']) && !empty($parsedWorkbook['rows']), 'Đọc lại được workbook vừa xuất');
    testAssert(count($parsedWorkbook['baseline']) === count($parsedWorkbook['rows']), 'Đọc đủ snapshot hệ thống cho các sản phẩm xuất ra');
    $roundTripPreview = productBulkValidateRows($parsedWorkbook['rows'], $type, $parsedWorkbook['baseline']);
    testAssert($roundTripPreview['errors'] === 0 && $roundTripPreview['conflicts'] === 0, 'Workbook xuất ra có thể nhập lại không lỗi/xung đột giả');
    testAssert($roundTripPreview['counts']['unchanged'] === count($parsedWorkbook['rows']), 'Workbook chưa chỉnh sửa không tạo cập nhật thừa');
    exit;
}

function commitRowsForTest($rows, $token, $baseline = array(), $overwriteChoices = array())
{
    global $type, $bulkMessage, $bulkResult, $bulkPreview;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-bulk-test-'.$token.'.json';
    file_put_contents($path, json_encode(array('type' => $type, 'created_at' => time(), 'rows' => $rows, 'baseline' => $baseline), JSON_UNESCAPED_UNICODE));
    $_SESSION['product_bulk_preview'][$token] = array('path' => $path, 'type' => $type, 'created_at' => time());
    $_SESSION['product_bulk_csrf'] = 'test-csrf';
    $_POST = array('product_bulk_csrf' => 'test-csrf', 'confirm_bulk' => 'CAP_NHAT', 'preview_token' => $token, 'overwrite_conflict' => $overwriteChoices);
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
    fputcsv($csv, array('Mã sản phẩm', 'Tên sản phẩm', 'Giá gốc', 'Giá bán/khuyến mãi'));
    fputcsv($csv, array($code, 'Sản phẩm kiểm thử bulk', '1000', '900'));
    fclose($csv);
    $parsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($csvPath), 'name' => 'test.csv', 'tmp_name' => $csvPath));
    testAssert(empty($parsed['errors']) && count($parsed['rows']) === 1, 'Đọc CSV với tiêu đề tiếng Việt'.(!empty($parsed['errors']) ? ': '.implode(' ', $parsed['errors']) : ''));
    testAssert(isset($parsed['rows'][0]['code']) && !isset($parsed['rows'][0]['action']), 'Ánh xạ tiêu đề tiếng Việt về field nội bộ');
    $autoCreatePreview = productBulkValidateRows($parsed['rows'], $type);
    testAssert($autoCreatePreview['errors'] === 0 && $autoCreatePreview['counts']['create'] === 1, 'Tự nhận diện dòng không có ID và mã mới là tạo mới');
    @unlink($csvPath);

    $legacyCsvPath = tempnam(sys_get_temp_dir(), 'pts-csv-legacy-');
    $legacyCsv = fopen($legacyCsvPath, 'wb');
    fputcsv($legacyCsv, array('action', 'code', 'namevi'));
    fputcsv($legacyCsv, array('skip', 'LEGACY', 'File cũ'));
    fclose($legacyCsv);
    $legacyParsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($legacyCsvPath), 'name' => 'legacy.csv', 'tmp_name' => $legacyCsvPath));
    testAssert(empty($legacyParsed['errors']) && isset($legacyParsed['rows'][0]['namevi']), 'Vẫn đọc được tiêu đề kỹ thuật của file cũ');
    @unlink($legacyCsvPath);

    $createRows = array(array('_row_number' => 2, 'action' => 'create', 'code' => $code, 'namevi' => 'Sản phẩm kiểm thử bulk', 'regular_price' => '1000', 'sale_price' => '900', 'status' => 'hienthi', 'descvi' => '<script>alert(1)</script><p>Nội dung hợp lệ</p>'));
    $preview = productBulkValidateRows($createRows, $type);
    testAssert($preview['errors'] === 0 && $preview['counts']['create'] === 1, 'Preview tạo mới hợp lệ');
    commitRowsForTest($createRows, bin2hex(random_bytes(12)));
    $created = $d->rawQueryOne('select * from #_product where type = ? and code = ? limit 0,1', array($type, $code));
    testAssert(!empty($created['id']), 'Sản phẩm thử đã được tạo');
    testAssert(stripos(htmlspecialchars_decode($created['descvi']), '<script') === false, 'HTML nguy hiểm bị loại bỏ');
    $createdId = (int) $created['id'];

    $autoUpdate = productBulkValidateRows(array(array('_row_number' => 8, 'code' => $code, 'material' => 'Nhôm')), $type);
    testAssert($autoUpdate['errors'] === 0 && $autoUpdate['counts']['update'] === 1 && $autoUpdate['rows'][0]['id'] === $createdId, 'Tự match sản phẩm hiện có theo mã');

    $baseline = array($createdId => $created);
    $d->rawQuery('update #_product set material = ?, date_updated = ? where id = ? and type = ?', array('Website mới', time() + 1, $createdId, $type));
    $conflictRows = array(array('_row_number' => 9, 'id' => $createdId, 'code' => $code, 'material' => 'Excel mới'));
    $conflictPreview = productBulkValidateRows($conflictRows, $type, $baseline);
    testAssert($conflictPreview['errors'] === 0 && $conflictPreview['conflicts'] === 1 && !isset($conflictPreview['rows'][0]['data']['material']), 'Phát hiện xung đột và mặc định giữ dữ liệu website');
    $overwritePreview = productBulkValidateRows($conflictRows, $type, $baseline, array('9:material' => true));
    testAssert($overwritePreview['conflicts'] === 1 && $overwritePreview['rows'][0]['data']['material'] === 'Excel mới', 'Cho phép chọn ghi đè từng ô bằng dữ liệu Excel');
    commitRowsForTest($conflictRows, bin2hex(random_bytes(12)), $baseline, array('9:material'));
    $conflictUpdated = $d->rawQueryOne('select material from #_product where id = ? and type = ?', array($createdId, $type));
    testAssert($conflictUpdated['material'] === 'Excel mới', 'Commit áp dụng đúng lựa chọn ghi đè xung đột');
    $d->rawQuery('update #_product set material = ?, date_updated = ? where id = ? and type = ?', array($created['material'], $created['date_updated'], $createdId, $type));

    $anotherProduct = $d->rawQueryOne("select id, code from #_product where type = ? and id <> ? and code <> '' limit 0,1", array($type, $createdId));
    if (!empty($anotherProduct)) {
        $identityMismatch = productBulkValidateRows(array(array('_row_number' => 10, 'id' => $createdId, 'code' => $anotherProduct['code'], 'namevi' => 'Sai định danh')), $type);
        testAssert($identityMismatch['errors'] > 0, 'Từ chối khi ID và mã chỉ đến hai sản phẩm khác nhau');
    }

    $visibleColumns = productBulkVisibleColumns();
    testAssert(!isset($visibleColumns['action']) && !isset($visibleColumns['expected_updated_at']) && isset($visibleColumns['id']), 'Ẩn cột thao tác/mốc cập nhật khỏi file nghiệp vụ');

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
