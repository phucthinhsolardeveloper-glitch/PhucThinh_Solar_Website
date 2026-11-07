<?php
/**
 * Thêm các thương hiệu đối tác mới vào bảng thương hiệu sản phẩm (type san-pham).
 * Thương hiệu đã có (theo slug hoặc tên) được giữ nguyên; slug đã dùng ở bảng khác thì bỏ qua thương hiệu đó và báo lại.
 * Logo hiển thị trên storefront nằm ở frontend/public/assets/images/brand-logos/.
 *
 * Preview: php migrations/077_add_partner_brands.php
 * Apply:   php migrations/077_add_partner_brands.php --apply
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) { fwrite(STDERR, "Không tìm thấy libraries/config.php.\n"); exit(1); }

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';

$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { fwrite(STDERR, "Table prefix không hợp lệ.\n"); exit(1); }

$db = $config['database'];
$pdo = new PDO(
    'mysql:host='.$db['host'].';port='.(int)$db['port'].';dbname='.$db['dbname'].';charset=utf8mb4',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$brands = array(
    array('TCL Solar', 'tcl-solar'),
    array('Sigenergy', 'sigenergy'),
    array('Deye', 'deye'),
    array('Anker SOLIX', 'anker-solix'),
    array('SOFAR', 'sofar'),
    array('Astronergy', 'astronergy'),
    array('GoodWe', 'goodwe'),
    array('Growatt', 'growatt'),
    array('Huawei', 'huawei'),
    array('Sharp', 'sharp'),
    array('Sungrow', 'sungrow'),
);

/* Mọi bảng có slugvi đều chia chung không gian URL, nên slug mới không được trùng ở bất kỳ bảng nào. */
$slugTables = $pdo->prepare("select table_name from information_schema.columns where table_schema = database() and column_name = 'slugvi' and table_name like ?");
$slugTables->execute(array($prefix.'%'));
$tables = $slugTables->fetchAll(PDO::FETCH_COLUMN);

$plan = array();
foreach($brands as $brand)
{
    list($name, $slug) = $brand;
    $existing = $pdo->prepare("select id from `{$prefix}product_brand` where type = 'san-pham' and (slugvi = ? or lower(namevi) = lower(?)) limit 1");
    $existing->execute(array($slug, $name));
    $id = $existing->fetchColumn();
    if($id) { echo "SKIP: {$name} đã có (id={$id}).\n"; continue; }

    foreach($tables as $table)
    {
        if(!preg_match('/^[a-zA-Z0-9_]+$/', $table)) continue;
        $taken = $pdo->prepare("select count(*) from `{$table}` where slugvi = ?");
        $taken->execute(array($slug));
        if((int)$taken->fetchColumn() > 0) { fwrite(STDERR, "DỪNG: slug '{$slug}' đã dùng ở {$table}. Không ghi gì.\n"); exit(1); }
    }
    $plan[] = $brand;
    echo "PREVIEW: sẽ thêm thương hiệu {$name} (slug {$slug}).\n";
}

if(!$plan) { echo "Không có gì để thêm.\n"; exit(0); }
if(!$apply) { echo "\nChưa ghi gì. Backup DB rồi chạy lại kèm --apply.\n"; exit(0); }

$insert = $pdo->prepare("insert into `{$prefix}product_brand` (namevi, nameen, slugvi, slugen, photo, options, numb, status, type, date_created, date_updated, contentvi, contenten, descvi, descen) values (?, ?, ?, ?, '', '[]', 0, 'hienthi', 'san-pham', ?, 0, '', '', '', '')");
$pdo->beginTransaction();
try
{
    foreach($plan as $brand) $insert->execute(array($brand[0], $brand[0], $brand[1], $brand[1], time()));
    $pdo->commit();
}
catch(Throwable $exception)
{
    $pdo->rollBack();
    fwrite(STDERR, 'LỖI: '.$exception->getMessage()."\n");
    exit(1);
}
echo "\nĐã thêm ".count($plan)." thương hiệu.\n";
