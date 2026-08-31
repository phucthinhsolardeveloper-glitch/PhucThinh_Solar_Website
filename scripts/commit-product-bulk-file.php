<?php
/**
 * Apply a real product bulk workbook to the LOCAL database.
 * Mirrors administrator/sources/product_bulk.php :: productBulkCommit()
 * (parse -> validate -> single transaction -> insert/update + seo -> commit -> cache flush),
 * without the CSRF / session / "CAP_NHAT" UI gate.
 *
 * Refuses to run unless argv contains --commit.
 *
 * Usage (inside PHP 7.4 container):
 *   php /tmp/commit-product-bulk-file.php /tmp/wb.xlsx --commit
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
$doCommit = in_array('--commit', $argv, true);
if ($path === '' || !is_file($path)) { fwrite(STDERR, "File not found: $path\n"); exit(1); }

$before = (int) $d->rawQueryOne("select count(*) c from #_product where type = ?", array($type))['c'];
echo "Sản phẩm hiện có ($type): $before\n";

$file = array(
    'name' => basename($path),
    'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'tmp_name' => $path,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($path),
);

$parsed = productBulkReadUpload($file);
if (!empty($parsed['errors'])) { echo "PARSE ERRORS:\n"; foreach ($parsed['errors'] as $e) echo " - $e\n"; exit(2); }

$preview = productBulkValidateRows($parsed['rows'], $type, $parsed['baseline']);
echo "Preview: create={$preview['counts']['create']} update={$preview['counts']['update']} archive={$preview['counts']['archive']} unchanged={$preview['counts']['unchanged']} errors={$preview['errors']} conflicts={$preview['conflicts']}\n";
if ($preview['errors'] > 0) { echo "CÓ LỖI — dừng, không ghi gì.\n"; exit(3); }

if (!$doCommit) { echo "\n(dry-run) Thêm --commit để ghi database.\n"; exit(0); }

$applied = array();
$createdIds = array();
$updatedIds = array();
$transactionStarted = false;
try {
    $d->startTransaction();
    $transactionStarted = true;
    foreach ($preview['rows'] as $row) {
        if ($row['action'] === 'skip' || empty($row['data'])) continue;
        $saveData = productBulkPrepareDataForSave($row['data']);
        if ($row['action'] === 'create') {
            if (!$d->insert('product', $saveData)) throw new Exception('Không thể tạo sản phẩm tại dòng '.$row['row']);
            $id = (int) $d->getLastInsertId();
            $applied[] = array('action' => 'create', 'id' => $id, 'before' => null);
            $createdIds[] = $id;
            $seo = array('id_parent' => $id, 'com' => 'product', 'act' => 'man', 'type' => $type);
            if (!$d->insert('seo', $seo)) throw new Exception('Không thể tạo SEO cho sản phẩm tại dòng '.$row['row']);
        } else {
            $applied[] = array('action' => $row['action'], 'id' => $row['id'], 'before' => $row['existing']);
            $updatedIds[] = $row['id'];
            $d->where('id', $row['id']);
            $d->where('type', $type);
            if (!$d->update('product', $saveData)) throw new Exception('Không thể cập nhật sản phẩm tại dòng '.$row['row']);
        }
    }
    $d->commit();
    $transactionStarted = false;
    $cache->delete();
    echo "\nĐÃ GHI THÀNH CÔNG.\n";
    echo "Tạo mới: ".count($createdIds)." (id ".(count($createdIds) ? min($createdIds).'..'.max($createdIds) : '-').")\n";
    echo "Cập nhật: ".implode(', ', $updatedIds)."\n";
} catch (Exception $e) {
    if ($transactionStarted) $d->rollback();
    productBulkRollback($applied, $type);
    echo "\nLỖI: ".$e->getMessage()."\nĐã rollback toàn bộ lượt này.\n";
    exit(4);
}

$after = (int) $d->rawQueryOne("select count(*) c from #_product where type = ?", array($type))['c'];
echo "Sản phẩm sau commit ($type): $after (chênh lệch ".($after - $before).")\n";
