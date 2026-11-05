<?php
/**
 * Cấp quyền cho các loại nội dung Admin điều khiển giao diện Nuxt:
 * - home-energy-chain: 5 thẻ chuỗi năng lượng trang chủ
 * - catalog-benefit: dải 4 lợi ích dưới đầu trang Danh mục/Tìm kiếm
 * - quick-search: chip từ khóa nhanh trong ô tìm kiếm header
 * - about-showroom: ảnh showroom trang Giới thiệu
 *
 * Mỗi loại chỉ cấp cho nhóm đã giữ đủ quyền của loại "neo" cùng khu vực, nên
 * không nhóm nào được mở rộng phạm vi. Mặc định dry-run; --apply dùng
 * transaction và chạy lặp an toàn.
 *
 *   php migrations/075_grant_nuxt_admin_content_permissions.php
 *   php migrations/075_grant_nuxt_admin_content_permissions.php --apply
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';

$apply = in_array('--apply', $argv, true);
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function newsPermissions($type)
{
    $permissions = array();
    foreach (array('man', 'add', 'edit', 'delete') as $verb) $permissions[] = 'news_'.$verb.'_'.$type;
    return $permissions;
}

$grants = array(
    'Trang chủ - Chuỗi năng lượng' => array('anchor' => newsPermissions('home-about-point'), 'grant' => newsPermissions('home-energy-chain')),
    'Danh mục - Dải lợi ích' => array('anchor' => newsPermissions('cam-ket'), 'grant' => newsPermissions('catalog-benefit')),
    'Tìm kiếm - Từ khóa nhanh' => array('anchor' => newsPermissions('cam-ket'), 'grant' => newsPermissions('quick-search')),
    'Giới thiệu - Ảnh showroom' => array('anchor' => newsPermissions('lich-su'), 'grant' => array('photo_photo_static_about-showroom')),
);
$groups = $pdo->query("select id, name from `{$prefix}permission_group` order by id")
    ->fetchAll(PDO::FETCH_ASSOC);
$heldStatement = $pdo->prepare("select permission from `{$prefix}permission` where id_permission_group = ?");
$plan = array();

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
foreach ($groups as $group)
{
    $groupId = (int)$group['id'];
    $heldStatement->execute(array($groupId));
    $held = $heldStatement->fetchAll(PDO::FETCH_COLUMN);
    echo 'Nhóm #', $groupId, ' "', $group['name'], '"', PHP_EOL;

    foreach ($grants as $label => $rule)
    {
        if (array_diff($rule['anchor'], $held))
        {
            echo '  - ', $label, ": bỏ qua (nhóm không quản lý khu vực này)\n";
            continue;
        }
        $missing = array_values(array_diff($rule['grant'], $held));
        if (!$missing)
        {
            echo '  - ', $label, ": đã đủ quyền\n";
            continue;
        }
        $plan[$groupId] = isset($plan[$groupId]) ? array_merge($plan[$groupId], $missing) : $missing;
        echo '  - ', $label, ': sẽ thêm ', count($missing), " quyền\n";
    }
    echo PHP_EOL;
}

if (!$plan)
{
    echo "Không có quyền nào cần bổ sung.\n";
    exit(0);
}
if (!$apply)
{
    echo "Chưa ghi gì. Chạy lại kèm --apply để thực hiện.\n";
    exit(0);
}

$insert = $pdo->prepare("insert into `{$prefix}permission` (id_permission_group, permission) values (?, ?)");
$pdo->beginTransaction();
try
{
    $inserted = 0;
    foreach ($plan as $groupId => $missing)
        foreach (array_values(array_unique($missing)) as $permission)
        {
            $insert->execute(array($groupId, $permission));
            $inserted++;
        }
    $pdo->commit();
}
catch (Throwable $exception)
{
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "LỖI: ".$exception->getMessage()."\nĐã rollback, dữ liệu giữ nguyên.\n");
    exit(1);
}

echo 'Đã thêm ', $inserted, ' quyền cho ', count($plan), " nhóm.\n";
echo "Người đang đăng nhập cần đăng xuất rồi vào lại để nhận quyền mới.\n";
