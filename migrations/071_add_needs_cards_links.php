<?php
/**
 * Gắn link cho khối "Chọn X đúng nhu cầu" ở 7 danh mục (28,25,27,19,22,21,23)
 * để bấm vào mỗi thẻ dẫn tới sản phẩm liên quan, thay vì chỉ là ảnh tĩnh
 * không bấm được. Phát hiện 2026-10-02: sếp muốn mỗi thẻ "định hướng và dẫn
 * dắt" khách tới sản phẩm phù hợp - cơ chế link theo "nhu cầu" (?is_need=)
 * vốn đã có sẵn và đang chạy cho Đèn NLMT (list id=20, không đổi ở đây).
 *
 * Cách gắn link, ưu tiên theo mức độ grounded (không suy đoán mơ hồ):
 * - 6/8 danh mục: có danh mục con (product_cat) khớp tự nhiên với từng thẻ
 *   -> dùng URL canonical của danh mục con đó (?is_cat= bị redirect mất, đã
 *   test; URL dạng đường dẫn mới filter đúng, đã verify qua curl từng cái).
 * - Trụ đèn thẻ 2+3 (Khu dân cư/Công viên) và Pin lưu trữ thẻ 2+3 (Tối ưu
 *   dùng điện/Hệ độc lập vùng xa): catalog không có đủ danh mục con để tách
 *   riêng biệt từng thẻ - 2 thẻ trỏ chung 1 danh mục con gần nhất, chấp nhận
 *   trùng đích thay vì suy đoán 1 target không có căn cứ.
 * - Quạt, Tấm pin: KHÔNG có danh mục con nào phân theo nhu cầu sử dụng (Tấm
 *   pin chỉ có "Kẹp tấm pin" - phụ kiện, không liên quan) - cả 3 thẻ trỏ về
 *   trang danh mục đầy đủ của chính nó (không suy đoán sản phẩm đại diện).
 * - Bơm nước: cards CHƯA được tạo (còn thiếu ảnh), nên không có trong
 *   migration này - đã cập nhật sẵn link tương tự trực tiếp trong
 *   migration 069 cho lần áp dụng tới.
 *
 * Không đổi title/desc/photo, chỉ set field 'link'.
 *
 * Mặc định chỉ preview:
 *   php migrations/071_add_needs_cards_links.php
 *   php migrations/071_add_needs_cards_links.php --apply
 *   php migrations/071_add_needs_cards_links.php --rollback
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

/* id_list => [label, [title_in_db => link]] */
$linksByList = array(
    28 => array('Đèn sân vườn', array(
        'Cổng & tường rào' => '/danh-muc-san-pham/den-san-vuon/den-tru-cong/',
        'Sân vườn & lối đi' => '/danh-muc-san-pham/den-san-vuon/den-san-vuon-den-nam/',
        'Tiểu cảnh & cây xanh' => '/danh-muc-san-pham/den-san-vuon/den-cam-co/',
    )),
    25 => array('Thiết bị & phụ kiện điện mặt trời', array(
        'Hệ thống mới' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-mppt/',
        'Nâng cấp & mở rộng' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/phu-kien-den-nlmt/',
        'Bảo trì & sửa chữa' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/tiep-dia-ong-bao-ve/',
    )),
    27 => array('Trụ đèn năng lượng mặt trời', array(
        'Đường & cao tốc' => '/danh-muc-san-pham/tru-den/tru-den-duong/',
        'Khu dân cư' => '/danh-muc-san-pham/tru-den/tru-den-trang-tri/',
        'Công viên' => '/danh-muc-san-pham/tru-den/tru-den-trang-tri/',
    )),
    19 => array('Biến tần năng lượng mặt trời', array(
        'Hòa lưới (Grid-tie)' => '/danh-muc-san-pham/bien-tan-inverter/hoa-luoi/',
        'Độc lập (Off-grid)' => '/danh-muc-san-pham/bien-tan-inverter/hybrid-luu-tru/',
    )),
    22 => array('Pin lưu trữ năng lượng mặt trời', array(
        'Dự phòng mất điện' => '/danh-muc-san-pham/pin-luu-tru/pin-battery/',
        'Tối ưu dùng điện' => '/danh-muc-san-pham/pin-luu-tru/he-thong-ess/',
        'Hệ độc lập vùng xa' => '/danh-muc-san-pham/pin-luu-tru/he-thong-ess/',
    )),
    21 => array('Tấm pin năng lượng mặt trời', array(
        'Hộ gia đình' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/',
        'Doanh nghiệp & nhà xưởng' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/',
        'Dự án công suất lớn' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/',
    )),
    23 => array('Quạt năng lượng mặt trời', array(
        'Nhà xưởng & công trình' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/',
        'Sân vườn & ngoài trời' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/',
        'Nơi chưa có điện lưới' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/',
    )),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$selectQuery = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = ? limit 1");
$updateQuery = $pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?");

$planned = array();
foreach($linksByList as $listId => $def)
{
    list($label, $linkMap) = $def;

    $selectQuery->execute(array($listId));
    $existing = $selectQuery->fetch(PDO::FETCH_ASSOC);
    if(!$existing)
    {
        fwrite(STDERR, "SKIP $label: không tìm thấy content_section (list id=$listId).\n");
        continue;
    }

    $decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
    $decoded = is_array($decoded) ? $decoded : array();

    $changed = false;
    $hasCards = false;
    foreach($decoded as &$block)
    {
        if(!is_array($block) || (($block['type'] ?? '') !== 'cards')) continue;
        $hasCards = true;
        foreach($block['items'] as &$item)
        {
            $itemTitle = trim($item['title'] ?? '');
            if(!isset($linkMap[$itemTitle])) continue;
            $target = $rollback ? '' : $linkMap[$itemTitle];
            if(($item['link'] ?? '') !== $target)
            {
                $item['link'] = $target;
                $changed = true;
            }
        }
        unset($item);
    }
    unset($block);

    if(!$hasCards)
    {
        fwrite(STDERR, "SKIP $label: chưa có khối cards (chạy migration 068/069 trước).\n");
        continue;
    }
    if(!$changed)
    {
        fwrite(STDERR, "SKIP $label: không có gì thay đổi.\n");
        continue;
    }

    $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => $decoded);
}

if(empty($planned))
{
    echo "Không có danh mục nào cần cập nhật.\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($planned as $p) echo "  [{$p['label']}] content_section id={$p['id']}\n";
    echo "Chạy lại với --apply để áp dụng (".count($planned)." danh mục).\n";
    exit(0);
}

$now = time();
$done = 0;
foreach($planned as $p)
{
    $json = htmlspecialchars(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
    $updateQuery->execute(array($json, $now, $p['id']));
    $done++;
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã cập nhật $done danh mục.\n";
