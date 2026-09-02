<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

function commerceTransactionAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

$engines = $d->rawQuery(
    "select table_name, engine from information_schema.tables where table_schema = database() and table_name in (?, ?)",
    array('table_order', 'table_order_detail')
);
$engineMap = array();
foreach($engines as $engine) $engineMap[$engine['table_name']] = strtoupper($engine['engine']);
commerceTransactionAssert(isset($engineMap['table_order']) && $engineMap['table_order'] === 'INNODB', 'table_order dùng InnoDB');
commerceTransactionAssert(isset($engineMap['table_order_detail']) && $engineMap['table_order_detail'] === 'INNODB', 'table_order_detail dùng InnoDB');

$indexes = $d->rawQuery(
    "select distinct index_name from information_schema.statistics where table_schema = database() and table_name = ? and index_name in (?, ?)",
    array('table_order_detail', 'idx_order_detail_order', 'idx_order_detail_product')
);
commerceTransactionAssert(count($indexes) === 2, 'order_detail có index theo order và product');

$code = 'CODXTX'.strtoupper(bin2hex(random_bytes(4)));
$beforeOrder = (int)$d->rawQueryOne('select count(*) as total from #_order')['total'];
$beforeDetail = (int)$d->rawQueryOne('select count(*) as total from #_order_detail')['total'];
$product = $d->rawQueryOne("select id, namevi, regular_price, sale_price from #_product where type = ? order by id limit 1", array('san-pham'));
commerceTransactionAssert(!empty($product), 'Có sản phẩm local để kiểm tra rollback');

$d->startTransaction();
try
{
    $orderId = (int)$d->insert('order', array(
        'code' => $code,
        'fullname' => 'Commerce transaction test',
        'phone' => '0900000000',
        'email' => 'commerce-transaction@example.invalid',
        'date_created' => time(),
        'order_status' => 1
    ));
    commerceTransactionAssert($orderId > 0, 'Insert order trong transaction thành công');
    $detailId = (int)$d->insert('order_detail', array(
        'id_order' => $orderId,
        'id_product' => (int)$product['id'],
        'name' => $product['namevi'],
        'code' => $code,
        'regular_price' => $product['regular_price'],
        'sale_price' => $product['sale_price'],
        'quantity' => 1
    ));
    commerceTransactionAssert($detailId > 0, 'Insert order_detail trong transaction thành công');
    $d->rollback();
}
catch(Exception $e)
{
    try { $d->rollback(); } catch(Exception $rollbackError) {}
    throw $e;
}

$orderLeft = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($code))['total'];
$detailLeft = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where code = ?', array($code))['total'];
$afterOrder = (int)$d->rawQueryOne('select count(*) as total from #_order')['total'];
$afterDetail = (int)$d->rawQueryOne('select count(*) as total from #_order_detail')['total'];
commerceTransactionAssert($orderLeft === 0 && $detailLeft === 0, 'ROLLBACK xóa thật cả order và order_detail');
commerceTransactionAssert($beforeOrder === $afterOrder && $beforeDetail === $afterDetail, 'Test không làm đổi tổng dữ liệu commerce');
