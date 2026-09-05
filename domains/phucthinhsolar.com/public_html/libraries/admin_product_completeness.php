<?php
if(!function_exists('adminProductDataStatusDefinitions'))
{
    function adminProductDataStatusDefinitions()
    {
        $specColumns = array(
            'cong_suat', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color',
            'battery_capacity', 'panel_power', 'material', 'charging_time', 'lighting_time',
            'waterproof_rating', 'led_chip', 'lighting_area', 'installation_height',
            'panel_dimensions', 'package_contents', 'carton_quantity', 'carton_dimensions', 'warranty',
            'additional_specs', 'thongsokythuatvi'
        );
        $emptySpecs = array();
        foreach($specColumns as $column) $emptySpecs[] = "coalesce(trim(cast(p.$column as char)), '') = ''";

        return array(
            'missing_photo' => array('label' => 'Thiếu ảnh đại diện', 'sql' => "coalesce(trim(p.photo), '') = ''"),
            'missing_desc' => array('label' => 'Thiếu mô tả ngắn', 'sql' => "coalesce(trim(p.descvi), '') = ''"),
            'missing_content' => array('label' => 'Thiếu nội dung chi tiết', 'sql' => "coalesce(trim(p.contentvi), '') = ''"),
            'missing_specs' => array('label' => 'Thiếu thông số kỹ thuật', 'sql' => '('.implode(' and ', $emptySpecs).')'),
            'missing_wattage' => array('label' => 'Thiếu công suất', 'sql' => '(p.cong_suat is null or p.cong_suat <= 0)'),
            'campaign_active' => array(
                'label' => 'Đang có khung chiến dịch',
                'sql' => "((coalesce(trim(p.photo_khuyenmai), '') <> '' and find_in_set('khuyenmai', p.status)) or exists (select 1 from #_product_campaign c inner join #_product_campaign_scope pcs on pcs.id_campaign = c.id where find_in_set('hienthi',c.status) and coalesce(trim(c.photo), '') <> '' and (c.start_at = 0 or c.start_at <= unix_timestamp()) and (c.end_at = 0 or c.end_at >= unix_timestamp()) and (pcs.scope_type = 'all' or (pcs.scope_type = 'product' and pcs.scope_id = p.id) or (pcs.scope_type = 'cat' and pcs.scope_id = p.id_cat) or (pcs.scope_type = 'brand' and pcs.scope_id = p.id_brand) or (pcs.scope_type = 'list' and pcs.scope_id = p.id_list))))"
            )
        );
    }

    function adminProductDataStatus($status)
    {
        $definitions = adminProductDataStatusDefinitions();
        return isset($definitions[$status]) ? $definitions[$status] : null;
    }

    function adminProductMissingLabels($product)
    {
        $labels = array();
        if(trim(isset($product['photo']) ? (string)$product['photo'] : '') === '') $labels[] = 'Ảnh';
        if(trim(isset($product['descvi']) ? (string)$product['descvi'] : '') === '') $labels[] = 'Mô tả';
        if(trim(isset($product['contentvi']) ? (string)$product['contentvi'] : '') === '') $labels[] = 'Nội dung';

        $hasSpecs = trim(isset($product['thongsokythuatvi']) ? (string)$product['thongsokythuatvi'] : '') !== ''
            || trim(isset($product['additional_specs']) ? (string)$product['additional_specs'] : '') !== '';
        foreach(array('cong_suat','light_source','lamp_dimensions','color_temperature','light_color','battery_capacity','panel_power','material','charging_time','lighting_time','waterproof_rating','led_chip','lighting_area','installation_height','panel_dimensions','package_contents','carton_quantity','carton_dimensions','warranty') as $column)
            if(isset($product[$column]) && trim((string)$product[$column]) !== '') { $hasSpecs = true; break; }
        if(!$hasSpecs) $labels[] = 'Thông số';
        if(!empty($product['_category_issue'])) $labels[] = ((int)$product['_category_issue'] === 2) ? 'Loại (liên kết lỗi)' : 'Loại sản phẩm';
        return $labels;
    }

    function adminProductHasStructuredContent($product)
    {
        foreach(array('highlightsvi', 'applicationsvi', 'installationvi') as $column)
            if(trim(isset($product[$column]) ? (string)$product[$column] : '') !== '') return true;
        return false;
    }
}
