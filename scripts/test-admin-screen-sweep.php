<?php
/**
 * Quét mọi màn hình Admin chỉ-đọc và bắt lỗi PHP / HTTP.
 *
 * Admin có 28 module và khoảng 200 action; mở tay từng cái là không khả thi,
 * và những lỗi kiểu "Undefined index" chỉ lộ ra khi thật sự mở đúng màn hình
 * đó. Script đăng nhập bằng một tài khoản fixture dùng một lần, liệt kê action
 * từ chính các câu `case` trong administrator/sources/*.php, rồi gọi từng màn
 * hình và soi phần HTML trả về.
 *
 * CHỈ GỌI MÀN HÌNH ĐỌC. Mọi action có động từ ghi (save/delete/update/copy...)
 * đều nằm trong danh sách chặn ở $writeVerbs và bị bỏ qua — script này không
 * được phép làm đổi dữ liệu.
 *
 * Báo cáo ba nhóm:
 *   LỖI       - HTTP 5xx, hoặc HTML chứa Fatal error / Parse error
 *   CẢNH BÁO  - Warning / Notice / Undefined / Deprecated lọt ra HTML
 *   TRỐNG     - HTTP 200 nhưng body quá ngắn, khả năng màn hình hỏng
 *
 * Chạy: php scripts/test-admin-screen-sweep.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
/* config-type.php điền $config['product']/['news']/['static']/['photo']/
   ['tags']/['newsletter'] (danh sách type hợp lệ cho từng com). Thiếu require
   này khiến typesFor() luôn rơi về fallback type='' cho MỌI com dùng type,
   tức là toàn bộ nhóm màn hình đó chỉ test được trang "Trang không tồn tại"
   thay vì nội dung thật - sweep báo sạch dù chưa thực sự chạy qua code. */
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$base = 'http://127.0.0.1/administrator/';
$sourcesDir = '/var/www/html/administrator/sources/';

/* Action có động từ ghi: tuyệt đối không gọi. */
$writeVerbs = array('save','delete','del','update','copy','insert','remove','clear','truncate',
                    'import','send','push','logout','login','sort','order_status','reset','active',
                    'upload','install','sync','run','apply','create','duplicate','merge','fix','repair');

function isWriteAction($act, $writeVerbs)
{
    $a = strtolower($act);
    foreach ($writeVerbs as $verb) if (strpos($a, $verb) !== false) return true;
    return false;
}

function httpGet($url, $cookie)
{
    $context = stream_context_create(array('http' => array(
        'method' => 'GET', 'ignore_errors' => true, 'timeout' => 25,
        'header' => "User-Agent: PTS-Admin-Sweep\r\nCookie: ".$cookie
    )));
    $body = @file_get_contents($url, false, $context);
    $headers = isset($http_response_header) ? (array)$http_response_header : array();
    $status = 0;
    $contentType = '';
    foreach ($headers as $header)
    {
        if (preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $m)) $status = (int)$m[1];
        if (stripos($header, 'Content-Type:') === 0) $contentType = strtolower(trim(substr($header, 13)));
    }
    return array('status' => $status, 'body' => (string)$body, 'type' => $contentType);
}

/* Tài khoản fixture role=3 để không bị chặn quyền; xoá ở cuối. */
$prefix = 'sweep-'.bin2hex(random_bytes(4));
$password = bin2hex(random_bytes(12));
$d->insert('user', array(
    'id_permission' => 0, 'username' => $prefix,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Admin screen sweep', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$sweepUserId = (int)$d->getLastInsertId();

$cleanup = function () use ($d, $sweepUserId) {
    $d->rawQuery('delete from #_user_log where id_user = ?', array($sweepUserId));
    $d->rawQuery('delete from #_user where id = ?', array($sweepUserId));
};
register_shutdown_function($cleanup);

/* Mở trang login một lần để nhận cookie phiên. */
$cookie = '';
$context = stream_context_create(array('http' => array('method' => 'GET', 'ignore_errors' => true, 'timeout' => 20)));
@file_get_contents($base.'index.php?com=user&act=login', false, $context);
$loginHeaders = isset($http_response_header) ? (array)$http_response_header : array();
foreach ($loginHeaders as $header)
    if (stripos($header, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $header, $m)) $cookie = $m[1];
$loginPage = httpGet($base.'index.php?com=user&act=login', $cookie);
if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $csrf))
{
    fwrite(STDERR, "Không lấy được CSRF đăng nhập.\n");
    exit(1);
}
$postContext = stream_context_create(array('http' => array(
    'method' => 'POST', 'ignore_errors' => true, 'timeout' => 20,
    'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: ".$cookie,
    'content' => http_build_query(array('username' => $prefix, 'password' => $password, 'admin_csrf' => $csrf[1]))
)));
$loginResult = @file_get_contents($base.'api/login.php', false, $postContext);
foreach ((array)$http_response_header as $header)
    if (stripos($header, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $header, $m)) $cookie = $m[1];
