<?php

// Decision metric units, icons and images for the product page.
$config = [];
$config['metric_units'] = ['cong_suat' => 'W', 'ac_power' => 'W', 'motor_power' => 'HP', 'panel_power' => 'W', 'battery_capacity' => 'Wh', 'usable_capacity' => 'kWh', 'battery_voltage' => 'V', 'lighting_time' => 'giờ', 'runtime' => 'giờ', 'cycle_life' => 'chu kỳ', 'efficiency' => '%', 'phase_count' => 'pha', 'mppt_count' => 'MPPT', 'input_voltage' => 'V', 'max_flow' => 'm³/h', 'max_head' => 'm', 'rated_voltage' => 'V', 'rated_current' => 'A', 'installation_height' => 'm', 'warranty' => 'tháng'];
$config['metric_icons'] = ['battery_capacity' => 'battery', 'usable_capacity' => 'battery', 'battery_voltage' => 'battery', 'installation_height' => 'height', 'max_head' => 'height', 'warranty' => 'shield', 'waterproof_rating' => 'shield', 'cong_suat' => 'bolt', 'ac_power' => 'bolt', 'motor_power' => 'bolt', 'rated_voltage' => 'bolt', 'rated_current' => 'bolt'];
$config['metric_images'] = [
    'inverter' => ['ac_power' => 'congsuatAC.png', 'phase_count' => 'sopha.png', 'mppt_count' => 'somppt.png', 'warranty' => 'baohanh.png'],
    'pump_controller' => ['motor_power' => 'congsuatden.png', 'phase_count' => 'sopha.png', 'input_voltage' => 'congsuatAC.png', 'warranty' => 'baohanh.png'],
    'pump_hardware' => ['motor_power' => 'congsuatden.png', 'max_flow' => 'luuluonglamviec.png', 'max_head' => 'cotaplamviec.png', 'warranty' => 'baohanh.png'],
    'solar_panel' => ['cong_suat' => 'congsuatstc.png', 'cell_type' => 'congnghecell.png', 'efficiency' => 'quanthong.png', 'warranty' => 'baohanh.png'],
    'storage' => ['usable_capacity' => 'pin.png', 'battery_voltage' => 'congsuatAC.png', 'cycle_life' => 'tuoichuky.png', 'warranty' => 'baohanh.png'],
    'fan' => ['cong_suat' => 'congsuatden.png', 'battery_capacity' => 'pin.png', 'runtime' => 'thoigianhoatdong.png', 'warranty' => 'baohanh.png'],
    'accessory' => ['rated_voltage' => 'congsuatAC.png', 'rated_current' => 'congsuatden.png', 'compatibility' => 'tuongthich.png', 'warranty' => 'baohanh.png'],
    'general' => ['cong_suat' => 'congsuatden.png', 'material' => 'vatlieu.png', 'waterproof_rating' => 'chuanchongnuoc.png', 'warranty' => 'baohanh.png'],
];
$config['metric_extra_dir_keys'] = ['cycle_life', 'runtime', 'compatibility', 'material', 'waterproof_rating'];
$config['lighting_metric_images'] = ['quang_thong' => 'quanthong.png', 'battery_capacity' => 'pin.png', 'installation_height' => 'chieucaoLapdat.png', 'warranty' => 'baohanh.png'];

return $config;
