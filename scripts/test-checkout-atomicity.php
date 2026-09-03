<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
define('SOURCES', '/var/www/html/sources/');
define('CHECKOUT_ATOMICITY_FUNCTIONS_ONLY', true);
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cart = new Cart($d);
require SOURCES.'order.php';

function checkoutAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

function checkoutTestOrderData($code, $checkoutToken = null)
{
	$data = array(
        'id_user' => 0,
        'code' => $code,
        'fullname' => 'Checkout atomicity test',
        'phone' => '0900000000',
        'address' => 'Local test only',
        'email' => 'checkout-test@example.invalid',
        'order_payment' => 0,
        'ship_price' => 0,
        'temp_price' => 1000,
        'total_price' => 1000,
        'requirements' => '',
        'date_created' => time(),
        'order_status' => 1,
        'city' => 0,
        'district' => 0,
        'ward' => 0,
        'numb' => 1
    );
	if($checkoutToken !== null) $data['checkout_token'] = $checkoutToken;
	return $data;
}

function checkoutTestCleanup($d, $prefix)
{
    $orders = $d->rawQuery('select id from #_order where code like ?', array($prefix.'%'));
    foreach($orders as $order)
    {
        $d->rawQuery('delete from #_order_detail where id_order = ?', array((int)$order['id']));
        $d->rawQuery('delete from #_order where id = ?', array((int)$order['id']));
    }
}

$prefix = 'CODXAT'.strtoupper(bin2hex(random_bytes(3)));
$beforeOrder = (int)$d->rawQueryOne('select count(*) as total from #_order')['total'];
$beforeDetail = (int)$d->rawQueryOne('select count(*) as total from #_order_detail')['total'];
$products = $d->rawQuery("select id from #_product where type = ? and regular_price > 0 order by id limit 2", array('san-pham'));
checkoutAssert(count($products) === 2, 'Có hai sản phẩm local để test checkout');
$thanksToken = checkoutRegisterThanks(123, 'TEST-CODE');
checkoutAssert(isset($_SESSION['order_thanks'][$thanksToken]) && (int)$_SESSION['order_thanks'][$thanksToken]['id'] === 123, 'Checkout đăng ký trạng thái trang cảm ơn theo order đã xác minh');
unset($_SESSION['order_thanks']);
$items = array(
    array('productid' => (int)$products[0]['id'], 'qty' => 1, 'color' => 0, 'size' => 0, 'code' => 'atomic-1'),
    array('productid' => (int)$products[1]['id'], 'qty' => 2, 'color' => 0, 'size' => 0, 'code' => 'atomic-2')
);

foreach($argv as $argument)
{
    if(strpos($argument, '--fatal-child=') !== 0) continue;
    $fatalCode = substr($argument, strlen('--fatal-child='));
    checkoutPersistOrder($d, $cart, $items, checkoutTestOrderData($fatalCode), 'vi', function($stage, $index) {
        if($stage === 'detail' && $index === 1) exit(23);
        return false;
    });
    exit(24);
}

