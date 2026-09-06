<?php
/**
 * Đối chiếu các nhóm quyền trong DB với danh mục quyền mà hệ thống thực sự có.
 *
 * Danh mục quyền được sinh động từ $config: thêm một loại tin tức, một loại ảnh
 * hay một module mới là danh sách đổi theo. Nhóm quyền thì nằm chết trong DB.
 * Hệ quả là theo thời gian nhóm quyền lệch khỏi thực tế theo hai hướng, và cả
 * hai đều âm thầm:
 *
 *   THIẾU     - quyền mới sinh ra chưa ai gán. Nhóm "cấp cao" tưởng là full
 *               quyền nhưng nhân viên lại không vào được phần mới.
 *   LỖI THỜI  - quyền của tính năng đã bỏ vẫn còn trong DB. Không cấp thêm
 *               quyền gì (không khớp cái nào) nhưng làm số liệu sai lệch và
 *               gây hiểu nhầm khi rà soát.
 *
 * Ngày 06/09/2026 nhóm "cấp cao" có 150 dòng nhưng chỉ 65 dòng còn hợp lệ,
 * 85 dòng thuộc tính năng đã gỡ (tuyển dụng, thư viện ảnh, tags, màu/kích
 * thước sản phẩm, pushOnesignal...), và thiếu 126 quyền đang dùng thật, trong
 * đó có quản lý chiến dịch và cài đặt website.
 *
 * Danh mục quyền lấy từ CHÍNH màn hình "Thêm nhóm quyền" của Admin, tức nguồn
 * sự thật duy nhất, thay vì chép lại logic sinh danh sách (chép là sẽ lệch).
 *
 * Cách sửa khi test báo lỗi: vào Admin -> Quản lý user -> Nhóm quyền -> sửa
 * nhóm -> tick "Chọn tất cả" (hoặc chọn đúng phạm vi mong muốn) -> Lưu.
 * savePermissionGroup() xoá sạch rồi ghi lại theo danh sách gửi lên, nên thao
 * tác đó vừa bổ sung quyền thiếu vừa dọn quyền lỗi thời.
 *
 * Chạy: php scripts/test-permission-catalogue.php
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
    $header = "User-Agent: PTS-Permission-Catalogue\r\n";
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

$username = 'permcat'.bin2hex(random_bytes(4));
$password = bin2hex(random_bytes(12));
$userId = 0;
register_shutdown_function(function () use (&$userId, $d) {
    if ($userId)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($userId));
        $d->rawQuery('delete from #_user where id = ?', array($userId));
    }
});

$d->insert('user', array(
    'id_permission' => 0, 'username' => $username,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Permission catalogue audit', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$userId = (int)$d->getLastInsertId();

$page = request($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = request($base.'index.php?com=user&act=login', $cookie);
if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrf))
{
    fwrite(STDERR, "Không lấy được CSRF đăng nhập.\n");
    exit(1);
}
$login = request($base.'api/login.php', $cookie,
    array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf[1]));
if ($login['cookie'] !== '') $cookie = $login['cookie'];
ok(!empty(json_decode($login['body'], true)['success']), 'Đăng nhập tài khoản rà soát');

/* Danh mục quyền = đúng những checkbox màn hình Thêm nhóm quyền render ra. */
$editor = request($base.'index.php?com=user&act=add_permission_group', $cookie);
$document = new DOMDocument();
libxml_use_internal_errors(true);
$document->loadHTML('<?xml encoding="UTF-8">'.$editor['body']);
libxml_clear_errors();
$xpath = new DOMXPath($document);

$catalogue = array();
foreach ($xpath->query('//input[@name="dataPermission[]"]') as $input)
{
    $value = $input->getAttribute('value');
    if ($value !== '') $catalogue[$value] = true;
}
$catalogue = array_keys($catalogue);
ok(count($catalogue) > 50, 'Đọc được danh mục quyền từ màn hình Admin ('.count($catalogue).' quyền)');
if (count($catalogue) < 50) { echo 'Không đọc được danh mục, dừng.', PHP_EOL; exit(1); }

/* Không nhóm nào được chứa quyền của tính năng đã gỡ. */
$groups = $d->rawQuery('select id, name from #_permission_group order by id');
ok(!empty($groups), 'Có nhóm quyền để rà soát');

$staleTotal = 0;
foreach ($groups as $group)
{
    $held = array();
    foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array((int)$group['id'])) as $row)
        $held[] = $row['permission'];

    $stale = array_values(array_diff($held, $catalogue));
    $valid = count(array_intersect($held, $catalogue));
    $staleTotal += count($stale);

    printf("  Nhóm #%s \"%s\": %d dòng — %d hợp lệ, %d lỗi thời%s\n",
        $group['id'], $group['name'], count($held), $valid, count($stale),
        count($stale) ? ' → '.implode(', ', array_slice($stale, 0, 3)).(count($stale) > 3 ? '...' : '') : '');
}

ok($staleTotal === 0,
   'Không nhóm quyền nào còn quyền lỗi thời'
   .($staleTotal ? ' (đang có '.$staleTotal.' dòng — mở nhóm trong Admin, chọn lại quyền rồi Lưu để dọn)' : ''));

/* Nhóm cao nhất nên phủ hết danh mục, nếu không thì "cấp cao" là tên gọi sai. */
$top = null;
foreach ($groups as $group)
{
    $count = $d->rawQueryOne('select count(*) as n from #_permission where id_permission_group = ?', array((int)$group['id']));
    if ($top === null || (int)$count['n'] > $top['count']) $top = array('group' => $group, 'count' => (int)$count['n']);
}
if ($top !== null)
{
    $held = array();
    foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array((int)$top['group']['id'])) as $row)
        $held[] = $row['permission'];
    $missing = array_values(array_diff($catalogue, $held));

    if ($missing)
    {
        $byModule = array();
        foreach ($missing as $permission) $byModule[explode('_', $permission)[0]][] = $permission;
        $summary = array();
        foreach ($byModule as $module => $list) $summary[] = $module.'('.count($list).')';
        ok(false, 'Nhóm quyền cao nhất "'.$top['group']['name'].'" thiếu '.count($missing)
            .'/'.count($catalogue).' quyền: '.implode(' ', $summary));
    }
    else ok(true, 'Nhóm quyền cao nhất "'.$top['group']['name'].'" phủ đủ danh mục');
}

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
