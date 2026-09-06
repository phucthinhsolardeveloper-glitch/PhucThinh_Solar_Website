<?php
/**
 * Sửa lỗi chính tả "Invester" -> "Inverter" trong nội dung hiển thị công khai.
 *
 * Mục "Những con số ấn tượng" ở trang chủ có dòng "Invester đã cung cấp".
 * Đúng chính tả của thiết bị này là "Inverter" (biến tần). Đây là chữ khách
 * hàng nhìn thấy ngay trang chủ nên đáng sửa.
 *
 * Script quét mọi cột text trong DB chứ không sửa cứng một dòng: nếu chữ sai
 * còn xuất hiện ở bài viết hay mô tả nào khác thì cũng bắt được luôn. Chỉ thay
 * đúng chuỗi "Invester", giữ nguyên hoa/thường theo từng ca, không đụng phần
 * còn lại của nội dung.
 *
 * Chạy:
 *   php migrations/027_fix_inverter_typo.php          # xem trước
 *   php migrations/027_fix_inverter_typo.php --apply  # ghi DB
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

/* config.php từ chối chạy nếu chưa có hằng này. */
define('LIBRARIES', './libraries/');

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}
require $publicHtml.'/libraries/config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schema = $pdo->query('select database()')->fetchColumn();
$columns = $pdo->prepare(
    "select table_name, column_name from information_schema.columns
     where table_schema = ? and data_type in ('varchar','text','mediumtext','longtext','tinytext','char')"
);
$columns->execute(array($schema));

/* Giữ nguyên kiểu chữ của từng ca gặp phải. */
$replacements = array('Invester' => 'Inverter', 'invester' => 'inverter', 'INVESTER' => 'INVERTER');

$found = array();
foreach ($columns->fetchAll(PDO::FETCH_ASSOC) as $column)
{
    $table = $column['table_name'];
    $field = $column['column_name'];
    try
    {
        $statement = $pdo->prepare("select count(*) from `$table` where `$field` like '%Invester%'");
        $statement->execute();
        $total = (int)$statement->fetchColumn();
    }
    catch (Throwable $exception) { continue; }
    if ($total === 0) continue;

    /* Bảng nào có khoá chính đơn thì sửa theo id cho chắc. */
    $keyStatement = $pdo->prepare(
        "select column_name from information_schema.columns
         where table_schema = ? and table_name = ? and column_key = 'PRI'"
    );
    $keyStatement->execute(array($schema, $table));
    $keys = $keyStatement->fetchAll(PDO::FETCH_COLUMN);
    if (count($keys) !== 1)
    {
        echo "  BỎ QUA ", $table, ".", $field, ": bảng không có khoá chính đơn\n";
        continue;
    }
    $found[] = array('table' => $table, 'field' => $field, 'key' => $keys[0], 'rows' => $total);
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
if (!$found)
{
    echo "Không còn chỗ nào chứa \"Invester\".\n";
    exit(0);
}

$changed = 0;
foreach ($found as $target)
{
    $rows = $pdo->query("select `{$target['key']}` as pk, `{$target['field']}` as body from `{$target['table']}` where `{$target['field']}` like '%Invester%'")
                ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row)
    {
        $before = (string)$row['body'];
        $after = strtr($before, $replacements);
        if ($after === $before) continue;

        printf("  %s.%s #%s\n", $target['table'], $target['field'], $row['pk']);
        printf("     cũ : %s\n", mb_substr(trim(preg_replace('/\s+/', ' ', $before)), 0, 90));
        printf("     mới: %s\n", mb_substr(trim(preg_replace('/\s+/', ' ', $after)), 0, 90));

        if ($apply)
        {
            $update = $pdo->prepare("update `{$target['table']}` set `{$target['field']}` = ? where `{$target['key']}` = ?");
            $update->execute(array($after, $row['pk']));
        }
        $changed++;
    }
}

echo "\n", $apply ? "Đã sửa " : "Sẽ sửa ", $changed, " chỗ.\n";
if (!$apply) echo "Chưa ghi gì. Chạy lại kèm --apply để sửa thật.\n";
else echo "Nhớ xoá cache công khai để trang chủ hiện chữ mới.\n";
