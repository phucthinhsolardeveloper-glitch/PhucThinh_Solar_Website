<?php
$_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
$webroot = getenv('PTS_WEBROOT') ?: '/var/www/html';
$libraries = rtrim($webroot, '/').'/libraries/';
if(!is_file($libraries.'config.php')) {
    fwrite(STDERR, "BLOCKED: Không tìm thấy webroot. Đặt PTS_WEBROOT tới document root của website.\n");
    exit(2);
}

define('LIBRARIES', $libraries);
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

function commercePreflightValue($d, $sql, $params = array())
{
    $row = $d->rawQueryOne($sql, $params);
    return $row ? (int)reset($row) : 0;
}

function commercePreflightCheck($ok, $label, $detail, &$blockers)
{
    echo ($ok ? 'PASS' : 'BLOCKED').': '.$label.' — '.$detail.PHP_EOL;
    if(!$ok) $blockers++;
}

$blockers = 0;
$schema = $config['database']['dbname'];
$tables = array();
foreach($d->rawQuery("select table_name, engine, table_rows, data_length, index_length from information_schema.tables where table_schema = ? and table_name in (?, ?)", array($schema, 'table_order', 'table_order_detail')) as $row) {
    $tables[$row['table_name']] = $row;
}

commercePreflightCheck(isset($tables['table_order']), 'Bảng đơn hàng tồn tại', 'table_order', $blockers);
commercePreflightCheck(isset($tables['table_order_detail']), 'Bảng chi tiết đơn tồn tại', 'table_order_detail', $blockers);
if($blockers) exit(2);

$orderCount = commercePreflightValue($d, 'select count(*) as total from #_order');
$detailCount = commercePreflightValue($d, 'select count(*) as total from #_order_detail');
$orphanOrders = commercePreflightValue($d, 'select count(*) as total from #_order_detail od left join #_order o on o.id = od.id_order where od.id_order > 0 and o.id is null');
$orphanProducts = commercePreflightValue($d, 'select count(*) as total from #_order_detail od left join #_product p on p.id = od.id_product where od.id_product > 0 and p.id is null');
commercePreflightCheck($orphanOrders === 0, 'Không có chi tiết mồ côi theo đơn', (string)$orphanOrders, $blockers);
commercePreflightCheck($orphanProducts === 0, 'Không có chi tiết mồ côi theo sản phẩm', (string)$orphanProducts, $blockers);

$tokenColumn = commercePreflightValue($d, "select count(*) as total from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?", array($schema, 'table_order', 'checkout_token')) > 0;
$tokenIndex = commercePreflightValue($d, "select count(*) as total from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? and non_unique = 0", array($schema, 'table_order', 'ux_order_checkout_token')) > 0;
$orderIndex = commercePreflightValue($d, "select count(*) as total from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?", array($schema, 'table_order_detail', 'idx_order_detail_order')) > 0;
$productIndex = commercePreflightValue($d, "select count(*) as total from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?", array($schema, 'table_order_detail', 'idx_order_detail_product')) > 0;

$duplicateTokens = 0;
if($tokenColumn) $duplicateTokens = commercePreflightValue($d, "select count(*) as total from (select checkout_token from #_order where checkout_token is not null and checkout_token <> '' group by checkout_token having count(*) > 1) duplicated");
commercePreflightCheck($duplicateTokens === 0, 'Không có checkout token trùng', (string)$duplicateTokens, $blockers);

$orderInnoDb = strtoupper($tables['table_order']['engine']) === 'INNODB';
$detailInnoDb = strtoupper($tables['table_order_detail']['engine']) === 'INNODB';
$migration008Applied = $orderInnoDb && $detailInnoDb && $orderIndex && $productIndex;
$migration008Pending = !$orderInnoDb && !$detailInnoDb && !$orderIndex && !$productIndex;
$migration009Applied = $tokenColumn && $tokenIndex;
$migration009Pending = !$tokenColumn && !$tokenIndex;
commercePreflightCheck($migration008Applied || $migration008Pending, 'Migration 008 không ở trạng thái dở dang', $migration008Applied ? 'đã áp dụng đủ' : ($migration008Pending ? 'chưa áp dụng' : 'engine/index không đồng bộ'), $blockers);
commercePreflightCheck($migration009Applied || $migration009Pending, 'Migration 009 không ở trạng thái dở dang', $migration009Applied ? 'đã áp dụng đủ' : ($migration009Pending ? 'chưa áp dụng' : 'cột/index không đồng bộ'), $blockers);
commercePreflightCheck(!$migration009Applied || $migration008Applied, 'Đúng thứ tự migration commerce', $migration009Applied && !$migration008Applied ? '009 đã áp dụng trước khi 008 hoàn tất' : '008 trước 009', $blockers);

echo 'INFO: table_order engine='.$tables['table_order']['engine'].'; rows='.$orderCount.PHP_EOL;
echo 'INFO: table_order_detail engine='.$tables['table_order_detail']['engine'].'; rows='.$detailCount.PHP_EOL;
echo 'INFO: migration 008='.($migration008Applied ? 'APPLIED' : ($migration008Pending ? 'PENDING' : 'PARTIAL')).PHP_EOL;
echo 'INFO: migration 009='.($migration009Applied ? 'APPLIED' : ($migration009Pending ? 'PENDING' : 'PARTIAL')).PHP_EOL;

if($blockers > 0) {
    echo 'KẾT LUẬN: BLOCKED ('.$blockers.' điều kiện). Không chạy migration hoặc deploy source checkout.'.PHP_EOL;
    exit(2);
}
echo 'KẾT LUẬN: PREFLIGHT PASS. Vẫn phải backup/restore-check và dừng checkout trước khi ALTER.'.PHP_EOL;
