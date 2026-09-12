<?php
/* Công tắc "Đóng logo lên ảnh sản phẩm & tin tức" (Admin > Thiết lập thông
   tin) - đọc theo đúng pattern public_pricing.php. $prefix phải khớp đúng
   route watermark/{prefix}/... ở libraries/router.php ('product' hoặc
   'news'), nếu không route đó sẽ không có handler và trả 404. */
function watermarkActive()
{
    global $optsetting;

    return isset($optsetting['watermark_active']) && (string)$optsetting['watermark_active'] === '1';
}

/* $forceSizes: một số nơi gọi getImage() chỉ truyền 'size-error' (dùng cho
   ảnh lỗi khi onerror) mà không có 'sizes' - class.Functions.php::getImage()
   chỉ đi qua nhánh sinh thumbs/watermark khi có 'sizes' (xem biến pathSrc),
   thiếu thì trả thẳng ảnh gốc, watermark không có tác dụng. Khi bật
   watermark, truyền thêm 'sizes' bằng đúng kích thước đó để ép đi qua đúng
   route watermark/... - không đổi gì khi tắt watermark (giữ hành vi cũ,
   ảnh gốc không qua thumbs, tránh regressions). */
function watermarkImageArgs($prefix, $forceSizes = '')
{
    if(!watermarkActive()) return array();

    $args = array('isWatermark' => true, 'prefix' => $prefix);
    if($forceSizes !== '') $args['sizes'] = $forceSizes;

    return $args;
}

/* Dùng cho link <a href> ảnh gốc/phóng to (MagicZoom...) không đi qua
   getImage() nên watermarkImageArgs() không áp dụng được - vd
   templates/product/product_detail_tpl.php, api/color.php. $path phải là
   đường dẫn upload đầy đủ dạng UPLOAD_PRODUCT_L.$photo (giống tham số truyền
   cho getImage()['upload'].['image']). Khi tắt watermark trả về nguyên $path
   như hành vi cũ (không đổi). */
function watermarkImageHref($prefix, $sizeToken, $path)
{
    if(!watermarkActive() || empty($path)) return $path;

    return ASSET.WATERMARK.'/'.$prefix.'/'.$sizeToken.'/'.$path;
}
