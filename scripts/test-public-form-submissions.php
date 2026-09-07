<?php
/**
 * Kiểm thử hai form công khai quan trọng nhất cho việc kinh doanh: Liên hệ và
 * Đăng ký làm đại lý. Cả hai đều không được bộ quét Admin hay các test hiện có
 * chạy qua đường submit thật (chỉ có test kiểm tra form tồn tại + CSRF, không
 * xác nhận dữ liệu thực sự ghi được vào DB qua đúng luồng public).
 *
 * Kiểm tra:
 *   - Nộp hợp lệ -> có bản ghi mới trong DB, đúng nội dung đã gửi.
 *   - Thiếu trường bắt buộc -> KHÔNG tạo bản ghi (validation phía server chạy đúng).
 *   - CSRF sai -> bị từ chối, không tạo bản ghi.
 *   - Giới hạn tần suất (rate limit) chặn đúng sau ngưỡng cho phép.
 *
 * Trên local, googleAPI.recaptcha.active = false nên checkRecaptcha() trả về
 * chế độ test, không cần giải captcha thật — xem class.Functions.php.
 *
 * Dọn dẹp: xoá đúng các bản ghi contact/newsletter do test tạo (nhận theo tiền
 * tố duy nhất), và xoá đúng các dòng rate-limit phát sinh sau khi test bắt đầu,
 * không đụng dữ liệu rate-limit đã tồn tại trước đó.
 *
 * Chạy: php scripts/test-public-form-submissions.php
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
    $header = "User-Agent: PTS-Public-Form-Test\r\n";
    if ($cookie !== '') $header .= 'Cookie: '.$cookie."\r\n";
    $options = array('http' => array('ignore_errors' => true, 'timeout' => 20,
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

/* Mở một trang public để lấy cookie phiên + form_csrf token thật, thay vì tự
   bịa token — token gắn với session nên phải lấy đúng từ trang vừa mở. */
function sessionAndCsrf($base, $path)
{
    $page = request($base.$path);
    $cookie = $page['cookie'];
    if ($cookie === '') return array('', '');
    if (!preg_match('/name="form_csrf"\s+value="([^"]+)"/', $page['body'], $m)) return array($cookie, '');
    return array($cookie, $m[1]);
}

$suffix = bin2hex(random_bytes(4));
$createdContactIds = array();
$createdNewsletterIds = array();
$existingRateLimitIds = array();
foreach ($d->rawQuery("select id from #_user_limit where login_ip like 'form:%'") as $rateRow)
    $existingRateLimitIds[(int)$rateRow['id']] = true;

register_shutdown_function(function () use (&$createdContactIds, &$createdNewsletterIds, $existingRateLimitIds, $d, $suffix) {
    foreach ($createdContactIds as $id) $d->rawQuery('delete from #_contact where id = ?', array((int)$id));
    foreach ($createdNewsletterIds as $id) $d->rawQuery('delete from #_newsletter where id = ?', array((int)$id));
    $d->rawQuery("delete from #_contact where email like ?", array('form-test-'.$suffix.'%'));
    $d->rawQuery("delete from #_newsletter where email like ?", array('form-test-'.$suffix.'%'));
    /* REMOTE_ADDR của Apache có thể là gateway Docker, nên nhận diện theo ID
       mới thay vì giả định request luôn đến từ 127.0.0.1. */
    foreach ($d->rawQuery("select id from #_user_limit where login_ip like 'form:%'") as $rateRow)
        if (!isset($existingRateLimitIds[(int)$rateRow['id']]))
            $d->rawQuery('delete from #_user_limit where id = ?', array((int)$rateRow['id']));
});

/* ------------------------------------------------------------------ LIÊN HỆ */

echo '== Form Liên hệ ==', PHP_EOL;

list($cookie, $csrf) = sessionAndCsrf($base, 'lien-he/');
ok($cookie !== '', 'Mở trang Liên hệ và nhận session');
ok($csrf !== '', 'Trang Liên hệ có form_csrf token');

$contactEmail = 'form-test-'.$suffix.'@example.test';
$validContact = array(
    'submit-contact' => '1',
    'form_csrf' => $csrf,
    'recaptcha_response_contact' => '',
    'dataContact' => array(
        'fullname' => 'Form Test '.$suffix,
        'phone' => '09' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        'email' => $contactEmail,
        'subject' => 'Kiem thu tu dong',
        'content' => 'Noi dung kiem thu tu dong '.$suffix
    )
);

