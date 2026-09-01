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
require LIBRARIES.'product_specs.php';

function testAssert($condition, $message)
{
    if (!$condition) throw new Exception($message);
    echo "PASS: ", $message, PHP_EOL;
}

testAssert(empty(productValidateCommercialFields(array('regular_price' => '1,000,000', 'sale_price' => '900,000', 'cong_suat' => '12,5'))), 'Validation CRUD chấp nhận giá và công suất hợp lệ');
testAssert(count(productValidateCommercialFields(array('regular_price' => '100000', 'sale_price' => '120000', 'cong_suat' => 'abc'))) === 2, 'Validation CRUD chặn giá mới vượt giá bán và công suất sai');

if (!empty($argv[1]) && $argv[1] === '--export-workbook') {
    $_GET['format'] = 'xlsx';
    productBulkExport(false);
}

if (!empty($argv[1]) && $argv[1] === '--export-csv') {
    $_GET['format'] = 'csv';
    productBulkExport(false);
}

if (!empty($argv[1]) && $argv[1] === '--inspect-workbook' && !empty($argv[2])) {
    require_once LIBRARIES.'PHPExcel.php';
    require_once LIBRARIES.'PHPExcel/IOFactory.php';
    $workbook = PHPExcel_IOFactory::load($argv[2]);
    $productSheet = $workbook->getSheetByName('SanPham');
    $systemSheet = $workbook->getSheetByName('_HeThong');
    $guideSheet = $workbook->getSheetByName('HuongDan');
    testAssert($productSheet !== null && $systemSheet !== null && $guideSheet !== null, 'Workbook có sheet sản phẩm, hướng dẫn và hệ thống');
    testAssert($productSheet->getCell('A1')->getValue() === 'ID sản phẩm', 'Sheet sản phẩm dùng tiêu đề nghiệp vụ tiếng Việt');
    testAssert($systemSheet->getSheetState() === PHPExcel_Worksheet::SHEETSTATE_VERYHIDDEN, 'Sheet hệ thống được ẩn hoàn toàn');
    testAssert($systemSheet->getCell('A2')->getValue() !== '', 'Sheet hệ thống chứa snapshot để so sánh xung đột');
    $headerColumns = array();
    foreach ($productSheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) $headerColumns[$cell->getValue()] = $cell->getColumn();
    testAssert(
        !empty($headerColumns['Mã sản phẩm']) && !empty($headerColumns['Tên sản phẩm'])
        && $productSheet->getStyle($headerColumns['Mã sản phẩm'].'1')->getFill()->getStartColor()->getRGB() === 'FDE68A'
        && $productSheet->getStyle($headerColumns['Tên sản phẩm'].'1')->getFill()->getStartColor()->getRGB() === 'FDE68A',
        'Hai cột bắt buộc khi tạo mới được tô màu vàng'
    );
    testAssert(strpos((string) $guideSheet->getCell('B2')->getValue(), 'Mã sản phẩm và Tên sản phẩm') !== false, 'Sheet hướng dẫn nêu rõ dữ liệu bắt buộc');
    testAssert(strpos((string) $guideSheet->getCell('B3')->getValue(), 'ô trống sẽ giữ') !== false, 'Sheet hướng dẫn giải thích cách cập nhật tối giản');
    testAssert(strpos((string) $guideSheet->getCell('B17')->getValue(), 'Model | JD-T200') !== false, 'Sheet hướng dẫn có ví dụ thông số khác');
    testAssert(strpos((string) $guideSheet->getCell('B21')->getValue(), 'Bước 1') !== false && strpos((string) $guideSheet->getCell('B21')->getValue(), 'CAP_NHAT') !== false, 'Sheet hướng dẫn có quy trình import từng bước');
    testAssert(!empty($headerColumns['Danh mục cấp 1']) && $productSheet->getCell($headerColumns['Danh mục cấp 1'].'2')->getDataValidation()->getType() === PHPExcel_Cell_DataValidation::TYPE_LIST, 'Danh mục có danh sách chọn trong Excel');
    $htmlFree = true;
    foreach(array('Mô tả ngắn', 'Nội dung', 'Chính sách mua hàng', 'Chứng nhận/kiểm nghiệm') as $header) {
        if(empty($headerColumns[$header])) continue;
        for($rowNumber = 2; $rowNumber <= $productSheet->getHighestRow(); $rowNumber++) {
            if(preg_match('~<[a-z][^>]*>~iu', (string) $productSheet->getCell($headerColumns[$header].$rowNumber)->getValue())) {
                $htmlFree = false;
                break 2;
            }
        }
    }
    testAssert($htmlFree, 'Các cột nội dung trong Excel không còn thẻ HTML');
    $parsedWorkbook = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($argv[2]), 'name' => 'san-pham.xlsx', 'tmp_name' => $argv[2]));
    testAssert(empty($parsedWorkbook['errors']) && !empty($parsedWorkbook['rows']), 'Đọc lại được workbook vừa xuất');
    testAssert(count($parsedWorkbook['baseline']) === count($parsedWorkbook['rows']), 'Đọc đủ snapshot hệ thống cho các sản phẩm xuất ra');
    $roundTripPreview = productBulkValidateRows($parsedWorkbook['rows'], $type, $parsedWorkbook['baseline']);
    testAssert($roundTripPreview['errors'] === 0 && $roundTripPreview['conflicts'] === 0, 'Workbook xuất ra có thể nhập lại không lỗi/xung đột giả');
    testAssert($roundTripPreview['counts']['unchanged'] === count($parsedWorkbook['rows']), 'Workbook chưa chỉnh sửa không tạo cập nhật thừa');
    exit;
}

