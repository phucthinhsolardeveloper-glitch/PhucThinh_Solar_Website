<?php
/**
 * Kiểm thử tab quản trị "Nhóm quyền" và "Tài khoản admin" qua HTTP thật.
 *
 * Đây là màn hình chủ project dùng để tự cấp tài khoản cho nhân viên và tự chọn
 * nhóm đó được làm những gì. Sau khi siết cổng phân quyền (chỉ role 3 vào được
 * khu vực này), cần chứng minh chính role 3 vẫn làm trọn được việc, chứ không
 * phải siết xong rồi chủ project cũng không vào nổi.
 *
 * Đi hết vòng: tạo nhóm quyền -> gán quyền cho nhóm -> tạo tài khoản nhân viên
 * gắn vào nhóm -> đăng nhập bằng tài khoản đó -> xác nhận vào được đúng màn
 * hình được cấp và bị chặn ở màn hình không được cấp -> sửa quyền của nhóm ->
 * xác nhận tài khoản nhân viên đổi quyền theo.
 *
 * Toàn bộ dữ liệu test mang tiền tố ACCTEST- và được dọn ở cuối.
 *
 * Chạy: php scripts/test-admin-account-management.php
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
    $header = "User-Agent: PTS-Account-Mgmt-Test\r\nX-PTS-Permission-Test: enforce\r\n";
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

$suffix = strtoupper(bin2hex(random_bytes(3)));
/* isAlphaNum() chỉ cho [a-z0-9], không có dấu gạch ngang. */
$ownerName = 'acctestowner'.strtolower($suffix);
$staffName = 'accteststaff'.strtolower($suffix);
$password = bin2hex(random_bytes(12));
$ownerId = 0;

register_shutdown_function(function () use (&$ownerId, $d, $suffix) {
    foreach ($d->rawQuery("select id from #_user where username like ?", array('acctest%')) as $row)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array((int)$row['id']));
        $d->rawQuery('delete from #_user where id = ?', array((int)$row['id']));
    }
    foreach ($d->rawQuery("select id from #_permission_group where name like ?", array('ACCTEST-%')) as $row)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array((int)$row['id']));
        $d->rawQuery('delete from #_permission_group where id = ?', array((int)$row['id']));
    }
});

/* Chủ project: role 3. */
$d->insert('user', array(
    'id_permission' => 0, 'username' => $ownerName,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Owner account mgmt test', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$ownerId = (int)$d->getLastInsertId();

$owner = login($base, $ownerName, $password);
ok($owner !== '', 'Chủ project (role 3) đăng nhập được');
if ($owner === '') { echo 'Không đăng nhập được, dừng.', PHP_EOL; exit(1); }

/* 1. Vào được khu vực quản trị tài khoản. */
$groupList = request($base.'index.php?com=user&act=permission_group', $owner);
ok($groupList['status'] === 200 && stripos($groupList['body'], 'không có quyền') === false,
   'Chủ project mở được màn hình Nhóm quyền');
$adminList = request($base.'index.php?com=user&act=man_admin', $owner);
ok($adminList['status'] === 200 && stripos($adminList['body'], 'không có quyền') === false,
   'Chủ project mở được màn hình Tài khoản admin');

/* 2. Tạo nhóm quyền mới, cấp đúng một quyền. */
$addGroup = request($base.'index.php?com=user&act=add_permission_group', $owner);
ok($addGroup['status'] === 200, 'Mở được form tạo nhóm quyền');
preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $addGroup['body'], $groupCsrf);
ok(!empty($groupCsrf[1]), 'Form nhóm quyền có CSRF token');

$groupName = 'ACCTEST-'.$suffix;
$saveGroup = request($base.'index.php?com=user&act=save_permission_group', $owner, array(
    'admin_csrf' => $groupCsrf[1],
    'data' => array('name' => $groupName, 'numb' => 0),
    'status' => array('hienthi'),
    'dataPermission' => array('product_man_san-pham')
));
ok(in_array($saveGroup['status'], array(200, 302), true), 'Gửi form tạo nhóm quyền');

$groupRow = $d->rawQueryOne('select id from #_permission_group where name = ? limit 1', array($groupName));
ok(!empty($groupRow['id']), 'Nhóm quyền mới xuất hiện trong DB');
if (empty($groupRow['id'])) { echo 'Không tạo được nhóm quyền, dừng.', PHP_EOL; exit(1); }
$groupId = (int)$groupRow['id'];

$granted = array();
foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array($groupId)) as $row)
    $granted[] = $row['permission'];
ok(in_array('product_man_san-pham', $granted, true), 'Quyền đã chọn được lưu vào nhóm');
ok(count($granted) === 1, 'Nhóm chỉ có đúng quyền đã chọn, không thừa');

/* 3. Tạo tài khoản nhân viên gắn vào nhóm đó. */
$addAdmin = request($base.'index.php?com=user&act=add_admin', $owner);
ok($addAdmin['status'] === 200, 'Mở được form tạo tài khoản nhân viên');
preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $addAdmin['body'], $adminCsrf);