$payload = json_decode((string)$loginResult, true);
if (empty($payload['success']))
{
    fwrite(STDERR, "Đăng nhập fixture thất bại: ".substr((string)$loginResult, 0, 200)."\n");
    exit(1);
}

/* Type hợp lệ cho từng com, lấy từ $config. */
$typesFor = function ($com) use ($config) {
    $map = array('product' => 'product', 'news' => 'news', 'static' => 'static',
                 'photo' => 'photo', 'tags' => 'tags', 'newsletter' => 'newsletter');
    if (isset($map[$com]) && !empty($config[$map[$com]]) && is_array($config[$map[$com]]))
    {
        $keys = array_keys($config[$map[$com]]);
        $keys = array_values(array_filter($keys, function ($k) { return is_string($k) && $k !== 'photo_static' && $k !== 'man_photo'; }));
        if ($keys) return array_slice($keys, 0, 3);
    }
    return array('');
};

/* Lấy action từ chính các câu case trong sources. */
$screens = array();
foreach (glob($sourcesDir.'*.php') as $file)
{
    $com = basename($file, '.php');
    $code = file_get_contents($file);
    preg_match_all('/case\s*[\'"]([a-z0-9_]+)[\'"]\s*:/i', $code, $matches);
    $acts = array_values(array_unique($matches[1]));
    if (!$acts) $acts = array('man');
    foreach ($acts as $act)
    {
        if (isWriteAction($act, $writeVerbs)) continue;
        foreach ($typesFor($com) as $type)
            $screens[] = array('com' => $com, 'act' => $act, 'type' => $type);
    }
}

$errors = array();
$warnings = array();
$empty = array();
$ok = 0;
$checked = 0;

foreach ($screens as $screen)
{
    $url = $base.'index.php?com='.urlencode($screen['com']).'&act='.urlencode($screen['act']);
    if ($screen['type'] !== '') $url .= '&type='.urlencode($screen['type']);
    $label = $screen['com'].'/'.$screen['act'].($screen['type'] !== '' ? '/'.$screen['type'] : '');
    $response = httpGet($url, $cookie);
    $checked++;

    if ($response['status'] >= 500 || preg_match('/(Fatal error|Parse error|Uncaught)/i', $response['body']))
    {
        preg_match('/(Fatal error|Parse error|Uncaught)[^<\n]{0,160}/i', $response['body'], $detail);
        $errors[] = sprintf('%-46s HTTP %d  %s', $label, $response['status'], isset($detail[0]) ? trim($detail[0]) : '');
        continue;
    }
    if (preg_match('/(Warning|Notice|Deprecated)\s*:\s*[^<\n]{0,140}/i', $response['body'], $detail))
    {
        $warnings[] = sprintf('%-46s %s', $label, trim($detail[0]));
        continue;
    }
    /* Endpoint JSON trả body ngắn là bình thường: thiếu tham số thì nó trả
       {"ok":false,...} chứ không dựng trang. Chỉ coi là hỏng khi body không
       phải JSON hợp lệ. */
    $looksJson = strpos($response['type'], 'application/json') !== false
        || preg_match('/^\s*[\{\[]/', $response['body']);
    if ($looksJson)
    {
        if (json_decode($response['body'], true) === null && strtolower(trim($response['body'])) !== 'null')
            $errors[] = sprintf('%-46s trả JSON hỏng', $label);
        else $ok++;
        continue;
    }
    if ($response['status'] === 200 && strlen($response['body']) < 900)
    {
        $empty[] = sprintf('%-46s HTTP 200 nhưng chỉ %d byte', $label, strlen($response['body']));
        continue;
    }
    $ok++;
}

echo "QUÉT MÀN HÌNH ADMIN\n";
echo str_repeat('=', 78), "\n";
printf("Đã gọi %d màn hình chỉ-đọc | OK: %d | Lỗi: %d | Cảnh báo: %d | Nghi trống: %d\n\n",
       $checked, $ok, count($errors), count($warnings), count($empty));

if ($errors)
{
    echo "LỖI (", count($errors), ")\n", str_repeat('-', 78), "\n";
    foreach ($errors as $line) echo '  ', $line, "\n";
    echo "\n";
}
if ($warnings)
{
    echo "CẢNH BÁO PHP LỌT RA HTML (", count($warnings), ")\n", str_repeat('-', 78), "\n";
    foreach ($warnings as $line) echo '  ', $line, "\n";
    echo "\n";
}
if ($empty)
{
    echo "MÀN HÌNH NGHI TRỐNG (", count($empty), ")\n", str_repeat('-', 78), "\n";
    foreach ($empty as $line) echo '  ', $line, "\n";
    echo "\n";
}
echo $errors ? "KẾT LUẬN: CÓ LỖI CẦN SỬA.\n" : "KẾT LUẬN: Không màn hình nào lỗi nặng.\n";
exit($errors ? 1 : 0);