function commitRowsForTest($rows, $token, $baseline = array(), $overwriteChoices = array(), $brandChoices = array())
{
    global $type, $bulkMessage, $bulkResult, $bulkPreview;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-bulk-test-'.$token.'.json';
    file_put_contents($path, json_encode(array('type' => $type, 'created_at' => time(), 'rows' => $rows, 'baseline' => $baseline), JSON_UNESCAPED_UNICODE));
    $_SESSION['product_bulk_preview'][$token] = array('path' => $path, 'type' => $type, 'created_at' => time());
    $_SESSION['product_bulk_csrf'] = 'test-csrf';
    $_POST = array('product_bulk_csrf' => 'test-csrf', 'confirm_bulk' => 'CAP_NHAT', 'preview_token' => $token, 'overwrite_conflict' => $overwriteChoices, 'brand_resolution' => $brandChoices);
    $bulkMessage = null;
    $bulkResult = null;
    $bulkPreview = null;
    productBulkCommit();
    testAssert(!empty($bulkMessage) && $bulkMessage['type'] === 'success', 'Commit batch thành công'.(!empty($bulkMessage['text']) ? ': '.$bulkMessage['text'] : ''));
}

function failCommitRowsForTest($rows, $token, $failAfter, $brandChoices = array())
{
    global $type, $bulkMessage, $productBulkTestFailAfter;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pts-bulk-test-'.$token.'.json';
    file_put_contents($path, json_encode(array('type' => $type, 'created_at' => time(), 'rows' => $rows, 'baseline' => array()), JSON_UNESCAPED_UNICODE));
    $_SESSION['product_bulk_preview'][$token] = array('path' => $path, 'type' => $type, 'created_at' => time());
    $_SESSION['product_bulk_csrf'] = 'test-csrf';
    $_POST = array('product_bulk_csrf' => 'test-csrf', 'confirm_bulk' => 'CAP_NHAT', 'preview_token' => $token, 'brand_resolution' => $brandChoices);
    $productBulkTestFailAfter = $failAfter;
    $bulkMessage = null;
    productBulkCommit();
    $productBulkTestFailAfter = null;
    @unlink($path);
    testAssert(!empty($bulkMessage) && $bulkMessage['type'] === 'danger', 'Batch lỗi giữa chừng trả về trạng thái thất bại');
}

