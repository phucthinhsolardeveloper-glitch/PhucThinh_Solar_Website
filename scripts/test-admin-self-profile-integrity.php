<?php
/**
 * Kiểm thử màn hình "tự sửa hồ sơ" của nhân viên (info_admin) không cho lách để
 * chiếm username của tài khoản khác.
 *
 * Lỗ hổng đã vá: màn hình này nhận $_POST['id'] rồi truyền vào checkAccount()
 * làm điều kiện loại trừ khi kiểm tra trùng username/email. Nhân viên (role 1)
 * gửi id của tài khoản admin kèm username = "admin" thì phần kiểm tra loại trừ
 * đúng tài khoản admin thật, không thấy trùng, và cho đổi — tạo ra hai tài
 * khoản cùng tên. Toàn bộ hệ thống định danh theo username nên đây là lỗi phá
 * vỡ toàn vẹn tài khoản.
 *
 * Test này đóng vai kẻ tấn công đúng như kịch bản đó và khẳng định bị chặn, đồng
 * thời xác nhận việc tự sửa hồ sơ hợp lệ vẫn chạy.
 *
 * Chạy được cả khi cổng phân quyền đang tắt: info_admin là màn hình tự phục vụ,
 * role 1 luôn vào được, nên lỗ hổng không phụ thuộc trạng thái phân quyền.
 *
 * Chạy: php scripts/test-admin-self-profile-integrity.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$base = 'http://127.0.0.1/administrator/';
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
    $header = "User-Agent: PTS-Profile-Integrity\r\n";
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
    $cookieOut = '';
    foreach ((array)$http_response_header as $line)
        if (stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $m)) $cookieOut = $m[1];
    return array('body' => (string)$body, 'cookie' => $cookieOut);
}

$victimName = 'victim'.bin2hex(random_bytes(4));
$attackerName = 'attacker'.bin2hex(random_bytes(4));
$password = bin2hex(random_bytes(12));
$victimId = 0;
$attackerId = 0;

register_shutdown_function(function () use (&$victimId, &$attackerId, $d) {
    foreach (array($victimId, $attackerId) as $id)
        if ($id) { $d->rawQuery('delete from #_user_log where id_user = ?', array($id)); $d->rawQuery('delete from #_user where id = ?', array($id)); }
});

/* Nạn nhân: một tài khoản có username riêng cần được bảo vệ. */
$d->insert('user', array(
    'id_permission' => 0, 'username' => $victimName,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Victim', 'email' => $victimName.'@example.test', 'status' => 'hienthi',
    'role' => 3, 'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0,
    'gender' => 1, 'birthday' => 0, 'address' => 'x', 'phone' => '0912345678'
));
$victimId = (int)$d->getLastInsertId();

/* Kẻ tấn công: nhân viên role 1 thường. */
$d->insert('user', array(
    'id_permission' => 0, 'username' => $attackerName,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Attacker', 'email' => $attackerName.'@example.test', 'status' => 'hienthi',
    'role' => 1, 'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0,
    'gender' => 1, 'birthday' => 0, 'address' => 'x', 'phone' => '0912345678'
));
$attackerId = (int)$d->getLastInsertId();

$page = request($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = request($base.'index.php?com=user&act=login', $cookie);
if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrf)) { fwrite(STDERR, "Không lấy được CSRF.\n"); exit(1); }
$login = request($base.'api/login.php', $cookie,
    array('username' => $attackerName, 'password' => $password, 'admin_csrf' => $csrf[1]));
if ($login['cookie'] !== '') $cookie = $login['cookie'];
ok(!empty(json_decode($login['body'], true)['success']), 'Kẻ tấn công (role 1) đăng nhập được');

$form = request($base.'index.php?com=user&act=info_admin', $cookie);
ok(stripos($form['body'], 'không có quyền') === false, 'Nhân viên vào được màn hình tự sửa hồ sơ');
preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $form['body'], $formCsrf);

/* KHAI THÁC: gửi id của nạn nhân để checkAccount loại trừ đúng nạn nhân, kèm
   username = username nạn nhân. Email mới để không tự đụng chính mình. */
$attack = request($base.'index.php?com=user&act=info_admin', $cookie, array(
    'admin_csrf' => isset($formCsrf[1]) ? $formCsrf[1] : '',
    'id' => $victimId,
    'data' => array(
        'username' => $victimName,
        'fullname' => 'Attacker',
        'email' => 'fresh'.bin2hex(random_bytes(3)).'@example.test',
        'phone' => '0912345678', 'address' => 'x', 'gender' => 1, 'birthday' => '01/01/1990'
    )
));

$attackerRow = $d->rawQueryOne('select username from #_user where id = ?', array($attackerId));
ok($attackerRow['username'] === $attackerName, 'Kẻ tấn công KHÔNG đổi được username của mình thành tên nạn nhân');

$count = $d->rawQueryOne('select count(*) as n from #_user where username = ?', array($victimName));
ok((int)$count['n'] === 1, 'Chỉ còn đúng một tài khoản mang username của nạn nhân');

/* Tự sửa hồ sơ hợp lệ vẫn phải chạy. */
$formAgain = request($base.'index.php?com=user&act=info_admin', $cookie);
preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $formAgain['body'], $csrfAgain);
request($base.'index.php?com=user&act=info_admin', $cookie, array(
    'admin_csrf' => isset($csrfAgain[1]) ? $csrfAgain[1] : '',
    'data' => array(
        'username' => $attackerName,
        'fullname' => 'Ho So Da Sua',
        'email' => $attackerName.'@example.test',
        'phone' => '0912345678', 'address' => 'dia chi moi', 'gender' => 1, 'birthday' => '01/01/1990'
    )
));
$afterEdit = $d->rawQueryOne('select fullname, address from #_user where id = ?', array($attackerId));
ok(trim($afterEdit['fullname']) === 'Ho So Da Sua' && trim($afterEdit['address']) === 'dia chi moi',
   'Nhân viên vẫn tự sửa được hồ sơ chính mình');

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
