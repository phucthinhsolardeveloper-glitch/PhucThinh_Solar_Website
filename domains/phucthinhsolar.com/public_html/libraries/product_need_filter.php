<?php
if(!function_exists('productNeedFilterDefinitions'))
{
    /* Không có cột riêng cho "nhu cầu sử dụng" - suy ra từ tên + mô tả sản
       phẩm bằng từ khoá, cùng cách productPdpUseCaseVariant() đang dùng để
       chọn ảnh minh hoạ ứng dụng ở trang chi tiết. Chỉ áp dụng cho nhóm đèn;
       "Loại đèn" (đường/pha/sân vườn/ốp trần) đã có dải tab riêng nên không
       lặp lại ở đây. */
    function productNeedFilterDefinitions()
    {
        return array(
            'duong-giao-thong' => array('đường', 'giao thông', 'khu dân cư', 'nông thôn', 'lối lưu thông'),
            'san-nha' => array('sân nhà', 'sân vườn', 'lối đi', 'cổng', 'nhà vườn', 'homestay', 'ban công'),
            'khuon-vien' => array('khuôn viên', 'công trình', 'nhà xưởng', 'resort', 'bãi xe', 'bãi đỗ', 'khu sản xuất')
        );
    }

    function productNeedFilterSql($rawValue, $lang = 'vi')
    {
        $definitions = productNeedFilterDefinitions();
        $nameCol = 'name'.$lang;
        $descCol = 'desc'.$lang;
        $conditions = array();
        $params = array();
        foreach(array_unique(explode(',', (string)$rawValue)) as $key)
        {
            $key = trim($key);
            if(!isset($definitions[$key])) continue;
            $keywordConds = array();
            foreach($definitions[$key] as $keyword)
            {
                $keywordConds[] = "($nameCol like ? or $descCol like ?)";
                $like = '%'.$keyword.'%';
                $params[] = $like;
                $params[] = $like;
            }
            if(!empty($keywordConds)) $conditions[] = '('.implode(' or ', $keywordConds).')';
        }
        if(!empty($conditions)) return array(' and ('.implode(' or ', $conditions).')', $params);
        return array('', array());
    }
}