$before = (int) $d->rawQueryOne('select count(*) as total from #_product where type = ?', array($type))['total'];
$code = 'CODEX-BULK-'.date('His');
$createdId = 0;
$createdBrandId = 0;
try {
    $csvPath = tempnam(sys_get_temp_dir(), 'pts-csv-');
    $csv = fopen($csvPath, 'wb');
    fputcsv($csv, array('Mã sản phẩm', 'Tên sản phẩm', 'Giá gốc', 'Giá bán/khuyến mãi', 'Nguồn sáng', 'Thông số khác (Tên | Giá trị)'));
    fputcsv($csv, array($code, 'Sản phẩm kiểm thử bulk', '1000', '900', 'LED SMD', "Model | CODEX-T200\nGóc chiếu | 120°"));
    fclose($csv);
    $parsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($csvPath), 'name' => 'test.csv', 'tmp_name' => $csvPath));
    testAssert(empty($parsed['errors']) && count($parsed['rows']) === 1, 'Đọc CSV với tiêu đề tiếng Việt'.(!empty($parsed['errors']) ? ': '.implode(' ', $parsed['errors']) : ''));
    testAssert(isset($parsed['rows'][0]['code']) && !isset($parsed['rows'][0]['action']), 'Ánh xạ tiêu đề tiếng Việt về field nội bộ');
    $autoCreatePreview = productBulkValidateRows($parsed['rows'], $type);
    testAssert($autoCreatePreview['errors'] === 0 && $autoCreatePreview['counts']['create'] === 1, 'Tự nhận diện dòng không có ID và mã mới là tạo mới');
    $parsedSpecs = json_decode($autoCreatePreview['rows'][0]['data']['additional_specs'], true);
    testAssert($autoCreatePreview['rows'][0]['data']['light_source'] === 'LED SMD' && count($parsedSpecs) === 2 && $parsedSpecs[1]['value'] === '120°', 'Đọc thông số chuẩn và danh sách thông số riêng từ từng ô');
    $duplicateSpecs = productBulkValidateRows(array(array('_row_number' => 3, 'code' => $code.'-DUP', 'namevi' => 'Sản phẩm trùng thông số', 'additional_specs' => "Model | A\nmodel | B")), $type);
    testAssert($duplicateSpecs['errors'] > 0, 'Từ chối tên thông số riêng bị trùng');
    $formSpecs = productBulkParseSpecLines("Model | TEST\nMàu | Trắng");
    testAssert(empty($formSpecs['errors']) && count($formSpecs['specs']) === 2, 'Parser dùng chung cho CRUD form đọc đủ thông số riêng');
    $duplicateRender = productStructuredSpecs(array('additional_specs' => '[{"label":"Cánh quạt","value":"5 cánh"},{"label":"Cánh quạt","value":"16 inch"}]'), 'vi');
    testAssert(!$duplicateRender['use_legacy'] && count($duplicateRender['specs']) === 2, 'Frontend giữ đủ thông số legacy trùng nhãn');
    $legacyRender = productStructuredSpecs(array('additional_specs' => '{json lỗi', 'thongsokythuatvi' => '<table><tr><td>Legacy</td></tr></table>'), 'vi');
    testAssert($legacyRender['use_legacy'] && empty($legacyRender['specs']), 'Frontend fallback về HTML cũ khi JSON thông số lỗi');
    @unlink($csvPath);

    $legacyCsvPath = tempnam(sys_get_temp_dir(), 'pts-csv-legacy-');
    $legacyCsv = fopen($legacyCsvPath, 'wb');
    fputcsv($legacyCsv, array('action', 'code', 'namevi'));
    fputcsv($legacyCsv, array('skip', 'LEGACY', 'File cũ'));
    fclose($legacyCsv);
    $legacyParsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($legacyCsvPath), 'name' => 'legacy.csv', 'tmp_name' => $legacyCsvPath));
    testAssert(empty($legacyParsed['errors']) && isset($legacyParsed['rows'][0]['namevi']), 'Vẫn đọc được tiêu đề kỹ thuật của file cũ');
    @unlink($legacyCsvPath);

    require_once LIBRARIES.'PHPExcel.php';
    require_once LIBRARIES.'PHPExcel/IOFactory.php';
    $legacyXlsPath = tempnam(sys_get_temp_dir(), 'pts-xls-');
    $legacyBook = new PHPExcel();
    $legacyBook->getActiveSheet()->fromArray(array(array('Mã sản phẩm', 'Tên sản phẩm'), array($code.'-XLS', 'File XLS cũ')));
    $legacyErrorReporting = error_reporting();
    error_reporting($legacyErrorReporting & ~E_DEPRECATED);
    PHPExcel_IOFactory::createWriter($legacyBook, 'Excel5')->save($legacyXlsPath);
    error_reporting($legacyErrorReporting);
    $legacyXlsParsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($legacyXlsPath), 'name' => 'legacy.xls', 'tmp_name' => $legacyXlsPath));
    testAssert(empty($legacyXlsParsed['errors']) && $legacyXlsParsed['rows'][0]['code'] === $code.'-XLS', 'Đọc được định dạng XLS legacy');
    @unlink($legacyXlsPath);

    $incompleteThemePath = tempnam(sys_get_temp_dir(), 'pts-xlsx-theme-');
    $themeBook = new PHPExcel();
    $themeBook->getActiveSheet()->fromArray(array(array('Mã sản phẩm', 'Tên sản phẩm'), array($code.'-THEME', 'File XLSX theme thiếu')));
    if (!class_exists('PHPExcel_Writer_Excel2007_Comments', true)) {
        class PHPExcel_Writer_Excel2007_Comments extends PHPExcel_Writer_Excel2007_WriterPart
        {
        }
    }
    PHPExcel_IOFactory::createWriter($themeBook, 'Excel2007')->save($incompleteThemePath);
    $themeZip = new ZipArchive();
    testAssert($themeZip->open($incompleteThemePath) === true, 'Mở workbook để mô phỏng theme không đầy đủ');
    $themeZip->addFromString('xl/theme/theme1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Incomplete"></a:theme>');
    $themeZip->close();
    $incompleteThemeParsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($incompleteThemePath), 'name' => 'incomplete-theme.xlsx', 'tmp_name' => $incompleteThemePath));
    testAssert(empty($incompleteThemeParsed['errors']) && $incompleteThemeParsed['rows'][0]['code'] === $code.'-THEME', 'XLSX thiếu colour scheme vẫn đọc được ở chế độ data-only');
    @unlink($incompleteThemePath);

    $strictThemePath = tempnam(sys_get_temp_dir(), 'pts-xlsx-strict-');
    $strictBook = new PHPExcel();
    $strictBook->getActiveSheet()->fromArray(array(array('Mã sản phẩm', 'Tên sản phẩm'), array($code.'-STRICT', 'File Strict OOXML')));
    PHPExcel_IOFactory::createWriter($strictBook, 'Excel2007')->save($strictThemePath);
    $strictZip = new ZipArchive();
    testAssert($strictZip->open($strictThemePath) === true, 'Mở workbook để mô phỏng Strict OOXML');
    $strictEntryCount = $strictZip->numFiles;
    for ($zipIndex = 0; $zipIndex < $strictEntryCount; $zipIndex++) {
        $entryName = $strictZip->getNameIndex($zipIndex);
        if (!preg_match('/(?:\.xml|\.rels)$/', $entryName)) continue;
        $entryContents = $strictZip->getFromIndex($zipIndex);
        $entryContents = str_replace(
            array(
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'http://schemas.openxmlformats.org/package/2006/relationships',
                'http://schemas.openxmlformats.org/drawingml/2006/main'
            ),
            array(
                'http://purl.oclc.org/ooxml/spreadsheetml/main',
                'http://purl.oclc.org/ooxml/officeDocument/relationships',
                'http://purl.oclc.org/ooxml/package/relationships',
                'http://purl.oclc.org/ooxml/drawingml/main'
            ),
            $entryContents
        );
        $strictZip->addFromString($entryName, $entryContents);
    }
    $strictZip->close();
    $strictParsed = productBulkReadUpload(array('error' => UPLOAD_ERR_OK, 'size' => filesize($strictThemePath), 'name' => 'strict.xlsx', 'tmp_name' => $strictThemePath));
    testAssert(empty($strictParsed['errors']) && $strictParsed['rows'][0]['code'] === $code.'-STRICT', 'Strict OOXML được chuẩn hóa và đọc đủ sheet');
    @unlink($strictThemePath);

    $createRows = array(array('_row_number' => 2, 'action' => 'create', 'code' => $code, 'namevi' => 'Sản phẩm kiểm thử bulk', 'regular_price' => '1000', 'sale_price' => '900', 'status' => 'hienthi', 'descvi' => '<script>alert(1)</script><p>Nội dung hợp lệ</p>', 'light_source' => 'LED SMD', 'additional_specs' => "Model | CODEX-T200\nGóc chiếu | 120°"));
    $preview = productBulkValidateRows($createRows, $type);
    testAssert($preview['errors'] === 0 && $preview['counts']['create'] === 1, 'Preview tạo mới hợp lệ');
    commitRowsForTest($createRows, bin2hex(random_bytes(12)));
    $created = $d->rawQueryOne('select * from #_product where type = ? and code = ? limit 0,1', array($type, $code));
    testAssert(!empty($created['id']), 'Sản phẩm thử đã được tạo');
    $createdId = (int) $created['id'];
    testAssert(stripos(htmlspecialchars_decode($created['descvi']), '<script') === false, 'HTML nguy hiểm bị loại bỏ');
    $savedSpecs = json_decode(htmlspecialchars_decode($created['additional_specs'], ENT_QUOTES), true);
    testAssert($created['light_source'] === 'LED SMD' && count($savedSpecs) === 2 && $savedSpecs[0]['label'] === 'Model', 'Lưu đúng thông số cấu trúc và JSON thông số riêng');

    $newBrandName = 'Codex Solar '.date('His');
    $newBrandKey = productBulkBrandKey($newBrandName);
    $brandRows = array(array('_row_number' => 7, 'action' => 'update', 'id' => $createdId, '_brand_name' => $newBrandName));
    $brandPreview = productBulkValidateRows($brandRows, $type);
    testAssert($brandPreview['errors'] === 0 && isset($brandPreview['new_brands'][$newBrandKey]) && $brandPreview['warnings'] > 0, 'Preview gom thương hiệu mới để quản trị viên xác nhận');
    $brandMissingChoice = productBulkValidateRows($brandRows, $type, array(), array(), array(), true);
    testAssert($brandMissingChoice['errors'] > 0, 'Commit từ chối thương hiệu mới chưa có lựa chọn xử lý');
    commitRowsForTest($brandRows, bin2hex(random_bytes(12)), array(), array(), array($newBrandKey => 'create'));
    $createdBrand = $d->rawQueryOne('select id,namevi from #_product_brand where type = ? and namevi = ? limit 0,1', array($type, $newBrandName));
    $createdBrandId = !empty($createdBrand['id']) ? (int) $createdBrand['id'] : 0;
    $productBrand = $d->rawQueryOne('select id_brand from #_product where id = ? and type = ?', array($createdId, $type));
    testAssert($createdBrandId > 0 && (int) $productBrand['id_brand'] === $createdBrandId, 'Tạo brand và gán sản phẩm trong cùng lượt commit');
    $existingBrand = $d->rawQueryOne('select id,namevi from #_product_brand where type = ? and id <> ? limit 0,1', array($type, $createdBrandId));
    $existingBrandPreview = productBulkValidateRows(array(array('_row_number' => 8, 'action' => 'update', 'id' => $createdId, '_brand_name' => mb_strtolower($existingBrand['namevi'], 'UTF-8'))), $type);
    testAssert($existingBrandPreview['errors'] === 0 && empty($existingBrandPreview['new_brands']), 'Tên brand có sẵn được ghép không phân biệt hoa thường');
    $aliasName = 'Alias chưa có '.date('His');
    $aliasKey = productBulkBrandKey($aliasName);
    $aliasPreview = productBulkValidateRows(array(array('_row_number' => 9, 'action' => 'update', 'id' => $createdId, '_brand_name' => $aliasName)), $type, array(), array(), array($aliasKey => 'existing:'.(int) $existingBrand['id']), true);
    testAssert($aliasPreview['errors'] === 0 && (int) $aliasPreview['rows'][0]['data']['id_brand'] === (int) $existingBrand['id'], 'Cho phép ghép tên brand mới vào brand chuẩn có sẵn');

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

    $clearRows = array(array('_row_number' => 3, 'action' => 'update', 'id' => $createdId, 'light_source' => '__CLEAR__', 'additional_specs' => '__CLEAR__'));
    $clearPreview = productBulkValidateRows($clearRows, $type);
    testAssert($clearPreview['errors'] === 0 && array_key_exists('light_source', $clearPreview['rows'][0]['data']) && $clearPreview['rows'][0]['data']['light_source'] === null, '__CLEAR__ được chuyển thành giá trị rỗng có chủ ý');
    commitRowsForTest($clearRows, bin2hex(random_bytes(12)));
    $cleared = $d->rawQueryOne('select light_source,additional_specs from #_product where id = ? and type = ?', array($createdId, $type));
    testAssert($cleared['light_source'] === null && $cleared['additional_specs'] === null, '__CLEAR__ xóa đúng dữ liệu khi commit');

    $beforeRollbackName = $d->rawQueryOne('select namevi from #_product where id = ? and type = ?', array($createdId, $type))['namevi'];
    $rollbackRows = array(
        array('_row_number' => 20, 'action' => 'update', 'id' => $createdId, 'namevi' => 'Tên không được lưu'),
        array('_row_number' => 21, 'action' => 'create', 'code' => $code.'-ROLLBACK', 'namevi' => 'Sản phẩm rollback')
    );
    failCommitRowsForTest($rollbackRows, bin2hex(random_bytes(12)), 1);
    $afterRollbackName = $d->rawQueryOne('select namevi from #_product where id = ? and type = ?', array($createdId, $type))['namevi'];
    $rollbackCreated = $d->rawQueryOne('select id from #_product where code = ? and type = ?', array($code.'-ROLLBACK', $type));
    testAssert($afterRollbackName === $beforeRollbackName && empty($rollbackCreated), 'Transaction rollback không để lại cập nhật/tạo mới dở dang');

    $rollbackBrandName = 'Codex Rollback Brand '.date('His');
    $rollbackBrandKey = productBulkBrandKey($rollbackBrandName);
    $rollbackBrandRows = array(array('_row_number' => 22, 'action' => 'update', 'id' => $createdId, '_brand_name' => $rollbackBrandName, 'namevi' => 'Tên rollback brand'));
    failCommitRowsForTest($rollbackBrandRows, bin2hex(random_bytes(12)), 1, array($rollbackBrandKey => 'create'));
    $rollbackBrand = $d->rawQueryOne('select id from #_product_brand where type = ? and namevi = ? limit 0,1', array($type, $rollbackBrandName));
    testAssert(empty($rollbackBrand), 'Rollback xóa thương hiệu mới nếu batch sản phẩm thất bại');

    $archiveRows = array(array('_row_number' => 2, 'action' => 'archive', 'id' => $createdId));
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
    if ($createdBrandId > 0) {
        $d->rawQuery('update #_product set id_brand = 0 where id_brand = ? and type = ?', array($createdBrandId, $type));
        $d->rawQuery('delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?', array($createdBrandId, 'product', 'man_brand', $type));
        $d->rawQuery('delete from #_product_brand where id = ? and type = ?', array($createdBrandId, $type));
    }
    $after = (int) $d->rawQueryOne('select count(*) as total from #_product where type = ?', array($type))['total'];
    testAssert($after === $before, 'Dữ liệu thử đã được dọn sạch, tổng sản phẩm không đổi');
}
