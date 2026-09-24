<?php
/**
 * Cấu hình dữ liệu cho trang chi tiết sản phẩm.
 *
 * Một template được dùng cho mọi nhóm. Profile chỉ quyết định nhãn, thứ tự
 * thông số và câu hỏi tư vấn; không tạo template riêng theo danh mục.
 */
if(!function_exists('productPdpProfiles'))
{
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
                    'efficiency' => 'Hiệu suất theo datasheet', 'battery_voltage_range' => 'Dải điện áp pin lưu trữ',
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

    function productPdpSpecValue($row, $key)
    {
        if(isset($row[$key]) && trim((string)$row[$key]) !== '' && !($key === 'cong_suat' && (float)$row[$key] <= 0))
        {
            $value = trim((string)$row[$key]);
            if($key === 'cong_suat' && is_numeric(str_replace(',', '.', $value))) $value = sprintf('%g', (float)str_replace(',', '.', $value)).'W';
            return $value;
        }
        foreach(productPdpAdditionalSpecs($row) as $spec) if($spec['key'] === $key) return $spec['value'];
        return '';
    }
}
