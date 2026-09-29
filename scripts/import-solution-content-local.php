<?php
/**
 * Nhập bộ Markdown Giải pháp đã được nguồn dữ liệu đã phê duyệt vào DB local.
 *
 * Mặc định chỉ preview. Chỉ --apply mới ghi; toàn bộ thay đổi dùng transaction.
 * Script không tải ảnh, không đụng production và không xoá nhóm/URL legacy.
 *
 * php scripts/import-solution-content-local.php --file=/path/content.md
 * php scripts/import-solution-content-local.php --file=/path/content.md --apply
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

function solutionImportArgument(array $arguments, $name)
{
    $prefix = $name.'=';
    foreach ($arguments as $index => $argument)
    {
        if (strpos($argument, $prefix) === 0) return substr($argument, strlen($prefix));
        if ($argument === $name && isset($arguments[$index + 1])) return $arguments[$index + 1];
    }
    return '';
}

function solutionImportPlain($value)
{
    $value = preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', (string)$value);
    $value = str_replace(array('**', '__', '`'), '', $value);
    $value = preg_replace('/(?<!\*)\*([^*]+)\*/u', '$1', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function solutionImportInline($value)
{
    $value = htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace_callback('~\[([^\]]+)\]\((https?://[^\s\)]+)\)~u', function($match) {
        return '<a href="'.$match[2].'" rel="noopener">'.$match[1].'</a>';
    }, $value);
    $value = preg_replace('~`([^`]+)`~u', '<code>$1</code>', $value);
    $value = preg_replace('~\*\*([^*]+)\*\*~u', '<strong>$1</strong>', $value);
    $value = preg_replace('~(?<!\*)\*([^*]+)\*(?!\*)~u', '<em>$1</em>', $value);
    return $value;
}

