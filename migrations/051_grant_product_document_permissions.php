<?php
/**
 * Cấp quyền mới "Quản lý tài liệu sản phẩm theo loại" (datasheet/manual/
 * warranty/certificate/other) cho nhóm quyền đang giữ nhiều quyền nhất
 * (nhóm "cấp cao" theo quy ước dự án — được test-permission-catalogue.php
 * xác định bằng số dòng #_permission, không theo tên gọi).
 *
 * Đây là 5 quyền độc lập với danh mục sản phẩm (xem
 * administrator/templates/user/permission/permission_group_tpl.php, khối
 * "Quản lý Tài liệu sản phẩm"), nên không cần logic anchor theo type như các
 * migration grant_*_permissions.php trước đó.
 *
 * Mặc định dry-run; --apply dùng transaction và có thể chạy lặp an toàn.
 *
 *   php migrations/051_grant_product_document_permissions.php
 *   php migrations/051_grant_product_document_permissions.php --apply
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

$newPermissions = array(
    'product_document_manage_datasheet' => 'Quản lý Datasheet',
    'product_document_manage_manual' => 'Quản lý Hướng dẫn sử dụng',
    'product_document_manage_warranty' => 'Quản lý Bảo hành',
    'product_document_manage_certificate' => 'Quản lý Chứng nhận',
    'product_document_manage_other' => 'Quản lý Tài liệu khác'
);

$groups = $pdo->query("select id, name from `{$prefix}permission_group` order by id")->fetchAll(PDO::FETCH_ASSOC);
$countStatement = $pdo->prepare("select count(*) as n from `{$prefix}permission` where id_permission_group = ?");
$heldStatement = $pdo->prepare("select permission from `{$prefix}permission` where id_permission_group = ?");

$top = null;
foreach ($groups as $group)
{
    $countStatement->execute(array((int)$group['id']));
    $count = (int)$countStatement->fetch(PDO::FETCH_ASSOC)['n'];
    if ($top === null || $count > $top['count']) $top = array('group' => $group, 'count' => $count);
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";

if ($top === null)
{
    echo "Không có nhóm quyền nào, không có gì để cấp.\n";
    exit(0);
}

$heldStatement->execute(array((int)$top['group']['id']));
$held = $heldStatement->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_diff(array_keys($newPermissions), $held));

echo 'Nhóm giữ nhiều quyền nhất: #', $top['group']['id'], ' "', $top['group']['name'], '" (', $top['count'], " quyền)\n";
if (!$missing)
{
    echo "Đã đủ cả 5 quyền tài liệu sản phẩm.\n";
    exit(0);
}
foreach ($missing as $permission) echo '  - sẽ thêm ', $newPermissions[$permission], ' (', $permission, ")\n";

if (!$apply)
{
    echo "\nChưa ghi gì. Chạy lại kèm --apply để thực hiện.\n";
    exit(0);
}

$insert = $pdo->prepare("insert into `{$prefix}permission` (id_permission_group, permission) values (?, ?)");
$pdo->beginTransaction();
try
{
    foreach ($missing as $permission) $insert->execute(array((int)$top['group']['id'], $permission));
    $pdo->commit();
}
catch (Throwable $exception)
{
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "LỖI: ".$exception->getMessage()."\nĐã rollback, dữ liệu giữ nguyên.\n");
    exit(1);
}

echo "\nĐã thêm ", count($missing), ' quyền cho nhóm "', $top['group']['name'], "\".\n";
echo "Người đang đăng nhập cần đăng xuất rồi vào lại để nhận quyền mới.\n";
