<?php
if(!function_exists('productMerchantFeedHeaders'))
{
    function productMerchantFeedHeaders()
    {
        return array(
            'id', 'title', 'description', 'link', 'image_link',
            'availability', 'price', 'sale_price', 'brand', 'condition',
            'identifier_exists', 'product_type'
        );
    }

    function productMerchantPlainText($text, $limit = 0)
    {
        $text = html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
        if($limit > 0 && mb_strlen($text, 'UTF-8') > $limit) $text = mb_substr($text, 0, $limit, 'UTF-8');
        return $text;
    }

    function productMerchantImageMeetsMinimum($imagePath, $minimumWidth = 500, $minimumHeight = 500)
    {
        if(!is_file($imagePath)) return false;
        $imageSize = @getimagesize($imagePath);
        return $imageSize && (int)$imageSize[0] >= (int)$minimumWidth && (int)$imageSize[1] >= (int)$minimumHeight;
    }

    function productMerchantFeedRow(array $row, $configBase, $uploadRoot)
    {
        $id = trim(isset($row['code']) ? (string)$row['code'] : '');
        $title = productMerchantPlainText(isset($row['namevi']) ? $row['namevi'] : '', 150);
        $slug = trim(isset($row['slugvi']) ? (string)$row['slugvi'] : '');
        $image = trim(isset($row['photo_feed']) ? (string)$row['photo_feed'] : '');
        $price = isset($row['regular_price']) ? (float)$row['regular_price'] : 0;
        $salePrice = isset($row['sale_price']) ? (float)$row['sale_price'] : 0;
        $statuses = array_filter(array_map('trim', explode(',', isset($row['status']) ? (string)$row['status'] : '')));

        /* ID Merchant không được thay đổi giữa các lần đồng bộ. Không cắt chuỗi
           quá dài vì có thể tạo trùng ID; yêu cầu Admin sửa mã sản phẩm. */
        if($id === '' || mb_strlen($id, 'UTF-8') > 50 || $title === '' || $slug === '') return null;

        /* Feed chỉ nhận ảnh Shopping đã được biên tập riêng. Ảnh `photo` trên
           website có thể chứa banner/chữ/khung chiến dịch, không được tự coi là
           ảnh sạch vì Google cấm watermark, chữ quảng cáo, viền và placeholder. */
        if($image === '' || basename($image) !== $image || !preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $image)) return null;
        $imagePath = rtrim((string)$uploadRoot, '/').'/'.$image;
        if(!productMerchantImageMeetsMinimum($imagePath)) return null;

        /* Sản phẩm gắn cờ báo giá không hiển thị giá ở landing page, nên tuyệt
           đối không xuất giá vào feed dù công tắc giá toàn site đang bật. */
        if(in_array('baogia', $statuses, true)) return null;
        if($price <= 0 && $salePrice > 0) { $price = $salePrice; $salePrice = 0; }
        if($price <= 0 || ($salePrice > 0 && $salePrice >= $price)) return null;

        $description = productMerchantPlainText(isset($row['descvi']) ? $row['descvi'] : '');
        if($description === '') $description = $title;
        if(!empty($row['cong_suat']))
        {
            $wattage = (float)$row['cong_suat'];
            $description .= ' Công suất: '.(($wattage == round($wattage)) ? (string)round($wattage) : (string)$wattage).' W.';
        }
        $description = productMerchantPlainText($description, 5000);

        $productTypeParts = array();
        foreach(array('list_name', 'cat_name') as $categoryKey)
        {
            $categoryName = productMerchantPlainText(isset($row[$categoryKey]) ? $row[$categoryKey] : '');
            if($categoryName !== '') $productTypeParts[] = $categoryName;
        }

        return array(
            $id,
            $title,
            $description,
            rtrim((string)$configBase, '/').'/san-pham/'.rawurlencode($slug),
            rtrim((string)$configBase, '/').'/upload/product/'.rawurlencode($image),
            'in_stock',
            number_format($price, 0, '.', '').' VND',
            $salePrice > 0 ? number_format($salePrice, 0, '.', '').' VND' : '',
            productMerchantPlainText(isset($row['brand_name']) ? $row['brand_name'] : ''),
            'new',
            /* DB hiện chưa có GTIN/MPN đã xác minh. Không tự lấy SKU làm MPN;
               khi có định danh nhà sản xuất thật cần bổ sung field chuyên trách. */
            'no',
            implode(' > ', $productTypeParts)
        );
    }
}
