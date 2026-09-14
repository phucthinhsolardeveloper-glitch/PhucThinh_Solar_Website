<?php
/**
 * Cấp quyền cho type ảnh mới hero-carousel (Hero carousel trang chủ, layout 3
 * - Admin hoá 2026-09-14). Neo vào home-hero-bg (Ảnh nền Hero trang chủ) -
 * cùng khu vực Hero, nhóm nào đã quản lý ảnh nền Hero thì quản lý luôn nội
 * dung carousel.
 *
 *   php migrations/040_grant_hero_carousel_photo_permission.php
 *   php migrations/040_grant_hero_carousel_photo_permission.php --apply
 *
 * Mặc định chỉ xem trước. Chế độ --apply dùng transaction và có thể chạy lặp.
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
$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function redesignPhotoPermissions($type)
{
    $permissions = array();
    foreach (array('man', 'add', 'edit', 'delete') as $verb)
        $permissions[] = 'photo_'.$verb.'_photo_'.$type;
    return $permissions;
}

$grants = array(
    array(
        'label' => 'Hero carousel trang chủ',
        'anchor' => redesignPhotoPermissions('home-hero-bg'),
        'permissions' => redesignPhotoPermissions('hero-carousel')
    )
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
    $missingForGroup = array();

    echo 'Nhóm #', $groupId, ' "', $group['name'], '"', PHP_EOL;
    foreach ($grants as $grant)
    {
        if (array_diff($grant['anchor'], $held))
        {
            echo '  - ', $grant['label'], ": bỏ qua (không đủ quyền module gốc)\n";
            continue;
        }

        $missing = array_values(array_diff($grant['permissions'], $held));
        if (!$missing)
        {
            echo '  - ', $grant['label'], ": đã đủ quyền\n";
            continue;
        }

        $missingForGroup = array_merge($missingForGroup, $missing);
        echo '  - ', $grant['label'], ': sẽ thêm ', count($missing), " quyền\n";
    }

    if ($missingForGroup) $plan[$groupId] = array_values(array_unique($missingForGroup));
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
    foreach ($plan as $groupId => $permissions)
        foreach ($permissions as $permission)
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
