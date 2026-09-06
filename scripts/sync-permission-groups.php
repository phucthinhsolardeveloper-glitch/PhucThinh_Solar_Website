<?php
/**
 * Đồng bộ nhóm quyền với danh mục quyền hiện tại của hệ thống.
 *
 * Vì sao cần: danh mục quyền sinh động từ $config, còn nhóm quyền nằm chết
 * trong DB. Thêm loại tin/ảnh/module mới là nhóm quyền lệch ngay mà không ai
 * biết. Ngày 06/09/2026 cả ba nhóm đều lệch: 117 dòng trỏ tới tính năng đã gỡ,
 * và nhóm "cấp cao" thiếu 126 quyền đang dùng thật.
 *
 * Danh mục lấy từ CHÍNH màn hình "Thêm nhóm quyền" của Admin — nguồn sự thật
 * duy nhất. Không chép lại logic sinh danh sách, vì chép là sẽ lệch.
 *
 * Dùng tài khoản quản trị có sẵn của bạn, KHÔNG tự tạo tài khoản tạm trên
 * production.
 *
 *   php scripts/sync-permission-groups.php --user=admin --pass=xxx
 *       Chỉ báo cáo, không ghi gì.
 *
 *   php scripts/sync-permission-groups.php --user=admin --pass=xxx --clean-stale --apply
 *       Xoá các dòng quyền trỏ tới tính năng đã gỡ, ở TẤT CẢ các nhóm.
 *       An toàn tuyệt đối: những dòng đó không khớp quyền nào nên không hề
 *       cấp thêm hay bớt quyền của ai.
 *
 *   php scripts/sync-permission-groups.php --user=admin --pass=xxx --fill=1 --apply
 *       Cấp ĐỦ toàn bộ danh mục cho nhóm số 1. Chỉ dùng cho nhóm mà bạn thật
 *       sự muốn full quyền. Lưu ý quản lý tài khoản và nhóm quyền KHÔNG nằm
 *       trong danh mục này — khu vực đó vĩnh viễn chỉ dành cho role 3.
 *
 * Làm bằng tay cũng được và tương đương: Admin -> Quản lý user -> Nhóm quyền
 * -> sửa nhóm -> tick "Chọn tất cả" -> Lưu. savePermissionGroup() xoá sạch rồi
 * ghi lại theo danh sách gửi lên.
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
if (!is_file(LIBRARIES.'config.php'))
{
    fwrite(STDERR, "Chạy script này trên máy chủ có mã nguồn tại /var/www/html.\n");
    exit(1);
}
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

$options = array('user' => '', 'pass' => '', 'fill' => 0);
$apply = in_array('--apply', $argv, true);
$cleanStale = in_array('--clean-stale', $argv, true);
foreach ($argv as $argument)
{
    if (strpos($argument, '--user=') === 0) $options['user'] = substr($argument, 7);
    if (strpos($argument, '--pass=') === 0) $options['pass'] = substr($argument, 7);
    if (strpos($argument, '--fill=') === 0) $options['fill'] = (int)substr($argument, 7);
}
if ($options['user'] === '' || $options['pass'] === '')
{
    fwrite(STDERR, "Thiếu --user= và --pass= (tài khoản quản trị role 3).\n");
    exit(1);
}

$base = 'http://127.0.0.1/administrator/';

function request($url, $cookie = '', $post = null)
{
    $header = "User-Agent: PTS-Permission-Sync\r\n";
    if ($cookie !== '') $header .= 'Cookie: '.$cookie."\r\n";
    $options = array('http' => array('ignore_errors' => true, 'timeout' => 30,
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

$page = request($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = request($base.'index.php?com=user&act=login', $cookie);
if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrf))
{
    fwrite(STDERR, "Không lấy được CSRF đăng nhập.\n");
    exit(1);
}
$login = request($base.'api/login.php', $cookie,
    array('username' => $options['user'], 'password' => $options['pass'], 'admin_csrf' => $csrf[1]));
if ($login['cookie'] !== '') $cookie = $login['cookie'];
if (empty(json_decode($login['body'], true)['success']))
{
    fwrite(STDERR, "Đăng nhập thất bại. Kiểm tra lại --user/--pass.\n");
    exit(1);
}

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
if (count($catalogue) < 50)
{
    fwrite(STDERR, "Chỉ đọc được ".count($catalogue)." quyền — có vẻ tài khoản không phải role 3.\n");
    exit(1);
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
echo "Danh mục quyền hệ thống: ", count($catalogue), "\n\n";

$staleTotal = 0;
$plan = array();
foreach ($d->rawQuery('select id, name from #_permission_group order by id') as $group)
{
    $id = (int)$group['id'];
    $held = array();
    foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array($id)) as $row)
        $held[] = $row['permission'];

    $stale = array_values(array_diff($held, $catalogue));
    $valid = array_values(array_intersect($held, $catalogue));
    $missing = array_values(array_diff($catalogue, $held));
    $staleTotal += count($stale);

    printf("Nhóm #%d \"%s\"\n", $id, $group['name']);
    printf("  đang có %d dòng: %d hợp lệ, %d lỗi thời | thiếu %d so với đầy đủ\n",
           count($held), count($valid), count($stale), count($missing));

    $target = null;
    if ($options['fill'] === $id) { $target = $catalogue; echo "  -> sẽ cấp ĐỦ ", count($catalogue), " quyền\n"; }
    elseif ($cleanStale && $stale) { $target = $valid; echo "  -> sẽ xoá ", count($stale), " dòng lỗi thời, giữ nguyên ", count($valid), " quyền hợp lệ\n"; }
    if ($target !== null) $plan[$id] = $target;
    echo "\n";
}

if (!$plan)
{
    echo $staleTotal ? "Không có gì để làm (thêm --clean-stale hoặc --fill=<id>).\n"
                     : "Mọi nhóm đều khớp danh mục.\n";
    exit(0);
}
if (!$apply)
{
    echo "Chưa ghi gì. Chạy lại kèm --apply để thực hiện.\n";
    exit(0);
}

/* Ghi qua transaction, không dùng form Admin: form bắt buộc gửi kèm tên nhóm và
   trạng thái, gửi thiếu là sửa nhầm dữ liệu khác. Ở đây chỉ đụng bảng quyền. */
