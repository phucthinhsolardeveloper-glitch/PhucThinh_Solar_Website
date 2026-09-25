<?php
/**
 * Dọn quyền legacy của các type Admin đã bị loại bỏ ở commit 93db949.
 *
 * Mặc định chỉ preview:
 *   php migrations/056_cleanup_removed_admin_permissions.php
 *   php migrations/056_cleanup_removed_admin_permissions.php --apply
 *   php migrations/056_cleanup_removed_admin_permissions.php --rollback
 *
 * Khi apply, các dòng sắp xóa được sao lưu vào bảng
 * table_permission_legacy_backup_056 để rollback không phụ thuộc ID nhóm quyền
 * giữa các môi trường. Rollback chỉ khôi phục nhóm còn tồn tại và tránh trùng.
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$rollback = in_array('--rollback', $argv, true);
if($apply && $rollback)
{
    fwrite(STDERR, "Chỉ được chọn một trong --apply hoặc --rollback.\n");
    exit(1);
}

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

$permissions = array();
foreach(array('tieu-chi','loi-ich','so-lieu-thong-ke') as $type)
    foreach(array('man','add','edit','delete') as $action) $permissions[] = 'news_'.$action.'_'.$type;
foreach(array('doitac','home-proof-background','home-hero-bg','slide') as $type)
    foreach(array('man_photo','add_photo','edit_photo','delete_photo') as $action) $permissions[] = 'photo_'.$action.'_'.$type;
foreach(array('loi-ich','su-menh') as $type) $permissions[] = 'static_update_'.$type;
$permissions = array_values(array_unique($permissions));

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$permissionTable = "`{$prefix}permission`";
$groupTable = "`{$prefix}permission_group`";
$backupTable = "`{$prefix}permission_legacy_backup_056`";
$marks = implode(',', array_fill(0, count($permissions), '?'));

if($rollback)
{
    $exists = $pdo->query("show tables like ".$pdo->quote($prefix.'permission_legacy_backup_056'))->fetchColumn();
    if(!$exists)
    {
        echo "SKIP: không có bảng backup để rollback.\n";
        exit(0);
    }
    $sql = "insert into $permissionTable (id_permission_group, permission)
            select b.id_permission_group, b.permission from $backupTable b
            inner join $groupTable g on g.id=b.id_permission_group
            where not exists (
                select 1 from $permissionTable p
                where p.id_permission_group=b.id_permission_group and p.permission=b.permission
            )";
    $restored = $pdo->exec($sql);
    $pdo->exec("drop table $backupTable");
    echo "ROLLBACK xong: đã khôi phục ".(int)$restored." quyền legacy và xóa bảng backup.\n";
    exit(0);
}

$countStmt = $pdo->prepare("select id_permission_group, count(*) total from $permissionTable where permission in ($marks) group by id_permission_group order by id_permission_group");
$countStmt->execute($permissions);
$groups = $countStmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach($groups as $group)
{
    $total += (int)$group['total'];
    echo "Nhóm #".(int)$group['id_permission_group'].": ".(int)$group['total']." quyền legacy.\n";
}

if(!$apply)
{
    echo "PREVIEW — không ghi gì. Tổng $total quyền sẽ được sao lưu rồi xóa.\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$pdo->exec("create table if not exists $backupTable (
    id_permission_group int(11) not null,
    permission varchar(255) not null,
    backed_up_at int(11) not null default 0,
    unique key uq_group_permission (id_permission_group, permission)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci");

$backupSql = "insert ignore into $backupTable (id_permission_group, permission, backed_up_at)
              select id_permission_group, permission, ? from $permissionTable where permission in ($marks)";
$backupStmt = $pdo->prepare($backupSql);
$backupStmt->execute(array_merge(array(time()), $permissions));

$deleteStmt = $pdo->prepare("delete from $permissionTable where permission in ($marks)");
$deleteStmt->execute($permissions);
echo "APPLY xong: đã dọn ".$deleteStmt->rowCount()." quyền legacy; backup giữ tại {$prefix}permission_legacy_backup_056.\n";

