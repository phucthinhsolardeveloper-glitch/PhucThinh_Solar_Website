<?php
/**
 * Kiểm chứng cổng phân quyền Admin bằng HTTP thật, không tin cấu hình suông.
 *
 * Tạo hai tài khoản fixture dùng một lần:
 *   - staff  : role 1, thuộc nhóm quyền chỉ có product_man_san-pham
 *   - nobody : role 1, không thuộc nhóm quyền nào
 * rồi gọi thẳng URL để xác nhận cổng chặn đúng chỗ.
 *
 * Bật $config['permission']['active'] tạm thời trong chính tiến trình test
 * không đủ, vì mỗi request HTTP là một tiến trình PHP riêng đọc lại config.
 * Mọi request test gửi header X-PTS-Permission-Test: enforce; config-type.php
 * chỉ nhận header này khi APP_ENV=local và tắt debug-developer cho request đó.
 *
 * Chạy: php scripts/test-admin-permission-gate.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
/* permission.active nằm trong config-type.php chứ không phải config.php. */
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$base = 'http://127.0.0.1/administrator/';
$failures = 0;
$passes = 0;

function ok($condition, $message)
{
    global $failures, $passes;
    if ($condition) { $passes++; echo "PASS: ", $message, PHP_EOL; }
    else { $failures++; echo "FAIL: ", $message, PHP_EOL; }
}

function request($url, $cookie = '', $post = null)
{
    $header = "User-Agent: PTS-Permission-Gate\r\nX-PTS-Permission-Test: enforce\r\n";
    if ($cookie !== '') $header .= "Cookie: ".$cookie."\r\n";
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

function login($base, $username, $password)
{
    $page = request($base.'index.php?com=user&act=login');
    $cookie = $page['cookie'];
    $page = request($base.'index.php?com=user&act=login', $cookie);
    if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrf)) return '';
    $result = request($base.'api/login.php', $cookie,
        array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf[1]));
    if ($result['cookie'] !== '') $cookie = $result['cookie'];
    $payload = json_decode($result['body'], true);
    return empty($payload['success']) ? '' : $cookie;
}

if (empty($config['permission']['active']))
{
    echo "FAIL: \$config['permission']['active'] đang tắt nên không thể kiểm chứng cổng phân quyền.", PHP_EOL;
    exit(1);
}
if (!empty($config['website']['debug-developer']) && getenv('APP_ENV') !== 'local')
{
    echo "FAIL: debug-developer đang bật ngoài local nên mọi kiểm tra quyền bị bỏ qua.", PHP_EOL;
    exit(1);
}
if (!empty($config['website']['debug-developer']))
{
    echo "INFO: Request HTTP dùng chế độ ép kiểm tra quyền dành riêng cho APP_ENV=local.", PHP_EOL;
}

$suffix = bin2hex(random_bytes(4));
$groupName = 'GATE-'.$suffix;
$staffName = 'gate-staff-'.$suffix;
$nobodyName = 'gate-nobody-'.$suffix;
$password = bin2hex(random_bytes(12));
$groupId = 0; $staffId = 0; $nobodyId = 0;

register_shutdown_function(function () use (&$groupId, &$staffId, &$nobodyId, $d) {
    foreach (array($staffId, $nobodyId) as $id)
        if ($id) { $d->rawQuery('delete from #_user_log where id_user = ?', array($id)); $d->rawQuery('delete from #_user where id = ?', array($id)); }
    if ($groupId)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array($groupId));
        $d->rawQuery('delete from #_permission_group where id = ?', array($groupId));
    }
});

$d->insert('permission_group', array('name' => $groupName, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
$groupId = (int)$d->getLastInsertId();
$d->insert('permission', array('id_permission_group' => $groupId, 'permission' => 'product_man_san-pham'));

foreach (array(array($staffName, $groupId, 'staffId'), array($nobodyName, 0, 'nobodyId')) as $spec)
{
    $d->insert('user', array(
        'id_permission' => $spec[1], 'username' => $spec[0],
        'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
        'fullname' => 'Permission gate test', 'status' => 'hienthi', 'role' => 1,
        'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
    ));
    ${$spec[2]} = (int)$d->getLastInsertId();
}

$staffCookie = login($base, $staffName, $password);
ok($staffCookie !== '', 'Đăng nhập tài khoản staff');
$nobodyCookie = login($base, $nobodyName, $password);
ok($nobodyCookie !== '', 'Đăng nhập tài khoản không nhóm quyền');
if ($staffCookie === '' || $nobodyCookie === '') { echo "Không đăng nhập được, dừng.", PHP_EOL; exit(1); }

$denied = function ($response) {
    return $response['status'] === 302
        || stripos($response['body'], 'không có quyền') !== false
        || stripos($response['body'], 'quản trị cấp cao') !== false;
};

/* 1. Quyền được cấp thì vào được. */
$r = request($base.'index.php?com=product&act=man&type=san-pham', $staffCookie);
ok(!$denied($r) && $r['status'] === 200, 'Staff có product_man_san-pham thì mở được danh sách sản phẩm');

/* 2. Quyền không được cấp thì bị chặn. */
$r = request($base.'index.php?com=news&act=man&type=tin-tuc', $staffCookie);
ok($denied($r), 'Staff không có quyền tin tức thì bị chặn');

/* 3. Quản lý tài khoản chỉ dành cho role 3. */
$r = request($base.'index.php?com=user&act=man_admin', $staffCookie);
ok($denied($r), 'Staff KHÔNG vào được màn hình quản lý tài khoản');
$r = request($base.'index.php?com=user&act=add_admin', $staffCookie);
ok($denied($r), 'Staff KHÔNG mở được form tạo tài khoản mới');
$r = request($base.'index.php?com=user&act=permission_group', $staffCookie);
ok($denied($r), 'Staff KHÔNG vào được màn hình nhóm quyền');

/* 4. Thao tác ghi phải bị chặn, không còn lọt qua như trước. */
$r = request($base.'index.php?com=product&act=save&type=san-pham', $staffCookie, array('data' => array('namevi' => 'x')));
ok($denied($r), 'Staff chỉ có quyền xem thì KHÔNG ghi được sản phẩm qua act=save');

/* 5. Tài khoản không nhóm quyền thì không vào được gì. */
$r = request($base.'index.php?com=product&act=man&type=san-pham', $nobodyCookie);
ok($denied($r), 'Tài khoản không nhóm quyền bị chặn khỏi sản phẩm');
$r = request($base.'index.php?com=user&act=man_admin', $nobodyCookie);
ok($denied($r), 'Tài khoản không nhóm quyền bị chặn khỏi quản lý tài khoản');

/* 6. Màn hình tự phục vụ vẫn mở.
   Phải đăng nhập lại lấy phiên sạch: transfer() của những lần bị chặn ở trên để
   lại một flash toast, và toast đó hiện ở trang kế tiếp — kiểm tra theo chuỗi
   "không có quyền" trên phiên cũ sẽ bắt nhầm chính cái toast đó chứ không phải
   kết quả của màn hình này. */
$freshCookie = login($base, $staffName, $password);
ok($freshCookie !== '', 'Đăng nhập lại lấy phiên sạch');
$r = request($base.'index.php?com=user&act=info_admin', $freshCookie);
ok($r['status'] === 200 && !$denied($r), 'Staff vẫn xem được hồ sơ của chính mình');

echo PHP_EOL, "Kết quả: ", $passes, " pass, ", $failures, " fail", PHP_EOL;
exit($failures ? 1 : 0);