$changed = 0;
$d->startTransaction();
try
{
    foreach ($plan as $id => $permissions)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array($id));
        foreach ($permissions as $permission)
            if (!$d->insert('permission', array('id_permission_group' => $id, 'permission' => $permission)))
                throw new RuntimeException('Không ghi được quyền '.$permission.' cho nhóm '.$id);
        $changed++;
    }
    $d->commit();
}
catch (Throwable $exception)
{
    $d->rollback();
    fwrite(STDERR, "LỖI: ".$exception->getMessage()."\nĐã rollback, dữ liệu giữ nguyên.\n");
    exit(1);
}

echo "Đã cập nhật ", $changed, " nhóm quyền.\n\n";
echo "KIỂM CHỨNG\n";
$left = 0;
foreach ($d->rawQuery('select id, name from #_permission_group order by id') as $group)
{
    $held = array();
    foreach ($d->rawQuery('select permission from #_permission where id_permission_group = ?', array((int)$group['id'])) as $row)
        $held[] = $row['permission'];
    $stale = count(array_diff($held, $catalogue));
    $left += $stale;
    printf("  #%d %-26s %3d quyền, %d lỗi thời\n", $group['id'], $group['name'], count($held), $stale);
}
echo "  Tổng dòng lỗi thời còn lại: ", $left, " (mong đợi 0)\n";
echo "\nNhắc: người đang đăng nhập cần đăng xuất rồi vào lại để nhận quyền mới.\n";
exit($left === 0 ? 0 : 1);
