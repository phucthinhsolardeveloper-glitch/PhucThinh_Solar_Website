<?php
if(!function_exists('productPriceFilterRanges'))
{
    /* Ba bậc giá dùng chung cho sidebar và AJAX lọc sản phẩm, đúng bố cục
       catalog: dưới 1 triệu, từ 1 đến 3 triệu, trên 3 triệu. */
    function productPriceFilterRanges()
    {
        return array(
            '1' => array(0, 1000000,    'Dưới 1.000.000đ'),
            '2' => array(1000000, 3000000, '1.000.000đ - 3.000.000đ'),
            '3' => array(3000000, null,   'Trên 3.000.000đ'),
        );
    }

    /**
     * $rawValue: chuỗi các key bậc giá cách nhau bởi dấu phẩy (vd "2,3").
     * Trả về array($whereSqlThemVao, $params) — where rỗng nếu không khớp bậc nào.
     * Áp theo sale_price nếu có, ngược lại regular_price; loại sản phẩm "báo giá".
     */
    function productPriceFilterSql($rawValue)
    {
        $ranges = productPriceFilterRanges();
        $conds = array();
        $params = array();
        foreach(explode(',', (string) $rawValue) as $key)
        {
            $key = trim($key);
            if(!isset($ranges[$key])) continue;
            list($min, $max) = $ranges[$key];
            if($max === null)
            {
                $conds[] = "(if(sale_price>0,sale_price,regular_price) >= ?)";
                $params[] = $min;
            }
            else
            {
                $conds[] = "(if(sale_price>0,sale_price,regular_price) >= ? and if(sale_price>0,sale_price,regular_price) < ?)";
                $params[] = $min;
                $params[] = $max;
            }
        }
        if(empty($conds)) return array('', array());
        return array(" and not find_in_set('baogia',status) and (".implode(' or ', $conds).")", $params);
    }
}
