<?php
/**
 * Cấp quyền cho các khối CMS mới thêm 2026-09-14 (audit tích hợp Admin):
 * home-about / home-about-point (trang chủ), project-benefit / project-trust
 * (trang Dự án).
 *
 * Cùng khuôn với migrations/037: chỉ cấp cho nhóm đã có đủ 4 quyền của
 * module "gốc" liên quan (anchor) - không tự cấp cho nhóm hẹp.
 *   - project-benefit/project-trust neo vào du-an, giống hệt cách
 *     project-intro/project-cta đã neo trong 037 (cùng nhóm "Trang Dự án").
 *   - home-about/home-about-point là khu vực hoàn toàn mới, không có module
 *     "gốc" sẵn để neo - neo tạm vào tin-tuc (Tin tức) làm chuẩn "nhóm đã
 *     được tin biên tập nội dung công khai trang chủ/tin bài", tương đương
 *     mức tin cậy đang áp dụng cho các khối CMS công khai khác.
 *
 *   php migrations/039_grant_home_and_project_point_permissions.php
 *   php migrations/039_grant_home_and_project_point_permissions.php --apply
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

function redesignNewsPermissions($type)
{
    $permissions = array();
    foreach (array('man', 'add', 'edit', 'delete') as $verb)
        $permissions[] = 'news_'.$verb.'_'.$type;
    return $permissions;
}

$grants = array(
    array(
        'label' => 'Trang chủ - Giới thiệu ngắn',
        'anchor' => redesignNewsPermissions('tin-tuc'),
        'permissions' => array_merge(
            redesignNewsPermissions('home-about'),
            redesignNewsPermissions('home-about-point')
        )
    ),
    array(
        'label' => 'Trang Dự án - Lợi ích/Tin cậy',
        'anchor' => redesignNewsPermissions('du-an'),
        'permissions' => array_merge(
            redesignNewsPermissions('project-benefit'),
            redesignNewsPermissions('project-trust')
        )
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
