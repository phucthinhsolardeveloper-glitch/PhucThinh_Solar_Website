<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();

function pdodbAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

$d = new PDODb($config['database']);
$caught = null;
$d->startTransaction();
try {
    $d->rawQuery('select * from #_table_that_does_not_exist where id = ?', array(1));
} catch(PDODbException $e) {
    $caught = $e;
    $d->rollback();
}

pdodbAssert($caught instanceof PDODbException, 'Lỗi SQL được ném thành PDODbException thay vì die');
pdodbAssert($caught->getOperation() === 'SQL Prepare Error', 'Exception giữ loại thao tác để controller/log xử lý');
pdodbAssert(strpos($caught->getQueryTemplate(), '?') !== false && strpos($caught->getQueryTemplate(), '1') === false, 'Chẩn đoán chỉ giữ query template, không ghép giá trị tham số');
pdodbAssert(strpos($caught->getMessage(), 'table_that_does_not_exist') === false, 'Thông báo công khai không lộ SQL hoặc lỗi driver');
pdodbAssert((int)$d->rawQueryOne('select 1 as healthy')['healthy'] === 1, 'Kết nối DB dùng lại được sau khi catch và rollback');

$argumentCaught = false;
try {
    $d->inc('khong-phai-so');
} catch(PDODbException $e) {
    $argumentCaught = $e->getOperation() === 'Generates Incremental';
}
pdodbAssert($argumentCaught, 'Lỗi tham số query builder cũng có thể được catch');

echo "PDODb exception tests completed.\n";
