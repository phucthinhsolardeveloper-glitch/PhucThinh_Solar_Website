<?php
/**
 * Chuyển các bảng MyISAM sang InnoDB và thêm index cho các truy vấn tra cứu lặp
 * nhiều nhất (ảnh theo type, SEO, thư viện ảnh, sản phẩm theo danh mục).
 *
 * Vì sao: MyISAM không có transaction, khóa cả bảng khi Admin ghi và dễ hỏng
 * bảng khi sập máy; các đoạn code đã dùng beginTransaction() chỉ thật sự an toàn
 * trên InnoDB. Không có FULLTEXT/LOCK TABLES nào phụ thuộc MyISAM.
 *
 * ALTER TABLE là DDL nên không gói trong transaction: chạy từng bảng, bảng đã
 * là InnoDB hoặc index đã có thì bỏ qua, chạy lặp an toàn. Bắt buộc backup DB
 * trước khi --apply (deploy SAFE đã làm backup.sh --db-only).
 *
 *   php migrations/076_innodb_and_lookup_indexes.php
 *   php migrations/076_innodb_and_lookup_indexes.php --apply
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
$schema = $config['database']['dbname'];

$indexes = array(
    'photo' => array('idx_photo_type_act' => array('type', 'act')),
    'seo' => array('idx_seo_lookup' => array('com', 'act', 'type', 'id_parent')),
    'gallery' => array('idx_gallery_parent' => array('id_parent', 'com', 'type')),
    'product' => array('idx_product_type_list_cat' => array('type', 'id_list', 'id_cat')),
);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";

$tables = $pdo->prepare("select table_name, engine from information_schema.tables where table_schema = ? and table_type = 'BASE TABLE' and table_name like ? order by table_name");
$tables->execute(array($schema, str_replace('_', '\\_', $prefix).'%'));
$myisam = array();
foreach ($tables->fetchAll(PDO::FETCH_ASSOC) as $row)
    if (strcasecmp((string)$row['engine'], 'MyISAM') === 0) $myisam[] = $row['table_name'];

echo "Bảng MyISAM cần chuyển: ", count($myisam), PHP_EOL;
$failed = array();
foreach ($myisam as $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) continue;
    echo '  - ', $table;
    if (!$apply) { echo " -> InnoDB\n"; continue; }
    try
    {
        $pdo->exec("alter table `{$table}` engine=InnoDB row_format=dynamic");
        echo " -> InnoDB OK\n";
    }
    catch (Throwable $exception)
    {
        $failed[] = $table;
        echo " -> LỖI: ", $exception->getMessage(), "\n";
    }
}

echo PHP_EOL, "Index tra cứu:\n";
$existing = $pdo->prepare("select count(*) from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?");
$columns = $pdo->prepare("select count(*) from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?");
foreach ($indexes as $shortTable => $tableIndexes)
{
    $table = $prefix.$shortTable;
    foreach ($tableIndexes as $name => $cols)
    {
        $existing->execute(array($schema, $table, $name));
        if ((int)$existing->fetchColumn() > 0) { echo '  - ', $table, '.', $name, ": đã có\n"; continue; }
        $missingColumn = false;
        foreach ($cols as $col)
        {
            $columns->execute(array($schema, $table, $col));
            if ((int)$columns->fetchColumn() === 0) $missingColumn = true;
        }
        if ($missingColumn) { echo '  - ', $table, '.', $name, ": bỏ qua (thiếu cột)\n"; continue; }
        echo '  - ', $table, '.', $name, ' (', implode(', ', $cols), ')';
        if (!$apply) { echo " -> sẽ thêm\n"; continue; }
        try
        {
            $pdo->exec("alter table `{$table}` add index `{$name}` (`".implode('`, `', $cols)."`)");
            echo " -> OK\n";
        }
        catch (Throwable $exception)
        {
            $failed[] = $table.'.'.$name;
            echo " -> LỖI: ", $exception->getMessage(), "\n";
        }
    }
}

if (!$apply)
{
    echo "\nChưa ghi gì. Backup DB rồi chạy lại kèm --apply.\n";
    exit(0);
}
if ($failed)
{
    fwrite(STDERR, "\nCó ".count($failed)." mục lỗi: ".implode(', ', $failed)."\nCác mục khác đã áp dụng; xử lý lỗi rồi chạy lại (an toàn).\n");
    exit(1);
}
echo "\nHoàn tất.\n";
