<?php
/**
 * Kiểm thử hai luồng ghi công khai còn lại: form "Đăng ký tư vấn" ở trang chi
 * tiết sản phẩm, và gửi nhận xét sản phẩm.
 *
 * Bổ sung cho test-public-form-submissions.php (Liên hệ + Đăng ký đại lý).
 * Cùng nhau, ba luồng này phủ hết mọi đường khách hàng ghi dữ liệu vào hệ thống.
 *
 * Riêng nhận xét có cơ chế chống spam đáng kiểm tra: chỉ nhận khi mã đơn hàng +
 * số điện thoại + email khớp một đơn CÓ CHỨA đúng sản phẩm đó. Test dựng một
 * đơn hàng fixture để đi được cả hai nhánh — bị từ chối khi thông tin không
 * khớp, và được nhận kèm cờ "verified-purchase" khi khớp.
 *
 * KHÔNG kiểm tra khu vực tài khoản khách (/account/...): route đó bị route tổng
 * quát '[a:sub]/[a:com]' trong libraries/router.php chặn trước nên không bao giờ
 * tới nơi, và site cũng không có link nào trỏ tới. Đây là tính năng chưa dùng,
 * bật lên là quyết định của chủ project chứ không phải việc của test.
 *
 * Mọi dữ liệu test mang tiền tố LEADTEST/REVIEWTEST và được dọn ở cuối.
 *
 * Chạy: php scripts/test-public-lead-and-review.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

$base = 'http://127.0.0.1/';
$passes = 0;
$failures = 0;

function ok($condition, $message)
{
    global $passes, $failures;
    if ($condition) { $passes++; echo 'PASS: ', $message, PHP_EOL; }
    else { $failures++; echo 'FAIL: ', $message, PHP_EOL; }
}

function request($url, $cookie = '', $post = null)
{
    $header = "User-Agent: PTS-Lead-Review-Test\r\n";
    if ($cookie !== '') $header .= 'Cookie: '.$cookie."\r\n";
    $options = array('http' => array('ignore_errors' => true, 'timeout' => 25,
        'method' => $post === null ? 'GET' : 'POST', 'follow_location' => 0));
    if ($post !== null)
    {
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = http_build_query($post);
    }
    $options['http']['header'] = $header;
    $body = @file_get_contents($url, false, stream_context_create($options));
    $status = 0; $newCookie = '';
    foreach ((array)$http_response_header as $line)
    {
        if (preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $m)) $status = (int)$m[1];
        if (stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $m)) $newCookie = $m[1];
    }
    return array('status' => $status, 'body' => (string)$body, 'cookie' => $newCookie);
}

function sessionAndCsrf($base, $path)
{
    $page = request($base.$path);
    $cookie = $page['cookie'];
    if ($cookie === '') return array('', '', $page['body']);
    if (!preg_match('/name="form_csrf"\s+value="([^"]+)"/', $page['body'], $m)) return array($cookie, '', $page['body']);
    return array($cookie, $m[1], $page['body']);
}

function phone() { return '09'.str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT); }

$suffix = bin2hex(random_bytes(4));
$orderId = 0;
$orderCode = 'LEADTEST-'.strtoupper($suffix);
$settingBefore = $d->rawQueryOne('select id, options from #_setting limit 0,1');
$settingOptionsBefore = isset($settingBefore['options']) ? (string)$settingBefore['options'] : '';
$settingChanged = false;
$existingRateLimitIds = array();
foreach ($d->rawQuery("select id from #_user_limit where login_ip like 'form:%'") as $rateRow)
    $existingRateLimitIds[(int)$rateRow['id']] = true;

register_shutdown_function(function () use (&$orderId, &$settingChanged, $settingBefore, $settingOptionsBefore, $existingRateLimitIds, $d, $suffix, $orderCode) {
    $d->rawQuery("delete from #_newsletter where email like ?", array('leadtest-'.$suffix.'%'));
    $d->rawQuery("delete from #_comment where email like ?", array('reviewtest-'.$suffix.'%'));
    if ($orderId)
    {
        $d->rawQuery('delete from #_order_detail where id_order = ?', array($orderId));
        $d->rawQuery('delete from #_order where id = ?', array($orderId));
    }
    $d->rawQuery("delete from #_order where code = ?", array($orderCode));
    if($settingChanged && !empty($settingBefore['id']))
    {
        $d->where('id', (int)$settingBefore['id']);
        $d->update('setting', array('options' => $settingOptionsBefore));
    }
    /* Không giả định REMOTE_ADDR là loopback: chỉ xoá các bucket form được tạo
       sau thời điểm test bắt đầu, giữ nguyên toàn bộ dòng đã có trước đó. */
    foreach ($d->rawQuery("select id from #_user_limit where login_ip like 'form:%'") as $rateRow)
        if (!isset($existingRateLimitIds[(int)$rateRow['id']]))
            $d->rawQuery('delete from #_user_limit where id = ?', array((int)$rateRow['id']));
});