try
{
    $happyCode = $prefix.'H';
    $happy = checkoutPersistOrder($d, $cart, $items, checkoutTestOrderData($happyCode), 'vi');
    checkoutAssert(!empty($happy['success']) && (int)$happy['detail_count'] === count($items), 'Happy path tạo đủ số dòng chi tiết');
    $happyOrderCount = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($happyCode))['total'];
    $happyDetailCount = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where id_order = ?', array((int)$happy['id']))['total'];
    checkoutAssert($happyOrderCount === 1 && $happyDetailCount === count($items), 'Happy path lưu đúng 1 order và N order_detail');

    $failureCode = $prefix.'F';
    $originalCart = $items;
    $emailCalls = 0;
    $failed = checkoutPersistOrder($d, $cart, $items, checkoutTestOrderData($failureCode), 'vi', function($stage, $index) {
        return $stage === 'detail' && $index === 1;
    });
    if(!empty($failed['success'])) $emailCalls++;
    checkoutAssert(empty($failed['success']) && $items === $originalCart && $emailCalls === 0, 'Detail fail giữ nguyên giỏ và không đi vào nhánh gửi email');
    $failedOrders = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($failureCode))['total'];
    $failedDetails = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where code = ?', array($failureCode))['total'];
    checkoutAssert($failedOrders === 0 && $failedDetails === 0, 'Detail fail xóa bù trừ order và các detail đã chèn');

    $fatalCode = $prefix.'X';
    $fatalOutput = array();
    $fatalExit = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg('--fatal-child='.$fatalCode), $fatalOutput, $fatalExit);
    $fatalOrders = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($fatalCode))['total'];
    $fatalDetails = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where code = ?', array($fatalCode))['total'];
    checkoutAssert($fatalExit === 23 && $fatalOrders === 0 && $fatalDetails === 0, 'Shutdown cleanup bù trừ khi request chết giữa insert detail (exit='.$fatalExit.', orders='.$fatalOrders.', details='.$fatalDetails.')');

    $missingCode = $prefix.'M';
    $missingItems = $items;
    $missingItems[1]['productid'] = 2147483647;
    $missing = checkoutPersistOrder($d, $cart, $missingItems, checkoutTestOrderData($missingCode), 'vi');
    checkoutAssert(empty($missing['success']), 'Thiếu product giữa chừng làm checkout thất bại');
    $missingOrders = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($missingCode))['total'];
    $missingDetails = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where code = ?', array($missingCode))['total'];
    checkoutAssert($missingOrders === 0 && $missingDetails === 0, 'Thiếu product không để lại order hoặc detail rác');

    $doubleCode = $prefix.'D';
    $sessionCart = $items;
    $first = checkoutPersistOrder($d, $cart, $sessionCart, checkoutTestOrderData($doubleCode), 'vi');
    if(!empty($first['success'])) unset($sessionCart);
    $second = !empty($sessionCart) ? checkoutPersistOrder($d, $cart, $sessionCart, checkoutTestOrderData($doubleCode), 'vi') : array('success' => false);
    $doubleOrders = (int)$d->rawQueryOne('select count(*) as total from #_order where code = ?', array($doubleCode))['total'];
    checkoutAssert(!empty($first['success']) && empty($second['success']) && $doubleOrders === 1, 'Double-submit cùng session tạo tối đa một order sau khi cart được xóa');

	$idempotencyCode = $prefix.'I';
	$idempotencyToken = bin2hex(random_bytes(32));
	$idempotencyData = checkoutTestOrderData($idempotencyCode, $idempotencyToken);
	$idempotencyFirst = checkoutPersistOrder($d, $cart, $items, $idempotencyData, 'vi');
	$idempotencyReplay = checkoutPersistOrder($d, $cart, $items, $idempotencyData, 'vi');
	$idempotencyOrders = (int)$d->rawQueryOne('select count(*) as total from #_order where checkout_token = ?', array($idempotencyToken))['total'];
	$idempotencyDetails = (int)$d->rawQueryOne('select count(*) as total from #_order_detail where id_order = ?', array((int)$idempotencyFirst['id']))['total'];
	checkoutAssert(
		!empty($idempotencyFirst['success']) && empty($idempotencyFirst['reused'])
			&& !empty($idempotencyReplay['success']) && !empty($idempotencyReplay['reused'])
			&& (int)$idempotencyReplay['id'] === (int)$idempotencyFirst['id']
			&& $idempotencyReplay['code'] === $idempotencyCode
			&& $idempotencyOrders === 1 && $idempotencyDetails === count($items),
		'Replay cùng checkout token tái sử dụng đúng đơn đã commit và không nhân đôi detail'
	);
}
finally
{
    checkoutTestCleanup($d, $prefix);
}

$afterOrder = (int)$d->rawQueryOne('select count(*) as total from #_order')['total'];
$afterDetail = (int)$d->rawQueryOne('select count(*) as total from #_order_detail')['total'];
$orphans = (int)$d->rawQueryOne('select count(*) as total from #_order_detail d left join #_order o on o.id = d.id_order where o.id is null')['total'];
checkoutAssert($afterOrder === $beforeOrder && $afterDetail === $beforeDetail, 'Test cleanup giữ nguyên tổng order và order_detail');
checkoutAssert($orphans === 0, 'Không có order_detail mồ côi sau test');