$staffPassword = bin2hex(random_bytes(12));
/* saveAdmin() bắt buộc đủ: nhóm quyền, username [a-z0-9], mật khẩu +
   confirm_password (LƯU Ý: nằm ngoài mảng data), họ tên, email hợp lệ và duy
   nhất, giới tính, ngày sinh hợp lệ, địa chỉ. Gửi đúng bộ mà form thật gửi. */
$saveAdmin = request($base.'index.php?com=user&act=save_admin', $owner, array(
    'admin_csrf' => $adminCsrf[1],
    'confirm_password' => $staffPassword,
    'data' => array(
        'username' => $staffName,
        'fullname' => 'Nhan vien '.$suffix,
        'email' => $staffName.'@example.test',
        'phone' => '0912345678',
        'address' => 'Pleiku, Gia Lai',
        'gender' => '1',
        'birthday' => '01/01/1990',
        'password' => $staffPassword,
        'id_permission' => $groupId,
        'numb' => 0
    ),
    'status' => array('hienthi')
));
if (empty($d->rawQueryOne('select id from #_user where username = ? limit 1', array($staffName))['id'])
    && preg_match('/FLASH_TOAST\s*=\s*([^;]+);/', $saveAdmin['body'], $toastInfo))
    echo '  (Admin báo: ', preg_replace('/\s+/', ' ', substr($toastInfo[1], 0, 200)), ')', PHP_EOL;
ok(in_array($saveAdmin['status'], array(200, 302), true), 'Gửi form tạo tài khoản nhân viên');

$staffRow = $d->rawQueryOne('select id, id_permission, role from #_user where username = ? limit 1', array($staffName));
ok(!empty($staffRow['id']), 'Tài khoản nhân viên mới xuất hiện trong DB');
if (!empty($staffRow['id']))
{
    ok((int)$staffRow['id_permission'] === $groupId, 'Tài khoản được gắn đúng nhóm quyền vừa tạo');
    ok((int)$staffRow['role'] !== 3, 'Tài khoản nhân viên KHÔNG được cấp role 3');
}

/* 4. Nhân viên dùng được đúng phần được cấp, và chỉ phần đó.
   Chỉ kiểm tra khi cổng phân quyền thật sự đang hoạt động. */
$gateActive = !empty($config['permission']['active'])
    && (empty($config['website']['debug-developer']) || getenv('APP_ENV') === 'local');
if (!$gateActive)
{
    ok(false, 'Không thể kiểm tra cổng phân quyền: permission.active đang tắt hoặc debug-developer bật ngoài local');
}
else if (!empty($staffRow['id']))
{
    $staff = login($base, $staffName, $staffPassword);
    ok($staff !== '', 'Nhân viên đăng nhập được bằng tài khoản vừa cấp');
    if ($staff !== '')
    {
        $allowed = request($base.'index.php?com=product&act=man&type=san-pham', $staff);
        ok($allowed['status'] === 200 && stripos($allowed['body'], 'không có quyền') === false,
           'Nhân viên vào được màn hình đã được cấp quyền');
        $blocked = request($base.'index.php?com=news&act=man&type=tin-tuc', $staff);
        ok($blocked['status'] === 302 || stripos($blocked['body'], 'không có quyền') !== false,
           'Nhân viên bị chặn ở màn hình chưa được cấp');
        $blockedAccounts = request($base.'index.php?com=user&act=man_admin', $staff);
        ok($blockedAccounts['status'] === 302 || stripos($blockedAccounts['body'], 'cấp cao') !== false
           || stripos($blockedAccounts['body'], 'không có quyền') !== false,
           'Nhân viên KHÔNG vào được khu vực quản lý tài khoản');
    }

    /* 5. Chủ project cấp thêm quyền -> nhân viên dùng được ngay. */
    $editGroup = request($base.'index.php?com=user&act=edit_permission_group&id='.$groupId, $owner);
    preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $editGroup['body'], $editCsrf);
    request($base.'index.php?com=user&act=save_permission_group&id='.$groupId, $owner, array(
        'admin_csrf' => $editCsrf[1],
        'id' => $groupId,
        'data' => array('name' => $groupName, 'numb' => 0),
        'status' => array('hienthi'),
        'dataPermission' => array('product_man_san-pham', 'news_man_tin-tuc')
    ));
    $grantedAfter = array();
    foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array($groupId)) as $row)
        $grantedAfter[] = $row['permission'];
    ok(in_array('news_man_tin-tuc', $grantedAfter, true), 'Chủ project cấp thêm được quyền cho nhóm');

    $staffAgain = login($base, $staffName, $staffPassword);
    if ($staffAgain !== '')
    {
        $nowAllowed = request($base.'index.php?com=news&act=man&type=tin-tuc', $staffAgain);
        ok($nowAllowed['status'] === 200 && stripos($nowAllowed['body'], 'không có quyền') === false,
           'Nhân viên dùng được ngay quyền vừa được cấp thêm');
    }
}

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
