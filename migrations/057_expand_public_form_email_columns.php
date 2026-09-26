<?php
/**
 * Đồng bộ schema email với maxlength=150 đang dùng ở các form public.
 * Hai bảng legacy hiện chỉ varchar(100), có thể cắt email hợp lệ dài hơn.
 *
 * Mặc định chỉ preview:
 *   php migrations/057_expand_public_form_email_columns.php
 *   php migrations/057_expand_public_form_email_columns.php --apply
 *   php migrations/057_expand_public_form_email_columns.php --rollback
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
$database = !empty($config['database']['dbname']) ? $config['database']['dbname'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix) || !preg_match('/^[a-zA-Z0-9_]+$/', $database))
{
    fwrite(STDERR, "Tên database hoặc table prefix không hợp lệ.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$database.';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$tables = array('newsletter', 'contact');
$targetLength = $rollback ? 100 : 150;
$changed = 0;
foreach($tables as $suffix)
{
    $table = $prefix.$suffix;
    $meta = $pdo->prepare("select character_maximum_length from information_schema.columns where table_schema=? and table_name=? and column_name='email' limit 1");
    $meta->execute(array($database, $table));
    $current = (int)$meta->fetchColumn();
    if($current <= 0)
    {
        fwrite(STDERR, "Không tìm thấy cột $table.email.\n");
        exit(1);
    }
    echo "$table.email: varchar($current) -> varchar($targetLength)".($current === $targetLength ? " (đã đúng)" : '').".\n";
    if($current === $targetLength) continue;
    if($rollback && $current > 100)
    {
        $tooLong = (int)$pdo->query("select count(*) from `{$table}` where char_length(email) > 100")->fetchColumn();
        if($tooLong > 0)
        {
            fwrite(STDERR, "TỪ CHỐI rollback: $table có $tooLong email dài hơn 100 ký tự.\n");
            exit(1);
        }
    }
    if(!$apply && !$rollback) continue;
    $pdo->exec("alter table `{$table}` modify email varchar($targetLength) default null");
    $changed++;
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì. Chạy lại với --apply để áp dụng.\n";
    exit(0);
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã đổi schema $changed bảng.\n";
