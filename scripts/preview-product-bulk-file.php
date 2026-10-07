<?php
/**
 * Read-only preview for a real product bulk workbook.
 * Runs the production parse + validation pipeline without committing anything.
 *
 * Usage (inside PHP 8.1 container):
 *   php /tmp/preview-product-bulk-file.php /tmp/<workbook>.xlsx
 */

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

$path = isset($argv[1]) ? $argv[1] : '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "File not found: $path\n");
    exit(1);
}

$file = array(
    'name' => basename($path),
    'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'tmp_name' => $path,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($path),
);

echo "== FILE ==\n";
echo "path: $path\n";
echo "size: ".number_format($file['size'])." bytes\n";
echo "sha256: ".hash_file('sha256', $path)."\n\n";

$parsed = productBulkReadUpload($file);
if (!empty($parsed['errors'])) {
    echo "== PARSE ERRORS ==\n";
    foreach ($parsed['errors'] as $e) echo " - $e\n";
    exit(2);
}

echo "== PARSE ==\n";
echo "cột nhận diện: ".implode(', ', $parsed['headers'])."\n";
echo "số dòng có dữ liệu: ".count($parsed['rows'])."\n";
echo "baseline snapshot (_HeThong): ".count($parsed['baseline'])." sản phẩm\n\n";

$statusValues = array();
foreach ($parsed['rows'] as $parsedRow) {
    $status = isset($parsedRow['status']) ? trim((string)$parsedRow['status']) : '';
    $statusKey = $status === '' ? '(trống)' : $status;
    if (!isset($statusValues[$statusKey])) $statusValues[$statusKey] = 0;
    $statusValues[$statusKey]++;
}
echo "== GIÁ TRỊ TRẠNG THÁI TRONG FILE ==\n";
foreach ($statusValues as $status => $count) echo "  $status: $count\n";
echo "\n";

$preview = productBulkValidateRows($parsed['rows'], $type, $parsed['baseline']);

echo "== TỔNG HỢP ==\n";
echo "create   : ".$preview['counts']['create']."\n";
echo "update   : ".$preview['counts']['update']."\n";
echo "archive  : ".$preview['counts']['archive']."\n";
echo "draft new: ".$preview['counts']['draft_create']."\n";
echo "hide upd : ".$preview['counts']['hide_update']."\n";
echo "show upd : ".$preview['counts']['show_update']."\n";
echo "skip     : ".$preview['counts']['skip']."\n";
echo "unchanged: ".$preview['counts']['unchanged']."\n";
echo "errors   : ".$preview['errors']."\n";
echo "warnings : ".$preview['warnings']."\n";
echo "conflicts: ".$preview['conflicts']."\n\n";

if (!empty($preview['product_matches'])) {
    echo "== TRÙNG URL CẦN QUYẾT ĐỊNH (".count($preview['product_matches']).") ==\n";
    foreach ($preview['product_matches'] as $match) {
        echo "  {$match['slug']} — file: {$match['incoming_name']} | website #{$match['existing_id']}: {$match['existing_name']}\n";
    }
    echo "\n";
}

if (!empty($preview['new_brands'])) {
    echo "== THƯƠNG HIỆU MỚI CẦN QUYẾT ĐỊNH (".count($preview['new_brands']).") ==\n";
    foreach ($preview['new_brands'] as $brand) {
        echo "  {$brand['name']}: ".count($brand['rows'])." dòng\n";
    }
    echo "\n";
}

$errRows = array();
$warnRows = array();
$conflictRows = array();
$changeRows = array();
foreach ($preview['rows'] as $r) {
    if (!empty($r['errors'])) $errRows[] = $r;
    if (!empty($r['warnings'])) $warnRows[] = $r;
    if (!empty($r['conflicts'])) $conflictRows[] = $r;
    if (!empty($r['changes'])) $changeRows[] = $r;
}

if ($errRows) {
    echo "== DÒNG CÓ LỖI (".count($errRows).") ==\n";
    foreach ($errRows as $r) {
        echo "  dòng {$r['row']} [{$r['action']}] {$r['code']} — {$r['name']}\n";
        foreach ($r['errors'] as $e) echo "     x $e\n";
    }
    echo "\n";
}

if ($warnRows) {
    echo "== CẢNH BÁO (".count($warnRows).") ==\n";
    foreach ($warnRows as $r) {
        echo "  dòng {$r['row']} {$r['code']} — {$r['name']}\n";
        foreach ($r['warnings'] as $w) echo "     ! $w\n";
    }
    echo "\n";
}

if ($conflictRows) {
    echo "== XUNG ĐỘT (website đã đổi so với snapshot; mặc định GIỮ website) ==\n";
    foreach ($conflictRows as $r) {
        echo "  dòng {$r['row']} {$r['code']} — {$r['name']}\n";
        foreach ($r['conflicts'] as $c) {
            echo "     [{$c['label']}]\n";
            echo "        snapshot : ".mb_strimwidth((string)$c['base'], 0, 120, '…')."\n";
            echo "        website  : ".mb_strimwidth((string)$c['current'], 0, 120, '…')."\n";
            echo "        file     : ".mb_strimwidth((string)$c['incoming'], 0, 120, '…')."\n";
        }
    }
    echo "\n";
}

echo "== THAY ĐỔI SẼ ÁP DỤNG (".count($changeRows)." dòng update có diff, chưa tính create) ==\n";
$shown = 0;
foreach ($changeRows as $r) {
    echo "  dòng {$r['row']} #{$r['id']} {$r['code']} — {$r['name']}\n";
    foreach ($r['changes'] as $field => $ch) {
        echo "     {$ch['label']} ({$field}): ".mb_strimwidth((string)$ch['current'], 0, 80, '…')."  ->  ".mb_strimwidth((string)$ch['incoming'], 0, 80, '…')."\n";
    }
    if (++$shown >= 60) { echo "  … (còn ".(count($changeRows) - $shown)." dòng, rút gọn)\n"; break; }
}
echo "\n";

$createRows = array();
foreach ($preview['rows'] as $r) if ($r['action'] === 'create' && empty($r['errors'])) $createRows[] = $r;
if ($createRows) {
    echo "== TẠO MỚI HỢP LỆ (".count($createRows).") ==\n";
    foreach ($createRows as $r) {
        $fields = array_keys($r['data']);
        echo "  dòng {$r['row']} {$r['code']} — {$r['name']} | field: ".implode(',', $fields)."\n";
    }
    echo "\n";
}

echo "KẾT LUẬN: ".($preview['errors'] === 0
    ? "preview HỢP LỆ, có thể commit khi được duyệt."
    : "preview CÓ LỖI, không commit cho tới khi sửa file.")."\n";
echo "Script này chỉ đọc; không ghi database.\n";
