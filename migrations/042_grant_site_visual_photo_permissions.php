<?php
/**
 * Cấp quyền cho các type ảnh mới của hệ thống media public:
 * - catalog-campaign: banner ngắt catalog sản phẩm
 * - contact-visual: ảnh tư vấn trang Liên hệ
 * - dealer-hero: ảnh năng lực phân phối trang Đại lý
 * - product-category-hero: banner ảnh của từng danh mục sản phẩm
 * - home-proof-background: nền dải chứng nhận trang chủ
 *
 * Chỉ nhóm đã có đủ bốn quyền `adv` mới nhận quyền mới. Mặc định dry-run;
 * --apply dùng transaction và có thể chạy lặp an toàn.
 *
 *   php migrations/042_grant_site_visual_photo_permissions.php
 *   php migrations/042_grant_site_visual_photo_permissions.php --apply
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

function siteVisualPhotoPermissions($type)
{
    $permissions = array();
    foreach (array('man', 'add', 'edit', 'delete') as $verb)
        $permissions[] = 'photo_'.$verb.'_photo_'.$type;
    return $permissions;
}

$types = array(
    'catalog-campaign' => 'Banner giữa catalog sản phẩm',
    'contact-visual' => 'Ảnh tư vấn trang Liên hệ',
    'dealer-hero' => 'Ảnh năng lực phân phối trang Đại lý',
    'product-category-hero' => 'Banner danh mục sản phẩm',
    'home-proof-background' => 'Nền dải chứng nhận trang chủ'
);
$anchor = siteVisualPhotoPermissions('adv');
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
        echo "  - Bỏ qua: không đủ quyền Banner quảng cáo\n\n";
        continue;
    }

    foreach ($types as $type => $label)
    {
        $missing = array_values(array_diff(siteVisualPhotoPermissions($type), $held));
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
