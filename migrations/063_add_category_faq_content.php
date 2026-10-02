<?php
/**
 * Soạn FAQ (3 câu hỏi-đáp/danh mục) cho 8 danh mục cấp 1 còn thiếu - trước đó
 * chỉ "Đèn năng lượng mặt trời" (list id 20) có sẵn FAQ, các danh mục khác có
 * nội dung mô tả (heading/list) nhưng 0 khối FAQ nên khối "Câu hỏi thường gặp"
 * ở trang danh mục của chúng trống rỗng.
 *
 * Nội dung FAQ KHÔNG bịa mới - chắt lọc trực tiếp từ chính nội dung mô tả kỹ
 * thuật đã có sẵn trong content_section của từng danh mục (soạn bởi phiên
 * trước), chỉ chuyển sang định dạng hỏi-đáp cho khớp mẫu đã dùng ở "đèn".
 *
 * list_id 28 (Đèn sân vườn) chưa có content_section nào - tạo mới hẳn 1 row
 * chỉ gồm FAQ (không có heading/list vì khối ẩn với mọi danh mục "đèn-" theo
 * $ptsIsLightingCategory, soạn thêm prose cũng không hiển thị).
 *
 * Mặc định chỉ preview:
 *   php migrations/063_add_category_faq_content.php
 *   php migrations/063_add_category_faq_content.php --apply
 *   php migrations/063_add_category_faq_content.php --rollback
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

/* slug (chỉ để đối chiếu/log) => [id_list, 3 cặp faq] */
$faqByList = array(
    'tam-pin-nang-luong-mat-troi' => array(21, array(
        array('q' => 'Nên chọn tấm pin theo những tiêu chí nào?', 'a' => "Xem hiệu suất mô-đun, công suất, kích thước và các thông số Voc, Vmp, Isc, Imp, hệ số nhiệt, tải cơ học, cấu trúc kính cùng điều kiện bảo hành của đúng model. Hiệu suất cao hữu ích khi diện tích mái hạn chế, nhưng sản lượng thực tế còn phụ thuộc hướng mái, góc nghiêng, bóng che và thiết kế điện."),
        array('q' => 'Sản lượng điện phụ thuộc vào đâu?', 'a' => "Công suất danh định đo trong điều kiện thử nghiệm tiêu chuẩn (STC) nên công suất thực tế thay đổi theo bức xạ, nhiệt độ tế bào, góc chiếu và tổn hao hệ thống. Shading, bụi bẩn, mismatch giữa các tấm/chuỗi và tổn hao trên cáp cũng làm giảm sản lượng."),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => "Tránh bóng che cục bộ, chừa khoảng thông gió phía sau tấm pin, kẹp đúng vùng cho phép và không để dây DC nằm trên mái hoặc nơi đọng nước. Các chuỗi đấu cùng MPPT nên có đặc tính và điều kiện chiếu sáng tương đồng để tránh mismatch."),
    )),
    'bien-tan-inverter' => array(19, array(
        array('q' => 'Nên chọn biến tần theo những tiêu chí nào?', 'a' => "Xem công suất AC, điện áp DC tối đa, dải MPPT, số MPPT, dòng vào mỗi MPPT, hiệu suất, cấp bảo vệ và khả năng giám sát. Với hybrid cần thêm điện áp pin, dòng sạc/xả, công suất backup và danh sách pin tương thích."),
        array('q' => 'Sản lượng điện hòa lưới phụ thuộc vào đâu?', 'a' => "Hiệu suất tối đa chỉ là thông số tham khảo; sản lượng thực còn phụ thuộc tải, điểm làm việc trong ngày, số tấm trên mỗi string và việc Vmp có nằm trong dải MPPT hay không."),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => "Tính số tấm mỗi string để Voc trong điều kiện lạnh nhất không vượt giới hạn DC; lắp inverter nơi thông thoáng, chừa khoảng tản nhiệt, bố trí bảo vệ DC/AC, tiếp địa và chống sét lan truyền phù hợp."),
    )),
    'pin-luu-tru' => array(22, array(
        array('q' => 'Nên chọn pin lưu trữ theo những tiêu chí nào?', 'a' => "Phân biệt rõ dung lượng kWh (năng lượng lưu trữ được) và công suất kW (khả năng cấp tải tức thời); pin phải tương thích điện áp, dòng sạc/xả và giao tiếp BMS với inverter, ưu tiên cấu hình nằm trong danh sách tương thích do nhà sản xuất công bố."),
        array('q' => 'Dung lượng pin cần bao nhiêu là đủ?', 'a' => "Liệt kê tải cần dự phòng, công suất từng tải và số giờ dự kiến để tính tổng Wh/kWh cần thiết. Dung lượng danh định không bằng dung lượng sử dụng thực tế - cần trừ theo giới hạn SOC/DoD và hiệu suất inverter."),
        array('q' => 'Cần lưu ý gì khi lắp đặt và sử dụng?', 'a' => "Lắp tại nơi khô, thông thoáng, tránh nhiệt độ cao; không tự ý thay đổi số module nối tiếp/song song hoặc vô hiệu hóa bảo vệ BMS. Nhiệt độ cao kéo dài có thể làm pin lão hóa nhanh hơn."),
    )),
    'quat-nang-luong-mat-troi' => array(23, array(
        array('q' => 'Nên chọn quạt năng lượng mặt trời theo những tiêu chí nào?', 'a' => "Phân biệt công suất tấm pin, công suất động cơ, dung lượng pin và thời gian sử dụng mong muốn. Hiệu quả làm mát còn phụ thuộc đường kính cánh, lưu lượng gió và cách bố trí quạt, không chỉ dựa vào công suất điện."),
        array('q' => 'Thời gian chạy được bao lâu phụ thuộc vào đâu?', 'a' => "Nhu cầu năng lượng tính từ công suất tiêu thụ thực × số giờ sử dụng (ví dụ quạt 40W chạy 5 giờ cần khoảng 200Wh trước tổn hao). Nếu chạy trực tiếp từ tấm pin, tốc độ thay đổi theo nắng; muốn chạy ổn định hoặc ban đêm cần pin và bộ điều khiển phù hợp."),
        array('q' => 'Cần lưu ý gì khi lắp đặt và sử dụng?', 'a' => "Đặt tấm pin nơi không bị che bóng và vệ sinh định kỳ; tránh để bộ điều khiển, pin và đầu nối ở nơi tích nhiệt hoặc nước xâm nhập. Không để pin lithium trong khoang quá nóng hoặc dưới nắng trực tiếp nếu sản phẩm không thiết kế cho điều kiện đó."),
    )),
    'bien-tan-bom' => array(24, array(
        array('q' => 'Nên chọn hệ bơm năng lượng mặt trời theo những tiêu chí nào?', 'a' => "Bắt đầu từ nhu cầu nước (lưu lượng m³/ngày) và cột áp tổng (gồm cả độ cao nâng nước và tổn thất đường ống, van, co). Chọn bơm theo đường đặc tính lưu lượng - cột áp, sau đó mới chọn biến tần và công suất dàn pin phù hợp."),
        array('q' => 'Lưu lượng nước bơm được phụ thuộc vào đâu?', 'a' => "Sản lượng nước thay đổi theo nắng trong ngày và bức xạ theo mùa; hệ thống cần kiểm tra ở tháng bất lợi nếu phải đảm bảo nguồn nước quanh năm. Mực nước giếng khi đang bơm (mực nước động) thường thấp hơn mực nước tĩnh nên cần dùng để tính toán thực tế."),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => "Bổ sung bảo vệ chạy khô, quá dòng, quá áp, mất pha hoặc cảm biến mực nước khi ứng dụng yêu cầu; bố trí chống sét và tiếp địa cho hệ ngoài trời. Bể chứa nước thường là giải pháp lưu trữ kinh tế hơn so với dùng pin điện để bơm ban đêm."),
    )),
    'thiet-bi-dien-nang-luong-mat-troi' => array(25, array(
        array('q' => 'Cần những thiết bị phụ trợ nào cho một hệ điện mặt trời?', 'a' => "Ngoài tấm pin và inverter, hệ cần cáp DC, đầu nối, cầu chì, MCB/MCCB, isolator, SPD, tủ điện, thiết bị đo lường và phụ kiện đi dây - gọi chung là BOS (balance of system)."),
        array('q' => 'Vì sao không dùng được thiết bị AC cho mạch DC?', 'a' => "Hồ quang DC khó dập hơn AC nên thiết bị DC cần điện áp định mức cao hơn điện áp cực đại dự kiến của chuỗi pin và khả năng đóng cắt phù hợp dòng DC - không nên dùng thiết bị chỉ có thông số AC cho mạch DC nếu nhà sản xuất không cho phép."),
        array('q' => 'Cần lưu ý gì khi thi công và nghiệm thu?', 'a' => "Tách tuyến DC/AC hợp lý, đánh dấu cực tính rõ ràng, siết đúng mô-men khuyến nghị; trước khi đóng điện cần đo cách điện, cực tính và điện áp chuỗi theo quy trình nghiệm thu."),
    )),
    'tru-den' => array(27, array(
        array('q' => 'Nên chọn trụ đèn theo những tiêu chí nào?', 'a' => "Chiều cao, độ dày, đường kính, kết cấu thân trụ và móng phải phù hợp tải trọng bộ đèn, tấm pin, cần đèn và điều kiện gió tại khu vực lắp đặt. Lớp bảo vệ bề mặt như mạ kẽm nhúng nóng giúp tăng khả năng chống ăn mòn, đặc biệt ở khu vực ven biển."),
        array('q' => 'Khả năng chịu lực của trụ phụ thuộc vào đâu?', 'a' => "Chiều cao trụ, độ vươn của cần, khối lượng và diện tích cản gió của bộ đèn/tấm pin là các đầu vào chính khi đánh giá kết cấu. Mặt bích, số lượng - đường kính bu-lông móng và khoảng cách tâm lỗ phải đồng bộ với bộ móng."),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => "Móng và bu-lông neo phải đúng kích thước, cao độ và khoảng cách lỗ mặt bích; kiểm tra độ thẳng đứng trước khi siết hoàn thiện. Không tự ý treo thêm biển, thiết bị hoặc tấm pin lớn hơn thiết kế nếu chưa kiểm tra lại tải gió."),
    )),
    'den-san-vuon' => array(28, array(
        array('q' => 'Nên chọn đèn sân vườn theo những tiêu chí nào?', 'a' => "Chọn theo vị trí lắp (trụ cổng, treo tường, cắm cỏ hay đặt nền sân vườn), công suất, quang thông và kiểu ánh sáng phù hợp không gian. Đèn ngoài trời cần cấp bảo vệ chống nước/bụi và vật liệu phù hợp mưa, bụi và tia UV."),
        array('q' => 'Thời gian chiếu sáng phụ thuộc vào đâu?', 'a' => "Với đèn năng lượng mặt trời, thời gian sáng phụ thuộc dung lượng pin tích hợp, vị trí đặt tấm pin có đủ nắng hay không và chế độ chiếu sáng (cảm biến, hẹn giờ hay sáng liên tục)."),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => "Chọn cao độ và góc chiếu hạn chế chói trực tiếp vào mắt, xử lý kín lỗ đi dây và mối nối, tránh vị trí tấm pin bị mái hiên hoặc cây cối che nắng."),
    )),
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$selectQuery = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope = 'list' and scope_id = ? limit 1");
$insertQuery = $pdo->prepare("insert into `{$prefix}content_section` (scope, scope_id, mode, inherit_source, blocksvi, blocksen, status, date_updated) values ('list', ?, 'own', 'auto', ?, '', 'hienthi', ?)");
$updateQuery = $pdo->prepare("update `{$prefix}content_section` set blocksvi = ?, date_updated = ? where id = ?");

