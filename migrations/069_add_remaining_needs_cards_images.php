<?php
/**
 * Hoàn thiện khối "Chọn X đúng nhu cầu" bằng ảnh minh họa riêng cho từng thẻ
 * (thay thế cách dùng tạm 1 ảnh sản phẩm thật lặp lại 3 lần ở migration 068),
 * và bổ sung khối cards cho 4 danh mục còn lại (Trụ đèn, Biến tần, Pin lưu
 * trữ, Bơm nước) hiện chưa có ảnh nào trong catalog để dùng tạm.
 *
 * Ảnh do công cụ AI tạo riêng (minh họa tình huống sử dụng, không phải ảnh
 * sản phẩm thật) theo danh sách đã gửi sếp 2026-10-02. Script này CHỦ ĐỘNG
 * kiểm tra file tồn tại trước khi ghi - danh mục nào thiếu bất kỳ ảnh nào
 * trong bộ 3 (hoặc 2, với Biến tần) sẽ bị SKIP toàn bộ, không ghi nửa vời,
 * để không có danh mục nào hiện thiếu 1-2 thẻ ảnh giữa chừng. Chạy lại bao
 * nhiêu lần cũng được, danh mục nào đủ ảnh lúc đó mới được áp dụng.
 *
 * Đặt file ảnh vào thư mục upload sản phẩm của container local
 * (UPLOAD_PRODUCT_L, tức /var/www/html/upload/product/ trong container
 * pts-php81-local) với đúng tên file bên dưới trước khi chạy --apply.
 *
 * Mặc định chỉ preview:
 *   php migrations/069_add_remaining_needs_cards_images.php
 *   php migrations/069_add_remaining_needs_cards_images.php --apply
 *   php migrations/069_add_remaining_needs_cards_images.php --rollback
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

$uploadDir = rtrim($publicHtml, '/').'/'.UPLOAD_PRODUCT_L;

/*
 * id_list => [label, title, [ [photo, title, desc], ... ]]
 * "mode" = 'replace' (ghi đè toàn bộ khối cards hiện có bằng bộ ảnh riêng
 * từng thẻ) hoặc 'add' (danh mục chưa có cards, thêm mới).
 */