function solutionImportSimplifyLanguage($markdown)
{
    /* Giữ thuật ngữ cần cho SEO/sản phẩm, nhưng diễn đạt phần nội dung public
       theo ngôn ngữ khách hàng. Các thay thế cụ thể đặt trước cụm tổng quát. */
    return strtr((string)$markdown, array(
        '## SECTION 1 — BẠN ĐANG MUỐN GIẢI QUYẾT ĐIỀU GÌ?' => '## SECTION 1 — XÁC ĐỊNH NHU CẦU SỬ DỤNG NĂNG LƯỢNG',
        '### Bắt đầu từ nhu cầu của bạn' => '### Phương án bắt đầu từ nhu cầu thực tế',
        'Thay vì bắt đầu bằng tên thiết bị hay thông số kỹ thuật, hãy bắt đầu bằng vấn đề bạn muốn giải quyết. Phúc Thịnh Solar sẽ đề xuất hệ thống phù hợp từ đó.' => 'Phúc Thịnh Solar xác định mục tiêu sử dụng trước khi lựa chọn thiết bị và thông số kỹ thuật. Phương án được đề xuất dựa trên nhu cầu thực tế của từng công trình.',
        'Bạn sử dụng nhiều điện vào ban ngày, có mái nhà phù hợp và muốn tận dụng nguồn năng lượng mặt trời tại chỗ?' => 'Giải pháp phù hợp với công trình có mái lắp đặt và mức sử dụng điện cao vào ban ngày.',
        'Bạn cần duy trì các thiết bị quan trọng khi mất điện hoặc muốn sử dụng phần điện mặt trời đã tạo ra vào những thời điểm khác trong ngày?' => 'Pin lưu trữ phù hợp khi cần duy trì thiết bị quan trọng lúc mất điện hoặc sử dụng điện mặt trời vào thời điểm khác trong ngày.',
        'Doanh nghiệp có diện tích mái lớn, nhu cầu điện cao vào ban ngày và muốn giảm chi phí vận hành dài hạn?' => 'Doanh nghiệp có diện tích mái lớn và sử dụng nhiều điện ban ngày có thể tận dụng mái để giảm lượng điện mua từ lưới.',
        'Khu vực cần chiếu sáng ở xa nguồn điện, đường nội bộ, sân vườn, khu công nghiệp hoặc công trình ngoài trời?' => 'Giải pháp đáp ứng nhu cầu chiếu sáng tại đường nội bộ, sân vườn, khu công nghiệp và các vị trí ngoài trời xa nguồn điện.',
        'Trang trại hoặc khu sản xuất cần bơm nước vào ban ngày nhưng vị trí xa lưới điện hoặc chi phí kéo điện cao?' => 'Giải pháp phù hợp với trang trại hoặc khu sản xuất cần bơm nước ban ngày tại vị trí xa lưới điện hoặc có chi phí kéo điện cao.',
        '## SECTION 3 — MỘT HỆ THỐNG TỐT KHÔNG BẮT ĐẦU TỪ “CÔNG SUẤT BAO NHIÊU?”' => '## SECTION 3 — THIẾT KẾ DỰA TRÊN NHU CẦU SỬ DỤNG THỰC TẾ',
        '# Chúng tôi bắt đầu bằng việc hiểu cách bạn sử dụng điện' => '# Phúc Thịnh Solar khảo sát cách khách hàng sử dụng điện',
        '## SECTION 5 — VÌ SAO CHỌN PHÚC THỊNH SOLAR?' => '## SECTION 5 — NĂNG LỰC TRIỂN KHAI CỦA PHÚC THỊNH SOLAR',
        '## SECTION 1 — KHI NÀO GIA ĐÌNH NÊN CÂN NHẮC ĐIỆN MẶT TRỜI?' => '## SECTION 1 — NHỮNG GIA ĐÌNH PHÙ HỢP VỚI ĐIỆN MẶT TRỜI',
        'Điện mặt trời đặc biệt đáng cân nhắc nếu gia đình có một hoặc nhiều đặc điểm sau:' => 'Điện mặt trời phù hợp hơn khi gia đình có một hoặc nhiều đặc điểm sau:',
        '## SECTION 3 — HỆ THỐNG HOẠT ĐỘNG NHƯ THẾ NÀO?' => '## SECTION 3 — NGUYÊN LÝ HOẠT ĐỘNG CỦA HỆ THỐNG',
        '## SECTION 4 — MỘT HỆ THỐNG GỒM NHỮNG GÌ?' => '## SECTION 4 — CÁC THÀNH PHẦN CHÍNH CỦA HỆ THỐNG',
        '## SECTION 1 — TẠI SAO DOANH NGHIỆP QUAN TÂM ĐẾN ĐIỆN MẶT TRỜI?' => '## SECTION 1 — LỢI ÍCH CỦA ĐIỆN MẶT TRỜI ĐỐI VỚI DOANH NGHIỆP',
        '## SECTION 2 — KHÔNG NÊN BẮT ĐẦU BẰNG “MÁI LẮP ĐƯỢC BAO NHIÊU MWp?”' => '## SECTION 2 — XÁC ĐỊNH CÔNG SUẤT THEO NHU CẦU SỬ DỤNG ĐIỆN',
        '# Công suất phù hợp phải đi cùng phụ tải phù hợp' => '# Công suất được tính theo mức sử dụng điện thực tế',
        '## SECTION 6 — PHÚC THỊNH SOLAR TRIỂN KHAI NHƯ THẾ NÀO?' => '## SECTION 6 — QUY TRÌNH TRIỂN KHAI CỦA PHÚC THỊNH SOLAR',
        '## SECTION 7 — BẢNG THÔNG TIN CẦN CHO MỘT BÁO GIÁ CHẤT LƯỢNG' => '## SECTION 7 — THÔNG TIN CẦN CÓ ĐỂ LẬP PHƯƠNG ÁN VÀ BÁO GIÁ',
        '## SECTION 1 — PIN LƯU TRỮ KHÔNG CHỈ LÀ “MỘT CỤC PIN LỚN”' => '## SECTION 1 — PIN LƯU TRỮ LÀ MỘT HỆ THỐNG ĐIỆN HOÀN CHỈNH',
        'Không nên chọn pin chỉ bằng câu hỏi: “Nhà tôi dùng bao nhiêu số điện một tháng?”' => 'Dung lượng pin không được xác định chỉ từ tổng điện năng sử dụng trong một tháng.',
        '### Ví dụ tư duy thiết kế' => '### Cách xác định theo từng mục tiêu',
        'Nếu mục tiêu là **backup**, hãy bắt đầu bằng:' => 'Với mục tiêu **cấp điện dự phòng khi mất điện**, Phúc Thịnh Solar xác định:',
        'Nếu mục tiêu là **tối ưu solar**, hãy bắt đầu bằng:' => 'Với mục tiêu **tận dụng điện mặt trời**, Phúc Thịnh Solar xác định:',
        '## SECTION 1 — PHÙ HỢP VỚI NHỮNG CÔNG TRÌNH NÀO?' => '## SECTION 1 — CÁC CÔNG TRÌNH PHÙ HỢP',
        '## SECTION 2 — ĐỪNG CHỌN ĐÈN CHỈ THEO SỐ “W”' => '## SECTION 2 — THIẾT KẾ CHIẾU SÁNG THEO MẶT BẰNG VÀ NHU CẦU',
        '## SECTION 3 — MỘT BỘ ĐÈN HOẠT ĐỘNG NHƯ THẾ NÀO?' => '## SECTION 3 — NGUYÊN LÝ HOẠT ĐỘNG CỦA BỘ ĐÈN',
        '## SECTION 2 — BƠM NƯỚC KHÔNG THỂ CHỌN CHỈ THEO “BAO NHIÊU HP”' => '## SECTION 2 — XÁC ĐỊNH MÁY BƠM THEO NGUỒN NƯỚC VÀ NHU CẦU',
        'Phúc Thịnh Solar chỉ nên đưa ra ước tính sau khi có dữ liệu đầu vào cụ thể.' => 'Phúc Thịnh Solar chỉ đưa ra ước tính sau khi có đủ dữ liệu đầu vào.',
        'Có thể bố trí khảo sát theo quy trình tư vấn dự án và phạm vi dịch vụ tại từng khu vực.' => 'Phúc Thịnh Solar thực hiện khảo sát theo quy trình tư vấn dự án và phạm vi phục vụ tại từng khu vực.',
        'Có thể nghiên cứu. BESS cần được thiết kế dựa trên phụ tải, công suất, mục tiêu vận hành và bài toán kinh tế cụ thể.' => 'Phúc Thịnh Solar sẽ đánh giá khả năng tích hợp pin lưu trữ dựa trên mức sử dụng điện, công suất cần thiết, mục tiêu vận hành và hiệu quả đầu tư.',
        '## SECTION 5 — BAO NHIÊU kWp LÀ PHÙ HỢP?' => '## SECTION 5 — XÁC ĐỊNH CÔNG SUẤT PHÙ HỢP',
        'Không nên chọn hệ thống chỉ bằng cách nhìn vào hóa đơn điện.' => 'Công suất hệ thống cần được xác định từ hóa đơn điện cùng đặc điểm sử dụng và điều kiện lắp đặt thực tế.',
        'Hệ thống pin lưu trữ cho doanh nghiệp cần được tính toán trên dữ liệu sử dụng điện theo từng thời điểm thực tế; không nên thiết kế chỉ dựa trên tổng điện năng tiêu thụ theo tháng.' => 'Phúc Thịnh Solar tính toán hệ thống pin lưu trữ cho doanh nghiệp từ dữ liệu sử dụng điện theo từng thời điểm, thay vì chỉ dựa trên tổng điện năng tiêu thụ theo tháng.',
        'Không cần biết trước nên lắp 5 kWp, 10 kWp hay dùng inverter nào.' => 'Bạn không cần biết trước hệ thống nên có công suất bao nhiêu hoặc dùng loại bộ biến tần nào.',
        '`Solar + Grid ↔ EMS/BESS ↔ Load`' => '`Điện mặt trời + Điện lưới ↔ Hệ thống quản lý và pin lưu trữ ↔ Thiết bị sử dụng điện`',
        '`Đường phát solar → Đường phụ tải → Phần năng lượng dư theo thời gian → Chiến lược lưu trữ`' => '`Sản lượng điện mặt trời theo giờ → Mức điện sử dụng theo giờ → Phần điện còn dư → Dung lượng pin phù hợp`',
        '`Điện mặt trời → Phục vụ tải → Phần phù hợp được đưa vào pin theo cấu hình`' => '`Điện mặt trời → Cấp điện cho thiết bị → Phần điện còn dư được lưu vào pin`',
        '`Điện mặt trời → Inverter → Hệ thống điện nội bộ → Phụ tải nhà máy`' => '`Điện mặt trời → Bộ biến tần → Hệ thống điện nội bộ → Máy móc và thiết bị đang hoạt động`',
        '`Ánh nắng → Tấm pin → Inverter → Thiết bị trong nhà`' => '`Ánh nắng → Tấm pin → Bộ biến tần → Thiết bị trong nhà`',
        '`Tấm pin mặt trời → Bộ điều khiển/inverter bơm → Máy bơm → Nguồn nước → Bể/tưới`' => '`Tấm pin mặt trời → Bộ điều khiển máy bơm → Máy bơm → Nguồn nước → Bể chứa hoặc hệ thống tưới`',
        'Inverter Hybrid giúp phối hợp giữa solar, grid, battery và load.' => 'Bộ biến tần hybrid giúp phối hợp điện mặt trời, điện lưới, pin lưu trữ và các thiết bị sử dụng điện.',
        'Inverter Hybrid cho phép hệ thống được thiết kế để phối hợp giữa nguồn điện mặt trời, điện lưới và pin lưu trữ.' => 'Bộ biến tần hybrid (inverter hybrid) giúp phối hợp điện mặt trời, điện lưới và pin lưu trữ trong cùng một hệ thống.',
        'Inverter Hybrid kết hợp pin lưu trữ.' => 'Hệ thống phối hợp điện mặt trời, điện lưới và pin lưu trữ.',
        'Battery Management System — BMS.' => 'Bộ quản lý và bảo vệ pin (BMS).',
        'BESS — Battery Energy Storage System — là hệ thống lưu trữ năng lượng được thiết kế ở quy mô lớn hơn, có thể phối hợp với nguồn điện mặt trời, lưới điện và phụ tải của doanh nghiệp.' => 'BESS là hệ thống pin lưu trữ quy mô doanh nghiệp, có thể phối hợp với điện mặt trời, điện lưới và nhu cầu sử dụng điện của nhà máy.',
        'Battery Energy Storage System' => 'hệ thống pin lưu trữ quy mô lớn',
        'Battery module.' => 'Cụm pin lưu trữ.',
        'Inverter/PCS phù hợp.' => 'Bộ biến tần hai chiều phù hợp.',
        'EMS đối với hệ thống quy mô lớn.' => 'Bộ điều khiển năng lượng (EMS) cho hệ thống quy mô lớn.',
        'Tủ/cabinet hoặc container tùy thiết kế.' => 'Tủ pin chuyên dụng hoặc container, tùy quy mô.',
        '### DoD — Depth of Discharge' => '### Mức dung lượng pin được sử dụng (DoD)',
        '### Cycle — Chu kỳ' => '### Chu kỳ sạc và xả',
        '### BMS' => '### Bộ quản lý pin (BMS)',
        '### EMS' => '### Hệ thống quản lý năng lượng (EMS)',
        '### Hybrid & pin lưu trữ' => '### Điện mặt trời kết hợp pin lưu trữ',
        '### BESS cho doanh nghiệp' => '### Lưu trữ điện quy mô doanh nghiệp (BESS)',
        '### 2. Hệ thống Hybrid' => '### 2. Hệ thống kết hợp điện mặt trời và pin lưu trữ',
        '### 3. Hybrid + pin lưu trữ' => '### 3. Hệ thống có pin lưu trữ',
        '### Kiến trúc Hybrid' => '### Hệ thống kết hợp điện mặt trời, điện lưới và pin',
        '### Inverter' => '### Bộ biến tần (inverter)',
        '# Chọn kiến trúc phù hợp trước khi chọn thiết bị' => '# Chọn cách hệ thống hoạt động trước khi chọn thiết bị',
        'Phụ tải tập trung vào ban ngày hay buổi tối.' => 'Điện được sử dụng chủ yếu vào ban ngày hay buổi tối.',
        'Biểu đồ phụ tải nếu có.' => 'Biểu đồ mức sử dụng điện theo giờ, nếu có.',
        'biểu đồ tải' => 'biểu đồ mức sử dụng điện theo giờ',
        'Phụ tải ngày làm việc và cuối tuần.' => 'Mức sử dụng điện trong ngày làm việc và cuối tuần.',
        'Kế hoạch mở rộng tải.' => 'Kế hoạch tăng nhu cầu sử dụng điện.',
        '9. Kế hoạch tăng tải trong tương lai.' => '9. Kế hoạch dùng thêm thiết bị điện trong tương lai.',
        'phụ tải lớn vào ban ngày' => 'mức sử dụng điện cao vào ban ngày',
        'phân tích phụ tải' => 'phân tích nhu cầu dùng điện theo từng thời điểm',
        'dữ liệu phụ tải' => 'dữ liệu sử dụng điện theo từng thời điểm',
        'phụ tải điện' => 'mức sử dụng điện theo từng thời điểm',
        'tải đang hoạt động trong nhà' => 'các thiết bị đang sử dụng điện trong nhà',
        'các tải lớn' => 'các thiết bị dùng nhiều điện',
        'tải ưu tiên' => 'thiết bị cần duy trì khi mất điện',
        'nhóm tải' => 'nhóm thiết bị',
        'tải tiêu thụ' => 'nhu cầu sử dụng điện',
        'các tải quan trọng' => 'các thiết bị quan trọng',
        'các tải cần thiết khác' => 'các thiết bị cần thiết khác',
        'Công suất tải.' => 'Tổng công suất thiết bị đang sử dụng.',
        'tự sản xuất, tự tiêu thụ' => 'tạo điện và ưu tiên sử dụng ngay tại chỗ',
        'tự sản xuất tự tiêu thụ' => 'tạo điện và ưu tiên sử dụng tại chỗ',
        'kiến trúc hệ thống' => 'cách hệ thống được kết nối và vận hành',
        'kiến trúc linh hoạt hơn' => 'cách kết nối linh hoạt hơn',
        'chiến lược vận hành' => 'cách vận hành',
        'chiến lược cài đặt' => 'chế độ đã cài đặt',
        'chiến lược quản lý phụ tải phù hợp' => 'cách sử dụng điện phù hợp theo từng thời điểm',
        'công suất inverter/PCS' => 'công suất bộ biến tần',
        'Công suất inverter/PCS.' => 'Công suất bộ biến tần.',
        '1. Tải nào cần cấp điện.' => '1. Những thiết bị nào cần được cấp điện.',
        '2. Công suất tức thời của các tải.' => '2. Tổng công suất của các thiết bị tại cùng một thời điểm.',
        'tổng công suất tải' => 'tổng công suất thiết bị',
        'phối hợp các nguồn, tải và chiến lược vận hành' => 'phối hợp nguồn điện với nhu cầu sử dụng theo cách đã cài đặt',
        'Điều phối nguồn và tải.' => 'Điều phối nguồn điện theo nhu cầu sử dụng.',
        'loại inverter' => 'loại bộ biến tần',
        'mạch tải ưu tiên' => 'mạch cấp điện cho các thiết bị cần duy trì',
        'tấm pin, inverter, pin lưu trữ' => 'tấm pin, bộ biến tần, pin lưu trữ',
        'tấm pin, inverter, hệ khung' => 'tấm pin, bộ biến tần, hệ khung',
        'tổng công suất tải, inverter' => 'tổng công suất thiết bị, bộ biến tần',
        'Thiết bị phù hợp với kiến trúc.' => 'Các thiết bị được thiết kế để hoạt động an toàn cùng nhau.',
        'Giải pháp nhiệt và an toàn tùy kiến trúc sản phẩm.' => 'Giải pháp kiểm soát nhiệt độ và an toàn theo thiết kế sản phẩm.',
        'Cấu hình vận hành rõ ràng.' => 'Thiết lập cách sạc, xả và cấp điện rõ ràng.',
        'Có thể có các kiến trúc lưu trữ khác nhau' => 'Có nhiều cách kết nối hệ thống lưu trữ',
        'đèn, router, tủ lạnh' => 'đèn, bộ phát Wi-Fi, tủ lạnh',
        'Tủ pin chuyên dụng hoặc container, tùy quy mô.' => 'Tủ pin hoặc thùng kỹ thuật chuyên dụng, tùy quy mô.',
        'Kết hợp với BESS trong các bài toán quản lý năng lượng phù hợp.' => 'Kết hợp với hệ thống pin lưu trữ quy mô doanh nghiệp (BESS) khi cần quản lý năng lượng sâu hơn.',
        'Chuẩn bị tích hợp BESS.' => 'Chuẩn bị khả năng tích hợp pin lưu trữ khi cần.',
        '### Khi kết hợp BESS' => '### Khi kết hợp hệ thống pin lưu trữ',
        'BESS mở ra khả năng lưu trữ và điều phối năng lượng theo chiến lược phù hợp với từng bài toán.' => 'Hệ thống pin lưu trữ giúp tích trữ và phân phối điện theo nhu cầu vận hành thực tế.',
        'Bài toán BESS cho doanh nghiệp' => 'Hệ thống pin lưu trữ cho doanh nghiệp',
        'cùng BESS khi bài toán phụ tải' => 'cùng hệ thống pin lưu trữ khi mức sử dụng điện',
        'Nhu cầu BESS nếu có.' => 'Nhu cầu pin lưu trữ quy mô lớn, nếu có.',
        'Các bài toán có thể nghiên cứu:' => 'Các nhu cầu có thể xem xét:',
        'bài toán quản lý năng lượng' => 'nhu cầu quản lý năng lượng',
        'bài toán phụ tải' => 'mức sử dụng điện',
        'bài toán kinh tế' => 'hiệu quả đầu tư',
        'từng bài toán' => 'từng nhu cầu',
        'bài toán sử dụng' => 'nhu cầu sử dụng',
        'một số bài toán công suất' => 'một số yêu cầu về công suất',
        'các bài toán tưới tiêu' => 'nhu cầu tưới tiêu',
        'các bài toán tưới tiêu, cấp nước và vận hành' => 'nhu cầu tưới tiêu, cấp nước và vận hành',
        'Đây là một hướng thiết kế có thể giúp đơn giản hóa bài toán lưu trữ ở những ứng dụng phù hợp.' => 'Cách thiết kế này có thể giảm nhu cầu dùng pin lưu trữ trong các ứng dụng phù hợp.',
        'Đối với những nơi đang sử dụng máy phát hoặc động cơ nhiên liệu, solar có thể là một phương án đáng cân nhắc tùy bài toán.' => 'Tại nơi đang sử dụng máy phát hoặc động cơ nhiên liệu, Phúc Thịnh Solar sẽ đánh giá khả năng thay thế một phần bằng điện mặt trời theo nhu cầu vận hành thực tế.',
        'Đối với những nơi đang sử dụng máy phát hoặc động cơ nhiên liệu, điện mặt trời có thể là một phương án đáng cân nhắc tùy bài toán.' => 'Tại nơi đang sử dụng máy phát hoặc động cơ nhiên liệu, Phúc Thịnh Solar sẽ đánh giá khả năng thay thế một phần bằng điện mặt trời theo nhu cầu vận hành thực tế.',
        '### 03. Mô phỏng sản lượng' => '### 03. Ước tính sản lượng điện',
        '### 04. Phân tích tỷ lệ tự sử dụng' => '### 04. Phân tích phần điện dùng trực tiếp tại chỗ',
        'tỷ lệ tự sử dụng năng lượng mặt trời' => 'phần điện mặt trời được dùng trực tiếp tại chỗ',
        'tỷ lệ tự sử dụng điện mặt trời' => 'phần điện mặt trời được dùng trực tiếp tại chỗ',
        'dòng điện DC' => 'dòng điện một chiều (DC)',
        'backup' => 'cấp điện dự phòng khi mất điện',
        'tối ưu solar' => 'tận dụng điện mặt trời',
        'solar độc lập' => 'đèn năng lượng mặt trời độc lập',
        'solar có thể' => 'điện mặt trời có thể',
        'một model' => 'một mẫu đèn',
        'điều kiện bức xạ' => 'điều kiện nắng',
        'có bức xạ' => 'có nắng',
        'đặc tính quang học của đèn' => 'khả năng phân bố ánh sáng của đèn',
        'trong battery' => 'trong pin lưu trữ',
        'phụ tải' => 'nhu cầu sử dụng điện'
    ));
}