$before = (int)$d->rawQueryOne("select count(*) as n from #_contact")['n'];
$response = request($base.'lien-he/', $cookie, $validContact);
ok(in_array($response['status'], array(200, 302), true), 'Nộp form Liên hệ hợp lệ');
$contactDestination = request($base.'lien-he/', $cookie);
$contactToast = preg_match('/var\s+FLASH_TOAST\s*=\s*(\{[^;]*\});/', $contactDestination['body'], $contactToastMatch)
    ? json_decode($contactToastMatch[1], true) : null;
ok(is_array($contactToast) && $contactToast['status'] === 'success' && strpos($contactToast['text'], 'Gửi liên hệ thành công') !== false, 'Nộp Liên hệ hợp lệ hiện toast thành công trên trang đích');

$row = $d->rawQueryOne('select id, fullname, phone, subject from #_contact where email = ? order by id desc limit 1', array($contactEmail));
ok(!empty($row['id']), 'Bản ghi liên hệ mới xuất hiện trong DB');
if (!empty($row['id']))
{
    $createdContactIds[] = (int)$row['id'];
    ok(trim($row['fullname']) === 'Form Test '.$suffix, 'Họ tên lưu đúng như đã gửi');
    ok(trim($row['subject']) === 'Kiem thu tu dong', 'Chủ đề lưu đúng như đã gửi');
}

/* Thiếu trường bắt buộc (subject) -> không được tạo bản ghi. */
list($cookie2, $csrf2) = sessionAndCsrf($base, 'lien-he/');
$missingEmail = 'form-test-'.$suffix.'-missing@example.test';
$invalidContact = $validContact;
$invalidContact['form_csrf'] = $csrf2;
$invalidContact['dataContact']['email'] = $missingEmail;
$invalidContact['dataContact']['subject'] = '';
request($base.'lien-he/', $cookie2, $invalidContact);
$missingRow = $d->rawQueryOne('select id from #_contact where email = ? limit 1', array($missingEmail));
ok(empty($missingRow), 'Thiếu chủ đề thì KHÔNG tạo bản ghi liên hệ');

/* CSRF sai -> không được tạo bản ghi. */
list($cookie3, ) = sessionAndCsrf($base, 'lien-he/');
$csrfEmail = 'form-test-'.$suffix.'-csrf@example.test';
$badCsrfContact = $validContact;
$badCsrfContact['form_csrf'] = 'sai-token-hoan-toan';
$badCsrfContact['dataContact']['email'] = $csrfEmail;
request($base.'lien-he/', $cookie3, $badCsrfContact);
$csrfRow = $d->rawQueryOne('select id from #_contact where email = ? limit 1', array($csrfEmail));
ok(empty($csrfRow), 'CSRF sai thì KHÔNG tạo bản ghi liên hệ');

/* Giới hạn tần suất: form_security.php cho phép 5 lần / 600 giây theo IP.
   Đã dùng 1 lần hợp lệ + 2 lần bị chặn ở trên đều tính vào bộ đếm vì
   formPersistentRateLimit() tăng đếm trước khi biết dữ liệu có hợp lệ hay
   không. Gửi thêm cho đủ ngưỡng rồi xác nhận lần vượt ngưỡng bị chặn hẳn. */
$rateLimitEmail = '';
for ($i = 0; $i < 4; $i++)
{
    list($rCookie, $rCsrf) = sessionAndCsrf($base, 'lien-he/');
    $rateLimitEmail = 'form-test-'.$suffix.'-rl'.$i.'@example.test';
    $attempt = $validContact;
    $attempt['form_csrf'] = $rCsrf;
    $attempt['dataContact']['email'] = $rateLimitEmail;
    request($base.'lien-he/', $rCookie, $attempt);
}
$beforeOverLimitCount = (int)$d->rawQueryOne("select count(*) as n from #_contact where email like ?", array('form-test-'.$suffix.'%'))['n'];
list($overCookie, $overCsrf) = sessionAndCsrf($base, 'lien-he/');
$overLimitEmail = 'form-test-'.$suffix.'-overlimit@example.test';
$overLimitAttempt = $validContact;
$overLimitAttempt['form_csrf'] = $overCsrf;
$overLimitAttempt['dataContact']['email'] = $overLimitEmail;
request($base.'lien-he/', $overCookie, $overLimitAttempt);
$overLimitRow = $d->rawQueryOne('select id from #_contact where email = ? limit 1', array($overLimitEmail));
ok(empty($overLimitRow), 'Vượt ngưỡng 5 lần/600 giây thì bị chặn, không tạo thêm bản ghi');

