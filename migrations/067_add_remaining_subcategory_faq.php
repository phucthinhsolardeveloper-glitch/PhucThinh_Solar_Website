<?php
/**
 * Soạn FAQ riêng cho 19 danh mục con còn thiếu (Đèn sân vườn x4, Trụ đèn x4,
 * Kẹp tấm pin, Biến tần x2, Pin lưu trữ x3, Bơm nước x2, Thiết bị & phụ kiện
 * x7) - phát hiện qua rà soát toàn site 2026-10-02 (sếp: "thấy còn vài cái
 * vẫn thiếu câu hỏi thường gặp"). Trước đó chỉ 4 danh mục con của riêng
 * "Đèn năng lượng mặt trời" có FAQ (migrations/065).
 *
 * Nội dung KHÔNG bịa mới - chắt lọc trực tiếp từ nội dung mô tả kỹ thuật đã
 * có sẵn riêng từng danh mục con (content_section scope=cat, soạn từ phiên
 * trước), chỉ chuyển sang định dạng hỏi-đáp, cùng cách làm với migrations
 * 063/065.
 *
 * Mặc định chỉ preview:
 *   php migrations/067_add_remaining_subcategory_faq.php
 *   php migrations/067_add_remaining_subcategory_faq.php --apply
 *   php migrations/067_add_remaining_subcategory_faq.php --rollback
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

/* id_cat => [label (chỉ để log), 3 cặp faq] */
$faqByCat = array(
    44 => array('Đèn trụ cổng', array(
        array('q' => 'Nên chọn đèn trụ cổng theo tiêu chí nào?', 'a' => 'Chọn kích thước cân đối với trụ/cổng, ánh sáng không quá chói và vật liệu phù hợp môi trường ngoài trời. Có thể dùng nguồn điện lưới hoặc năng lượng mặt trời tùy điều kiện kéo dây và mức độ nhận nắng.'),
        array('q' => 'Nguồn điện lưới hay solar phù hợp hơn?', 'a' => 'Nguồn solar thuận tiện nơi không có dây điện nhưng cần vị trí nhận nắng, không bị mái cổng che phần lớn thời gian. Nguồn AC ổn định hơn ở khu vực ít nắng nhưng phải có chống nước và bảo vệ điện đầy đủ.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Bề mặt lắp phải phẳng, chắc chắn, xử lý kín điểm đi dây để tránh nước thấm, đế phải được neo chắc và mối nối AC đặt trong hộp kín. Vệ sinh chụp đèn định kỳ vì bụi và côn trùng làm giảm ánh sáng.'),
    )),
    45 => array('Đèn treo tường', array(
        array('q' => 'Nên chọn đèn treo tường theo tiêu chí nào?', 'a' => 'Có loại chiếu một hướng, hai hướng, tỏa rộng hoặc tạo hiệu ứng trang trí - chọn theo nhu cầu không gian (ban công, hành lang, cổng, mặt tiền). Đèn ngoài trời cần cấp bảo vệ và vật liệu phù hợp mưa, bụi và tia UV.'),
        array('q' => 'Vị trí lắp ảnh hưởng thế nào đến hiệu quả chiếu sáng?', 'a' => 'Up/down light tạo hiệu ứng mặt đứng nhưng không phải lúc nào cũng đủ chiếu sáng lối đi; đèn tán rộng phù hợp hành lang/cổng hơn. Cao độ thấp dễ gây chói nếu nhìn trực tiếp vào LED, và với đèn solar cần tấm pin có góc nhận nắng tốt.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt và bảo trì?', 'a' => 'Chọn cao độ hạn chế chói trực tiếp vào mắt, xử lý kín lỗ đi dây và mối nối; với đèn solar tránh vị trí tấm pin bị mái hiên che nắng. Định kỳ làm sạch kính/chụp, kiểm tra silicone/gasket và vít neo vào tường.'),
    )),
    46 => array('Đèn sân vườn / đèn nấm', array(
        array('q' => 'Nên chọn đèn sân vườn/đèn nấm theo tiêu chí nào?', 'a' => 'Đèn nấm có chiều cao thấp, phù hợp lối đi, bồn cây, công viên - mục tiêu tạo ánh sáng dẫn hướng và không khí cảnh quan hơn là chiếu xa. Thiết kế chụp đèn tốt giúp hạn chế chói trực tiếp.'),
        array('q' => 'Khoảng cách lắp đặt quan trọng thế nào?', 'a' => 'Bố trí khoảng cách đều để tránh vùng quá sáng - quá tối; khoảng cách lắp quyết định độ liên tục của lối đi. Ánh sáng quá mạnh có thể phá vỡ không khí cảnh quan mong muốn.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Đế lắp cần chắc chắn và có thoát nước, đường dây AC cần chống nước nếu dùng điện lưới; phiên bản solar cần nhận đủ nắng ban ngày, tránh vùng bóng cây. Kiểm tra va chạm từ xe/cắt cỏ vì đèn thường đặt sát đất.'),
    )),
    47 => array('Đèn cắm cỏ', array(
        array('q' => 'Nên chọn đèn cắm cỏ theo tiêu chí nào?', 'a' => 'Đèn có chân nhọn cắm trực tiếp vào đất, dùng chiếu cây, biển nhỏ, lối đi hoặc tiểu cảnh - góc đầu đèn thường điều chỉnh được để tạo điểm nhấn thay vì chiếu sáng diện rộng. Vì lắp sát đất, cần đặc biệt chú ý khả năng chống nước và chất lượng dây, đầu nối.'),
        array('q' => 'Đèn cắm cỏ dùng cho những ứng dụng nào?', 'a' => 'Chiếu cây thân cao, chiếu bụi cây, nhấn tường/biển nhỏ, dẫn hướng lối đi hoặc tạo lớp sáng nền trong cảnh quan - tùy vào góc chiếu và vị trí đặt.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Không đặt tại vị trí thường xuyên ngập sâu nếu sản phẩm không thiết kế cho ngâm nước, điều chỉnh hướng chiếu tránh rọi thẳng vào mắt. Đèn solar cần vị trí tấm pin không bị cây cối che bóng, và tránh đặt nơi máy cắt cỏ thường đi qua.'),
    )),
    40 => array('Trụ đèn đường', array(
        array('q' => 'Nên chọn trụ đèn đường theo tiêu chí nào?', 'a' => 'Trụ phải chịu tải bản thân, cần đèn, bộ đèn và tải gió trong điều kiện sử dụng lâu dài; kích thước mặt bích, bu-lông móng và móng bê tông cần đồng bộ với thiết kế trụ. Chiều cao trụ ảnh hưởng trực tiếp vùng phủ sáng và khoảng cách bố trí cột.'),
        array('q' => 'Yếu tố nào quyết định khả năng chịu lực của trụ?', 'a' => 'Tải gió thường là tải quan trọng nhất vì bộ đèn và cần đèn tạo diện tích cản gió ở cao độ lớn. Độ dày thành trụ, đường kính chân/ngọn, vật liệu và móng (phụ thuộc địa chất) đều ảnh hưởng khả năng chịu uốn - không thể chọn móng chỉ theo chiều cao trụ.'),
        array('q' => 'Cần lưu ý gì khi thi công và bảo trì?', 'a' => 'Căn chỉnh bu-lông móng chính xác trước khi bê tông đông kết, kiểm tra độ thẳng đứng và siết bu-lông đều sau khi dựng trụ. Định kỳ kiểm tra ăn mòn vùng chân trụ, bu-lông mặt bích và độ nghiêng bất thường sau bão/gió mạnh.'),
    )),
    41 => array('Trụ đèn trang trí', array(
        array('q' => 'Nên chọn trụ đèn trang trí theo tiêu chí nào?', 'a' => 'Ngoài độ sáng, thiết kế cần chú trọng hình thức, tỷ lệ với cảnh quan và khả năng chống ăn mòn - phù hợp công viên, quảng trường, khu đô thị, sân vườn hoặc khuôn viên. Cụm nhiều tay đèn tạo tải gió đáng kể nên kết cấu trụ và móng cần tính toán phù hợp.'),
        array('q' => 'Số lượng tay đèn ảnh hưởng thế nào đến kết cấu?', 'a' => 'Nhiều tay đèn làm tăng tải gió và mô-men chân trụ; tỷ lệ chiều cao trụ và kích thước tay/chóa cũng ảnh hưởng cảm nhận cảnh quan. Hoa văn, đúc nhôm/gang hay thép tạo yêu cầu bảo dưỡng khác nhau.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Chọn chiều cao và số tay đèn theo không gian, đảm bảo mặt bích, bu-lông móng và nền móng đồng bộ. Kiểm tra tiếp địa và an toàn điện tại cửa đấu nối, lớp sơn/mạ cần phù hợp môi trường ngoài trời.'),
    )),
    42 => array('Cần đèn & giá đỡ', array(
        array('q' => 'Nên chọn cần đèn và giá đỡ theo tiêu chí nào?', 'a' => 'Cần đèn quyết định vị trí, độ vươn và góc nghiêng của đèn so với trụ; giá đỡ cần phù hợp đường kính trụ, khối lượng đèn và tải gió. Với đèn solar tách thể, giá đỡ tấm pin còn phải bảo đảm góc nhận nắng và khả năng chịu gió.'),
        array('q' => 'Độ vươn và góc nghiêng ảnh hưởng thế nào đến ánh sáng?', 'a' => 'Độ vươn đưa đèn ra khỏi mép trụ hoặc vật cản, góc nghiêng thay đổi vị trí vùng sáng chính. Cần quá dài sẽ tăng mô-men và tải gió, nên không dùng bộ chuyển đổi (adapter) quá yếu cho đèn nặng hoặc cần vươn dài.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Siết bu-lông đều và dùng biện pháp chống tự tháo khi cần, kiểm tra góc chiếu thực tế sau khi cấp điện. Adapter sai đường kính dễ xoay hoặc lỏng - cần kiểm tra đường kính lắp, khối lượng bộ đèn và tải gió trước khi chọn.'),
    )),
    43 => array('Móng / đế trụ', array(
        array('q' => 'Nên chọn móng/đế trụ theo tiêu chí nào?', 'a' => 'Kích thước móng phụ thuộc chiều cao trụ, tải đầu trụ, điều kiện gió và khả năng chịu tải của nền đất - không có kích thước cố định chung cho mọi trường hợp. Bu-lông móng phải đúng đường kính, khoảng cách và chiều dài neo theo đúng mặt bích.'),
        array('q' => 'Điều kiện nền đất ảnh hưởng thế nào đến thiết kế móng?', 'a' => 'Móng phải chống lật, trượt và chịu tải đứng; đất yếu cần kích thước hoặc giải pháp móng khác so với đất tốt. Tải gió tăng mạnh theo chiều cao trụ và diện tích cản gió, nên cần tính toán riêng cho từng công trình.'),
        array('q' => 'Cần lưu ý gì khi thi công?', 'a' => 'Định vị bộ bu-lông bằng dưỡng trước khi đổ bê tông, chờ bê tông đạt cường độ cần thiết trước khi dựng trụ. Hoàn thiện cao độ nền để tránh nước đọng quanh chân trụ.'),
    )),
    49 => array('Kẹp tấm pin', array(
        array('q' => 'Nên chọn kẹp tấm pin theo tiêu chí nào?', 'a' => 'End clamp (kẹp biên) dùng ở biên dãy pin, mid clamp (kẹp giữa) dùng giữa hai module. Chiều cao kẹp phải phù hợp chiều dày khung mô-đun, và vị trí kẹp phải nằm trong vùng nhà sản xuất cho phép.'),
        array('q' => 'Lực siết kẹp quan trọng thế nào?', 'a' => 'Lực siết quá thấp có thể làm kẹp lỏng, quá cao có thể làm biến dạng khung module. Nên siết bằng mô-men khuyến nghị nếu nhà sản xuất cung cấp, và kiểm tra lại sau khi hoàn thành toàn bộ dãy pin.'),
        array('q' => 'Lỗi thi công thường gặp là gì?', 'a' => 'Kẹp ngoài vùng cho phép, siết quá lực, T-nut chưa xoay khóa, hoặc kẹp lệch chỉ ăn một phần frame. Dùng clamp không đúng chiều cao cũng có thể gây lỏng sau khi module co giãn nhiệt.'),
    )),
    26 => array('Hòa lưới', array(
        array('q' => 'Inverter hòa lưới hoạt động theo nguyên lý nào?', 'a' => 'Inverter bám theo điện áp và tần số lưới để chuyển điện DC từ tấm pin thành AC dùng tại công trình, phần dư có thể phát ra lưới tùy cơ chế đấu nối. Khi mất điện lưới, inverter hòa lưới thông thường ngừng cấp điện để chống đảo lưới - muốn có điện dự phòng cần giải pháp hybrid/backup riêng.'),
        array('q' => 'Nên chọn inverter hòa lưới theo tiêu chí nào?', 'a' => 'Kiểm tra dải MPPT, điện áp DC tối đa, dòng MPPT và tỷ lệ công suất DC/AC. Số tấm trên mỗi chuỗi phải phù hợp cả nhiệt độ thấp (ảnh hưởng Voc) và nhiệt độ vận hành cao.'),
        array('q' => 'Cần kiểm tra gì trước khi đóng điện?', 'a' => 'Điện áp từng chuỗi (string) và cực tính, khả năng cách điện DC, cài đặt grid code/điện áp/tần số phù hợp quy định tại khu vực. Nếu có zero-export cần kiểm tra vị trí và chiều CT đo công suất.'),
    )),
    27 => array('Hybrid / lưu trữ', array(
        array('q' => 'Hệ hybrid khác gì so với hòa lưới thông thường?', 'a' => 'Hệ hybrid quản lý đồng thời nguồn PV, lưới điện, pin lưu trữ và tải - có thể ưu tiên tự dùng điện mặt trời, lưu trữ điện dư hoặc cấp tải dự phòng khi mất lưới. Khả năng backup phụ thuộc công suất inverter, pin và cổng dự phòng của đúng model.'),
        array('q' => 'Có những chiến lược vận hành nào?', 'a' => 'Self-consumption (ưu tiên dùng PV tại chỗ), time-of-use (sạc/xả theo khung giá điện), backup reserve (giữ SOC dự phòng mất điện), peak shaving (giảm công suất lấy từ lưới giờ cao điểm) và zero-export.'),
        array('q' => 'Cần lưu ý gì khi thiết kế hệ hybrid?', 'a' => 'Tách tải thiết yếu và tải công suất lớn để tránh quá tải cổng backup, kiểm tra tương thích điện áp và giao tiếp BMS giữa inverter-pin. Sizing cần dựa theo mục tiêu thực tế (backup/tự dùng/peak shaving), không chỉ chọn pin càng lớn càng tốt.'),
    )),
    28 => array('Pin / Battery', array(
        array('q' => 'Nên chọn pin lưu trữ theo tiêu chí nào?', 'a' => 'Phân biệt dung lượng danh định, dung lượng khả dụng, dòng sạc/xả cho phép, điện áp hệ và tuổi thọ chu kỳ. Loại hóa học pin ảnh hưởng tới mật độ năng lượng, độ bền, yêu cầu BMS và điều kiện an toàn.'),
        array('q' => 'Làm sao đọc đúng thông số pin để so sánh?', 'a' => 'Wh/kWh phản ánh năng lượng và phù hợp hơn Ah để so sánh pin khác điện áp. DoD/SOC giới hạn vùng sử dụng để bảo vệ tuổi thọ, và số chu kỳ luôn gắn với điều kiện thử nghiệm cụ thể - không nên so sánh cycle life nếu điều kiện khác nhau.'),
        array('q' => 'Cần lưu ý gì khi bảo quản và sử dụng?', 'a' => 'Tránh nhiệt độ cao kéo dài, không để pin xả sâu vượt giới hạn BMS, không trộn cell/module khác loại hoặc khác tuổi nếu nhà sản xuất không hỗ trợ. Định kỳ kiểm tra phồng, nóng bất thường và cảnh báo BMS.'),
    )),
    29 => array('Hệ thống ESS', array(
        array('q' => 'Hệ thống ESS gồm những thành phần nào?', 'a' => 'Battery module/rack lưu trữ năng lượng, BMS giám sát cell, PCS/inverter chuyển đổi AC-DC, EMS quyết định chiến lược sạc/xả, cùng hệ bảo vệ, thông gió/làm mát và giám sát hỗ trợ vận hành.'),
        array('q' => 'Nên chọn quy mô ESS theo tiêu chí nào?', 'a' => 'Phải tính cả năng lượng (kWh) và công suất (kW), cùng điều kiện nhiệt, an toàn và không gian lắp đặt. Cần xác định biểu đồ tải và mục tiêu vận hành (dự phòng/tối ưu tự dùng/peak shaving) trước khi chọn dung lượng.'),
        array('q' => 'Cần dữ liệu gì trước khi thiết kế ESS?', 'a' => 'Biểu đồ tải chi tiết theo thời gian, mục tiêu vận hành, công suất cực đại và thời lượng cần cấp, không gian/nhiệt độ/thông gió, và điều kiện đấu nối giới hạn xuất/nhập công suất.'),
    )),
    30 => array('BMS / Rack & phụ kiện', array(
        array('q' => 'BMS bảo vệ pin khỏi những gì?', 'a' => 'Quá áp/thấp áp cell, quá dòng sạc/xả, nhiệt độ cao/thấp, mất cân bằng cell (tùy hệ), và giao tiếp trạng thái với inverter/EMS để vận hành an toàn.'),
        array('q' => 'Rack cần đáp ứng yêu cầu gì?', 'a' => 'Rack phải chịu đủ tải trọng pin, đặt trên nền chắc, khô và đủ không gian thao tác. Cáp DC cần cùng chiều dài/tiết diện khi hệ yêu cầu chia dòng đều, busbar và terminal phải đủ dòng.'),
        array('q' => 'Cần lưu ý gì khi đấu nối?', 'a' => 'Không bỏ qua hoặc vô hiệu hóa BMS, đấu nối module theo đúng thứ tự, cực tính và sơ đồ nhà sản xuất. Với hệ nhiều module, địa chỉ/communication chain phải được cấu hình đúng.'),
    )),
    31 => array('Biến tần bơm NLMT', array(
        array('q' => 'Biến tần bơm NLMT hoạt động theo nguyên lý nào?', 'a' => 'Nhận điện từ dàn pin và điều khiển tần số động cơ (VFD) để bơm thích ứng với mức bức xạ thay đổi trong ngày - MPPT giúp khai thác công suất dàn pin tốt hơn. Khi nắng yếu, inverter giảm tần số để bơm chạy chậm thay vì cố giữ công suất.'),
        array('q' => 'Nên chọn theo thông số nào?', 'a' => 'Cần khớp với điện áp và số pha motor, dòng định mức và công suất kW/HP, tần số 50/60Hz và khoảng Vmp/Voc của dàn PV. Không để Voc dàn pin vượt điện áp DC tối đa của biến tần.'),
        array('q' => 'Cần lưu ý gì để bảo vệ hệ thống?', 'a' => 'Bổ sung cảm biến chống chạy khô hoặc mực nước nếu công trình cần - chống chạy khô giúp bảo vệ bơm khi nguồn nước cạn. Đặt đúng thông số định mức động cơ và giới hạn tần số trước khi vận hành.'),
    )),
    32 => array('Máy bơm NLMT', array(
        array('q' => 'Nên chọn máy bơm theo tiêu chí nào?', 'a' => 'Phải chọn theo lưu lượng và cột áp làm việc thực tế, không chỉ theo công suất motor. Điểm làm việc thực tế là giao điểm giữa đường đặc tính bơm và đường hệ thống (đường ống).'),
        array('q' => 'Loại bơm nào phù hợp với ứng dụng nào?', 'a' => 'Bơm chìm phù hợp giếng sâu, bơm mặt thường bị giới hạn khả năng hút. Đường kính ống quá nhỏ làm tăng tổn thất ma sát và nhu cầu công suất, nên cần tính cột áp tĩnh cộng tổn thất đường ống.'),
        array('q' => 'Những lỗi nào thường gặp?', 'a' => 'Chạy khô làm nóng và hỏng bơm, cát/rác gây mòn cánh hoặc kẹt bơm, điện áp motor sai làm quá dòng. Đảo pha có thể làm bơm ba pha quay ngược chiều.'),
    )),
    33 => array('Bộ điều khiển sạc MPPT', array(
        array('q' => 'MPPT hoạt động theo nguyên lý nào?', 'a' => 'Điều chỉnh điểm làm việc của dàn PV để thu công suất hiệu quả hơn, rồi chuyển đổi phù hợp với điện áp sạc pin - có bộ chuyển đổi DC-DC nên giữ dàn PV gần điểm công suất cực đại. Hiệu quả rõ nhất khi điện áp dàn pin cao hơn điện áp pin.'),
        array('q' => 'Nên chọn theo thông số nào?', 'a' => 'Điện áp hệ pin được hỗ trợ, Voc PV tối đa ở nhiệt độ thấp, dòng sạc tối đa và công suất PV khuyến nghị theo điện áp pin. Với pin lithium, khả năng giao tiếp BMS hoặc cài profile sạc phù hợp rất quan trọng.'),
        array('q' => 'Cần lưu ý gì khi lắp đặt?', 'a' => 'Đấu pin vào bộ điều khiển theo đúng trình tự nhà sản xuất yêu cầu trước khi kết nối PV nếu tài liệu quy định. Chọn tiết diện dây đủ lớn để giảm sụt áp trên tuyến PV và tuyến pin.'),
    )),
    34 => array('Bộ điều khiển sạc PWM', array(
        array('q' => 'PWM khác gì so với MPPT?', 'a' => 'PWM điều khiển sạc bằng cách đóng cắt/điều chế kết nối giữa tấm pin và pin lưu trữ, không chuyển đổi điện áp để khai thác công suất PV như MPPT. Thường đơn giản hơn nhưng phù hợp hệ nhỏ khi điện áp danh định của tấm pin và pin tương thích.'),
        array('q' => 'Khi nào nên dùng PWM thay vì MPPT?', 'a' => 'Phù hợp hệ nhỏ, ngân sách thấp, tấm pin có điện áp danh định phù hợp pin, khoảng cách PV ngắn và cấu hình 12/24V đơn giản - khi không cần khai thác tối đa công suất PV trong mọi điều kiện.'),
        array('q' => 'Cần lưu ý gì khi sử dụng?', 'a' => 'Chọn đúng điện áp hệ (12/24/48V) theo model hỗ trợ, không vượt dòng sạc và công suất PV cho phép. Không nên dùng chuỗi PV điện áp cao vượt giới hạn controller, và cần cài đúng loại pin để tránh giảm tuổi thọ.'),
    )),
    35 => array('Cáp & dây điện', array(
        array('q' => 'Nên chọn cáp và dây điện theo tiêu chí nào?', 'a' => 'Cáp DC cho PV cần chịu được điện áp hệ, nhiệt độ và điều kiện ngoài trời; cáp AC chọn theo tải và phương thức lắp đặt. Tiết diện dây ảnh hưởng sụt áp và phát nhiệt, đặc biệt với tuyến dài.'),
        array('q' => 'Cách chọn tiết diện dây phù hợp?', 'a' => 'Cần tính dòng làm việc/dòng sự cố, chiều dài tuyến và sụt áp, nhiệt độ môi trường, cách đi dây (máng/ống/chôn ngầm) và khả năng chịu UV/ẩm/hóa chất của môi trường lắp đặt.'),
        array('q' => 'Cần lưu ý gì về đầu nối?', 'a' => 'Đầu cốt phải đúng tiết diện, dùng kìm bấm đúng khuôn, torque đúng giúp giảm phát nhiệt. Không để dây chịu kéo tại đầu nối hoặc cọ vào cạnh kim loại, và nên đánh nhãn hai đầu cáp để bảo trì nhanh.'),
    )),
    36 => array('Giám sát / đo lường', array(
        array('q' => 'Thiết bị giám sát theo dõi những gì?', 'a' => 'Công suất, điện năng, điện áp/dòng MPPT hoặc string, điện năng nhập/xuất lưới, SOC và công suất pin (ở hệ storage), cùng cảnh báo inverter/BMS/communication.'),
        array('q' => 'Độ chính xác dữ liệu phụ thuộc vào đâu?', 'a' => 'Cách lắp cảm biến dòng (CT) và công tơ ảnh hưởng trực tiếp kết quả đo - cần cấu hình đúng chiều CT và thứ tự pha, vì CT lắp ngược sẽ tạo số liệu import/export sai.'),
        array('q' => 'Làm sao phân tích dữ liệu giám sát đúng cách?', 'a' => 'Chuỗi yếu kéo dài có thể do shading, connector hoặc module; sản lượng giảm đồng loạt có thể do thời tiết, bụi hoặc inverter. Nên so sánh theo cùng điều kiện thời tiết/chu kỳ mùa để tránh kết luận sai.'),
    )),
    37 => array('Phụ kiện đèn NLMT', array(
        array('q' => 'Phụ kiện đèn NLMT gồm những gì?', 'a' => 'Tay gá, giá tấm pin, cáp, đầu nối, remote, bộ điều khiển, cảm biến, pin và các chi tiết cơ khí khác - tất cả phải tương thích đúng điện áp, dòng điện, kích thước và giao tiếp của bộ đèn.'),
        array('q' => 'Vì sao cần chọn đúng phụ kiện thay thế?', 'a' => 'Dùng sai pin hoặc controller có thể làm giảm thời gian sáng hoặc gây hư hỏng. Điện áp pin phải đúng hệ, controller phải hỗ trợ đúng loại pin và công suất LED, connector phải đúng cực tính.'),
        array('q' => 'Cần lưu ý gì khi thay phụ kiện?', 'a' => 'Ghi lại model đèn trước khi đặt phụ kiện thay thế, kiểm tra cực tính và đầu nối trước khi cấp nguồn. Ưu tiên phụ kiện đúng hệ để giữ độ kín nước và độ chắc cơ khí.'),
    )),
    38 => array('Tiếp địa & ống bảo vệ', array(
        array('q' => 'Tiếp địa có vai trò gì trong hệ thống điện?', 'a' => 'Tạo đường dẫn an toàn cho dòng sự cố, hỗ trợ thiết bị bảo vệ hoạt động đúng, liên kết đẳng thế giảm chênh lệch điện áp nguy hiểm và phối hợp với SPD để dẫn xung quá áp.'),
        array('q' => 'Nên chọn ống bảo vệ dây theo tiêu chí nào?', 'a' => 'Vật liệu ống phải phù hợp vị trí lắp - ống ngoài trời cần chịu UV nếu lộ thiên, ống chôn cần chịu cơ và ẩm. Không vượt tỷ lệ lấp đầy để tránh khó tản nhiệt/kéo dây.'),
        array('q' => 'Cần lưu ý gì khi thi công?', 'a' => 'Bảo đảm liên tục điện của dây PE và liên kết đẳng thế tại các vị trí cần thiết, đầu ống ngoài trời cần xử lý tránh nước chảy trực tiếp vào tủ/thiết bị. Bo cua phù hợp để kéo cáp không bị tổn thương.'),
    )),
    39 => array('Vật tư phụ trợ', array(
        array('q' => 'Vật tư phụ trợ gồm những gì?', 'a' => 'Bu-lông, dây rút UV, gen co nhiệt, đầu cos, hộp nối, keo, băng cách điện, nhãn, kẹp dây và nhiều chi tiết thi công khác - cần phù hợp môi trường và điện áp sử dụng.'),
        array('q' => 'Vì sao những vật tư nhỏ lại ảnh hưởng đến độ tin cậy hệ thống?', 'a' => 'Dây rút ngoài trời phải chịu UV, ferrule/đầu cos giúp terminal ổn định, bu-lông inox/mạ cần cân nhắc ăn mòn điện hóa khi tiếp xúc kim loại khác. Chọn sai vật tư dễ gây lỗi tiếp xúc hoặc xuống cấp nhanh.'),
        array('q' => 'Cần lưu ý gì khi sử dụng và quản lý vật tư?', 'a' => 'Không thay đầu cos đúng chuẩn bằng cách quấn dây tạm, dùng dây rút/kẹp cáp chịu UV cho khu vực ngoài trời. Đánh nhãn chuỗi, tủ và cáp để thuận tiện nghiệm thu và bảo trì sau này.'),
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
foreach($faqByCat as $catId => $def)
{
    list($label, $faqPairs) = $def;
    $faqBlocks = array();
    foreach($faqPairs as $pair) $faqBlocks[] = array('type' => 'faq', 'question' => $pair['q'], 'answer' => $pair['a']);

    $selectQuery->execute(array($catId));
    $existing = $selectQuery->fetch(PDO::FETCH_ASSOC);
    if(!$existing)
    {
        fwrite(STDERR, "SKIP $label: không tìm thấy content_section (cat id=$catId).\n");
        continue;
    }

    $decoded = json_decode(htmlspecialchars_decode((string)$existing['blocksvi'], ENT_QUOTES), true);
    $decoded = is_array($decoded) ? $decoded : array();
    $hasFaqAlready = false;
    foreach($decoded as $block) if(is_array($block) && (($block['type'] ?? '') === 'faq')) { $hasFaqAlready = true; break; }

    if(!$rollback && $hasFaqAlready)
    {
        fwrite(STDERR, "SKIP $label: content_section id={$existing['id']} đã có khối FAQ, không ghi đè.\n");
        continue;
    }
    if($rollback && !$hasFaqAlready)
    {
        fwrite(STDERR, "SKIP $label: content_section id={$existing['id']} hiện không có FAQ, không có gì để rollback.\n");
        continue;
    }

    if($rollback)
    {
        $restored = array();
        foreach($decoded as $block) if(!(is_array($block) && (($block['type'] ?? '') === 'faq'))) $restored[] = $block;
        $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => $restored);
    }
    else
    {
        $planned[] = array('label' => $label, 'id' => (int)$existing['id'], 'blocks' => array_merge($decoded, $faqBlocks));
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