function solutionImportMarkdown($markdown)
{
    /* Form và nút là module template; không nhập placeholder giả vào CKEditor. */
    $markdown = preg_replace('/^### Form đề xuất.*?(?=^## SECTION|\z)/msu', '', $markdown);
    $markdown = preg_replace('/^\*\*CTA[^\n]*\n(?:\s*`[^`]+`\s*\n)?/mu', '', $markdown);
    $markdown = preg_replace('/^\s*`(?:XEM|GỬI|ĐĂNG|TƯ VẤN|NHẬN)[^`]*`\s*$/mu', '', $markdown);
    $lines = preg_split('/\R/u', trim($markdown));
    $html = array();
    $paragraph = array();
    $list = '';
    $quote = array();

    $flushParagraph = function() use (&$paragraph, &$html) {
        if(!$paragraph) return;
        $html[] = '<p>'.solutionImportInline(implode(' ', $paragraph)).'</p>';
        $paragraph = array();
    };
    $closeList = function() use (&$list, &$html) {
        if($list === '') return;
        $html[] = '</'.$list.'>';
        $list = '';
    };
    $flushQuote = function() use (&$quote, &$html) {
        if(!$quote) return;
        $html[] = '<blockquote>'.solutionImportInline(implode(' ', $quote)).'</blockquote>';
        $quote = array();
    };

    foreach($lines as $index => $line)
    {
        $line = rtrim($line);
        if(trim($line) === '' || trim($line) === '---')
        {
            $flushParagraph(); $closeList(); $flushQuote();
            continue;
        }

        if(preg_match('/^## SECTION\s+\d+\s+—\s+(.+)$/u', $line, $match))
        {
            $flushParagraph(); $closeList(); $flushQuote();
            $next = '';
            for($look = $index + 1; $look < count($lines); $look++)
                if(trim($lines[$look]) !== '') { $next = trim($lines[$look]); break; }
            if(strpos($next, '# ') !== 0) $html[] = '<h2>'.solutionImportInline($match[1]).'</h2>';
            continue;
        }
        if(preg_match('/^#\s+(.+)$/u', $line, $match))
        {
            $flushParagraph(); $closeList(); $flushQuote();
            $html[] = '<h2>'.solutionImportInline($match[1]).'</h2>';
            continue;
        }
        if(preg_match('/^#{3,6}\s+(.+)$/u', $line, $match))
        {
            $flushParagraph(); $closeList(); $flushQuote();
            $heading = preg_replace('/^Card\s+\d+\s+—\s+/ui', '', $match[1]);
            $html[] = '<h3>'.solutionImportInline($heading).'</h3>';
            continue;
        }
        if(preg_match('/^>\s?(.*)$/u', $line, $match))
        {
            $flushParagraph(); $closeList();
            $quote[] = $match[1];
            continue;
        }
        if(preg_match('/^-\s+(.+)$/u', $line, $match))
        {
            $flushParagraph(); $flushQuote();
            if($list !== 'ul') { $closeList(); $list = 'ul'; $html[] = '<ul>'; }
            $html[] = '<li>'.solutionImportInline($match[1]).'</li>';
            continue;
        }
        if(preg_match('/^\d+\.\s+(.+)$/u', $line, $match))
        {
            $flushParagraph(); $flushQuote();
            if($list !== 'ol') { $closeList(); $list = 'ol'; $html[] = '<ol>'; }
            $html[] = '<li>'.solutionImportInline($match[1]).'</li>';
            continue;
        }
        $closeList(); $flushQuote();
        $paragraph[] = trim($line);
    }
    $flushParagraph(); $closeList(); $flushQuote();
    return implode("\n", $html);
}

