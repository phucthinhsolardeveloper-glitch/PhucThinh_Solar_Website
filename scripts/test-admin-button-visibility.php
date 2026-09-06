<?php
/**
 * Nút Thêm/Sửa/Xoá trong Admin phải khớp với quyền thật của tài khoản.
 *
 * Vì sao cần: cổng phân quyền ở requick.php chặn đúng, nhưng template vẫn vẽ
 * đủ nút cho mọi người. Một nhân viên chỉ có quyền XEM sản phẩm vẫn thấy nút
 * "Thêm mới", link "Sửa" ở từng dòng và nút "Xoá"; bấm vào là bị đá về trang
 * chủ kèm thông báo từ chối. Đúng về bảo mật nhưng rất ức chế khi dùng.
 *
 * Test này khoá hai đầu, và đầu thứ hai mới là đầu quan trọng:
 *   1) Nhân viên chỉ có quyền xem  -> không còn nút thao tác nào,
 *      NHƯNG vẫn đọc được danh sách (tên sản phẩm vẫn hiện, chỉ mất liên kết).
 *   2) Tài khoản cấp cao (role 3)  -> thấy ĐỦ mọi nút như trước.
 * Nếu chỉ kiểm tra vế 1 thì một lỗi ẩn nhầm toàn bộ nút của chủ website vẫn
 * lọt qua, mà đó mới là hỏng nặng.
 *
 * Mỗi request HTTP là một tiến trình PHP riêng đọc lại config, nên không thể
 * bật/tắt phân quyền trong tiến trình test. Dùng header
 * X-PTS-Permission-Test: enforce như test cổng phân quyền: config-type.php chỉ
 * nhận header này khi APP_ENV=local và tắt debug-developer cho request đó.
 *
 * Chạy: php scripts/test-admin-button-visibility.php
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
    $header = "User-Agent: PTS-Button-Visibility\r\nX-PTS-Permission-Test: enforce\r\n";
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
    $newCookie = '';
    foreach ((array)$http_response_header as $line)
        if (stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $m)) $newCookie = $m[1];
    return array('body' => (string)$body, 'cookie' => $newCookie);
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

/**
 * Đếm phần tử còn NHÌN THẤY được, tức không mang class d-none ở chính nó hay ở
 * bất kỳ thẻ cha nào — ẩn thẻ cha thì con cũng biến mất khỏi mắt người dùng.
 */
function countVisible($html, $query)
{
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);

    $visible = 0;
    foreach ($xpath->query($query) as $node)
    {
        $hidden = false;
        for ($cursor = $node; $cursor && $cursor->nodeType === XML_ELEMENT_NODE; $cursor = $cursor->parentNode)
            if (preg_match('/(^|\s)d-none(\s|$)/', $cursor->getAttribute('class'))) { $hidden = true; break; }
        if (!$hidden) $visible++;
    }
    return $visible;
}

if (empty($config['permission']['active']))
{
    echo "FAIL: \$config['permission']['active'] đang tắt nên không kiểm chứng được.", PHP_EOL;
    exit(1);
}
if (!empty($config['website']['debug-developer']) && getenv('APP_ENV') !== 'local')
{
    echo "FAIL: debug-developer đang bật ngoài local nên mọi kiểm tra quyền bị bỏ qua.", PHP_EOL;
    exit(1);
}

/* Dọn fixture nếu lượt chạy trước bị ngắt cứng trước shutdown handler. */
foreach ($d->rawQuery('select id from #_user where username like ? or username like ?', array('btn-viewer-%', 'btn-owner-%')) as $staleUser)
{
    $d->rawQuery('delete from #_user_log where id_user = ?', array((int)$staleUser['id']));
    $d->rawQuery('delete from #_user where id = ?', array((int)$staleUser['id']));
}
foreach ($d->rawQuery('select id from #_permission_group where name like ?', array('BTN-%')) as $staleGroup)
{
    $d->rawQuery('delete from #_permission where id_permission_group = ?', array((int)$staleGroup['id']));
    $d->rawQuery('delete from #_permission_group where id = ?', array((int)$staleGroup['id']));
}

