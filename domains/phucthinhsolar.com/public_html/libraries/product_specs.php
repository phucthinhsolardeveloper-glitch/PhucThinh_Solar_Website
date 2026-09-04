<?php
if(!function_exists('productSpecFieldHasValue'))
{
    /**
     * `cong_suat` là cột số, mặc định 0 cho MỌI sản phẩm kể cả loại không
     * có công suất (trụ đèn, khung nhôm...) -> 0 nghĩa là CHƯA NHẬP, không
     * phải "công suất bằng 0". Coi 0/âm là rỗng để khỏi hiện "Công suất 0W".
     */
    function productSpecFieldHasValue($row, $field)
    {
        if(empty($row[$field])) return false;
        if($field === 'cong_suat') {
            $numeric = str_replace(',', '.', trim((string) $row[$field]));
            return is_numeric($numeric) && (float) $numeric > 0;
        }
        return true;
    }
}

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
            if(!productSpecFieldHasValue($row, $field)) continue;
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

if(!function_exists('productAutoHighlights'))
{
    /**
     * Rút các trường thông số có cấu trúc thành "chip điểm nổi bật".
     * Không cần biên tập gõ tay: điền bảng thông số là có ngay.
     * $type để dành cho cấu hình theo loại sản phẩm về sau.
     */
    function productAutoHighlights($row, $lang = 'vi', $type = '')
    {
        $order = array(
            'cong_suat', 'light_source', 'battery_capacity', 'panel_power',
            'waterproof_rating', 'warranty', 'lighting_time', 'led_chip',
            'color_temperature', 'charging_time', 'material', 'installation_height'
        );
        $prefix = array(
            'cong_suat' => 'Công suất', 'light_source' => 'Quang thông', 'battery_capacity' => 'Pin',
            'panel_power' => 'Tấm pin', 'waterproof_rating' => 'Chống nước', 'warranty' => 'Bảo hành',
            'lighting_time' => 'Chiếu sáng', 'led_chip' => 'Chip LED', 'color_temperature' => 'Nhiệt độ màu',
            'charging_time' => 'Sạc', 'material' => 'Chất liệu', 'installation_height' => 'Cao lắp đặt'
        );
        $max = 7;
        $chips = array();
        foreach($order as $field) {
            if(count($chips) >= $max) break;
            if(!productSpecFieldHasValue($row, $field)) continue;
            $value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row[$field])));
            if($value === '') continue;
            if($field === 'cong_suat' && is_numeric(str_replace(',', '.', $value)))
                $value = sprintf('%g', (float) str_replace(',', '.', $value)).'W';
            $label = isset($prefix[$field]) ? $prefix[$field] : '';
            $chip = ($label === '' || mb_stripos($value, $label) !== false) ? $value : $label.' '.$value;
            if(mb_strlen($chip) > 46) $chip = rtrim(mb_substr($chip, 0, 44)).'…';
            $chips[] = $chip;
        }
        return $chips;
    }
}

if(!function_exists('productAutoDescription'))
{
    /**
     * Đoạn văn mô tả tự sinh từ thông số có cấu trúc — dùng làm mặc định khi
     * sản phẩm chưa có bất kỳ khối nội dung / ứng dụng / lắp đặt nào.
     * Trả về '' nếu quá ít thông số để tạo câu có nghĩa.
     */
    function productAutoDescription($row, $lang = 'vi', $type = '')
    {
        $name = trim(isset($row['name'.$lang]) ? strip_tags((string) $row['name'.$lang]) : '');
        if($name === '') return '';

        $clauseFields = array(
            'cong_suat'         => 'công suất %s',
            'light_source'      => 'nguồn sáng %s',
            'led_chip'          => 'chip LED %s',
            'battery_capacity'  => 'pin %s',
            'panel_power'       => 'tấm pin %s',
            'color_temperature' => 'nhiệt độ màu %s',
            'lighting_time'     => 'thời gian chiếu sáng %s',
            'waterproof_rating' => 'chuẩn chống nước %s',
            'material'          => 'chất liệu %s',
        );
        $clauses = array();
        foreach($clauseFields as $field => $tpl) {
            if(count($clauses) >= 5) break;
            if(!productSpecFieldHasValue($row, $field)) continue;
            $value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row[$field])));
            if($value === '') continue;
            if($field === 'cong_suat' && is_numeric(str_replace(',', '.', $value)))
                $value = sprintf('%g', (float) str_replace(',', '.', $value)).'W';
            $clauses[] = sprintf($tpl, $value);
        }
        if(count($clauses) < 2) return '';

        $text = $name.' có '.implode(', ', $clauses).'.';
        if(!empty($row['warranty'])) {
            $w = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row['warranty'])));
            if($w !== '') $text .= ' Bảo hành '.$w.'.';
        }
        if(!empty($row['installation_height'])) {
            $h = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row['installation_height'])));
            if($h !== '') $text .= ' Phù hợp lắp đặt ở độ cao '.$h.'.';
        }
        return $text;
    }
}