function solutionImportPage($source, $number)
{
    $pattern = '/^# '.(int)$number.'\. [^\n]+\n(.*?)(?=^# '.((int)$number + 1).'\.|\z)/msu';
    if(!preg_match($pattern, $source, $pageMatch)) throw new RuntimeException('Không tìm thấy trang số '.$number.'.');
    $page = solutionImportSimplifyLanguage($pageMatch[1]);
    foreach(array('SEO Title' => 'seo_title', 'Meta Description' => 'meta', 'H1' => 'h1') as $label => $field)
    {
        if(!preg_match('/^\*\*'.preg_quote($label, '/').':\*\*\s*(.+)$/mu', $page, $match))
            throw new RuntimeException('Trang '.$number.' thiếu '.$label.'.');
        $data[$field] = solutionImportPlain($match[1]);
    }
    if(!preg_match('/^## HERO\s*\n(.*?)(?=^## SECTION)/msu', $page, $heroMatch))
        throw new RuntimeException('Trang '.$number.' thiếu HERO.');
    $hero = $heroMatch[1];
    preg_match('/^###\s+(.+)$/mu', $hero, $eyebrowMatch);
    $data['eyebrow'] = !empty($eyebrowMatch[1]) ? solutionImportPlain($eyebrowMatch[1]) : '';

    $heroBody = preg_replace('/^#{1,6}\s+.*$/mu', '', $hero);
    $heroBody = preg_replace('/^\*\*CTA.*$/mu', '', $heroBody);
    $heroBody = preg_replace('/^Microcopy:.*$/mu', '', $heroBody);
    $heroBody = preg_replace('/^>.*$/mu', '', $heroBody);
    $heroBody = preg_replace('/^\s*`[^`]+`\s*$/mu', '', $heroBody);
    $heroBody = preg_replace('/^\s*---\s*$/mu', '', $heroBody);
    $heroParagraphs = array_filter(array_map('solutionImportPlain', preg_split('/\R\s*\R/u', trim($heroBody))));
    $data['desc'] = implode(' ', $heroParagraphs);

    $contentEnd = $number === 1 ? '(?=^## SECTION 6\s+—)' : '(?=^## CTA CUỐI TRANG)';
    if(!preg_match('/(^## SECTION 1\s+—.*?'.$contentEnd.')/msu', $page, $contentMatch))
        throw new RuntimeException('Trang '.$number.' thiếu chuỗi section nội dung.');
    $data['content'] = solutionImportMarkdown($contentMatch[1]);
    if(substr_count($data['content'], '<h2>') < 3)
        throw new RuntimeException('Trang '.$number.' sinh quá ít section H2.');
    return $data;
}

