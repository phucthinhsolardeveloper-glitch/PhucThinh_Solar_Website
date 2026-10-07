<?php

if(!function_exists('publicProductDefaultOrder'))
{
    /**
     * Thứ tự mặc định của danh sách public: sản phẩm có ảnh đại diện trước,
     * sau đó vẫn tôn trọng thứ tự thủ công và ID như hành vi cũ.
     */
    function publicProductDefaultOrder($alias = '')
    {
        $alias = trim((string)$alias);
        if($alias !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $alias)) throw new InvalidArgumentException('Alias SQL không hợp lệ');
        /* Luôn định danh cột bảng ngoài. Trong subquery gallery, `id`/`type`
           không prefix sẽ bị MariaDB hiểu là cột của gallery, làm fallback
           gallery bị xếp nhầm vào nhóm thiếu ảnh. */
        $prefix = $alias !== '' ? $alias.'.' : '#_product.';
        /* 2026-09-29: field photo (ảnh đại diện) trống không có nghĩa sản
           phẩm không có ảnh thật - PDP tự lấy ảnh gallery làm ảnh chính khi
           field này trống (xem product_detail_tpl.php:55-74, cùng lỗi với
           bộ lọc "Thiếu ảnh đại diện" ở admin_product_completeness.php). Chỉ
           đẩy xuống cuối khi CẢ ảnh đại diện lẫn gallery đều trống, tránh
           chôn vùi sản phẩm khách đã thấy ảnh thật trên site. */
        $hasPhoto = "({$prefix}photo is not null and trim({$prefix}photo) <> '') or exists (select 1 from #_gallery g where g.id_parent = {$prefix}id and g.com = 'product' and g.kind = 'man' and g.val = {$prefix}type and find_in_set('hienthi', g.status))";
        return " order by case when $hasPhoto then 0 else 1 end, {$prefix}numb,{$prefix}id desc";
    }
}