foreach ($d->rawQuery("select id from #_contact where email like ?", array('form-test-'.$suffix.'%')) as $leftover)
    $createdContactIds[] = (int)$leftover['id'];

/* -------------------------------------------------------------- ĐẠI LÝ */

echo PHP_EOL, '== Form Đăng ký đại lý ==', PHP_EOL;

list($dealerCookie, $dealerCsrf) = sessionAndCsrf($base, 'dai-ly/');
ok($dealerCookie !== '', 'Mở trang Đại lý và nhận session');
ok($dealerCsrf !== '', 'Trang Đại lý có form_csrf token');

$dealerEmail = 'form-test-'.$suffix.'-dealer@example.test';
$dealerSubmit = array(
    'submit-newsletter2' => '1',
    'form_csrf' => $dealerCsrf,
    'recaptcha_response_newsletter2' => '',
    'dataNewsletter' => array(
        'fullname' => 'Dai ly Test '.$suffix,
        'phone' => '09' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        'email' => $dealerEmail,
        'address' => 'Ho Chi Minh',
        'company' => 'Cong ty kiem thu '.$suffix
    )
);
$dealerResponse = request($base.'dai-ly/', $dealerCookie, $dealerSubmit);
ok(in_array($dealerResponse['status'], array(200, 302), true), 'Nộp form đăng ký đại lý hợp lệ');
$dealerDestination = request($base.'dai-ly/', $dealerCookie);
$dealerToast = preg_match('/var\s+FLASH_TOAST\s*=\s*(\{[^;]*\});/', $dealerDestination['body'], $dealerToastMatch)
    ? json_decode($dealerToastMatch[1], true) : null;
ok(is_array($dealerToast) && $dealerToast['status'] === 'success' && strpos($dealerToast['text'], 'Đăng ký đại lý thành công') !== false, 'Nộp Đại lý hợp lệ hiện toast thành công trên trang đích');

$dealerRow = $d->rawQueryOne("select id, fullname, company, type from #_newsletter where email = ? order by id desc limit 1", array($dealerEmail));
ok(!empty($dealerRow['id']), 'Bản ghi đăng ký đại lý mới xuất hiện trong DB');
if (!empty($dealerRow['id']))
{
    $createdNewsletterIds[] = (int)$dealerRow['id'];
    ok($dealerRow['type'] === 'dangkynhantin2', 'Bản ghi được gắn đúng loại "dangkynhantin2" (phân biệt với tư vấn thường)');
    ok(trim($dealerRow['company']) === 'Cong ty kiem thu '.$suffix, 'Tên công ty lưu đúng như đã gửi');
}

/* CSRF sai cho form đại lý cũng phải bị chặn. */
list($dealerCookie2, ) = sessionAndCsrf($base, 'dai-ly/');
$dealerCsrfEmail = 'form-test-'.$suffix.'-dealer-csrf@example.test';
$badDealerSubmit = $dealerSubmit;
$badDealerSubmit['form_csrf'] = 'sai-token-hoan-toan';
$badDealerSubmit['dataNewsletter']['email'] = $dealerCsrfEmail;
request($base.'dai-ly/', $dealerCookie2, $badDealerSubmit);
$dealerCsrfRow = $d->rawQueryOne('select id from #_newsletter where email = ? limit 1', array($dealerCsrfEmail));
ok(empty($dealerCsrfRow), 'CSRF sai thì KHÔNG tạo bản ghi đăng ký đại lý');

foreach ($d->rawQuery("select id from #_newsletter where email like ?", array('form-test-'.$suffix.'%')) as $leftover)
    $createdNewsletterIds[] = (int)$leftover['id'];

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
