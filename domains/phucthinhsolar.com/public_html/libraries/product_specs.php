<?php
if(!function_exists('productStructuredSpecs'))
{
    function productStructuredSpecs($row, $lang = 'vi')
    {
        $labels = array(
            'cong_suat' => 'Công suất', 'light_source' => 'Nguồn sáng', 'lamp_dimensions' => 'Kích thước đèn',
            'color_temperature' => 'Nhiệt độ màu (CCT)', 'light_color' => 'Màu ánh sáng',
            'battery_capacity' => 'Dung lượng pin', 'panel_power' => 'Công suất tấm pin', 'material' => 'Chất liệu',
            'charging_time' => 'Thời gian sạc', 'lighting_time' => 'Thời gian chiếu sáng',
            'waterproof_rating' => 'Chỉ số chống nước', 'led_chip' => 'Loại chip LED',
            'lighting_area' => 'Diện tích chiếu sáng', 'installation_height' => 'Độ cao lắp đặt',
            'panel_dimensions' => 'Kích thước tấm pin', 'package_contents' => 'Bộ sản phẩm gồm',
            'carton_quantity' => 'Số lượng / thùng', 'carton_dimensions' => 'Kích thước thùng', 'warranty' => 'Bảo hành'
        );
        $specs = array();
        foreach($labels as $field => $label) {
            if(empty($row[$field])) continue;
            $value = $row[$field];
            if($field === 'cong_suat' && is_numeric(str_replace(',', '.', trim((string) $value))))
                $value = sprintf('%g', (float) str_replace(',', '.', trim((string) $value))).'W';
            $specs[] = array('label' => $label, 'value' => $value);
        }
        $additionalValid = true;
        if(!empty($row['additional_specs'])) {
            $additional = json_decode(htmlspecialchars_decode($row['additional_specs'], ENT_QUOTES), true);
            $additionalValid = is_array($additional);
            if($additionalValid) foreach($additional as $spec) {
                $label = !empty($spec['label']) ? trim((string) $spec['label']) : '';
                $value = isset($spec['value']) ? trim((string) $spec['value']) : '';
                if($label !== '' && $value !== '') $specs[] = array('label' => $label, 'value' => $value);
            }
        }
        $legacyField = 'thongsokythuat'.$lang;
        $useLegacy = empty($specs) || (!$additionalValid && !empty($row[$legacyField]));
        return array('specs' => $useLegacy ? array() : $specs, 'use_legacy' => $useLegacy, 'legacy_field' => $legacyField);
    }
}
