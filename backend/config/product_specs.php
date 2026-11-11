<?php

/*
 * The typed "Thông số" tab of the product form, per product type (the profiles of config/pdp.php).
 *
 * - The fields and their labels come from config('pdp.profiles.<type>.fields'), so Admin and the product page
 *   name a spec the same way. Lighting adds the extra rows the lighting spec table looks up by label.
 * - A key that is a products column (ProductSpecTemplates::COLUMNS) is stored in that column; any other key is a
 *   row of additional_specs with that key, which the product page reads before guessing from labels.
 * - unit: shown after the box; a bare number typed there is saved as "<number> <unit>".
 * - suggest: values offered while typing (free text still allowed).
 * - required: missing ones are listed as warnings when the product is switched on (never block saving).
 */
return [
    'extra_fields' => [
        'lighting' => [
            'quang_thong' => ['label' => 'Quang thông', 'after' => 'light_source'],
            'housing_color' => ['label' => 'Màu sắc thân đèn', 'after' => 'material'],
            'operating_mode' => ['label' => 'Chế độ hoạt động', 'after' => 'lighting_time'],
            'impact_rating' => ['label' => 'Chuẩn chống va đập', 'after' => 'waterproof_rating'],
            'installation_spacing' => ['label' => 'Khoảng cách lắp đặt', 'after' => 'installation_height'],
            'carton_quantity' => ['label' => 'Số lượng / thùng', 'after' => 'warranty'],
            'carton_dimensions' => ['label' => 'Kích thước thùng', 'after' => 'carton_quantity'],
        ],
    ],

    'fields' => [
        'cong_suat' => ['unit' => 'W', 'placeholder' => '100'],
        'quang_thong' => ['unit' => 'lm', 'placeholder' => '1200'],
        'battery_capacity' => ['placeholder' => '20000mAh hoặc 512Wh'],
        'panel_power' => ['placeholder' => '30W'],
        'charging_time' => ['placeholder' => '6–8 giờ'],
        'lighting_time' => ['placeholder' => '12–14 giờ'],
        'runtime' => ['placeholder' => '8–10 giờ'],
        'waterproof_rating' => ['suggest' => ['IP65', 'IP66', 'IP67', 'IP68']],
        'color_temperature' => ['suggest' => ['3000K', '4000K', '6500K']],
        'warranty' => ['suggest' => ['12 tháng', '24 tháng', '36 tháng', '5 năm', '10 năm', '12 năm']],
        'installation_height' => ['placeholder' => '4–6m'],

        'cell_type' => ['suggest' => ['Mono PERC', 'N-Type TOPCon', 'Half-cell', 'Bifacial', 'HJT']],
        'efficiency' => ['unit' => '%', 'placeholder' => '21,5'],
        'open_circuit_voltage' => ['unit' => 'V', 'placeholder' => '49,5'],
        'short_circuit_current' => ['unit' => 'A', 'placeholder' => '13,9'],
        'max_power_voltage' => ['unit' => 'V', 'placeholder' => '41,6'],
        'max_power_current' => ['unit' => 'A', 'placeholder' => '13,2'],

        'inverter_type' => ['suggest' => ['Hòa lưới', 'Hybrid (có lưu trữ)', 'Độc lập (off-grid)']],
        'ac_power' => ['unit' => 'kW', 'placeholder' => '5'],
        'phase_count' => ['suggest' => ['1 pha', '3 pha']],
        'mppt_count' => ['placeholder' => '2 MPPT'],
        'max_dc_voltage' => ['unit' => 'V', 'placeholder' => '600'],
        'mppt_voltage_range' => ['placeholder' => '120–550V'],
        'max_mppt_current' => ['unit' => 'A', 'placeholder' => '16'],
        'battery_voltage_range' => ['placeholder' => '40–60V'],
        'backup_power' => ['unit' => 'kW', 'placeholder' => '5'],
        'monitoring' => ['suggest' => ['WiFi', 'WiFi + ứng dụng điện thoại', '4G', 'RS485']],

        'battery_chemistry' => ['suggest' => ['LiFePO4', 'Lithium-ion', 'Ắc quy chì (AGM/Gel)']],
        'nominal_capacity' => ['placeholder' => '5,12kWh hoặc 100Ah'],
        'usable_capacity' => ['unit' => 'kWh', 'placeholder' => '4,6'],
        'battery_voltage' => ['unit' => 'V', 'placeholder' => '51,2'],
        'max_charge_current' => ['unit' => 'A', 'placeholder' => '100'],
        'max_discharge_current' => ['unit' => 'A', 'placeholder' => '100'],
        'depth_of_discharge' => ['unit' => '%', 'placeholder' => '90'],
        'cycle_life' => ['placeholder' => '6000 chu kỳ'],
        'communication' => ['suggest' => ['CAN', 'RS485', 'CAN + RS485']],

        'motor_power' => ['placeholder' => '2,2kW (3HP)'],
        'input_voltage' => ['placeholder' => '220V AC / 300–800V DC'],
        'output_voltage' => ['placeholder' => '3 pha 220V'],
        'max_current' => ['unit' => 'A', 'placeholder' => '10'],
        'max_flow' => ['unit' => 'm³/h', 'placeholder' => '6'],
        'max_head' => ['unit' => 'm', 'placeholder' => '45'],

        'controller_type' => ['suggest' => ['PWM', 'MPPT']],
        'system_voltage' => ['suggest' => ['12V', '12/24V tự nhận', '12/24/48V tự nhận']],
        'rated_charge_current' => ['unit' => 'A', 'placeholder' => '20'],
        'rated_discharge_current' => ['unit' => 'A', 'placeholder' => '20'],
        'max_pv_voltage' => ['unit' => 'V', 'placeholder' => '100'],
        'max_pv_power' => ['placeholder' => '260W/12V, 520W/24V'],

        'rated_voltage' => ['unit' => 'V', 'placeholder' => '1000'],
        'rated_current' => ['unit' => 'A', 'placeholder' => '32'],
        'cable_section' => ['placeholder' => '4mm²'],
    ],

    'required' => [
        'lighting' => ['cong_suat', 'battery_capacity', 'panel_power', 'lighting_time', 'warranty'],
        'solar_panel' => ['cong_suat', 'cell_type', 'open_circuit_voltage', 'short_circuit_current', 'panel_dimensions', 'warranty'],
        'fan' => ['cong_suat', 'battery_capacity', 'panel_power', 'warranty'],
        'inverter' => ['inverter_type', 'ac_power', 'phase_count', 'mppt_count', 'warranty'],
        'storage' => ['battery_chemistry', 'nominal_capacity', 'battery_voltage', 'warranty'],
        'pump_controller' => ['motor_power', 'input_voltage', 'output_voltage', 'warranty'],
        'pump_hardware' => ['motor_power', 'max_flow', 'max_head', 'warranty'],
        'charge_controller' => ['controller_type', 'system_voltage', 'rated_charge_current', 'max_pv_voltage', 'warranty'],
        'accessory' => ['accessory_type'],
        'general' => [],
    ],

    /*
     * Extra labels staff have typed for a spec, beyond config('pdp.spec_aliases'), used once to give the
     * existing rows their key (php artisan pts:spec-keys). Lower case, exact match after trimming.
     */
    'aliases' => [
        'quang_thong' => ['lumen', 'quang thông', 'quang thông (lm)'],
        'housing_color' => ['màu sắc', 'màu', 'màu vỏ', 'màu sắc thân đèn'],
        'operating_mode' => ['chế độ điều khiển', 'chế độ hoạt động', 'chế độ làm việc'],
        'impact_rating' => ['chuẩn chống va đập', 'chống va đập'],
        'installation_spacing' => ['khoảng cách lắp đặt'],
        'cell_type' => ['cells pin', 'loại cell', 'công nghệ cell'],
        'efficiency' => ['hiệu suất tấm pin', 'hiệu suất mô-đun (%)', 'hiệu suất mô-đun', 'hiệu suất'],
        'open_circuit_voltage' => ['điện áp hở mạch', 'điện áp hở mạch (voc)', 'voc'],
        'short_circuit_current' => ['dòng điện ngắn mạch', 'dòng ngắn mạch', 'isc'],
        'max_power_voltage' => ['điện áp tối ưu (vmp)', 'điện áp mpp tối đa', 'vmp'],
        'max_power_current' => ['dòng điện tối ưu (imp)', 'imp'],
        'weight' => ['khối lượng', 'trọng lượng'],
        'max_pv_voltage' => ['điện áp đầu vào pv max', 'điện áp pv tối đa', 'điện áp pv tối đã', 'điện áp pv max'],
        'max_pv_power' => ['công suất đầu vào  pv max', 'công suất đầu vào pv max', 'công suất pv tối đa'],
        'max_charge_current' => ['dòng sạc tối đa'],
        'max_discharge_current' => ['dòng xả tối đa'],
        'rated_charge_current' => ['dòng sạc định mức'],
        'rated_discharge_current' => ['dòng xả định mức'],
        'system_voltage' => ['điện áp hệ thống'],
        'working_voltage_range' => ['dải điện áp làm việc'],
        'usb_output' => ['đầu ra usb', 'cổng usb'],
        'dimensions' => ['kích thước'],
        'motor_power' => ['công suất định mức', 'công suất'],
        'input_voltage' => ['điện áp đầu vào ac (v)', 'điện áp đầu vào'],
        'output_voltage' => ['điện áp đầu ra ac (v)', 'điện áp ra'],
        'cycle_life' => ['cycle life', 'tuổi thọ chu kỳ'],
        'depth_of_discharge' => ['độ xả sâu (dod)', 'dod'],
        'nominal_capacity' => ['dung lượng'],
        'mppt_voltage_range' => ['dải điện áp mppt'],
        'max_dc_voltage' => ['max dc đầu vào'],
        'runtime' => ['thời gian hoạt động', 'thời gian sử dụng'],
        'ac_power' => ['max hòa lưới ac'],
        'backup_power' => ['max backup ac'],
        'led_chip' => ['led chip', 'chip led'],
        'installation_height' => ['chiều cao lắp đặt', 'độ cao lắp đặt'],
        'waterproof_rating' => ['ip rate', 'chống nước', 'tiêu chuẩn chống nước, bụi'],
        'color_temperature' => ['cct', 'nhiệt độ màu'],
        'warranty' => ['thời gian bảo hành', 'bảo hành sản phẩm'],
        'panel_dimensions' => ['kích thước tấm pin', 'kích thước tấm pin năng lượng'],
        'speed_levels' => ['cấp độ gió'],
        'blade_size' => ['cánh quạt', 'kích thước lồng quạt'],
    ],
];
