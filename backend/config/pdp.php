<?php

// Product page spec profiles: which specs each kind of product shows and how they are labelled.
// Product page profiles: labels, field order, metrics and use-case defaults. Candidate for an Admin editor.
return [
    'profiles' => [
        'general' => [
            'label' => 'Dùng chung',
            'application_title' => 'Phù hợp với nhu cầu của bạn',
            'metrics' => [
                0 => 'cong_suat',
                1 => 'material',
                2 => 'waterproof_rating',
                3 => 'warranty',
            ],
            'fields' => [
                'cong_suat' => 'Công suất',
                'material' => 'Chất liệu',
                'waterproof_rating' => 'Cấp bảo vệ',
                'package_contents' => 'Bộ sản phẩm gồm',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'kiem-tra-phu-hop' => 'Kiểm tra sản phẩm có phù hợp',
                'bao-gia' => 'Nhận báo giá',
                'ho-tro-ky-thuat' => 'Hỗ trợ kỹ thuật',
            ],
        ],
        'lighting' => [
            'label' => 'Đèn năng lượng mặt trời',
            'application_title' => 'Phù hợp với nhu cầu của bạn',
            'metrics' => [
                0 => 'cong_suat',
                1 => 'panel_power',
                2 => 'battery_capacity',
                3 => 'lighting_time',
            ],
            'fields' => [
                'cong_suat' => 'Công suất',
                'light_source' => 'Nguồn sáng',
                'lamp_dimensions' => 'Kích thước đèn',
                'color_temperature' => 'Nhiệt độ màu (CCT)',
                'light_color' => 'Màu ánh sáng',
                'battery_capacity' => 'Dung lượng pin',
                'panel_power' => 'Công suất tấm pin',
                'material' => 'Chất liệu',
                'charging_time' => 'Thời gian sạc',
                'lighting_time' => 'Thời gian chiếu sáng',
                'waterproof_rating' => 'Cấp bảo vệ',
                'led_chip' => 'Chip LED',
                'lighting_area' => 'Diện tích chiếu sáng',
                'installation_height' => 'Độ cao lắp đặt',
                'panel_dimensions' => 'Kích thước tấm pin',
                'package_contents' => 'Bộ sản phẩm gồm',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'vi-tri-lap-dat' => 'Tư vấn theo vị trí lắp đặt',
                'do-sang' => 'Tư vấn độ sáng',
                'so-luong' => 'Tính số lượng đèn',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'solar_panel' => [
            'label' => 'Tấm pin năng lượng mặt trời',
            'application_title' => 'Phù hợp với hệ thống của bạn',
            'metrics' => [
                0 => 'cong_suat',
                1 => 'cell_type',
                2 => 'efficiency',
                3 => 'warranty',
            ],
            'fields' => [
                'cong_suat' => 'Công suất danh định',
                'cell_type' => 'Loại cell',
                'efficiency' => 'Hiệu suất mô-đun',
                'open_circuit_voltage' => 'Điện áp hở mạch (Voc)',
                'short_circuit_current' => 'Dòng ngắn mạch (Isc)',
                'max_power_voltage' => 'Điện áp tại Pmax (Vmp)',
                'max_power_current' => 'Dòng tại Pmax (Imp)',
                'panel_dimensions' => 'Kích thước tấm pin',
                'weight' => 'Khối lượng',
                'connector_type' => 'Đầu nối',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'cong-suat-he-thong' => 'Tính công suất hệ thống',
                'dien-tich-mai' => 'Kiểm tra diện tích mái',
                'tuong-thich' => 'Kiểm tra tương thích',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'fan' => [
            'label' => 'Quạt năng lượng mặt trời',
            'application_title' => 'Phù hợp với không gian của bạn',
            'metrics' => [
                0 => 'cong_suat',
                1 => 'battery_capacity',
                2 => 'runtime',
                3 => 'warranty',
            ],
            'fields' => [
                'cong_suat' => 'Công suất',
                'fan_type' => 'Loại quạt',
                'blade_size' => 'Đường kính cánh',
                'airflow' => 'Lưu lượng gió',
                'speed_levels' => 'Số cấp gió',
                'battery_capacity' => 'Dung lượng pin',
                'panel_power' => 'Công suất tấm pin',
                'runtime' => 'Thời gian hoạt động',
                'charging_time' => 'Thời gian sạc',
                'package_contents' => 'Bộ sản phẩm gồm',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'khong-gian' => 'Tư vấn theo không gian',
                'thoi-gian-su-dung' => 'Tư vấn thời gian sử dụng',
                'so-luong' => 'Tư vấn số lượng',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'inverter' => [
            'label' => 'Biến tần điện mặt trời',
            'application_title' => 'Ứng dụng trong hệ thống nào?',
            'metrics' => [
                0 => 'ac_power',
                1 => 'phase_count',
                2 => 'mppt_count',
                3 => 'warranty',
            ],
            'fields' => [
                'inverter_type' => 'Loại biến tần',
                'ac_power' => 'Công suất AC định mức',
                'phase_count' => 'Số pha',
                'mppt_count' => 'Số MPPT / số chuỗi đầu vào',
                'max_dc_voltage' => 'Điện áp DC tối đa',
                'mppt_voltage_range' => 'Dải điện áp MPPT',
                'max_mppt_current' => 'Dòng đầu vào tối đa mỗi MPPT',
                'efficiency' => 'Hiệu suất chuyển đổi',
                'battery_voltage_range' => 'Dải điện áp pin lưu trữ',
                'backup_power' => 'Công suất dự phòng',
                'monitoring' => 'Kết nối giám sát',
                'waterproof_rating' => 'Cấp bảo vệ',
                'dimensions_weight' => 'Kích thước & khối lượng',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'cong-suat-tai' => 'Kiểm tra công suất tải',
                'so-pha' => 'Kiểm tra số pha',
                'pin-luu-tru' => 'Kiểm tra pin lưu trữ',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'storage' => [
            'label' => 'Pin lưu trữ',
            'application_title' => 'Phù hợp với nhu cầu lưu trữ nào?',
            'metrics' => [
                0 => 'usable_capacity',
                1 => 'battery_voltage',
                2 => 'cycle_life',
                3 => 'warranty',
            ],
            'fields' => [
                'battery_chemistry' => 'Công nghệ pin',
                'nominal_capacity' => 'Dung lượng danh định',
                'usable_capacity' => 'Dung lượng sử dụng',
                'battery_voltage' => 'Điện áp danh định',
                'max_charge_current' => 'Dòng sạc tối đa',
                'max_discharge_current' => 'Dòng xả tối đa',
                'depth_of_discharge' => 'Độ sâu xả (DoD)',
                'cycle_life' => 'Tuổi thọ chu kỳ',
                'communication' => 'Giao tiếp BMS',
                'compatible_inverters' => 'Biến tần tương thích',
                'waterproof_rating' => 'Cấp bảo vệ',
                'dimensions_weight' => 'Kích thước & khối lượng',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'dung-luong' => 'Tính dung lượng cần dùng',
                'tai-du-phong' => 'Tính tải dự phòng',
                'tuong-thich-inverter' => 'Kiểm tra biến tần tương thích',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'pump_controller' => [
            'label' => 'Biến tần bơm',
            'application_title' => 'Phù hợp với hệ bơm nào?',
            'metrics' => [
                0 => 'motor_power',
                1 => 'phase_count',
                2 => 'input_voltage',
                3 => 'warranty',
            ],
            'fields' => [
                'motor_power' => 'Công suất động cơ',
                'phase_count' => 'Số pha',
                'input_voltage' => 'Điện áp đầu vào',
                'output_voltage' => 'Điện áp đầu ra',
                'max_current' => 'Dòng điện tối đa',
                'solar_input_range' => 'Dải điện áp DC từ pin',
                'control_mode' => 'Chế độ điều khiển',
                'protections' => 'Chức năng bảo vệ',
                'waterproof_rating' => 'Cấp bảo vệ',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'cong-suat-bom' => 'Kiểm tra công suất bơm',
                'cot-ap-luu-luong' => 'Tư vấn cột áp/lưu lượng',
                'nguon-dien' => 'Kiểm tra nguồn điện',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'pump_hardware' => [
            'label' => 'Máy bơm năng lượng mặt trời',
            'application_title' => 'Phù hợp với nhu cầu bơm nước nào?',
            'metrics' => [
                0 => 'motor_power',
                1 => 'max_flow',
                2 => 'max_head',
                3 => 'warranty',
            ],
            'fields' => [
                'pump_type' => 'Loại bơm',
                'motor_power' => 'Công suất động cơ',
                'max_flow' => 'Lưu lượng tối đa',
                'max_head' => 'Cột áp tối đa',
                'outlet_size' => 'Kích thước đầu ra',
                'working_voltage' => 'Điện áp làm việc',
                'waterproof_rating' => 'Cấp bảo vệ',
                'material' => 'Vật liệu',
                'package_contents' => 'Bộ sản phẩm gồm',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'nguon-nuoc' => 'Tư vấn theo nguồn nước',
                'cot-ap' => 'Tính cột áp',
                'luu-luong' => 'Tính lưu lượng',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'charge_controller' => [
            'label' => 'Bộ điều khiển sạc',
            'application_title' => 'Dùng cho hệ thống nào?',
            'metrics' => [
                0 => 'system_voltage',
                1 => 'rated_charge_current',
                2 => 'max_pv_voltage',
                3 => 'warranty',
            ],
            'fields' => [
                'controller_type' => 'Công nghệ sạc',
                'system_voltage' => 'Điện áp hệ thống',
                'rated_charge_current' => 'Dòng sạc định mức',
                'max_charge_current' => 'Dòng sạc tối đa',
                'rated_discharge_current' => 'Dòng xả định mức',
                'max_discharge_current' => 'Dòng xả tối đa',
                'max_pv_voltage' => 'Điện áp đầu vào PV tối đa',
                'max_pv_power' => 'Công suất đầu vào PV tối đa',
                'working_voltage_range' => 'Dải điện áp làm việc',
                'usb_output' => 'Đầu ra USB',
                'waterproof_rating' => 'Cấp bảo vệ',
                'dimensions' => 'Kích thước',
                'weight' => 'Trọng lượng',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'chon-dong-sac' => 'Chọn dòng sạc phù hợp',
                'tuong-thich' => 'Kiểm tra tương thích pin và tấm pin',
                'lap-dat' => 'Tư vấn lắp đặt',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
        'accessory' => [
            'label' => 'Phụ kiện điện mặt trời',
            'application_title' => 'Dùng trong vị trí nào của hệ thống?',
            'metrics' => [
                0 => 'rated_voltage',
                1 => 'rated_current',
                2 => 'compatibility',
                3 => 'warranty',
            ],
            'fields' => [
                'accessory_type' => 'Loại phụ kiện',
                'rated_voltage' => 'Điện áp định mức',
                'rated_current' => 'Dòng điện định mức',
                'compatibility' => 'Thiết bị tương thích',
                'connector_type' => 'Kiểu kết nối',
                'cable_section' => 'Tiết diện dây',
                'material' => 'Vật liệu',
                'waterproof_rating' => 'Cấp bảo vệ',
                'standard' => 'Tiêu chuẩn',
                'package_contents' => 'Quy cách đóng gói',
                'warranty' => 'Bảo hành',
            ],
            'needs' => [
                'thiet-bi-tuong-thich' => 'Kiểm tra thiết bị tương thích',
                'so-luong' => 'Tư vấn số lượng',
                'lap-dat' => 'Tư vấn lắp đặt',
                'bao-gia' => 'Nhận báo giá',
            ],
        ],
    ],
    'use_cases' => [
        'profiles' => [
            'general' => [
                0 => [
                    'title' => 'Đường nội bộ',
                    'text' => 'Chiếu sáng đường nội bộ, lối lưu thông và khu dân cư bằng hệ đèn độc lập.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-road.webp',
                ],
                1 => [
                    'title' => 'Nhà & lối đi',
                    'text' => 'Bổ sung ánh sáng cho cổng, sân nhà và lối đi bên hông vào ban đêm.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-home-gate.webp',
                ],
                2 => [
                    'title' => 'Khuôn viên công trình',
                    'text' => 'Hỗ trợ chiếu sáng lối vào, bãi xe và khuôn viên nhà xưởng hoặc công trình.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-commercial-campus.webp',
                ],
            ],
            'lighting' => [
                0 => [
                    'title' => 'Đường nội bộ',
                    'text' => 'Chiếu sáng đường nội bộ, lối lưu thông và khu dân cư bằng hệ đèn độc lập.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-road.webp',
                ],
                1 => [
                    'title' => 'Nhà & lối đi',
                    'text' => 'Bổ sung ánh sáng cho cổng, sân nhà và lối đi bên hông vào ban đêm.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-home-gate.webp',
                ],
                2 => [
                    'title' => 'Khuôn viên công trình',
                    'text' => 'Hỗ trợ chiếu sáng lối vào, bãi xe và khuôn viên nhà xưởng hoặc công trình.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-commercial-campus.webp',
                ],
            ],
            'solar_panel' => [
                0 => [
                    'title' => 'Mái nhà dân dụng',
                    'text' => 'Tạo nguồn điện mặt trời cho nhà ở và công trình có diện tích mái phù hợp.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/panel-residential-roof.webp',
                ],
                1 => [
                    'title' => 'Mái nhà xưởng',
                    'text' => 'Kết hợp theo chuỗi pin và thiết bị bảo vệ cho công trình thương mại, nhà xưởng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/panel-commercial-roof.webp',
                ],
                2 => [
                    'title' => 'Trang trại điện mặt trời',
                    'text' => 'Lựa chọn theo công suất, điện áp và quy mô phát điện của hệ thống.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/solar-farm.webp',
                ],
            ],
            'fan' => [
                0 => [
                    'title' => 'Phòng ở & không gian sinh hoạt',
                    'text' => 'Bổ sung luồng gió cho phòng khách, phòng ngủ hoặc không gian sinh hoạt cần sự linh hoạt.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/fan-living-room.webp',
                ],
                1 => [
                    'title' => 'Gian hàng & khu vực nhỏ',
                    'text' => 'Giải pháp làm mát cơ động cho quầy hàng, chốt trực và khu vực cần dùng điện linh hoạt.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/fan-shop-kiosk.webp',
                ],
                2 => [
                    'title' => 'Hiên có mái & khu vực bán ngoài trời',
                    'text' => 'Làm mát cho hiên, chòi nghỉ hoặc khu vực bán ngoài trời có mái che.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/fan-covered-patio.webp',
                ],
            ],
            'inverter' => [
                0 => [
                    'title' => 'Nhà ở & dân dụng',
                    'text' => 'Điều phối nguồn điện mặt trời, tải tiêu thụ và pin lưu trữ cho nhu cầu gia đình.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/inverter-home-room.webp',
                ],
                1 => [
                    'title' => 'Nhà xưởng & thương mại',
                    'text' => 'Lựa chọn theo công suất tải, số pha và phương án giám sát của công trình.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/inverter-commercial-room.webp',
                ],
                2 => [
                    'title' => 'Lưu trữ & dự phòng',
                    'text' => 'Kết hợp với pin lưu trữ để chủ động hơn khi mất điện hoặc cần tối ưu thời gian sử dụng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/inverter-hybrid-backup.webp',
                ],
            ],
            'storage' => [
                0 => [
                    'title' => 'Lưu trữ cho gia đình',
                    'text' => 'Dự trữ điện cho các tải thiết yếu và nhu cầu sử dụng vào buổi tối.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/storage-home-room.webp',
                ],
                1 => [
                    'title' => 'Dự phòng cho công trình',
                    'text' => 'Bổ sung nguồn dự phòng theo công suất tải và thời gian cần duy trì.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/storage-commercial-backup.webp',
                ],
                2 => [
                    'title' => 'Hệ thống điện mặt trời',
                    'text' => 'Kết hợp cùng biến tần tương thích và hệ thống giám sát phù hợp.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/storage-hybrid-system.webp',
                ],
            ],
            'pump_controller' => [
                0 => [
                    'title' => 'Tưới tiêu nông nghiệp',
                    'text' => 'Điều khiển bơm theo nguồn pin mặt trời cho ruộng, vườn và khu vực sản xuất.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-controller-irrigation.webp',
                ],
                1 => [
                    'title' => 'Bơm nước sinh hoạt',
                    'text' => 'Phù hợp với hệ bơm cần kiểm soát công suất, điện áp và lưu lượng theo thực tế.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-controller-water-tank.webp',
                ],
                2 => [
                    'title' => 'Nhà kính & hồ chứa',
                    'text' => 'Tư vấn theo cột áp, lưu lượng, nguồn nước và điều kiện lắp đặt cụ thể.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-controller-greenhouse.webp',
                ],
            ],
            'pump_hardware' => [
                0 => [
                    'title' => 'Tưới tiêu nông nghiệp',
                    'text' => 'Bơm nước cho ruộng, vườn và hệ thống tưới theo nhu cầu thực tế.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-irrigation-field.webp',
                ],
                1 => [
                    'title' => 'Bơm nước sinh hoạt',
                    'text' => 'Giải pháp bơm cho bồn chứa, giếng hoặc nguồn nước cần cấp ổn định.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-water-tank.webp',
                ],
                2 => [
                    'title' => 'Nhà kính & ao hồ',
                    'text' => 'Chọn máy bơm theo nguồn nước, cột áp, lưu lượng và công suất phù hợp.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/pump-greenhouse-pond.webp',
                ],
            ],
            'charge_controller' => [
                0 => [
                    'title' => 'Hệ đèn & điện độc lập',
                    'text' => 'Điều khiển sạc từ tấm pin vào ắc quy cho hệ đèn, camera và điện nhỏ không nối lưới.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-outdoor-connectors.webp',
                ],
                1 => [
                    'title' => 'Tủ điện năng lượng mặt trời',
                    'text' => 'Chọn theo điện áp hệ thống, dòng sạc và điện áp PV tối đa của dàn pin.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-distribution-cabinet.webp',
                ],
            ],
            'accessory' => [
                0 => [
                    'title' => 'Bảo vệ trên mái',
                    'text' => 'Bổ sung thiết bị kết nối và bảo vệ cho các chuỗi pin trên mái.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-rooftop-protection.webp',
                ],
                1 => [
                    'title' => 'Tủ điện & phân phối',
                    'text' => 'Lựa chọn theo điện áp, dòng điện và thiết bị tương thích trong hệ thống.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-distribution-cabinet.webp',
                ],
                2 => [
                    'title' => 'Kết nối ngoài trời',
                    'text' => 'Kiểm tra cấp bảo vệ, vật liệu và quy cách lắp đặt trước khi sử dụng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-outdoor-connectors.webp',
                ],
            ],
        ],
        'variants' => [
            'lighting_street' => [
                0 => [
                    'title' => 'Đường nội bộ',
                    'text' => 'Chiếu sáng tuyến đường nội bộ, lối lưu thông và khu dân cư theo mặt bằng thực tế.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-street-road.webp',
                ],
                1 => [
                    'title' => 'Bãi xe & lối vào',
                    'text' => 'Bố trí ánh sáng cho bãi xe, cổng vào và khu vực cần quan sát vào ban đêm.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-street-parking.webp',
                ],
                2 => [
                    'title' => 'Khuôn viên công trình',
                    'text' => 'Hỗ trợ chiếu sáng tuyến đường và khuôn viên nhà xưởng, công trình hoặc khu dịch vụ.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-street-campus.webp',
                ],
            ],
            'lighting_flood' => [
                0 => [
                    'title' => 'Sân & bãi ngoài trời',
                    'text' => 'Phủ sáng sân, bãi xe và khu vực ngoài trời cần góc chiếu rộng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-flood-yard.webp',
                ],
                1 => [
                    'title' => 'Nhà xưởng & kho',
                    'text' => 'Bổ sung ánh sáng cho mặt tiền, bãi thao tác và khu vực quanh nhà xưởng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-flood-warehouse.webp',
                ],
                2 => [
                    'title' => 'Khu vực sản xuất',
                    'text' => 'Phù hợp khu vực sản xuất hoặc công trình cần bố trí đèn chiếu tập trung ngoài trời.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-flood-worksite.webp',
                ],
            ],
            'lighting_garden' => [
                0 => [
                    'title' => 'Lối đi sân vườn',
                    'text' => 'Tạo điểm sáng dẫn hướng dọc lối đi, bồn cây và khuôn viên cảnh quan.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-side-path.webp',
                ],
                1 => [
                    'title' => 'Cổng & sân nhà',
                    'text' => 'Bổ sung ánh sáng cho cổng, sân nhà và lối vào theo phong cách cảnh quan.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-home-gate.webp',
                ],
                2 => [
                    'title' => 'Homestay & khu nghỉ dưỡng',
                    'text' => 'Tăng khả năng sử dụng buổi tối cho sân vườn, homestay, resort hoặc quán cà phê.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-garden-homestay.webp',
                ],
            ],
            'lighting_pole' => [
                0 => [
                    'title' => 'Cổng & hàng rào',
                    'text' => 'Bố trí đèn trên trụ cổng, hàng rào hoặc lối vào cần điểm sáng định hướng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-home-gate.webp',
                ],
                1 => [
                    'title' => 'Lối vào công trình',
                    'text' => 'Tạo nhịp ánh sáng cho lối vào, sân trước và tuyến tiếp cận công trình.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-pole-estate.webp',
                ],
                2 => [
                    'title' => 'Khuôn viên cảnh quan',
                    'text' => 'Phù hợp khuôn viên biệt thự, nhà vườn, dịch vụ hoặc công trình cần đồng bộ cảnh quan.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-garden-homestay.webp',
                ],
            ],
            'lighting_wall' => [
                0 => [
                    'title' => 'Tường rào & cổng',
                    'text' => 'Bổ sung ánh sáng gọn trên tường rào, cổng và hiên nhà.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-home-gate.webp',
                ],
                1 => [
                    'title' => 'Lối đi bên hông nhà',
                    'text' => 'Tạo điểm sáng cho lối đi hẹp, ban công hoặc mặt tiền mà không chiếm diện tích.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-side-path.webp',
                ],
                2 => [
                    'title' => 'Nhà vườn & homestay',
                    'text' => 'Kết hợp chiếu sáng và trang trí cho nhà vườn, homestay, quán cà phê hoặc resort.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-garden-homestay.webp',
                ],
            ],
            'lighting_ceiling' => [
                0 => [
                    'title' => 'Hiên & sảnh có mái',
                    'text' => 'Chiếu sáng hiên, sảnh và khu vực có mái che cần bố trí đèn gọn.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ceiling-porch.webp',
                ],
                1 => [
                    'title' => 'Khu vực sinh hoạt',
                    'text' => 'Bổ sung ánh sáng cho chòi nghỉ, hành lang hoặc không gian sinh hoạt có mái.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ceiling-courtyard.webp',
                ],
                2 => [
                    'title' => 'Kho & khu vực kỹ thuật',
                    'text' => 'Phù hợp khu vực có mái cần nguồn sáng độc lập, dễ bố trí theo mặt bằng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ceiling-warehouse.webp',
                ],
            ],
            'lighting_ufo' => [
                0 => [
                    'title' => 'Kho & nhà xưởng cao',
                    'text' => 'Bố trí tại không gian cao, cần vùng sáng rộng cho kho hoặc nhà xưởng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-warehouse.webp',
                ],
                1 => [
                    'title' => 'Sân sản xuất & bãi thao tác',
                    'text' => 'Hỗ trợ chiếu sáng vùng thao tác, sân sản xuất hoặc bãi tập kết ngoài trời.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-worksite.webp',
                ],
                2 => [
                    'title' => 'Trang trại & khu vực rộng',
                    'text' => 'Phù hợp khu vực rộng cần nguồn sáng độc lập và dễ bố trí theo vị trí sử dụng.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-farm.webp',
                ],
            ],
            'accessory_cable' => [
                0 => [
                    'title' => 'Tuyến cáp & dây dẫn',
                    'text' => 'Bố trí tuyến cáp, dây nối và phụ kiện dẫn điện trong hệ thống mặt trời.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-cable-rooftop.webp',
                ],
                1 => [
                    'title' => 'Ống & bảo vệ đường dây',
                    'text' => 'Bảo vệ tuyến dây tại mái, tường hoặc khu vực ngoài trời theo điều kiện lắp đặt.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-cable-conduit.webp',
                ],
                2 => [
                    'title' => 'Tiếp địa & kết nối',
                    'text' => 'Hoàn thiện các điểm kết nối, tiếp địa và bảo vệ đường dây theo thiết kế hệ thống.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-cable-grounding.webp',
                ],
            ],
            'accessory_controller' => [
                0 => [
                    'title' => 'Tủ điều khiển sạc',
                    'text' => 'Bố trí bộ điều khiển sạc và thiết bị giám sát trong tủ kỹ thuật phù hợp.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-controller-cabinet.webp',
                ],
                1 => [
                    'title' => 'Điểm giám sát hệ thống',
                    'text' => 'Theo dõi trạng thái sạc, tải và dữ liệu vận hành tại khu vực kỹ thuật.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-controller-monitoring.webp',
                ],
                2 => [
                    'title' => 'Phòng kỹ thuật điện mặt trời',
                    'text' => 'Tích hợp thiết bị điều khiển, đo lường và kết nối trong phòng kỹ thuật.',
                    'photo' => 'assets/images/site-visuals/pdp-usecase-v2/accessory-controller-room.webp',
                ],
            ],
        ],
    ],
    'spec_aliases' => [
        'cell_type' => [
            0 => 'loại cell',
            1 => 'công nghệ cell',
            2 => 'loại tế bào',
            3 => 'công nghệ',
        ],
        'efficiency' => [
            0 => 'hiệu suất module',
            1 => 'hiệu suất tối đa',
        ],
        'open_circuit_voltage' => [
            0 => 'voc',
            1 => 'điện áp hở mạch',
        ],
        'short_circuit_current' => [
            0 => 'isc',
            1 => 'dòng ngắn mạch',
        ],
        'max_power_voltage' => [
            0 => 'vmp',
            1 => 'điện áp tại pmax',
            2 => 'điện áp tại công suất cực đại',
        ],
        'max_power_current' => [
            0 => 'imp',
            1 => 'dòng tại pmax',
            2 => 'dòng điện công suất cực đại',
        ],
        'panel_dimensions' => [
            0 => 'kích thước tấm pin',
            1 => 'kích thước hiện có trong file',
            2 => 'kích thước',
        ],
        'weight' => [
            0 => 'khối lượng',
            1 => 'trọng lượng',
        ],
        'connector_type' => [
            0 => 'đầu nối',
            1 => 'kiểu kết nối',
            2 => 'cáp đầu nối',
        ],
        'inverter_type' => [
            0 => 'loại biến tần',
            1 => 'loại thiết bị',
        ],
        'ac_power' => [
            0 => 'công suất ac danh định',
            1 => 'công suất ac',
        ],
        'phase_count' => [
            0 => 'số pha',
        ],
        'mppt_count' => [
            0 => 'số mppt / chuỗi mỗi mppt',
            1 => 'số mppt / số chuỗi pv',
            2 => 'số mppt',
        ],
        'max_dc_voltage' => [
            0 => 'điện áp dc tối đa',
        ],
        'mppt_voltage_range' => [
            0 => 'dải điện áp mppt',
            1 => 'dải mppt đầy tải',
            2 => 'dải điện áp làm việc mppt',
        ],
        'max_mppt_current' => [
            0 => 'dòng vào tối đa mỗi mppt',
            1 => 'dòng vào tối đa mppt',
            2 => 'dòng mppt tối đa',
        ],
        'battery_voltage_range' => [
            0 => 'dải điện áp pin',
        ],
        'backup_power' => [
            0 => 'công suất eps định mức',
            1 => 'công suất eps đỉnh',
            2 => 'eps',
        ],
        'monitoring' => [
            0 => 'giám sát / kết nối',
            1 => 'giám sát cloud',
            2 => 'giao tiếp',
        ],
        'battery_chemistry' => [
            0 => 'loại pin',
            1 => 'công nghệ pin',
        ],
        'nominal_capacity' => [
            0 => 'dung lượng danh định',
        ],
        'usable_capacity' => [
            0 => 'dung lượng sử dụng',
        ],
        'battery_voltage' => [
            0 => 'điện áp pin',
            1 => 'điện áp danh định',
        ],
        'max_charge_current' => [
            0 => 'dòng sạc tối đa',
            1 => 'dòng sạc định mức',
        ],
        'max_discharge_current' => [
            0 => 'dòng xả tối đa',
            1 => 'dòng xả định mức',
        ],
        'depth_of_discharge' => [
            0 => 'độ sâu xả',
            1 => 'dod',
        ],
        'cycle_life' => [
            0 => 'tuổi thọ chu kỳ',
            1 => 'chu kỳ',
        ],
        'communication' => [
            0 => 'giao tiếp',
            1 => 'rs485',
            2 => 'can',
            3 => 'ethernet',
        ],
        'fan_type' => [
            0 => 'loại quạt',
        ],
        'blade_size' => [
            0 => 'đường kính cánh',
            1 => 'cánh quạt',
        ],
        'airflow' => [
            0 => 'lưu lượng gió',
            1 => 'tốc độ gió tối đa',
        ],
        'speed_levels' => [
            0 => 'số cấp gió',
            1 => 'cấp độ gió',
        ],
        'runtime' => [
            0 => 'thời gian hoạt động',
            1 => 'thời gian sử dụng',
        ],
        'motor_power' => [
            0 => 'công suất động cơ',
        ],
        'input_voltage' => [
            0 => 'điện áp đầu vào',
            1 => 'điện áp pv đầu vào',
        ],
        'output_voltage' => [
            0 => 'điện áp đầu ra',
        ],
        'max_current' => [
            0 => 'dòng điện tối đa',
        ],
        'solar_input_range' => [
            0 => 'dải điện áp dc từ pin',
            1 => 'dải điện áp pv',
        ],
        'control_mode' => [
            0 => 'chế độ điều khiển',
            1 => 'chế độ làm việc',
        ],
        'protections' => [
            0 => 'chức năng bảo vệ',
            1 => 'bảo vệ',
        ],
    ],
    'list_slug_profiles' => [
        'den-nlmt' => 'lighting',
        'den-san-vuon' => 'lighting',
        'tru-den' => 'lighting',
        'quat-nang-luong-mat-troi' => 'fan',
        'tam-pin-nang-luong-mat-troi' => 'solar_panel',
        'bien-tan-inverter' => 'inverter',
        'pin-luu-tru' => 'storage',
        'bien-tan-bom' => 'pump_controller',
        'thiet-bi-dien-nang-luong-mat-troi' => 'accessory',
    ],
    'package_components' => [
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
        'đèn' => 'Đèn',
    ],
];
