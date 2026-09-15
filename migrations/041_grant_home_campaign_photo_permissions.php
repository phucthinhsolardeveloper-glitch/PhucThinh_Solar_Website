<?php
/**
 * Cấp quyền cho type ảnh home-campaign (ba banner chiến dịch trang chủ).
 *
 * Type này được tách khỏi adv để nội dung chiến dịch không làm thay đổi các
 * CTA/popup đang dùng Banner quảng cáo. Vì cùng là nội dung banner marketing,
 * chỉ nhóm đã có đủ bốn quyền adv mới nhận bốn quyền home-campaign.
 *
 *   php migrations/041_grant_home_campaign_photo_permissions.php
 *   php migrations/041_grant_home_campaign_photo_permissions.php --apply
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

function homeCampaignPhotoPermissions($type)
{
    $permissions = array();
    foreach (array('man', 'add', 'edit', 'delete') as $verb)
        $permissions[] = 'photo_'.$verb.'_photo_'.$type;
    return $permissions;
}

$anchor = homeCampaignPhotoPermissions('adv');
$permissions = homeCampaignPhotoPermissions('home-campaign');
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
    if (array_diff($anchor, $held))
    {
        echo "  - Banner chiến dịch trang chủ: bỏ qua (không đủ quyền Banner quảng cáo)\n\n";
        continue;
    }

    $missing = array_values(array_diff($permissions, $held));
    if (!$missing)
    {
        echo "  - Banner chiến dịch trang chủ: đã đủ quyền\n\n";
        continue;
    }

    $plan[$groupId] = $missing;
    echo '  - Banner chiến dịch trang chủ: sẽ thêm ', count($missing), " quyền\n\n";
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
        foreach ($missing as $permission)
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
