<?php
if(!function_exists('productPriceFilterRanges'))
{
    /* Bậc giá dùng chung cho sidebar filter + AJAX lọc sản phẩm.
       Đổi 2026-09-10: nâng từ 4 bậc (tối đa "trên 2.000.000đ") lên 8 bậc,
       trần tới 30 triệu, khớp mặt bằng giá thật của đèn/pin lưu trữ NLMT
       (nhiều sản phẩm giá chục triệu, bậc cũ dồn hết vào 1 nhóm không lọc được). */
    function productPriceFilterRanges()
    {
        return array(
            '1' => array(0, 1000000,        'Giá dưới 1.000.000đ'),
            '2' => array(1000000, 2000000,  '1.000.000đ - 2.000.000đ'),
            '3' => array(2000000, 3000000,  '2.000.000đ - 3.000.000đ'),
            '4' => array(3000000, 5000000,  '3.000.000đ - 5.000.000đ'),
            '5' => array(5000000, 10000000, '5.000.000đ - 10.000.000đ'),
            '6' => array(10000000, 20000000,'10.000.000đ - 20.000.000đ'),
            '7' => array(20000000, 30000000,'20.000.000đ - 30.000.000đ'),
            '8' => array(30000000, null,    'Giá trên 30.000.000đ'),
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