$planned = array();
foreach($faqByList as $slug => $def)
{
    list($listId, $faqPairs) = $def;
    $faqBlocks = array();
    foreach($faqPairs as $pair) $faqBlocks[] = array('type' => 'faq', 'question' => $pair['q'], 'answer' => $pair['a']);

    $existing = $selectQuery->execute(array($listId)) ? $selectQuery->fetch(PDO::FETCH_ASSOC) : null;

    if(!$existing)
    {
        /* Chưa có row nào cho danh mục này (vd den-san-vuon) - tạo mới chỉ gồm FAQ. */
        if($rollback)
        {
            $planned[] = array('slug' => $slug, 'action' => 'delete-created-row', 'list_id' => $listId);
        }
        else
        {
            $planned[] = array('slug' => $slug, 'action' => 'insert', 'list_id' => $listId, 'blocks' => $faqBlocks);
        }
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
        $planned[] = array('slug' => $slug, 'action' => 'update', 'id' => (int)$existing['id'], 'blocks' => $restored);
    }
    else
    {
        $merged = array_merge($decoded, $faqBlocks);
        $planned[] = array('slug' => $slug, 'action' => 'update', 'id' => (int)$existing['id'], 'blocks' => $merged);
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
    foreach($planned as $p) echo "  [{$p['slug']}] action={$p['action']}\n";
    echo "Chạy lại với --apply để áp dụng (".count($planned)." danh mục).\n";
    exit(0);
}

$now = time();
$done = 0;
foreach($planned as $p)
{
    if($p['action'] === 'insert')
    {
        $json = htmlspecialchars(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
        $insertQuery->execute(array($p['list_id'], $json, $now));
    }
    elseif($p['action'] === 'update')
    {
        $json = htmlspecialchars(json_encode($p['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
        $updateQuery->execute(array($json, $now, $p['id']));
    }
    elseif($p['action'] === 'delete-created-row')
    {
        $pdo->prepare("delete from `{$prefix}content_section` where scope = 'list' and scope_id = ? and blocksvi like '%faq%'")->execute(array($p['list_id']));
    }
    $done++;
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã cập nhật $done danh mục.\n";