$suffix     = bin2hex(random_bytes(4));
$groupName  = 'BTN-'.$suffix;
$viewerName = 'btn-viewer-'.$suffix;
$ownerName  = 'btn-owner-'.$suffix;
$password   = bin2hex(random_bytes(12));
$groupId = 0; $viewerId = 0; $ownerId = 0;

register_shutdown_function(function () use (&$groupId, &$viewerId, &$ownerId, $d) {
    foreach (array($viewerId, $ownerId) as $id)
        if ($id) { $d->rawQuery('delete from #_user_log where id_user = ?', array($id)); $d->rawQuery('delete from #_user where id = ?', array($id)); }
    if ($groupId)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array($groupId));
        $d->rawQuery('delete from #_permission_group where id = ?', array($groupId));
    }
});

/* Nhóm quyền chỉ được XEM sản phẩm, không thêm/sửa/xoá. */
$d->insert('permission_group', array('name' => $groupName, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
$groupId = (int)$d->getLastInsertId();
$d->insert('permission', array('id_permission_group' => $groupId, 'permission' => 'product_man_san-pham'));

foreach (array(array($viewerName, $groupId, 1, 'viewerId'), array($ownerName, 0, 3, 'ownerId')) as $spec)
{
    $d->insert('user', array(
        'id_permission' => $spec[1], 'username' => $spec[0],
        'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
        'fullname' => 'Button visibility test', 'status' => 'hienthi', 'role' => $spec[2],
        'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
    ));
    ${$spec[3]} = (int)$d->getLastInsertId();
}

$listUrl = $base.'index.php?com=product&act=man&type=san-pham';

$addButton    = "//a[contains(@href,'act=add')]";
$editButton   = "//a[contains(@href,'act=edit')]";
$deleteButton = "//a[@data-url]";
$productRow   = "//table//tbody//tr";

/* --- Nhân viên chỉ có quyền xem --- */
$viewerCookie = login($base, $viewerName, $password);
ok($viewerCookie !== '', 'Nhân viên chỉ-xem đăng nhập được');

$viewerPage = request($listUrl, $viewerCookie)['body'];
$viewerRows = countVisible($viewerPage, $productRow);

ok($viewerRows > 0, 'Nhân viên vẫn đọc được danh sách sản phẩm ('.$viewerRows.' dòng)');
ok(countVisible($viewerPage, $addButton) === 0, 'Nhân viên không còn thấy nút Thêm mới');
ok(countVisible($viewerPage, $editButton) === 0, 'Nhân viên không còn thấy link Sửa');
ok(countVisible($viewerPage, $deleteButton) === 0, 'Nhân viên không còn thấy nút Xoá');

/* --- Tài khoản cấp cao: phải còn nguyên mọi nút --- */
$ownerCookie = login($base, $ownerName, $password);
ok($ownerCookie !== '', 'Tài khoản cấp cao đăng nhập được');

$ownerPage = request($listUrl, $ownerCookie)['body'];
$ownerAdd    = countVisible($ownerPage, $addButton);
$ownerEdit   = countVisible($ownerPage, $editButton);
$ownerDelete = countVisible($ownerPage, $deleteButton);

ok($ownerAdd > 0,    'Tài khoản cấp cao vẫn thấy nút Thêm mới ('.$ownerAdd.')');
ok($ownerEdit > 0,   'Tài khoản cấp cao vẫn thấy link Sửa ('.$ownerEdit.')');
ok($ownerDelete > 0, 'Tài khoản cấp cao vẫn thấy nút Xoá ('.$ownerDelete.')');
ok(countVisible($ownerPage, $productRow) === $viewerRows,
   'Hai tài khoản thấy cùng số dòng sản phẩm — ẩn nút không làm mất dữ liệu');

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
