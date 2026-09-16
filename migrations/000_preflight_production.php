<?php
/**
 * Báo cáo chỉ đọc trạng thái schema trước khi deploy production.
 *
 * Chạy qua Cron/CLI, không có tham số để xem migration nào thiếu/dở dang:
 *   php migrations/000_preflight_production.php
 *
 * Local CI yêu cầu mọi schema migration đã đủ:
 *   php migrations/000_preflight_production.php --require-all
 *
 * Script không ghi database, không in credential hoặc dữ liệu tài khoản.
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
set_exception_handler(function($exception) {
    fwrite(STDERR, "FAIL: Không thể hoàn tất schema preflight; kiểm tra config, kết nối DB và log server.\n");
    exit(1);
});

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "FAIL: Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

$schema = isset($config['database']['dbname']) ? (string)$config['database']['dbname'] : '';
$prefix = isset($config['database']['prefix']) ? (string)$config['database']['prefix'] : '';
$requireAll = in_array('--require-all', $argv, true);
if($schema === '' || !preg_match('/^[A-Za-z0-9_]+$/D', $prefix))
{
    fwrite(STDERR, "FAIL: Tên database/prefix không hợp lệ cho preflight.\n");
    exit(1);
}

function preflightTableName($prefix, $logical)
{
    return $prefix.$logical;
}

function preflightTableExists($d, $schema, $table)
{
    $row = $d->rawQueryOne(
        'select count(*) as n from information_schema.tables where table_schema = ? and table_name = ?',
        array($schema, $table)
    );
    return (int)$row['n'] > 0;
}

function preflightColumnExists($d, $schema, $table, $column)
{
    $row = $d->rawQueryOne(
        'select count(*) as n from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?',
        array($schema, $table, $column)
    );
    return (int)$row['n'] > 0;
}

function preflightColumnType($d, $schema, $table, $column)
{
    $row = $d->rawQueryOne(
        'select column_type from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
        array($schema, $table, $column)
    );
    return isset($row['column_type']) ? strtolower((string)$row['column_type']) : '';
}

function preflightIndexExists($d, $schema, $table, $index, $unique = null)
{
    $sql = 'select count(*) as n from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?';
    $params = array($schema, $table, $index);
    if($unique !== null)
    {
        $sql .= ' and non_unique = ?';
        $params[] = $unique ? 0 : 1;
    }
    $row = $d->rawQueryOne($sql, $params);
    return (int)$row['n'] > 0;
}

function preflightEngineIs($d, $schema, $table, $engine)
{
    $row = $d->rawQueryOne(
        'select engine from information_schema.tables where table_schema = ? and table_name = ? limit 1',
        array($schema, $table)
    );
    return isset($row['engine']) && strcasecmp((string)$row['engine'], (string)$engine) === 0;
}

function preflightCountColumns($d, $schema, $table, $columns)
{
    $found = 0;
    foreach($columns as $column) if(preflightColumnExists($d, $schema, $table, $column)) $found++;
    return $found;
}

function preflightStatus($found, $expected)
{
    if($found <= 0) return 'MISSING';
    if($found >= $expected) return 'APPLIED';
    return 'PARTIAL';
}

function preflightAdd(&$checks, $migration, $found, $expected, $detail)
{
    $checks[] = array(
        'migration' => $migration,
        'status' => preflightStatus((int)$found, (int)$expected),
        'found' => (int)$found,
        'expected' => (int)$expected,
        'detail' => $detail
    );
}

$product = preflightTableName($prefix, 'product');
$order = preflightTableName($prefix, 'order');
$orderDetail = preflightTableName($prefix, 'order_detail');
$contentSection = preflightTableName($prefix, 'content_section');
$user = preflightTableName($prefix, 'user');
$checks = array();

preflightAdd($checks, '001', preflightCountColumns($d, $schema, $product, array('cong_suat')), 1, 'table_product.cong_suat');
preflightAdd($checks, '003', preflightCountColumns($d, $schema, $product, array(
    'video_url','battery_capacity','panel_power','material','charging_time','lighting_time',
    'waterproof_rating','led_chip','lighting_area','certificatevi','certificateen'
)), 11, '11 cột nội dung quảng cáo');
preflightAdd($checks, '004', preflightCountColumns($d, $schema, $product, array(
    'light_source','lamp_dimensions','color_temperature','light_color','installation_height',
    'panel_dimensions','package_contents','carton_quantity','carton_dimensions','warranty','additional_specs'
)), 11, '11 cột thông số cấu trúc');

$routeTables = array('product','product_list','product_cat','product_item','product_sub','product_brand',
    'news','news_list','news_cat','news_item','news_sub');
$routeIndexes = 0;
foreach($routeTables as $logical)
{
    $table = preflightTableName($prefix, $logical);
    if(preflightIndexExists($d, $schema, $table, 'idx_route_type_slug')) $routeIndexes++;
    if(preflightIndexExists($d, $schema, $table, 'idx_type_numb_id')) $routeIndexes++;
}
preflightAdd($checks, '007', $routeIndexes, 22, '22 index route/listing');

$commerce = 0;
if(preflightEngineIs($d, $schema, $order, 'InnoDB')) $commerce++;
if(preflightEngineIs($d, $schema, $orderDetail, 'InnoDB')) $commerce++;
if(preflightIndexExists($d, $schema, $orderDetail, 'idx_order_detail_order')) $commerce++;
if(preflightIndexExists($d, $schema, $orderDetail, 'idx_order_detail_product')) $commerce++;
preflightAdd($checks, '008', $commerce, 4, '2 engine InnoDB + 2 index order_detail');

$idempotency = 0;
if(preflightColumnExists($d, $schema, $order, 'checkout_token')) $idempotency++;
if(preflightIndexExists($d, $schema, $order, 'ux_order_checkout_token', true)) $idempotency++;
preflightAdd($checks, '009', $idempotency, 2, 'checkout_token + unique index');
$productProject = preflightTableName($prefix, 'product_project');
$productProjectMarkers = 0;
if(preflightTableExists($d, $schema, $productProject)) $productProjectMarkers++;
if(preflightIndexExists($d, $schema, $productProject, 'ux_product_project_pair', true)) $productProjectMarkers++;
if(preflightIndexExists($d, $schema, $productProject, 'idx_product_project_product')) $productProjectMarkers++;
if(preflightIndexExists($d, $schema, $productProject, 'idx_product_project_project')) $productProjectMarkers++;
preflightAdd($checks, '010', $productProjectMarkers, 4, 'bảng + unique + 2 index product_project');
preflightAdd($checks, '011', preflightCountColumns($d, $schema, $product, array(
    'highlightsvi','highlightsen','applicationsvi','applicationsen','installationvi','installationen'
)), 6, '6 cột content block');
$redirect = preflightTableName($prefix, 'url_redirect');
$redirectMarkers = 0;
if(preflightTableExists($d, $schema, $redirect)) $redirectMarkers++;
if(preflightIndexExists($d, $schema, $redirect, 'uq_url_redirect_old_path', true)) $redirectMarkers++;
if(preflightIndexExists($d, $schema, $redirect, 'idx_url_redirect_entity')) $redirectMarkers++;
preflightAdd($checks, '012', $redirectMarkers, 3, 'bảng + unique source + index target redirect');
$contentMarkers = 0;
if(preflightTableExists($d, $schema, $contentSection)) $contentMarkers++;
if(preflightIndexExists($d, $schema, $contentSection, 'uq_content_section_scope', true)) $contentMarkers++;
preflightAdd($checks, '013', $contentMarkers, 2, 'bảng + unique content_section');
preflightAdd($checks, '016', preflightColumnExists($d, $schema, $contentSection, 'mode') ? 1 : 0, 1, 'table_content_section.mode');
$scopeType = preflightColumnType($d, $schema, $contentSection, 'scope');
preflightAdd($checks, '017', strpos($scopeType, "'cat'") !== false ? 1 : 0, 1, 'scope enum có cat');
preflightAdd($checks, '021', preflightColumnExists($d, $schema, $product, 'photo_khuyenmai') ? 1 : 0, 1, 'table_product.photo_khuyenmai');
$campaignTables = 0;
if(preflightTableExists($d, $schema, preflightTableName($prefix, 'product_campaign'))) $campaignTables++;
if(preflightTableExists($d, $schema, preflightTableName($prefix, 'product_campaign_scope'))) $campaignTables++;
preflightAdd($checks, '022', $campaignTables, 2, '2 bảng campaign');

$visitIndexes = 0;
foreach(array(
    array('counter','idx_counter_ip_tm'), array('counter','idx_counter_tm'),
    array('user_online','idx_user_online_session'), array('user_online','idx_user_online_time')
) as $marker)
{
    if(preflightIndexExists($d, $schema, preflightTableName($prefix, $marker[0]), $marker[1])) $visitIndexes++;
}
preflightAdd($checks, '026', $visitIndexes, 4, '4 index thống kê truy cập');
preflightAdd($checks, '029', preflightIndexExists($d, $schema, $user, 'uniq_user_username', true) ? 1 : 0, 1, 'unique username');
preflightAdd($checks, '036', preflightColumnExists($d, $schema, $product, 'photo_feed') ? 1 : 0, 1, 'table_product.photo_feed');

$unsafe = array();
if(preflightTableExists($d, $schema, $user))
{
    $duplicates = $d->rawQueryOne('select count(*) as n from (select username from '.$user.' group by username having count(*) > 1) duplicate_users');
    if((int)$duplicates['n'] > 0) $unsafe[] = 'Có '.(int)$duplicates['n'].' nhóm username trùng; không được chạy migration 029.';
}
if(preflightTableExists($d, $schema, $orderDetail) && preflightTableExists($d, $schema, $order))
{
    $orphanOrder = $d->rawQueryOne('select count(*) as n from '.$orderDetail.' od left join '.$order.' o on o.id = od.id_order where o.id is null');
    if((int)$orphanOrder['n'] > 0) $unsafe[] = 'Có '.(int)$orphanOrder['n'].' order_detail mồ côi theo order; không được chạy migration 008.';
}
if(preflightTableExists($d, $schema, $orderDetail) && preflightTableExists($d, $schema, $product))
{
    $orphanProduct = $d->rawQueryOne('select count(*) as n from '.$orderDetail.' od left join '.$product.' p on p.id = od.id_product where p.id is null');
    if((int)$orphanProduct['n'] > 0) $unsafe[] = 'Có '.(int)$orphanProduct['n'].' order_detail mồ côi theo product; phải rà trước migration 008.';
}

echo "PREFLIGHT MIGRATION — CHỈ ĐỌC\n";
echo str_repeat('=', 76), "\n";
$missing = array();
$partial = array();
foreach($checks as $check)
{
    printf("%-4s %-8s %2d/%-2d  %s\n", $check['migration'], $check['status'], $check['found'], $check['expected'], $check['detail']);
    if($check['status'] === 'MISSING') $missing[] = $check['migration'];
    if($check['status'] === 'PARTIAL') $partial[] = $check['migration'];
}

echo "\nMigration dữ liệu/PHP không suy đoán bằng schema; luôn chạy dry-run và đọc kết quả:\n";
echo "002, 005, 006, 014, 015, 018, 019, 020, 023, 024, 027, 028\n";
echo "025 nén ảnh là tác vụ tùy chọn, không phải điều kiện deploy code.\n\n";

if(!empty($unsafe))
{
    foreach($unsafe as $message) echo 'UNSAFE: '.$message."\n";
}
if(!empty($partial)) echo 'PARTIAL: '.implode(', ', $partial)." — dừng deploy và đối chiếu schema/backup.\n";
if(!empty($missing)) echo 'MISSING: '.implode(', ', $missing)." — chỉ chạy các file này theo thứ tự sau backup.\n";
if(empty($missing) && empty($partial) && empty($unsafe)) echo "READY: toàn bộ schema migration đã đủ, không chạy lại file SQL.\n";

if(!empty($unsafe) || !empty($partial) || ($requireAll && !empty($missing))) exit(1);
exit(0);
