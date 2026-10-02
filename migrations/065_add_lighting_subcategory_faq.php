<?php
/**
 * Soạn FAQ riêng cho 4 danh mục con của "Đèn năng lượng mặt trời" (Đèn Pha,
 * Đèn Đường, Đèn UFO, Đèn Ốp Trần) - trước đó các trang này tự kế thừa
 * nguyên bộ FAQ chung từ danh mục cha (migrations/063, cơ chế kế thừa thêm ở
 * sources/product.php) nên 4 trang hiện y hệt nhau, không phản ánh đặc thù
 * từng loại đèn (sếp phản hồi 2026-10-02: "content FAQ không đa dạng...
 * phải phối hợp đa dạng chứ").
 *
 * Nội dung KHÔNG bịa mới - chắt lọc trực tiếp từ nội dung mô tả kỹ thuật đã
 * có sẵn riêng từng danh mục con (content_section scope=cat, soạn từ phiên
 * trước), chỉ chuyển sang định dạng hỏi-đáp. Sau khi có FAQ riêng, cơ chế kế
 * thừa ở sources/product.php tự động không còn kế thừa từ cha nữa (chỉ kế
 * thừa khi danh mục con rỗng FAQ).
 *
 * Mặc định chỉ preview:
 *   php migrations/065_add_lighting_subcategory_faq.php
 *   php migrations/065_add_lighting_subcategory_faq.php --apply
 *   php migrations/065_add_lighting_subcategory_faq.php --rollback
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

/* slug (chỉ để log) => [id_cat, 3 cặp faq] */
$faqByCat = array(
    'den-pha-nang-luong-mat-troi' => array(19, array(
        array('q' => 'Nên chọn đèn pha theo những tiêu chí nào?', 'a' => 'Xem quang thông, góc chiếu, chiều cao lắp và diện tích cần chiếu - công suất điện chỉ là một phần của bài toán. Đèn ngoài trời cần kết cấu tản nhiệt tốt, kính/gioăng bền và cấp bảo vệ phù hợp môi trường sử dụng.'),
        array('q' => 'Vùng sáng và độ rọi phụ thuộc vào đâu?', 'a' => 'Góc chiếu hẹp cho cường độ cao, chiếu xa hơn; góc rộng phủ diện tích lớn nhưng độ rọi trung tâm giảm. Chiều cao lắp và góc ngẩng quyết định vùng phủ, độ đồng đều và mức chói - với sân bãi lớn nên mô phỏng lux thay vì ước lượng theo số lượng đèn.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt và bảo trì?', 'a' => 'Tránh hướng ánh sáng trực tiếp vào mắt người hoặc khu dân cư lân cận. Giữ mặt kính sạch, không che cánh tản nhiệt, kiểm tra gioăng/cable gland định kỳ và siết lại giá đỡ nếu có rung hoặc gió mạnh.'),
    )),
    'den-duong' => array(20, array(
        array('q' => 'Nên chọn đèn đường theo những tiêu chí nào?', 'a' => 'Xác định loại đường, bề rộng, chiều cao cột và khoảng cách dự kiến trước khi chọn công suất. Phân bố quang học của đèn quan trọng không kém quang thông vì ánh sáng cần kéo dọc theo tuyến đường.'),
        array('q' => 'Độ đồng đều ánh sáng trên mặt đường phụ thuộc vào đâu?', 'a' => 'Chiều cao cột, khoảng cách cột và optics phải phối hợp với nhau để tránh vùng tối giữa hai cột; đèn công suất lớn nhưng quang học không phù hợp vẫn có thể tạo điểm rất sáng ngay dưới cột trong khi vùng giữa hai cột lại tối.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Chọn nhiệt độ màu và góc nghiêng phù hợp, hạn chế ánh sáng hắt lên trời hoặc vào nhà dân; kiểm tra cần đèn, đường kính lỗ lắp và khả năng chịu gió của toàn bộ cụm đèn. Với loại chạy năng lượng mặt trời, cần tính thêm pin theo số giờ hoạt động và tính cả mùa mưa/ít nắng.'),
    )),
    'den-ufo' => array(23, array(
        array('q' => 'Nên chọn đèn UFO theo những tiêu chí nào?', 'a' => 'Chọn công suất và góc chiếu theo chiều cao treo, diện tích ô chiếu và độ rọi yêu cầu - trần càng cao thường càng cần quang thông và kiểm soát chùm sáng tốt hơn. Chất lượng driver, LED và tản nhiệt quyết định đáng kể độ ổn định và suy giảm quang thông theo thời gian.'),
        array('q' => 'Đèn UFO phù hợp không gian nào?', 'a' => 'Phù hợp nhà xưởng, kho, gara hoặc không gian trần cao nhờ thân gọn, tản nhiệt tốt và quang thông lớn. Optics hẹp phù hợp trần cao hơn, optics rộng phù hợp không gian thấp hoặc cần vùng phủ lớn; khu vực kiểm tra màu sắc/sản phẩm nên ưu tiên chỉ số hoàn màu CRI cao.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Dùng móc, xích hoặc phụ kiện treo đủ tải kèm biện pháp chống rơi khi môi trường rung động; không che kín khe tản nhiệt hoặc lắp sát nguồn nhiệt. Nhà xưởng cần độ rọi cụ thể nên mô phỏng chiếu sáng trước khi chốt số lượng và khoảng cách đèn.'),
    )),
    'den-op-tran' => array(24, array(
        array('q' => 'Nên chọn đèn ốp trần theo những tiêu chí nào?', 'a' => 'Chọn kích thước, quang thông, nhiệt độ màu và chỉ số hoàn màu phù hợp với không gian - phòng sinh hoạt thường cần ánh sáng dễ chịu hơn khu kỹ thuật. Vị trí ẩm hoặc ngoài trời có mái che cần chọn cấp bảo vệ phù hợp thay vì dùng đèn nội thất thông thường.'),
        array('q' => 'Nhiệt độ màu nên chọn thế nào?', 'a' => '3000K tạo cảm giác ấm, 4000K trung tính, 6500K trắng lạnh - tuỳ mục đích và sở thích. CRI cao giúp màu vật thể tự nhiên hơn, hữu ích trong không gian sinh hoạt hoặc trưng bày; driver tốt giúp giảm nhấp nháy và duy trì tuổi thọ LED.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt và xử lý lỗi?', 'a' => 'Ngắt điện trước khi đấu nối, cố định đế đèn chắc chắn vào bề mặt chịu lực, không để mối nối hở hoặc bị ép giữa thân đèn và trần. Nếu đèn nhấp nháy hoặc suy giảm sáng nhanh, kiểm tra nguồn, driver, mối nối và nhiệt độ vận hành.'),
    )),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$selectQuery = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'cat' and scope_id = ? limit 1");
$updateQuery = $pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?");

$planned = array();
foreach($faqByCat as $slug => $def)
{
    list($catId, $faqPairs) = $def;
    $faqBlocks = array();
    foreach($faqPairs as $pair) $faqBlocks[] = array('type' => 'faq', 'question' => $pair['q'], 'answer' => $pair['a']);

    $selectQuery->execute(array($catId));
    $existing = $selectQuery->fetch(PDO::FETCH_ASSOC);
    if(!$existing)
    {
        fwrite(STDERR, "SKIP $slug: không tìm thấy content_section (cat id=$catId) - cần danh mục đã có mô tả riêng trước.\n");
        continue;
    }

    $decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
    $decoded = is_array($decoded) ? $decoded : array();
    $hasFaqAlready = false;
    foreach($decoded as $block) if(is_array($block) && (($block['type'] ?? '') === 'faq')) { $hasFaqAlready = true; break; }

    if(!$rollback && $hasFaqAlready)
    {
        fwrite(STDERR, "SKIP $slug: content_section id={$existing['id']} đã có khối FAQ, không ghi đè.\n");
        continue;
    }
    if($rollback && !$hasFaqAlready)
    {
        fwrite(STDERR, "SKIP $slug: content_section id={$existing['id']} hiện không có FAQ, không có gì để rollback.\n");
        continue;
    }

    if($rollback)
    {
        $restored = array();
        foreach($decoded as $block) if(!(is_array($block) && (($block['type'] ?? '') === 'faq'))) $restored[] = $block;
        $planned[] = array('slug' => $slug, 'id' => (int)$existing['id'], 'blocks' => $restored);
    }
    else
    {
        $planned[] = array('slug' => $slug, 'id' => (int)$existing['id'], 'blocks' => array_merge($decoded, $faqBlocks));
    }
}

if(empty($planned))
{
    echo "Không có danh mục nào cần cập nhật.\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì.\n";
    foreach($planned as $p) echo "  [{$p['slug']}] content_section id={$p['id']}\n";
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