/* Lấy một sản phẩm đang hiển thị để làm đối tượng thật. */
$product = $d->rawQueryOne(
    "select id, slugvi, namevi from #_product where type = 'san-pham' and find_in_set('hienthi',status) order by id desc limit 1"
);
ok(!empty($product['id']), 'Có sản phẩm đang hiển thị để kiểm thử');
if (empty($product['id'])) exit(1);
$productUrl = $product['slugvi'].'/';

/* ------------------------------------------------ ĐĂNG KÝ TƯ VẤN (lead) */

echo PHP_EOL, '== Form Đăng ký tư vấn (trang chi tiết sản phẩm) ==', PHP_EOL;

list($cookie, $csrf, $detailBody) = sessionAndCsrf($base, $productUrl);
ok($cookie !== '', 'Mở trang chi tiết sản phẩm và nhận session');
ok($csrf !== '', 'Trang chi tiết có form_csrf token');
ok(strpos($detailBody, 'submit-newsletter') !== false, 'Trang chi tiết có form đăng ký tư vấn');

$leadEmail = 'leadtest-'.$suffix.'@example.test';
$lead = array(
    'submit-newsletter' => '1',
    'form_csrf' => $csrf,
    'recaptcha_response_newsletter' => '',
    'dataNewsletter' => array(
        'fullname' => 'Lead Test '.$suffix,
        'phone' => phone(),
        'email' => $leadEmail,
        'loaihinh' => 'Ho gia dinh',
        'congsuat' => '5kW',
        'address' => 'Gia Lai'
    )
);
$response = request($base.$productUrl, $cookie, $lead);
ok(in_array($response['status'], array(200, 302), true), 'Nộp form đăng ký tư vấn hợp lệ');

$leadRow = $d->rawQueryOne('select id, fullname, type, congsuat from #_newsletter where email = ? order by id desc limit 1', array($leadEmail));
ok(!empty($leadRow['id']), 'Bản ghi đăng ký tư vấn xuất hiện trong DB');
if (!empty($leadRow['id']))
{
    ok($leadRow['type'] === 'dangkynhantin', 'Gắn đúng loại "dangkynhantin" (phân biệt với đăng ký đại lý)');
    ok(trim($leadRow['congsuat']) === '5kW', 'Công suất khách quan tâm lưu đúng');
}

/* CSRF sai phải bị chặn. */
list($cookie2, ) = sessionAndCsrf($base, $productUrl);
$badEmail = 'leadtest-'.$suffix.'-csrf@example.test';
$badLead = $lead;
$badLead['form_csrf'] = 'sai-token';
$badLead['dataNewsletter']['email'] = $badEmail;
request($base.$productUrl, $cookie2, $badLead);
ok(empty($d->rawQueryOne('select id from #_newsletter where email = ? limit 1', array($badEmail))),
   'CSRF sai thì KHÔNG tạo bản ghi tư vấn');

/* ------------------------------------------------------- NHẬN XÉT SẢN PHẨM */

echo PHP_EOL, '== Gửi nhận xét sản phẩm ==', PHP_EOL;

/* Bài test này kiểm tra nhánh chống spam mạnh, nên phải chủ động bật đúng cờ
   Admin trong DB fixture. Trước đây test luôn kỳ vọng mã sai bị chặn nhưng lại
   phụ thuộc cấu hình local đang bật/tắt, mâu thuẫn với hành vi sản phẩm. Giá
   trị options nguyên bản được khôi phục trong shutdown handler kể cả khi test
   dừng giữa chừng. */