$file = solutionImportArgument($argv, '--file');
$apply = in_array('--apply', $argv, true);
if($file === '' || !is_file($file))
{
    fwrite(STDERR, "Cần truyền --file=/đường/dẫn/phuc-thinh-solar-content-giai-phap.md\n");
    exit(1);
}
$source = file_get_contents($file);
if($source === false || strlen($source) < 20000) { fwrite(STDERR, "File nguồn thiếu hoặc quá ngắn.\n"); exit(1); }
$source = str_replace("\r\n", "\n", $source);

$pages = array();
for($number = 1; $number <= 6; $number++) $pages[$number] = solutionImportPage($source, $number);
$definitions = array(
    2 => array('slug' => 'dien-mat-troi-ho-gia-dinh', 'short' => 'Điện mặt trời gia đình', 'aliases' => array(), 'list_slug' => 'ho-gia-dinh'),
    3 => array('slug' => 'dien-mat-troi-doanh-nghiep', 'short' => 'Điện mặt trời doanh nghiệp', 'aliases' => array(), 'list_slug' => 'doanh-nghiep'),
    4 => array('slug' => 'he-thong-luu-tru-bess', 'short' => 'Lưu trữ điện', 'aliases' => array(), 'list_slug' => ''),
    5 => array('slug' => 'chieu-sang-nang-luong-mat-troi', 'short' => 'Chiếu sáng mặt trời', 'aliases' => array('thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh'), 'list_slug' => ''),
    6 => array('slug' => 'bom-nuoc-nang-luong-mat-troi', 'short' => 'Bơm nước mặt trời', 'aliases' => array(), 'list_slug' => '')
);

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) { fwrite(STDERR, "Không tìm thấy libraries/config.php.\n"); exit(1); }
define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { fwrite(STDERR, "Table prefix không hợp lệ.\n"); exit(1); }
$pdo = new PDO('mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4', $config['database']['username'], $config['database']['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$findNews = $pdo->prepare("select * from `{$prefix}news` where type=? and slugvi=? limit 1");
$findType = $pdo->prepare("select * from `{$prefix}news` where type=? order by numb,id limit 1");
$findList = $pdo->prepare("select id from `{$prefix}news_list` where type=? and slugvi=? limit 1");
$slugCollision = $pdo->prepare("select id,type from `{$prefix}news` where slugvi=? limit 1");
$updateNews = $pdo->prepare("update `{$prefix}news` set id_list=?,namevi=?,title_indexvi=?,descvi=?,contentvi=?,content1vi=?,slugvi=?,slugen=?,numb=?,status=?,date_updated=? where id=? and type=?");
$insertNews = $pdo->prepare("insert into `{$prefix}news` (id_list,namevi,title_indexvi,descvi,contentvi,content1vi,slugvi,slugen,numb,status,type,date_created,date_updated) values (?,?,?,?,?,?,?,?,?,?,?,?,?)");
$updateBlock = $pdo->prepare("update `{$prefix}news` set namevi=?,title_indexvi=?,descvi=?,contentvi=?,numb=?,status=?,date_updated=? where id=? and type=?");
$insertBlock = $pdo->prepare("insert into `{$prefix}news` (namevi,title_indexvi,descvi,contentvi,numb,status,type,date_created,date_updated) values (?,?,?,?,?,?,?,?,?)");
$deleteSeo = $pdo->prepare("delete from `{$prefix}seo` where id_parent=? and com='news' and act='man' and type='giai-phap'");
$insertSeo = $pdo->prepare("insert into `{$prefix}seo` (id_parent,com,act,type,titlevi,descriptionvi) values (?,'news','man','giai-phap',?,?)");
$addRedirect = $pdo->prepare("insert into `{$prefix}url_redirect` (old_path,entity_table,entity_id,date_created) values (?,'news_giai_phap',?,?) on duplicate key update entity_table=values(entity_table),entity_id=values(entity_id)");

$now = time();
$plan = array();
try
{
    if($apply) $pdo->beginTransaction();

    $landing = $pages[1];
    $findType->execute(array('solution-intro'));
    $landingRow = $findType->fetch(PDO::FETCH_ASSOC);
    $landingStored = htmlspecialchars($landing['content'], ENT_QUOTES, 'UTF-8');
    $landingArgs = array($landing['h1'], $landing['eyebrow'], $landing['desc'], $landingStored, 1, 'hienthi', $now);
    if($landingRow)
    {
        $plan[] = 'Cập nhật landing tổng #'.$landingRow['id'];
        if($apply) $updateBlock->execute(array_merge($landingArgs, array((int)$landingRow['id'], 'solution-intro')));
    }
    else
    {
        $plan[] = 'Tạo landing tổng solution-intro';
        if($apply) $insertBlock->execute(array($landing['h1'], $landing['eyebrow'], $landing['desc'], $landingStored, 1, 'hienthi', 'solution-intro', $now, $now));
    }

    foreach($definitions as $number => $definition)
    {
        $page = $pages[$number];
        $findNews->execute(array('giai-phap', $definition['slug']));
        $row = $findNews->fetch(PDO::FETCH_ASSOC);
        if(!$row) foreach($definition['aliases'] as $alias)
        {
            $findNews->execute(array('giai-phap', $alias));
            $row = $findNews->fetch(PDO::FETCH_ASSOC);
            if($row) break;
        }
        $listId = 0;
        if($definition['list_slug'] !== '')
        {
            $findList->execute(array('giai-phap', $definition['list_slug']));
            $listId = (int)$findList->fetchColumn();
        }
        $statuses = $row ? array_filter(explode(',', $row['status'])) : array();
        foreach(array('hienthi', 'solutionhub') as $required) if(!in_array($required, $statuses, true)) $statuses[] = $required;
        $status = implode(',', array_values(array_unique($statuses)));
        $storedContent = htmlspecialchars($page['content'], ENT_QUOTES, 'UTF-8');
        if($row)
        {
            $oldSlug = (string)$row['slugvi'];
            if($oldSlug !== $definition['slug'])
            {
                $slugCollision->execute(array($definition['slug']));
                $collision = $slugCollision->fetch(PDO::FETCH_ASSOC);
                if($collision && (int)$collision['id'] !== (int)$row['id']) throw new RuntimeException('Slug đã tồn tại: '.$definition['slug']);
            }
            $plan[] = 'Cập nhật #'.$row['id'].' '.$definition['slug'].' ('.strlen($page['content']).' byte HTML)';
            if($apply)
            {
                if($oldSlug !== $definition['slug'])
                {
                    $addRedirect->execute(array($oldSlug, (int)$row['id'], $now));
                    $addRedirect->execute(array('giai-phap/'.$oldSlug, (int)$row['id'], $now));
                }
                $updateNews->execute(array($listId, $page['h1'], $definition['short'], $page['desc'], $storedContent, '', $definition['slug'], $definition['slug'], $number - 1, $status, $now, (int)$row['id'], 'giai-phap'));
                $id = (int)$row['id'];
            }
        }
        else
        {
            $slugCollision->execute(array($definition['slug']));
            if($slugCollision->fetch(PDO::FETCH_ASSOC)) throw new RuntimeException('Slug đã tồn tại ở type khác: '.$definition['slug']);
            $plan[] = 'Tạo '.$definition['slug'].' ('.strlen($page['content']).' byte HTML)';
            if($apply)
            {
                $insertNews->execute(array($listId, $page['h1'], $definition['short'], $page['desc'], $storedContent, '', $definition['slug'], $definition['slug'], $number - 1, $status, 'giai-phap', $now, $now));
                $id = (int)$pdo->lastInsertId();
            }
        }
        if($apply)
        {
            $deleteSeo->execute(array($id));
            $insertSeo->execute(array($id, $page['seo_title'], $page['meta']));
        }
    }

    $ctaSource = strtr($source, array(
        '### BẠN CÓ NHU CẦU' => '### TƯ VẤN THEO NHU CẦU THỰC TẾ',
        '# CHÚNG TÔI XÂY PHƯƠNG ÁN' => '# PHÚC THỊNH SOLAR ĐỀ XUẤT GIẢI PHÁP',
        'Không cần bắt đầu bằng tên thiết bị.' => 'Khách hàng không cần tự chọn thiết bị trước khi liên hệ.',
        'Chỉ cần cho Phúc Thịnh Solar biết **bạn đang sử dụng năng lượng như thế nào và muốn cải thiện điều gì**.' => 'Phúc Thịnh Solar tiếp nhận thông tin về **cách sử dụng năng lượng và mục tiêu cần cải thiện**.',
        'Đội ngũ của chúng tôi sẽ cùng bạn xác định giải pháp phù hợp từ khảo sát, thiết kế đến triển khai.' => 'Đội ngũ kỹ thuật sẽ khảo sát, thiết kế và đề xuất phương án phù hợp trước khi triển khai.'
    ));
    if(!preg_match('/^# 21\. .*?^###\s+(.+?)\s*\n^#\s+(.+?)\s*\n(.*?)(?=^\*\*Primary CTA:)/msu', $ctaSource, $ctaMatch))
        throw new RuntimeException('Không đọc được CTA dùng chung ở mục 21.');
    $ctaTitle = solutionImportPlain($ctaMatch[1]).' — '.solutionImportPlain($ctaMatch[2]);
    $ctaDesc = implode(' ', array_filter(array_map('solutionImportPlain', preg_split('/\R\s*\R/u', trim($ctaMatch[3])))));
    preg_match('/^\*\*Primary CTA:\*\*\s*`([^`]+)`/mu', $source, $ctaButtonMatch);
    $ctaButton = !empty($ctaButtonMatch[1]) ? solutionImportPlain($ctaButtonMatch[1]) : 'Nhận tư vấn giải pháp';
    $findType->execute(array('solution-cta'));
    $ctaRow = $findType->fetch(PDO::FETCH_ASSOC);
    $ctaArgs = array($ctaTitle, $ctaButton, $ctaDesc, '', 1, 'hienthi', $now);
    if($ctaRow)
    {
        $plan[] = 'Cập nhật CTA dùng chung #'.$ctaRow['id'];
        if($apply) $updateBlock->execute(array_merge($ctaArgs, array((int)$ctaRow['id'], 'solution-cta')));
    }
    else
    {
        $plan[] = 'Tạo CTA dùng chung solution-cta';
        if($apply) $insertBlock->execute(array($ctaTitle, $ctaButton, $ctaDesc, '', 1, 'hienthi', 'solution-cta', $now, $now));
    }

    if($apply) $pdo->commit();
}
catch(Throwable $exception)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "LỖI: ".$exception->getMessage()."\nĐã rollback toàn bộ lượt nhập.\n");
    exit(1);
}

echo $apply ? "CHẾ ĐỘ GHI LOCAL\n" : "CHẾ ĐỘ PREVIEW — chưa ghi DB\n";
foreach($plan as $item) echo '  - ', $item, "\n";
echo $apply ? "Hoàn tất transaction. Hãy xoá cache và chạy test public.\n" : "Chạy lại với --apply sau khi backup để thực hiện.\n";
