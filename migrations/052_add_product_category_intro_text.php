<?php
/**
 * Thêm mô tả riêng cho 9 danh mục sản phẩm cấp 1 - hiện tất cả đang rỗng nên
 * trang danh mục tự rơi về 1 câu fallback chung y hệt nhau
 * (templates/product/product_tpl.php:8), làm mất cơ hội SEO landing page
 * riêng cho từng danh mục.
 *
 * Nội dung viết dựa trên dữ liệu thật đang có trong DB (thương hiệu thật của
 * từng danh mục, truy vấn trực tiếp #_product/#_product_brand) - không thêm
 * số liệu kinh doanh, không claim chưa xác minh.
 *
 * Mặc định chỉ preview:
 *   php migrations/052_add_product_category_intro_text.php
 *   php migrations/052_add_product_category_intro_text.php --apply
 *   php migrations/052_add_product_category_intro_text.php --rollback
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
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
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

/* slug => mô tả mới. Chỉ áp dụng khi descvi hiện tại đang rỗng - không ghi
   đè nội dung ai đó đã tự tay soạn trong Admin. */
$descriptions = array(
    'den-nlmt' => 'Đèn đường, đèn pha, đèn UFO và đèn ốp trần dùng năng lượng mặt trời, thương hiệu JINDIAN, BLUE CARBON, ORIANA. Chọn theo công suất, quang thông và khu vực lắp đặt để đủ sáng cho đúng nhu cầu.',
    'den-san-vuon' => 'Đèn sân vườn, đèn trụ cổng, đèn treo tường và đèn cắm cỏ trang trí, thương hiệu BLUE CARBON, ORIANA. Phù hợp lối đi, sân vườn và không gian ngoài trời cần ánh sáng nhẹ, tiết kiệm điện.',
    'tru-den' => 'Trụ đèn đường, trụ trang trí và chân đế lắp đặt đồng bộ với đèn năng lượng mặt trời, thương hiệu JINDIAN, ORIANA. Chọn theo chiều cao trụ và tải trọng đèn để lắp đặt đúng kỹ thuật.',
    'quat-nang-luong-mat-troi' => 'Quạt chạy bằng năng lượng mặt trời cho không gian ngoài trời hoặc nơi chưa có điện lưới ổn định, thương hiệu BLUE CARBON, ORIANA.',
    'tam-pin-nang-luong-mat-troi' => 'Tấm pin năng lượng mặt trời chính hãng LONGi, Canadian Solar, Astronergy, AESOLAR, TCL Solar và các thương hiệu khác. Chọn theo công suất Wp, hiệu suất module và diện tích lắp đặt thực tế.',
    'bien-tan-inverter' => 'Biến tần hòa lưới và hybrid thương hiệu SolaX, chuyển đổi điện DC từ tấm pin sang AC và quản lý sạc/xả pin lưu trữ. Chọn theo công suất AC, số pha và số MPPT phù hợp hệ thống.',
    'pin-luu-tru' => 'Pin lưu trữ năng lượng thương hiệu SolaX, dùng cho hệ điện mặt trời hybrid hoặc dự phòng khi mất điện lưới. Chọn theo dung lượng khả dụng và điện áp hệ pin.',
    'bien-tan-bom' => 'Máy bơm và biến tần bơm nước chạy bằng năng lượng mặt trời, thương hiệu EPSIVO, Hober. Phù hợp tưới tiêu nông nghiệp, ao hồ hoặc cấp nước sinh hoạt ở nơi chưa có điện lưới.',
    'thiet-bi-dien-nang-luong-mat-troi' => 'Thiết bị và phụ kiện điện mặt trời: bộ điều khiển sạc, cáp dây điện, thiết bị giám sát và vật tư phụ trợ, thương hiệu BLUE CARBON, ORIANA, SolaX.'
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$query = $pdo->prepare("select id, slugvi, descvi from `{$prefix}product_list` where type = 'san-pham' and slugvi = ? limit 1");

$planned = array();
foreach($descriptions as $slug => $newDesc)
{
    $query->execute(array($slug));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if(!$row)
    {
        fwrite(STDERR, "SKIP $slug: không tìm thấy danh mục.\n");
        continue;
    }
    $current = (string) $row['descvi'];
    if(!$rollback && trim($current) !== '')
    {
        fwrite(STDERR, "SKIP $slug: descvi đã có nội dung, không ghi đè.\n");
        continue;
    }
    if($rollback && trim($current) !== trim($newDesc))
    {
        fwrite(STDERR, "SKIP $slug: nội dung hiện tại khác bản đã áp dụng, không tự rollback nhầm.\n");
        continue;
    }
    $planned[] = array('id' => (int)$row['id'], 'slug' => $slug, 'from' => $current, 'to' => $rollback ? '' : $newDesc);
}

if(empty($planned))
{
    echo "Không có danh mục nào cần cập nhật.\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($planned as $p) echo "  [{$p['slug']}] -> {$p['to']}\n";
    echo "Chạy lại với --apply để áp dụng (".count($planned)." danh mục).\n";
    exit(0);
}

$update = $pdo->prepare("update `{$prefix}product_list` set descvi = ? where id = ?");
$done = 0;
foreach($planned as $p)
{
    $update->execute(array($p['to'], $p['id']));
    $done++;
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã cập nhật $done danh mục.\n";
