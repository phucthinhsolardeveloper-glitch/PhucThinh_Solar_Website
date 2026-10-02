<?php
/**
 * Cấu hình dữ liệu cho trang chi tiết sản phẩm.
 *
 * Một template được dùng cho mọi nhóm. Profile chỉ quyết định nhãn, thứ tự
 * thông số và câu hỏi tư vấn; không tạo template riêng theo danh mục.
 */
if(!function_exists('productPdpProfiles'))
{
    /**
     * Chuẩn hóa nội dung bộ sản phẩm về một cách trình bày duy nhất.
     *
     * Dữ liệu cũ có cả dấu phân cách, HTML và chuỗi bị dính từ Excel
     * (ví dụ: "ĐènTấm pinPhụ kiện lắp đặtRemote"). Không tách theo từng
     * ký tự viết hoa vì sẽ làm hỏng tên model; chỉ tách theo các thành phần
     * đóng gói đã biết, đồng thời giữ nguyên phần không nhận diện được.
     */
    function productNormalizePackageContents($value)
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        if ($value === '') return '';

        $canonical = array(
            'trục và giá đỡ tấm pin' => 'Trục và giá đỡ tấm pin',
            'giá đỡ tấm pin' => 'Giá đỡ tấm pin',
            'tấm pin năng lượng mặt trời' => 'Tấm pin năng lượng mặt trời',
            'điều khiển từ xa' => 'Điều khiển từ xa',
            'phụ kiện lắp đặt' => 'Phụ kiện lắp đặt',
            'tấm pin nlmt' => 'Tấm pin NLMT',
            'dây sạc' => 'Dây sạc',
            'đèn liền thể' => 'Đèn liền thể',
            'trục đèn' => 'Trục đèn',
            'cần đèn' => 'Cần đèn',
            'tấm pin' => 'Tấm pin',
            'remote' => 'Remote',
            'quạt' => 'Quạt',
            'đèn' => 'Đèn'
        );

        /* Các dấu này là phân cách hợp lệ trong file cũ và file Excel. */
        $separated = preg_replace('/[ \t]*[+,;|·][ \t]*/u', '|', $value);
        $separated = preg_replace('/[ \t]*\R+[ \t]*/u', '|', $separated);
        $separated = preg_replace('/[ \t]+-[ \t]+/u', '|', $separated);
        $parts = preg_split('/\|+/', $separated, -1, PREG_SPLIT_NO_EMPTY);
        $segments = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $lower = mb_strtolower($part, 'UTF-8');
            $matched = false;
            foreach ($canonical as $needle => $label) {
                if ($lower === $needle) {
                    $segments[] = $label;
                    $matched = true;
                    break;
                }
            }
            if ($matched) continue;

            /* Chuỗi dính không có dấu phân cách: tìm token theo thứ tự dài trước. */
            $remaining = $part;
            $found = array();
            while (trim($remaining) !== '') {
                $remaining = ltrim($remaining);
                $matchedAt = false;
                foreach ($canonical as $needle => $label) {
                    $length = mb_strlen($needle, 'UTF-8');
                    if (mb_strtolower(mb_substr($remaining, 0, $length, 'UTF-8'), 'UTF-8') === $needle) {
                        $found[] = $label;
                        $remaining = mb_substr($remaining, $length, null, 'UTF-8');
                        $matchedAt = true;
                        break;
                    }
                }
                if (!$matchedAt) {
                    /* Giữ phần còn lại, tránh làm mất thông tin hợp lệ mới. */
                    $found[] = trim($remaining);
                    break;
                }
            }
            $segments = array_merge($segments, $found);
        }

        $result = array();
        foreach ($segments as $segment) {
            $segment = trim(preg_replace('/\s+/u', ' ', (string) $segment));
            if (preg_match('/^\(/u', $segment) && !empty($result)) {
                $result[count($result) - 1] .= ' '.$segment;
                continue;
            }
            if ($segment !== '' && !in_array(mb_strtolower($segment, 'UTF-8'), array_map('mb_strtolower', $result), true)) $result[] = $segment;
        }
        return implode(' · ', $result);
    }

    function productPdpProfiles()
    {
        static $profiles = null;
        if($profiles !== null) return $profiles;

        $profiles = array(
            'general' => array(
                'label' => 'Dùng chung',
                'application_title' => 'Phù hợp với nhu cầu của bạn',
                'metrics' => array('cong_suat', 'material', 'waterproof_rating', 'warranty'),
                'fields' => array(
                    'cong_suat' => 'Công suất', 'material' => 'Chất liệu',
                    'waterproof_rating' => 'Cấp bảo vệ', 'package_contents' => 'Bộ sản phẩm gồm',
                    'warranty' => 'Bảo hành'
                ),
                'needs' => array('kiem-tra-phu-hop' => 'Kiểm tra sản phẩm có phù hợp', 'bao-gia' => 'Nhận báo giá', 'ho-tro-ky-thuat' => 'Hỗ trợ kỹ thuật')
            ),
            'lighting' => array(
                'label' => 'Đèn năng lượng mặt trời',
                'application_title' => 'Phù hợp với nhu cầu của bạn',
                'metrics' => array('cong_suat', 'panel_power', 'battery_capacity', 'lighting_time'),
                'fields' => array(
                    'cong_suat' => 'Công suất', 'light_source' => 'Nguồn sáng', 'lamp_dimensions' => 'Kích thước đèn',
                    'color_temperature' => 'Nhiệt độ màu (CCT)', 'light_color' => 'Màu ánh sáng',
                    'battery_capacity' => 'Dung lượng pin', 'panel_power' => 'Công suất tấm pin',
                    'material' => 'Chất liệu', 'charging_time' => 'Thời gian sạc',
                    'lighting_time' => 'Thời gian chiếu sáng', 'waterproof_rating' => 'Cấp bảo vệ',
                    'led_chip' => 'Chip LED', 'lighting_area' => 'Diện tích chiếu sáng',
                    'installation_height' => 'Độ cao lắp đặt', 'panel_dimensions' => 'Kích thước tấm pin',
                    'package_contents' => 'Bộ sản phẩm gồm', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('vi-tri-lap-dat' => 'Tư vấn theo vị trí lắp đặt', 'do-sang' => 'Tư vấn độ sáng', 'so-luong' => 'Tính số lượng đèn', 'bao-gia' => 'Nhận báo giá')
            ),
            'solar_panel' => array(
                'label' => 'Tấm pin năng lượng mặt trời',
                'application_title' => 'Phù hợp với hệ thống của bạn',
                'metrics' => array('cong_suat', 'cell_type', 'efficiency', 'warranty'),
                'fields' => array(
                    'cong_suat' => 'Công suất danh định', 'cell_type' => 'Loại cell', 'efficiency' => 'Hiệu suất mô-đun',
                    'open_circuit_voltage' => 'Điện áp hở mạch (Voc)', 'short_circuit_current' => 'Dòng ngắn mạch (Isc)',
                    'max_power_voltage' => 'Điện áp tại Pmax (Vmp)', 'max_power_current' => 'Dòng tại Pmax (Imp)',
                    'panel_dimensions' => 'Kích thước tấm pin', 'weight' => 'Khối lượng',
                    'connector_type' => 'Đầu nối', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('cong-suat-he-thong' => 'Tính công suất hệ thống', 'dien-tich-mai' => 'Kiểm tra diện tích mái', 'tuong-thich' => 'Kiểm tra tương thích', 'bao-gia' => 'Nhận báo giá')
            ),
            'fan' => array(
                'label' => 'Quạt năng lượng mặt trời',
                'application_title' => 'Phù hợp với không gian của bạn',
                'metrics' => array('cong_suat', 'battery_capacity', 'runtime', 'warranty'),
                'fields' => array(
                    'cong_suat' => 'Công suất', 'fan_type' => 'Loại quạt', 'blade_size' => 'Đường kính cánh',
                    'airflow' => 'Lưu lượng gió', 'speed_levels' => 'Số cấp gió', 'battery_capacity' => 'Dung lượng pin',
                    'panel_power' => 'Công suất tấm pin', 'runtime' => 'Thời gian hoạt động',
                    'charging_time' => 'Thời gian sạc', 'package_contents' => 'Bộ sản phẩm gồm', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('khong-gian' => 'Tư vấn theo không gian', 'thoi-gian-su-dung' => 'Tư vấn thời gian sử dụng', 'so-luong' => 'Tư vấn số lượng', 'bao-gia' => 'Nhận báo giá')
            ),
            'inverter' => array(
                'label' => 'Biến tần điện mặt trời',
                'application_title' => 'Ứng dụng trong hệ thống nào?',
                'metrics' => array('ac_power', 'phase_count', 'mppt_count', 'warranty'),
                'fields' => array(
                    'inverter_type' => 'Loại biến tần', 'ac_power' => 'Công suất AC định mức', 'phase_count' => 'Số pha',
                    'mppt_count' => 'Số MPPT / số chuỗi đầu vào', 'max_dc_voltage' => 'Điện áp DC tối đa',
                    'mppt_voltage_range' => 'Dải điện áp MPPT', 'max_mppt_current' => 'Dòng đầu vào tối đa mỗi MPPT',
                    'efficiency' => 'Hiệu suất chuyển đổi', 'battery_voltage_range' => 'Dải điện áp pin lưu trữ',
                    'backup_power' => 'Công suất dự phòng', 'monitoring' => 'Kết nối giám sát',
                    'waterproof_rating' => 'Cấp bảo vệ', 'dimensions_weight' => 'Kích thước & khối lượng', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('cong-suat-tai' => 'Kiểm tra công suất tải', 'so-pha' => 'Kiểm tra số pha', 'pin-luu-tru' => 'Kiểm tra pin lưu trữ', 'bao-gia' => 'Nhận báo giá')
            ),
            'storage' => array(
                'label' => 'Pin lưu trữ',
                'application_title' => 'Phù hợp với nhu cầu lưu trữ nào?',
                'metrics' => array('usable_capacity', 'battery_voltage', 'cycle_life', 'warranty'),
                'fields' => array(
                    'battery_chemistry' => 'Công nghệ pin', 'nominal_capacity' => 'Dung lượng danh định',
                    'usable_capacity' => 'Dung lượng sử dụng', 'battery_voltage' => 'Điện áp danh định',
                    'max_charge_current' => 'Dòng sạc tối đa', 'max_discharge_current' => 'Dòng xả tối đa',
                    'depth_of_discharge' => 'Độ sâu xả (DoD)', 'cycle_life' => 'Tuổi thọ chu kỳ',
                    'communication' => 'Giao tiếp BMS', 'compatible_inverters' => 'Biến tần tương thích',
                    'waterproof_rating' => 'Cấp bảo vệ', 'dimensions_weight' => 'Kích thước & khối lượng', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('dung-luong' => 'Tính dung lượng cần dùng', 'tai-du-phong' => 'Tính tải dự phòng', 'tuong-thich-inverter' => 'Kiểm tra biến tần tương thích', 'bao-gia' => 'Nhận báo giá')
            ),
            'pump_controller' => array(
                'label' => 'Biến tần bơm',
                'application_title' => 'Phù hợp với hệ bơm nào?',
                'metrics' => array('motor_power', 'phase_count', 'input_voltage', 'warranty'),
                'fields' => array(
                    'motor_power' => 'Công suất động cơ', 'phase_count' => 'Số pha', 'input_voltage' => 'Điện áp đầu vào',
                    'output_voltage' => 'Điện áp đầu ra', 'max_current' => 'Dòng điện tối đa',
                    'solar_input_range' => 'Dải điện áp DC từ pin', 'control_mode' => 'Chế độ điều khiển',
                    'protections' => 'Chức năng bảo vệ', 'waterproof_rating' => 'Cấp bảo vệ', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('cong-suat-bom' => 'Kiểm tra công suất bơm', 'cot-ap-luu-luong' => 'Tư vấn cột áp/lưu lượng', 'nguon-dien' => 'Kiểm tra nguồn điện', 'bao-gia' => 'Nhận báo giá')
            ),
            'pump_hardware' => array(
                'label' => 'Máy bơm năng lượng mặt trời',
                'application_title' => 'Phù hợp với nhu cầu bơm nước nào?',
                'metrics' => array('motor_power', 'max_flow', 'max_head', 'warranty'),
                'fields' => array(
                    'pump_type' => 'Loại bơm', 'motor_power' => 'Công suất động cơ', 'max_flow' => 'Lưu lượng tối đa',
                    'max_head' => 'Cột áp tối đa', 'outlet_size' => 'Kích thước đầu ra', 'working_voltage' => 'Điện áp làm việc',
                    'waterproof_rating' => 'Cấp bảo vệ', 'material' => 'Vật liệu', 'package_contents' => 'Bộ sản phẩm gồm', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('nguon-nuoc' => 'Tư vấn theo nguồn nước', 'cot-ap' => 'Tính cột áp', 'luu-luong' => 'Tính lưu lượng', 'bao-gia' => 'Nhận báo giá')
            ),
            'accessory' => array(
                'label' => 'Phụ kiện điện mặt trời',
                'application_title' => 'Dùng trong vị trí nào của hệ thống?',
                'metrics' => array('rated_voltage', 'rated_current', 'compatibility', 'warranty'),
                'fields' => array(
                    'accessory_type' => 'Loại phụ kiện', 'rated_voltage' => 'Điện áp định mức', 'rated_current' => 'Dòng điện định mức',
                    'compatibility' => 'Thiết bị tương thích', 'connector_type' => 'Kiểu kết nối', 'cable_section' => 'Tiết diện dây',
                    'material' => 'Vật liệu', 'waterproof_rating' => 'Cấp bảo vệ', 'standard' => 'Tiêu chuẩn',
                    'package_contents' => 'Quy cách đóng gói', 'warranty' => 'Bảo hành'
                ),
                'needs' => array('thiet-bi-tuong-thich' => 'Kiểm tra thiết bị tương thích', 'so-luong' => 'Tư vấn số lượng', 'lap-dat' => 'Tư vấn lắp đặt', 'bao-gia' => 'Nhận báo giá')
            )
        );
        return $profiles;
    }

    function productPdpProfileKeys()
    {
        return array_keys(productPdpProfiles());
    }

    /**
     * Ảnh minh hoạ ứng dụng fallback theo profile/biến thể cho PDP.
     *
     * Đây là fallback theo nhóm sản phẩm, không thay thế ảnh do Admin nhập
     * trong content block. Ảnh đều là minh hoạ bối cảnh, không phải bằng
     * chứng công trình/khách hàng.
     */
    function productPdpUseCaseDefaults($profileKey, $variant = '')
    {
        $asset = 'assets/images/site-visuals/pdp-usecase-v2/';
        $defaults = array(
            'lighting' => array(
                array('title' => 'Đường nội bộ', 'text' => 'Chiếu sáng đường nội bộ, lối lưu thông và khu dân cư bằng hệ đèn độc lập.', 'photo' => $asset.'lighting-road.webp'),
                array('title' => 'Nhà & lối đi', 'text' => 'Bổ sung ánh sáng cho cổng, sân nhà và lối đi bên hông vào ban đêm.', 'photo' => $asset.'lighting-home-gate.webp'),
                array('title' => 'Khuôn viên công trình', 'text' => 'Hỗ trợ chiếu sáng lối vào, bãi xe và khuôn viên nhà xưởng hoặc công trình.', 'photo' => $asset.'lighting-commercial-campus.webp')
            ),
            'solar_panel' => array(
                array('title' => 'Mái nhà dân dụng', 'text' => 'Tạo nguồn điện mặt trời cho nhà ở và công trình có diện tích mái phù hợp.', 'photo' => $asset.'panel-residential-roof.webp'),
                array('title' => 'Mái nhà xưởng', 'text' => 'Kết hợp theo chuỗi pin và thiết bị bảo vệ cho công trình thương mại, nhà xưởng.', 'photo' => $asset.'panel-commercial-roof.webp'),
                array('title' => 'Trang trại điện mặt trời', 'text' => 'Lựa chọn theo công suất, điện áp và quy mô phát điện của hệ thống.', 'photo' => $asset.'solar-farm.webp')
            ),
            'fan' => array(
                array('title' => 'Phòng ở & không gian sinh hoạt', 'text' => 'Bổ sung luồng gió cho phòng khách, phòng ngủ hoặc không gian sinh hoạt cần sự linh hoạt.', 'photo' => $asset.'fan-living-room.webp'),
                array('title' => 'Gian hàng & khu vực nhỏ', 'text' => 'Giải pháp làm mát cơ động cho quầy hàng, chốt trực và khu vực cần dùng điện linh hoạt.', 'photo' => $asset.'fan-shop-kiosk.webp'),
                array('title' => 'Hiên có mái & khu vực bán ngoài trời', 'text' => 'Làm mát cho hiên, chòi nghỉ hoặc khu vực bán ngoài trời có mái che.', 'photo' => $asset.'fan-covered-patio.webp')
            ),
            'inverter' => array(
                array('title' => 'Nhà ở & dân dụng', 'text' => 'Điều phối nguồn điện mặt trời, tải tiêu thụ và pin lưu trữ cho nhu cầu gia đình.', 'photo' => $asset.'inverter-home-room.webp'),
                array('title' => 'Nhà xưởng & thương mại', 'text' => 'Lựa chọn theo công suất tải, số pha và phương án giám sát của công trình.', 'photo' => $asset.'inverter-commercial-room.webp'),
                array('title' => 'Lưu trữ & dự phòng', 'text' => 'Kết hợp với pin lưu trữ để chủ động hơn khi mất điện hoặc cần tối ưu thời gian sử dụng.', 'photo' => $asset.'inverter-hybrid-backup.webp')
            ),
            'storage' => array(
                array('title' => 'Lưu trữ cho gia đình', 'text' => 'Dự trữ điện cho các tải thiết yếu và nhu cầu sử dụng vào buổi tối.', 'photo' => $asset.'storage-home-room.webp'),
                array('title' => 'Dự phòng cho công trình', 'text' => 'Bổ sung nguồn dự phòng theo công suất tải và thời gian cần duy trì.', 'photo' => $asset.'storage-commercial-backup.webp'),
                array('title' => 'Hệ thống điện mặt trời', 'text' => 'Kết hợp cùng biến tần tương thích và hệ thống giám sát phù hợp.', 'photo' => $asset.'storage-hybrid-system.webp')
            ),
            'pump_controller' => array(
                array('title' => 'Tưới tiêu nông nghiệp', 'text' => 'Điều khiển bơm theo nguồn pin mặt trời cho ruộng, vườn và khu vực sản xuất.', 'photo' => $asset.'pump-controller-irrigation.webp'),
                array('title' => 'Bơm nước sinh hoạt', 'text' => 'Phù hợp với hệ bơm cần kiểm soát công suất, điện áp và lưu lượng theo thực tế.', 'photo' => $asset.'pump-controller-water-tank.webp'),
                array('title' => 'Nhà kính & hồ chứa', 'text' => 'Tư vấn theo cột áp, lưu lượng, nguồn nước và điều kiện lắp đặt cụ thể.', 'photo' => $asset.'pump-controller-greenhouse.webp')
            ),
            'pump_hardware' => array(
                array('title' => 'Tưới tiêu nông nghiệp', 'text' => 'Bơm nước cho ruộng, vườn và hệ thống tưới theo nhu cầu thực tế.', 'photo' => $asset.'pump-irrigation-field.webp'),
                array('title' => 'Bơm nước sinh hoạt', 'text' => 'Giải pháp bơm cho bồn chứa, giếng hoặc nguồn nước cần cấp ổn định.', 'photo' => $asset.'pump-water-tank.webp'),
                array('title' => 'Nhà kính & ao hồ', 'text' => 'Chọn máy bơm theo nguồn nước, cột áp, lưu lượng và công suất phù hợp.', 'photo' => $asset.'pump-greenhouse-pond.webp')
            ),
            'accessory' => array(
                array('title' => 'Bảo vệ trên mái', 'text' => 'Bổ sung thiết bị kết nối và bảo vệ cho các chuỗi pin trên mái.', 'photo' => $asset.'accessory-rooftop-protection.webp'),
                array('title' => 'Tủ điện & phân phối', 'text' => 'Lựa chọn theo điện áp, dòng điện và thiết bị tương thích trong hệ thống.', 'photo' => $asset.'accessory-distribution-cabinet.webp'),
                array('title' => 'Kết nối ngoài trời', 'text' => 'Kiểm tra cấp bảo vệ, vật liệu và quy cách lắp đặt trước khi sử dụng.', 'photo' => $asset.'accessory-outdoor-connectors.webp')
            )
        );
        $variants = array(
            'lighting_street' => array(
                array('title' => 'Đường nội bộ', 'text' => 'Chiếu sáng tuyến đường nội bộ, lối lưu thông và khu dân cư theo mặt bằng thực tế.', 'photo' => $asset.'lighting-street-road.webp'),
                array('title' => 'Bãi xe & lối vào', 'text' => 'Bố trí ánh sáng cho bãi xe, cổng vào và khu vực cần quan sát vào ban đêm.', 'photo' => $asset.'lighting-street-parking.webp'),
                array('title' => 'Khuôn viên công trình', 'text' => 'Hỗ trợ chiếu sáng tuyến đường và khuôn viên nhà xưởng, công trình hoặc khu dịch vụ.', 'photo' => $asset.'lighting-street-campus.webp')
            ),
            'lighting_flood' => array(
                array('title' => 'Sân & bãi ngoài trời', 'text' => 'Phủ sáng sân, bãi xe và khu vực ngoài trời cần góc chiếu rộng.', 'photo' => $asset.'lighting-flood-yard.webp'),
                array('title' => 'Nhà xưởng & kho', 'text' => 'Bổ sung ánh sáng cho mặt tiền, bãi thao tác và khu vực quanh nhà xưởng.', 'photo' => $asset.'lighting-flood-warehouse.webp'),
                array('title' => 'Khu vực sản xuất', 'text' => 'Phù hợp khu vực sản xuất hoặc công trình cần bố trí đèn chiếu tập trung ngoài trời.', 'photo' => $asset.'lighting-flood-worksite.webp')
            ),
            'lighting_garden' => array(
                array('title' => 'Lối đi sân vườn', 'text' => 'Tạo điểm sáng dẫn hướng dọc lối đi, bồn cây và khuôn viên cảnh quan.', 'photo' => $asset.'lighting-side-path.webp'),
                array('title' => 'Cổng & sân nhà', 'text' => 'Bổ sung ánh sáng cho cổng, sân nhà và lối vào theo phong cách cảnh quan.', 'photo' => $asset.'lighting-home-gate.webp'),
                array('title' => 'Homestay & khu nghỉ dưỡng', 'text' => 'Tăng khả năng sử dụng buổi tối cho sân vườn, homestay, resort hoặc quán cà phê.', 'photo' => $asset.'lighting-garden-homestay.webp')
            ),
            'lighting_pole' => array(
                array('title' => 'Cổng & hàng rào', 'text' => 'Bố trí đèn trên trụ cổng, hàng rào hoặc lối vào cần điểm sáng định hướng.', 'photo' => $asset.'lighting-home-gate.webp'),
                array('title' => 'Lối vào công trình', 'text' => 'Tạo nhịp ánh sáng cho lối vào, sân trước và tuyến tiếp cận công trình.', 'photo' => $asset.'lighting-pole-estate.webp'),
                array('title' => 'Khuôn viên cảnh quan', 'text' => 'Phù hợp khuôn viên biệt thự, nhà vườn, dịch vụ hoặc công trình cần đồng bộ cảnh quan.', 'photo' => $asset.'lighting-garden-homestay.webp')
            ),
            'lighting_wall' => array(
                array('title' => 'Tường rào & cổng', 'text' => 'Bổ sung ánh sáng gọn trên tường rào, cổng và hiên nhà.', 'photo' => $asset.'lighting-home-gate.webp'),
                array('title' => 'Lối đi bên hông nhà', 'text' => 'Tạo điểm sáng cho lối đi hẹp, ban công hoặc mặt tiền mà không chiếm diện tích.', 'photo' => $asset.'lighting-side-path.webp'),
                array('title' => 'Nhà vườn & homestay', 'text' => 'Kết hợp chiếu sáng và trang trí cho nhà vườn, homestay, quán cà phê hoặc resort.', 'photo' => $asset.'lighting-garden-homestay.webp')
            ),
            'lighting_ceiling' => array(
                array('title' => 'Hiên & sảnh có mái', 'text' => 'Chiếu sáng hiên, sảnh và khu vực có mái che cần bố trí đèn gọn.', 'photo' => $asset.'lighting-ceiling-porch.webp'),
                array('title' => 'Khu vực sinh hoạt', 'text' => 'Bổ sung ánh sáng cho chòi nghỉ, hành lang hoặc không gian sinh hoạt có mái.', 'photo' => $asset.'lighting-ceiling-courtyard.webp'),
                array('title' => 'Kho & khu vực kỹ thuật', 'text' => 'Phù hợp khu vực có mái cần nguồn sáng độc lập, dễ bố trí theo mặt bằng.', 'photo' => $asset.'lighting-ceiling-warehouse.webp')
            ),
            'lighting_ufo' => array(
                array('title' => 'Kho & nhà xưởng cao', 'text' => 'Bố trí tại không gian cao, cần vùng sáng rộng cho kho hoặc nhà xưởng.', 'photo' => $asset.'lighting-ufo-warehouse.webp'),
                array('title' => 'Sân sản xuất & bãi thao tác', 'text' => 'Hỗ trợ chiếu sáng vùng thao tác, sân sản xuất hoặc bãi tập kết ngoài trời.', 'photo' => $asset.'lighting-ufo-worksite.webp'),
                array('title' => 'Trang trại & khu vực rộng', 'text' => 'Phù hợp khu vực rộng cần nguồn sáng độc lập và dễ bố trí theo vị trí sử dụng.', 'photo' => $asset.'lighting-ufo-farm.webp')
            ),
            'accessory_cable' => array(
                array('title' => 'Tuyến cáp & dây dẫn', 'text' => 'Bố trí tuyến cáp, dây nối và phụ kiện dẫn điện trong hệ thống mặt trời.', 'photo' => $asset.'accessory-cable-rooftop.webp'),
                array('title' => 'Ống & bảo vệ đường dây', 'text' => 'Bảo vệ tuyến dây tại mái, tường hoặc khu vực ngoài trời theo điều kiện lắp đặt.', 'photo' => $asset.'accessory-cable-conduit.webp'),
                array('title' => 'Tiếp địa & kết nối', 'text' => 'Hoàn thiện các điểm kết nối, tiếp địa và bảo vệ đường dây theo thiết kế hệ thống.', 'photo' => $asset.'accessory-cable-grounding.webp')
            ),
            'accessory_controller' => array(
                array('title' => 'Tủ điều khiển sạc', 'text' => 'Bố trí bộ điều khiển sạc và thiết bị giám sát trong tủ kỹ thuật phù hợp.', 'photo' => $asset.'accessory-controller-cabinet.webp'),
                array('title' => 'Điểm giám sát hệ thống', 'text' => 'Theo dõi trạng thái sạc, tải và dữ liệu vận hành tại khu vực kỹ thuật.', 'photo' => $asset.'accessory-controller-monitoring.webp'),
                array('title' => 'Phòng kỹ thuật điện mặt trời', 'text' => 'Tích hợp thiết bị điều khiển, đo lường và kết nối trong phòng kỹ thuật.', 'photo' => $asset.'accessory-controller-room.webp')
            )
        );
        if($variant !== '' && isset($variants[$variant])) return $variants[$variant];
        return isset($defaults[$profileKey]) ? $defaults[$profileKey] : $defaults['lighting'];
    }

    function productPdpUseCasePhoto($profileKey, $title = '', $index = 0, $variant = '')
    {
        $defaults = productPdpUseCaseDefaults($profileKey, $variant);
        $asset = 'assets/images/site-visuals/pdp-usecase-v2/';
        $title = mb_strtolower(trim(strip_tags((string)$title)), 'UTF-8');
        if($variant !== '' && !empty($defaults)) return $defaults[$index % count($defaults)]['photo'];
        if($profileKey === 'lighting')
        {
            if(strpos($title, 'đường nội bộ') !== false || strpos($title, 'bãi đỗ') !== false) return $defaults[0]['photo'];
            if(strpos($title, 'tường rào') !== false || strpos($title, 'cổng') !== false) return $defaults[1]['photo'];
            if(strpos($title, 'lối đi') !== false || strpos($title, 'sân nhà') !== false) return $asset.'lighting-side-path.webp';
            if(strpos($title, 'nhà vườn') !== false || strpos($title, 'homestay') !== false) return $asset.'lighting-garden-homestay.webp';
            if(strpos($title, 'khuôn viên') !== false || strpos($title, 'công trình') !== false || strpos($title, 'nhà xưởng') !== false || strpos($title, 'resort') !== false) return $defaults[2]['photo'];
        }
        if($profileKey === 'solar_panel')
        {
            if(strpos($title, 'trang trại') !== false || strpos($title, 'farm') !== false) return $defaults[2]['photo'];
            if(strpos($title, 'nhà xưởng') !== false || strpos($title, 'thương mại') !== false || strpos($title, 'công nghiệp') !== false) return $defaults[1]['photo'];
            if(strpos($title, 'mái') !== false || strpos($title, 'nhà') !== false) return $defaults[0]['photo'];
        }
        if($profileKey === 'pump_controller' || $profileKey === 'pump_hardware')
        {
            if(strpos($title, 'nhà kính') !== false || strpos($title, 'ao hồ') !== false || strpos($title, 'hồ chứa') !== false) return $defaults[2]['photo'];
            if(strpos($title, 'sinh hoạt') !== false || strpos($title, 'bồn') !== false || strpos($title, 'bể') !== false || strpos($title, 'giếng') !== false) return $defaults[1]['photo'];
            if(strpos($title, 'tưới') !== false || strpos($title, 'ruộng') !== false || strpos($title, 'vườn') !== false || strpos($title, 'nông nghiệp') !== false) return $defaults[0]['photo'];
        }
        return isset($defaults[$index % count($defaults)]) ? $defaults[$index % count($defaults)]['photo'] : '';
    }

    function productPdpNormalizeProfile($value, $allowEmpty = true)
    {
        $value = trim((string)$value);
        if($allowEmpty && $value === '') return '';
        return in_array($value, productPdpProfileKeys(), true) ? $value : 'general';
    }

    function productPdpProfileFromSlug($slug)
    {
        $map = array(
            'den-nlmt' => 'lighting', 'den-san-vuon' => 'lighting', 'tru-den' => 'lighting',
            'quat-nang-luong-mat-troi' => 'fan', 'tam-pin-nang-luong-mat-troi' => 'solar_panel',
            'bien-tan-inverter' => 'inverter', 'pin-luu-tru' => 'storage', 'bien-tan-bom' => 'pump_controller',
            'thiet-bi-dien-nang-luong-mat-troi' => 'accessory'
        );
        $slug = trim((string)$slug, '/ ');
        return isset($map[$slug]) ? $map[$slug] : 'general';
    }

    function productPdpResolveProfile($product, $list = array())
    {
        if(!empty($product['pdp_profile'])) return productPdpNormalizeProfile($product['pdp_profile'], false);
        if(!empty($list['pdp_profile'])) return productPdpNormalizeProfile($list['pdp_profile'], false);
        return productPdpProfileFromSlug(isset($list['slugvi']) ? $list['slugvi'] : '');
    }

    /**
     * Tách bối cảnh minh hoạ trong cùng một danh mục cha.
     * Chỉ dùng cho khối ứng dụng; không thay đổi profile thông số/checkout.
     */
    function productPdpUseCaseVariant($product = array(), $list = array(), $profileKey = '')
    {
        $name = isset($product['namevi']) ? htmlspecialchars_decode((string)$product['namevi'], ENT_QUOTES) : '';
        $slug = isset($product['slugvi']) ? (string)$product['slugvi'] : '';
        $listSlug = isset($list['slugvi']) ? (string)$list['slugvi'] : '';
        /* Tên sản phẩm không phải lúc nào cũng nói rõ vị trí lắp đặt (một số
           model chỉ có mã DB/UFO). Đọc thêm các trường ứng dụng/lắp đặt đã
           nhập trong Admin để tránh rơi về ảnh minh hoạ sai ngữ cảnh. */
        $context = '';
        foreach(array('applicationsvi', 'installationvi', 'highlightsvi', 'descvi') as $field)
        {
            if(isset($product[$field]) && trim((string)$product[$field]) !== '')
            {
                $context .= ' '.htmlspecialchars_decode(strip_tags((string)$product[$field]), ENT_QUOTES);
            }
        }
        $context = mb_strtolower($context, 'UTF-8');
        $identity = mb_strtolower(trim($name.' '.$slug.' '.$listSlug), 'UTF-8');
        $haystack = mb_strtolower(trim($identity.' '.$context), 'UTF-8');
        if($profileKey === 'lighting')
        {
            /* Các tín hiệu kiểu sản phẩm chỉ lấy từ tên/slug; phần mô tả có
               thể nhắc “cần đèn”, “tương thích”, “trần” nhưng không có nghĩa
               sản phẩm thuộc nhóm trụ, tường hay ốp trần. */
            if(strpos($identity, 'trụ cổng') !== false || strpos($identity, 'tru-cong') !== false || strpos($identity, 'cần đèn') !== false || strpos($identity, 'can-den') !== false || $listSlug === 'tru-den') return 'lighting_pole';
            if(strpos($identity, 'ufo') !== false) return 'lighting_ufo';
            if(strpos($identity, 'treo tường') !== false || strpos($identity, 'treo-tuong') !== false || strpos($identity, 'tường') !== false) return 'lighting_wall';
            if(strpos($identity, 'ốp trần') !== false || strpos($identity, 'op-tran') !== false || strpos($identity, 'trần') !== false) return 'lighting_ceiling';
            if($listSlug === 'den-san-vuon') return 'lighting_garden';
            if(strpos($identity, 'pha') !== false || strpos($identity, 'đèn pha') !== false) return 'lighting_flood';
            if(strpos($identity, 'đường') !== false || strpos($identity, 'den-duong') !== false || strpos($context, 'đường nội bộ') !== false || strpos($context, 'tuyến đường') !== false || strpos($context, 'đường nông thôn') !== false || strpos($context, 'khu dân cư') !== false) return 'lighting_street';
            if(strpos($identity, 'sân vườn') !== false || strpos($identity, 'san-vuon') !== false) return 'lighting_garden';
            /* Đèn NLMT chỉ có mã model vẫn cần bối cảnh an toàn; dùng nhóm
               floodlight trung tính thay vì rơi vào ảnh đường/sân vườn. */
            return 'lighting_flood';
        }
        if($profileKey === 'accessory')
        {
            if(strpos($haystack, 'điều khiển sạc') !== false || strpos($haystack, 'dieu-khien-sac') !== false || strpos($haystack, 'mppt') !== false || strpos($haystack, 'pwm') !== false || strpos($haystack, 'meter') !== false || strpos($haystack, 'metter') !== false || strpos($haystack, 'ems') !== false || strpos($haystack, 'bám tải') !== false || strpos($haystack, 'bam-tai') !== false) return 'accessory_controller';
            if(strpos($haystack, 'cáp') !== false || strpos($haystack, 'cap-') !== false || strpos($haystack, 'dây') !== false || strpos($haystack, 'day-') !== false || strpos($haystack, 'ống') !== false || strpos($haystack, 'ong-') !== false || strpos($haystack, 'ruột gà') !== false || strpos($haystack, 'keo') !== false || strpos($haystack, 'băng keo') !== false || strpos($haystack, 'tiếp địa') !== false || strpos($haystack, 'tiep-dia') !== false || strpos($haystack, 'mc4') !== false || strpos($haystack, 'đầu nối') !== false) return 'accessory_cable';
        }
        return '';
    }

    function productPdpProfile($key)
    {
        $profiles = productPdpProfiles();
        $key = productPdpNormalizeProfile($key, false);
        return $profiles[$key];
    }

    function productPdpAdditionalSpecs($row)
    {
        $out = array();
        if(empty($row['additional_specs'])) return $out;
        $decoded = json_decode(htmlspecialchars_decode((string)$row['additional_specs'], ENT_QUOTES), true);
        if(!is_array($decoded)) return $out;
        foreach($decoded as $spec)
        {
            $label = !empty($spec['label']) ? trim((string)$spec['label']) : '';
            $value = isset($spec['value']) ? trim((string)$spec['value']) : '';
            $key = !empty($spec['key']) && preg_match('/^[a-z0-9_]+$/', $spec['key']) ? $spec['key'] : '';
            if($label === '' || $value === '') continue;
            $out[] = array('key' => $key, 'label' => $label, 'value' => $value);
        }
        return $out;
    }

    function productPdpValueIsMissing($value)
    {
        $normalized = mb_strtolower(trim(strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))), 'UTF-8');
        if($normalized === '') return true;
        return (bool) preg_match('/^(?:không công bố|không áp dụng|không có thông tin|chưa cập nhật|đang cập nhật|chưa xác minh|chưa tìm được|n\/a|na|-)\b[^\S\r\n]*(?:exact-model)?\.?$/iu', $normalized);
    }

    function productPdpSpecAliases($key)
    {
        static $aliases = array(
            'cell_type' => array('loại cell', 'công nghệ cell', 'loại tế bào', 'công nghệ'),
            'efficiency' => array('hiệu suất module', 'hiệu suất tối đa'),
            'open_circuit_voltage' => array('voc', 'điện áp hở mạch'),
            'short_circuit_current' => array('isc', 'dòng ngắn mạch'),
            'max_power_voltage' => array('vmp', 'điện áp tại pmax', 'điện áp tại công suất cực đại'),
            'max_power_current' => array('imp', 'dòng tại pmax', 'dòng điện công suất cực đại'),
            'panel_dimensions' => array('kích thước tấm pin', 'kích thước hiện có trong file', 'kích thước'),
            'weight' => array('khối lượng', 'trọng lượng'),
            'connector_type' => array('đầu nối', 'kiểu kết nối', 'cáp đầu nối'),
            'inverter_type' => array('loại biến tần', 'loại thiết bị'),
            'ac_power' => array('công suất ac danh định', 'công suất ac'),
            'phase_count' => array('số pha'),
            'mppt_count' => array('số mppt / chuỗi mỗi mppt', 'số mppt / số chuỗi pv', 'số mppt'),
            'max_dc_voltage' => array('điện áp dc tối đa'),
            'mppt_voltage_range' => array('dải điện áp mppt', 'dải mppt đầy tải', 'dải điện áp làm việc mppt'),
            'max_mppt_current' => array('dòng vào tối đa mỗi mppt', 'dòng vào tối đa mppt', 'dòng mppt tối đa'),
            'battery_voltage_range' => array('dải điện áp pin'),
            'backup_power' => array('công suất eps định mức', 'công suất eps đỉnh', 'eps'),
            'monitoring' => array('giám sát / kết nối', 'giám sát cloud', 'giao tiếp'),
            'battery_chemistry' => array('loại pin', 'công nghệ pin'),
            'nominal_capacity' => array('dung lượng danh định'),
            'usable_capacity' => array('dung lượng sử dụng'),
            'battery_voltage' => array('điện áp pin', 'điện áp danh định'),
            'max_charge_current' => array('dòng sạc tối đa', 'dòng sạc định mức'),
            'max_discharge_current' => array('dòng xả tối đa', 'dòng xả định mức'),
            'depth_of_discharge' => array('độ sâu xả', 'dod'),
            'cycle_life' => array('tuổi thọ chu kỳ', 'chu kỳ'),
            'communication' => array('giao tiếp', 'rs485', 'can', 'ethernet'),
            'fan_type' => array('loại quạt'),
            'blade_size' => array('đường kính cánh', 'cánh quạt'),
            'airflow' => array('lưu lượng gió', 'tốc độ gió tối đa'),
            'speed_levels' => array('số cấp gió', 'cấp độ gió'),
            'runtime' => array('thời gian hoạt động', 'thời gian sử dụng'),
            'motor_power' => array('công suất động cơ'),
            'input_voltage' => array('điện áp đầu vào', 'điện áp pv đầu vào'),
            'output_voltage' => array('điện áp đầu ra'),
            'max_current' => array('dòng điện tối đa'),
            'solar_input_range' => array('dải điện áp dc từ pin', 'dải điện áp pv'),
            'control_mode' => array('chế độ điều khiển', 'chế độ làm việc'),
            'protections' => array('chức năng bảo vệ', 'bảo vệ')
        );
        return isset($aliases[$key]) ? $aliases[$key] : array();
    }

    function productPdpSpecValue($row, $key)
    {
        if(isset($row[$key]) && trim((string)$row[$key]) !== '' && !($key === 'cong_suat' && (float)$row[$key] <= 0))
        {
            $value = trim((string)$row[$key]);
            if(productPdpValueIsMissing($value)) return '';
            if($key === 'package_contents') return productNormalizePackageContents($value);
            if($key === 'cong_suat' && is_numeric(str_replace(',', '.', $value))) $value = sprintf('%g', (float)str_replace(',', '.', $value)).'W';
            return $value;
        }
        $aliases = productPdpSpecAliases($key);
        if($aliases) foreach(productPdpAdditionalSpecs($row) as $spec) {
            $label = mb_strtolower(trim($spec['label']), 'UTF-8');
            foreach($aliases as $alias) {
                if($label === $alias && !productPdpValueIsMissing($spec['value'])) return $spec['value'];
            }
        }
        foreach(productPdpAdditionalSpecs($row) as $spec) if($spec['key'] === $key && !productPdpValueIsMissing($spec['value'])) return $key === 'package_contents' ? productNormalizePackageContents($spec['value']) : $spec['value'];
        return '';
    }
}
