<?php
if(!function_exists('adminProductDataStatusDefinitions'))
{
    /* missing_content/missing_wattage/missing_specs KHÔNG có key 'sql': độ
       hoàn thiện thật của chúng phụ thuộc kế thừa content_section
       (product->brand->cat->list, xem product_content_blocks.php) và profile
       PDP (product_pdp_profiles.php) - không thể diễn tả bằng 1 điều kiện SQL
       tĩnh đúng cho mọi sản phẩm. Caller phải dùng adminProductFilterIdsByStatus()
       để lọc 3 status này bằng PHP (xem hàm bên dưới), không tự ráp SQL. */
    function adminProductDataStatusDefinitions()
    {
        return array(
            'missing_photo' => array('label' => 'Thiếu ảnh đại diện', 'sql' => "coalesce(trim(p.photo), '') = ''"),
            'missing_photo_feed' => array('label' => 'Thiếu ảnh Shopping sạch', 'sql' => "coalesce(trim(p.photo_feed), '') = ''"),
            'missing_desc' => array('label' => 'Thiếu mô tả ngắn', 'sql' => "coalesce(trim(p.descvi), '') = ''"),
            'missing_content' => array('label' => 'Thiếu nội dung chi tiết'),
            'missing_specs' => array('label' => 'Thiếu thông số kỹ thuật'),
            'missing_wattage' => array('label' => 'Thiếu thông số quyết định'),
            /* Chuẩn bị cho lúc bật GHN/GHTK/Viettel Post/Nhất Tín thật (đang tắt
               vì thiếu credential - xem administrator > Thanh toán & vận chuyển):
               các hãng này cần kích thước đóng gói để gọi API tính phí thật, phí
               hiện tại KHÔNG dùng field này (chỉ tính theo bảng giá cố định theo
               phường/xã). Đánh dấu trước để biết sản phẩm nào cần bổ sung, tránh
               bật provider thật rồi mới phát hiện thiếu dữ liệu hàng loạt. */
            'missing_shipping_dims' => array('label' => 'Thiếu kích thước đóng gói', 'sql' => "(coalesce(trim(p.carton_dimensions), '') = '' or coalesce(p.weight, 0) = 0)"),
            'campaign_active' => array(
                'label' => 'Đang có khung chiến dịch',
                'sql' => "((coalesce(trim(p.photo_khuyenmai), '') <> '' and find_in_set('khuyenmai', p.status)) or exists (select 1 from #_product_campaign c inner join #_product_campaign_scope pcs on pcs.id_campaign = c.id where find_in_set('hienthi',c.status) and coalesce(trim(c.photo), '') <> '' and (c.start_at = 0 or c.start_at <= unix_timestamp()) and (c.end_at = 0 or c.end_at >= unix_timestamp()) and (pcs.scope_type = 'all' or (pcs.scope_type = 'product' and pcs.scope_id = p.id) or (pcs.scope_type = 'cat' and pcs.scope_id = p.id_cat) or (pcs.scope_type = 'brand' and pcs.scope_id = p.id_brand) or (pcs.scope_type = 'list' and pcs.scope_id = p.id_list))))"
            ),
            'has_discount' => array(
                'label' => 'Đang giảm giá (giá KM thấp hơn giá gốc)',
                'sql' => "(p.sale_price > 0 and p.sale_price < p.regular_price)"
            )
        );
    }

    function adminProductDataStatus($status)
    {
        $definitions = adminProductDataStatusDefinitions();
        return isset($definitions[$status]) ? $definitions[$status] : null;
    }

    /* $d truyền vào để dùng đúng logic kế thừa content_section thật
       (productResolveSections) thay vì chỉ nhìn contentvi. Không có $d (gọi
       cũ, chưa kịp cập nhật call site) thì lùi về check contentvi đơn thuần -
       vẫn đúng hướng, chỉ kém chính xác hơn. */
    function adminProductIsMissingContent($d, $product)
    {
        if($d === null || !function_exists('productResolveSections'))
            return trim(isset($product['contentvi']) ? (string)$product['contentvi'] : '') === '';
        $resolved = productResolveSections($d, $product, 'vi');
        /* Mode 'hide' là chủ động ẩn nội dung, không phải thiếu sót. */
        if($resolved['source'] === 'hide') return false;
        return empty($resolved['blocks']);
    }

    /* Thông số "quyết định" theo đúng profile PDP của sản phẩm (biến tần dùng
       ac_power, pin lưu trữ dùng usable_capacity...), không phải luôn luôn
       cong_suat. $list là row #_product_list (cần slugvi/pdp_profile) để
       resolve đúng profile khi sản phẩm không tự set pdp_profile riêng. */
    function adminProductIsMissingWattage($product, $list = array())
    {
        if(!function_exists('productPdpResolveProfile') || !function_exists('productPdpSpecValue'))
            return ((float)(isset($product['cong_suat']) ? $product['cong_suat'] : 0)) <= 0;
        $profile = productPdpProfile(productPdpResolveProfile($product, $list));
        $metrics = isset($profile['metrics']) ? $profile['metrics'] : array('cong_suat');
        $primary = isset($metrics[0]) ? $metrics[0] : 'cong_suat';
        return productPdpSpecValue($product, $primary) === '';
    }

    /* Thiếu TOÀN BỘ thông số quyết định của profile (không chỉ cái đầu) -
       dùng cho filter "missing_specs", nghiêm hơn missing_wattage. */
    function adminProductIsMissingSpecs($product, $list = array())
    {
        if(!function_exists('productPdpResolveProfile') || !function_exists('productPdpSpecValue'))
        {
            $hasSpecs = trim(isset($product['thongsokythuatvi']) ? (string)$product['thongsokythuatvi'] : '') !== ''
                || trim(isset($product['additional_specs']) ? (string)$product['additional_specs'] : '') !== '';
            foreach(array('cong_suat','light_source','lamp_dimensions','color_temperature','light_color','battery_capacity','panel_power','material','charging_time','lighting_time','waterproof_rating','led_chip','lighting_area','installation_height','panel_dimensions','package_contents','carton_quantity','carton_dimensions','warranty') as $column)
                if(isset($product[$column]) && trim((string)$product[$column]) !== '') { $hasSpecs = true; break; }
            return !$hasSpecs;
        }
        $profile = productPdpProfile(productPdpResolveProfile($product, $list));
        $metrics = isset($profile['metrics']) ? $profile['metrics'] : array();
        foreach($metrics as $metric) if(productPdpSpecValue($product, $metric) !== '') return false;
        return true;
    }

    function adminProductMissingLabels($product, $d = null, $list = array())
    {
        $labels = array();
        if(trim(isset($product['photo']) ? (string)$product['photo'] : '') === '') $labels[] = 'Ảnh';
        if(trim(isset($product['descvi']) ? (string)$product['descvi'] : '') === '') $labels[] = 'Mô tả';
        if(adminProductIsMissingContent($d, $product)) $labels[] = 'Nội dung';
        if(adminProductIsMissingSpecs($product, $list)) $labels[] = 'Thông số';
        if(!empty($product['_category_issue'])) $labels[] = ((int)$product['_category_issue'] === 2) ? 'Loại (liên kết lỗi)' : 'Loại sản phẩm';
        return $labels;
    }

    function adminProductHasStructuredContent($product)
    {
        foreach(array('highlightsvi', 'applicationsvi', 'installationvi') as $column)
            if(trim(isset($product[$column]) ? (string)$product[$column] : '') !== '') return true;
        return false;
    }

    /* Lọc id sản phẩm khớp 1 trong 3 status không biểu diễn được bằng SQL
       tĩnh (missing_content/missing_wattage/missing_specs). $whereSql phải
       là mệnh đề WHERE ĐẦY ĐỦ (bắt đầu bằng "where ...", đã gồm p.type = ?
       và mọi filter khác NGOẠI TRỪ data_status) áp cho bảng #_product p;
       $params khớp theo thứ tự placeholder trong $whereSql. Trả về mảng id
       int - rỗng nghĩa là không sản phẩm nào khớp filter. */
    function adminProductFilterIdsByStatus($d, $status, $whereSql, $params)
    {
        if(!in_array($status, array('missing_content','missing_wattage','missing_specs'), true)) return array();
        $rows = $d->rawQuery("select p.id, p.contentvi, p.id_brand, p.id_cat, p.id_list, p.cong_suat, p.additional_specs, p.pdp_profile from #_product p $whereSql", $params);
        if(empty($rows)) return array();

        $listCache = array();
        if($status !== 'missing_content' && function_exists('productPdpResolveProfile'))
        {
            $listIds = array_values(array_unique(array_filter(array_map(function($r){ return (int)$r['id_list']; }, $rows))));
            if(!empty($listIds))
            {
                $placeholders = implode(',', array_fill(0, count($listIds), '?'));
                foreach($d->rawQuery("select id, slugvi, pdp_profile from #_product_list where id in ($placeholders)", $listIds) as $listRow)
                    $listCache[(int)$listRow['id']] = $listRow;
            }
        }

        $ids = array();
        foreach($rows as $row)
        {
            $list = isset($listCache[(int)$row['id_list']]) ? $listCache[(int)$row['id_list']] : array();
            $matches = false;
            if($status === 'missing_content') $matches = adminProductIsMissingContent($d, $row);
            elseif($status === 'missing_wattage') $matches = adminProductIsMissingWattage($row, $list);
            elseif($status === 'missing_specs') $matches = adminProductIsMissingSpecs($row, $list);
            if($matches) $ids[] = (int)$row['id'];
        }
        return $ids;
    }
}