$cardsByList = array(
    28 => array('mode' => 'replace', 'label' => 'Đèn sân vườn', 'title' => 'Chọn đèn sân vườn đúng nhu cầu', 'items' => array(
        array('photo' => 'den-san-vuon-cong-tuong.png', 'title' => 'Cổng & tường rào', 'desc' => 'Đảm bảo lối vào sáng rõ, trang trọng', 'link' => '/danh-muc-san-pham/den-san-vuon/den-tru-cong/'),
        array('photo' => 'den-san-vuon-loi-di.png', 'title' => 'Sân vườn & lối đi', 'desc' => 'Tạo không gian cảnh quan ấm cúng', 'link' => '/danh-muc-san-pham/den-san-vuon/den-san-vuon-den-nam/'),
        array('photo' => 'den-san-vuon-tieu-canh.png', 'title' => 'Tiểu cảnh & cây xanh', 'desc' => 'Nhấn điểm cho cây, biển nhỏ', 'link' => '/danh-muc-san-pham/den-san-vuon/den-cam-co/'),
    )),
    23 => array('mode' => 'replace', 'label' => 'Quạt năng lượng mặt trời', 'title' => 'Chọn quạt đúng nhu cầu', 'items' => array(
        array('photo' => 'quat-nha-xuong.png', 'title' => 'Nhà xưởng & công trình', 'desc' => 'Làm mát không gian rộng, ổn định', 'link' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/'),
        array('photo' => 'quat-ngoai-troi.png', 'title' => 'Sân vườn & ngoài trời', 'desc' => 'Thoáng mát cho không gian sinh hoạt', 'link' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/'),
        array('photo' => 'quat-chua-co-dien.png', 'title' => 'Nơi chưa có điện lưới', 'desc' => 'Chủ động nguồn điện từ năng lượng mặt trời', 'link' => '/danh-muc-san-pham/quat-nang-luong-mat-troi/'),
    )),
    21 => array('mode' => 'replace', 'label' => 'Tấm pin năng lượng mặt trời', 'title' => 'Chọn tấm pin đúng nhu cầu', 'items' => array(
        array('photo' => 'tam-pin-ho-gia-dinh.png', 'title' => 'Hộ gia đình', 'desc' => 'Tiết kiệm điện cho mái nhà dân dụng', 'link' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/'),
        array('photo' => 'tam-pin-doanh-nghiep.png', 'title' => 'Doanh nghiệp & nhà xưởng', 'desc' => 'Giảm chi phí vận hành sản xuất', 'link' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/'),
        array('photo' => 'tam-pin-du-an-lon.png', 'title' => 'Dự án công suất lớn', 'desc' => 'Hệ thống điện mặt trời quy mô lớn', 'link' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/'),
    )),
    25 => array('mode' => 'replace', 'label' => 'Thiết bị & phụ kiện điện mặt trời', 'title' => 'Chọn thiết bị đúng nhu cầu', 'items' => array(
        array('photo' => 'thiet-bi-he-thong-moi.png', 'title' => 'Hệ thống mới', 'desc' => 'Đầy đủ thiết bị để lắp đặt hoàn chỉnh', 'link' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-mppt/'),
        array('photo' => 'thiet-bi-nang-cap.png', 'title' => 'Nâng cấp & mở rộng', 'desc' => 'Bổ sung, thay thế theo nhu cầu', 'link' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/phu-kien-den-nlmt/'),
        array('photo' => 'thiet-bi-bao-tri.png', 'title' => 'Bảo trì & sửa chữa', 'desc' => 'Vật tư thay thế, khắc phục sự cố', 'link' => '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/tiep-dia-ong-bao-ve/'),
    )),
    27 => array('mode' => 'add', 'label' => 'Trụ đèn năng lượng mặt trời', 'title' => 'Chọn trụ đèn đúng nhu cầu', 'items' => array(
        array('photo' => 'tru-den-duong-caotoc.png', 'title' => 'Đường & cao tốc', 'desc' => 'Chiếu sáng tuyến đường, đảm bảo an toàn giao thông', 'link' => '/danh-muc-san-pham/tru-den/tru-den-duong/'),
        array('photo' => 'tru-den-khu-dan-cu.png', 'title' => 'Khu dân cư', 'desc' => 'Chiếu sáng ngõ xóm, khu dân cư', 'link' => '/danh-muc-san-pham/tru-den/tru-den-trang-tri/'),
        array('photo' => 'tru-den-cong-vien.png', 'title' => 'Công viên', 'desc' => 'Chiếu sáng lối đi, không gian công cộng', 'link' => '/danh-muc-san-pham/tru-den/tru-den-trang-tri/'),
    )),
    19 => array('mode' => 'add', 'label' => 'Biến tần năng lượng mặt trời', 'title' => 'Chọn biến tần đúng nhu cầu', 'items' => array(
        array('photo' => 'bien-tan-hoa-luoi.png', 'title' => 'Hòa lưới (Grid-tie)', 'desc' => 'Hệ thống kết nối trực tiếp với điện lưới', 'link' => '/danh-muc-san-pham/bien-tan-inverter/hoa-luoi/'),
        array('photo' => 'bien-tan-doc-lap.png', 'title' => 'Độc lập (Off-grid)', 'desc' => 'Hệ thống độc lập kèm pin lưu trữ', 'link' => '/danh-muc-san-pham/bien-tan-inverter/hybrid-luu-tru/'),
    )),
    22 => array('mode' => 'add', 'label' => 'Pin lưu trữ năng lượng mặt trời', 'title' => 'Chọn pin lưu trữ đúng nhu cầu', 'items' => array(
        array('photo' => 'pin-luu-tru-du-phong.png', 'title' => 'Dự phòng mất điện', 'desc' => 'Duy trì điện khi lưới điện gặp sự cố', 'link' => '/danh-muc-san-pham/pin-luu-tru/pin-battery/'),
        array('photo' => 'pin-luu-tru-toi-uu.png', 'title' => 'Tối ưu dùng điện', 'desc' => 'Lưu điện dư để dùng vào giờ cao điểm', 'link' => '/danh-muc-san-pham/pin-luu-tru/he-thong-ess/'),
        array('photo' => 'pin-luu-tru-vung-xa.png', 'title' => 'Hệ độc lập vùng xa', 'desc' => 'Cấp điện cho khu vực chưa có điện lưới', 'link' => '/danh-muc-san-pham/pin-luu-tru/he-thong-ess/'),
    )),
    24 => array('mode' => 'add', 'label' => 'Bơm nước năng lượng mặt trời', 'title' => 'Chọn bơm nước đúng nhu cầu', 'items' => array(
        /* Không có danh mục con theo nhu cầu (chỉ có Biến tần bơm / Máy bơm,
           chia theo loại linh kiện) - cả 3 thẻ trỏ về trang danh mục đầy đủ,
           giống cách xử lý Quạt/Tấm pin ở migration 071. */
        array('photo' => 'bom-nuoc-tuoi-tieu.png', 'title' => 'Tưới tiêu nông nghiệp', 'desc' => 'Chủ động nguồn nước cho ruộng, vườn', 'link' => '/danh-muc-san-pham/bien-tan-bom/'),
        array('photo' => 'bom-nuoc-ao-ho.png', 'title' => 'Ao hồ & nuôi trồng', 'desc' => 'Cấp nước cho ao cá, hồ nuôi', 'link' => '/danh-muc-san-pham/bien-tan-bom/'),
        array('photo' => 'bom-nuoc-sinh-hoat.png', 'title' => 'Cấp nước sinh hoạt', 'desc' => 'Cấp nước cho téc nước hộ gia đình', 'link' => '/danh-muc-san-pham/bien-tan-bom/'),
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
foreach($cardsByList as $listId => $def)
{
    $label = $def['label'];

    $missing = array();
    foreach($def['items'] as $item) if(!is_file($uploadDir.$item['photo'])) $missing[] = $item['photo'];
    if(!$rollback && !empty($missing))
    {
        fwrite(STDERR, "SKIP $label: thiếu ảnh - ".implode(', ', $missing)."\n");
        continue;
    }

    $cardItems = array();
    foreach($def['items'] as $item) $cardItems[] = array('photo' => $item['photo'], 'title' => $item['title'], 'desc' => $item['desc'], 'link' => isset($item['link']) ? $item['link'] : '');
    $cardsBlock = array('type' => 'cards', 'title' => $def['title'], 'items' => $cardItems);

    $selectQuery->execute(array($listId));
    $existing = $selectQuery->fetch(PDO::FETCH_ASSOC);
    if(!$existing)
    {
        fwrite(STDERR, "SKIP $label: không tìm thấy content_section (list id=$listId).\n");
        continue;
    }

    $decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
    $decoded = is_array($decoded) ? $decoded : array();
    $withoutCards = array();
    $hasCardsAlready = false;
    foreach($decoded as $block)
    {
        if(is_array($block) && (($block['type'] ?? '') === 'cards')) { $hasCardsAlready = true; continue; }
        $withoutCards[] = $block;
    }

    if($rollback)
    {
        if(!$hasCardsAlready)
        {
            fwrite(STDERR, "SKIP $label: hiện không có cards, không có gì để rollback.\n");
            continue;
        }
        $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => $withoutCards);
        continue;
    }

    if($def['mode'] === 'add' && $hasCardsAlready)
    {
        fwrite(STDERR, "SKIP $label: đã có khối cards, không ghi đè (dùng mode replace nếu muốn thay).\n");
        continue;
    }
    if($def['mode'] === 'replace' && !$hasCardsAlready)
    {
        fwrite(STDERR, "SKIP $label: chưa có khối cards để thay (chạy migration 068 trước).\n");
        continue;
    }

    $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => array_merge($withoutCards, array($cardsBlock)));
}

if(empty($planned))
{
    echo "Không có danh mục nào đủ điều kiện cập nhật lúc này.\n";
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
