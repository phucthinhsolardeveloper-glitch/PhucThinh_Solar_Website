<?php

if(!function_exists('publicProductBossCategoryOrder'))
{
    /**
     * Thứ tự nhóm sản phẩm đã chốt cho catalog đèn. Các slug là taxonomy
     * nội bộ, không lấy từ request nên vẫn giữ được SQL parameterization của
     * các điều kiện lọc sản phẩm.
     */
    function publicProductBossCategoryOrder($alias = '', $type = 'san-pham')
    {
        $alias = trim((string)$alias);
        if($alias !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $alias)) throw new InvalidArgumentException('Alias SQL không hợp lệ');
        $prefix = $alias !== '' ? $alias.'.' : '#_product.';
        $safeType = str_replace("'", "''", (string)$type);
        $lightingList = "(select id from #_product_list where type = '".$safeType."' and slugvi = 'den-nlmt' limit 1)";
        $gardenList = "(select id from #_product_list where type = '".$safeType."' and slugvi = 'den-san-vuon' limit 1)";
        $orderedCats = array(
            'den-pha-nang-luong-mat-troi' => 10,
            'den-duong' => 20,
            'den-san-vuon' => 30,
            'den-ufo' => 40,
            'den-op-tran' => 50
        );
        $clauses = array();
        foreach($orderedCats as $slug => $rank)
        {
            $safeSlug = str_replace("'", "''", $slug);
            $cat = "(select id from #_product_cat where type = '".$safeType."' and id_list = ".$lightingList." and slugvi = '".$safeSlug."' limit 1)";
            $clauses[] = "when {$prefix}id_list = {$lightingList} and {$prefix}id_cat = {$cat} then ".$rank;
        }
        $clauses[] = "when {$prefix}id_list = {$gardenList} then 30";
        return 'case '.implode(' ', $clauses).' else 1000 end';
    }
}

if(!function_exists('publicProductDefaultOrder'))
{
    /**
     * Thứ tự mặc định của danh sách public: nếu có rank nhóm thì ưu tiên rank
     * đó, sau đó sản phẩm có ảnh đại diện trước và vẫn tôn trọng thứ tự thủ
     * công cùng ID như hành vi cũ.
     */
    function publicProductDefaultOrder($alias = '', $categoryOrder = '')
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
        $categoryOrder = trim((string)$categoryOrder);
        $categoryPrefix = $categoryOrder !== '' ? $categoryOrder.', ' : '';
        return " order by {$categoryPrefix}case when $hasPhoto then 0 else 1 end, {$prefix}numb,{$prefix}id desc";
    }
}