$settingOptionsTest = ($settingOptionsBefore !== '') ? json_decode($settingOptionsBefore, true) : array();
if(!is_array($settingOptionsTest)) $settingOptionsTest = array();
$settingOptionsTest['review_require_order_code'] = '1';
if(!empty($settingBefore['id']))
{
    $d->where('id', (int)$settingBefore['id']);
    $settingChanged = $d->update('setting', array(
        'options' => json_encode($settingOptionsTest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ));
}
ok($settingChanged, 'Bật fixture yêu cầu mã đơn cho nhánh kiểm tra xác minh mua hàng');

$reviewEmail = 'reviewtest-'.$suffix.'@example.test';
$reviewPhone = phone();

list($rCookie, $rCsrf) = sessionAndCsrf($base, $productUrl);
$reviewBase = array(
    'form_csrf' => $rCsrf,
    'dataReview' => array(
        'id_variant' => $product['id'],
        'type' => 'san-pham',
        'star' => 5,
        'title' => 'Nhan xet kiem thu',
        'content' => 'Noi dung nhan xet kiem thu '.$suffix,
        'fullname' => 'Review Test '.$suffix,
        'phone' => $reviewPhone,
        'email' => $reviewEmail
    )
);

/* 1. Chưa có đơn hàng khớp -> phải bị từ chối. */
$noOrder = $reviewBase;
$noOrder['dataReview']['order_code'] = 'KHONG-CO-DON-'.strtoupper($suffix);
$result = request($base.'api/comment.php?get=add', $rCookie, $noOrder);
$payload = json_decode($result['body'], true);
ok(!empty($payload['errors']), 'Nhận xét bị từ chối khi mã đơn hàng không tồn tại');
ok(empty($d->rawQueryOne('select id from #_comment where email = ? limit 1', array($reviewEmail))),
   'Không tạo bản ghi nhận xét khi chưa xác minh mua hàng');

/* 2. Dựng đơn hàng thật chứa đúng sản phẩm này. */
/* Tên cột theo đúng schema thật: total_price/order_status chứ không phải
   total/status, và order_detail dùng regular_price chứ không phải price. */
$d->insert('order', array(
    'code' => $orderCode, 'fullname' => 'Review Test '.$suffix,
    'phone' => $reviewPhone, 'email' => $reviewEmail,
    'address' => 'Gia Lai', 'temp_price' => 1000000, 'total_price' => 1000000,
    'order_status' => 1, 'date_created' => time()
));
$orderId = (int)$d->getLastInsertId();
$d->insert('order_detail', array(
    'id_order' => $orderId, 'id_product' => (int)$product['id'],
    'name' => $product['namevi'], 'regular_price' => 1000000, 'quantity' => 1
));
ok($orderId > 0, 'Dựng được đơn hàng fixture chứa sản phẩm cần nhận xét');

/* 3. Thông tin khớp đơn -> phải được nhận, kèm cờ đã xác minh mua hàng. */
list($rCookie2, $rCsrf2) = sessionAndCsrf($base, $productUrl);
$withOrder = $reviewBase;
$withOrder['form_csrf'] = $rCsrf2;
$withOrder['dataReview']['order_code'] = $orderCode;
$result = request($base.'api/comment.php?get=add', $rCookie2, $withOrder);
$payload = json_decode($result['body'], true);
ok(empty($payload['errors']), 'Nhận xét được chấp nhận khi mã đơn + SĐT + email khớp'
    .(empty($payload['errors']) ? '' : ' -- '.implode('; ', (array)$payload['errors'])));

$reviewRow = $d->rawQueryOne('select id, status, star from #_comment where email = ? order by id desc limit 1', array($reviewEmail));
ok(!empty($reviewRow['id']), 'Bản ghi nhận xét xuất hiện trong DB');
if (!empty($reviewRow['id']))
{
    ok(strpos((string)$reviewRow['status'], 'verified-purchase') !== false,
       'Nhận xét được gắn cờ "verified-purchase"');
    ok(strpos((string)$reviewRow['status'], 'new-admin') !== false,
       'Nhận xét chờ Admin duyệt, không tự hiện ra trang');
    ok((int)$reviewRow['star'] === 5, 'Số sao lưu đúng');
}

/* 4. Sai số điện thoại tuy đúng mã đơn -> vẫn phải bị từ chối. */
$wrongEmail = 'reviewtest-'.$suffix.'-wrong@example.test';
list($rCookie3, $rCsrf3) = sessionAndCsrf($base, $productUrl);
$wrongPhone = $reviewBase;
$wrongPhone['form_csrf'] = $rCsrf3;
$wrongPhone['dataReview']['order_code'] = $orderCode;
$wrongPhone['dataReview']['phone'] = phone();
$wrongPhone['dataReview']['email'] = $wrongEmail;
$result = request($base.'api/comment.php?get=add', $rCookie3, $wrongPhone);
$payload = json_decode($result['body'], true);
ok(!empty($payload['errors']), 'Đúng mã đơn nhưng sai SĐT/email vẫn bị từ chối');

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
