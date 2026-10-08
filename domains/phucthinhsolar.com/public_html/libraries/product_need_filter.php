<?php
if(!function_exists('productNeedFilterDefinitions'))
{
    /* Nhu cầu hiển thị ở sidebar dùng taxonomy đèn làm nguồn chính để một sản
       phẩm chỉ thuộc đúng một nhóm nhu cầu. Từ khoá chỉ là fallback cho các
       list cũ như Trụ đèn chưa có danh mục con tương ứng. */
    function productNeedFilterDefinitions()
    {
        return array(
            'du-an-cong-trinh' => array(
                'categories' => array('den-duong', 'den-pha-nang-luong-mat-troi', 'den-ufo'),
                'keywords' => array('đường', 'giao thông', 'khu dân cư', 'công trình', 'nhà xưởng', 'khu sản xuất', 'bãi xe', 'bãi đỗ')
            ),
            'chieu-sang-gia-dinh' => array(
                'categories' => array('den-op-tran'),
                'keywords' => array('gia đình', 'nhà ở', 'hiên nhà', 'hành lang', 'ban công', 'nhà phố', 'biệt thự', 'sân nhà')
            ),
            'san-vuon-canh-quan' => array(
                'categories' => array('den-san-vuon'),
                'keywords' => array('sân vườn', 'cảnh quan', 'khuôn viên', 'lối đi', 'cổng', 'resort', 'homestay', 'tiểu cảnh', 'lối dạo')
            ),
            /* Biến tần (list id=19) - giữ các mã cũ cho các bộ lọc nội bộ đã
               được lưu trước đó; nhóm này không dùng taxonomy đèn. */
            'bientan-hoaluoi-ho-gia-dinh' => array('keywords' => array('BOOST', 'SMART')),
            'bientan-hoaluoi-doanh-nghiep' => array('keywords' => array('MIC', 'PRO', 'MEGA', 'FORTH')),
        );
    }

    function productNeedFilterSql($rawValue, $lang = 'vi')
    {
        $definitions = productNeedFilterDefinitions();
        $aliases = array(
            'duong-giao-thong' => 'du-an-cong-trinh',
            'khuon-vien' => 'du-an-cong-trinh',
            'san-nha' => 'san-vuon-canh-quan'
        );
        $nameCol = 'name'.$lang;
        $descCol = 'desc'.$lang;
        $conditions = array();
        $params = array();

        foreach(array_unique(explode(',', (string)$rawValue)) as $rawKey)
        {
            $key = trim($rawKey);
            if(isset($aliases[$key])) $key = $aliases[$key];
            if(!isset($definitions[$key])) continue;

            $definition = $definitions[$key];
            $keyConditions = array();
            if(!empty($definition['categories']))
            {
                $categoryPlaceholders = implode(',', array_fill(0, count($definition['categories']), '?'));
                $keyConditions[] = "id_cat in (select pc.id from #_product_cat pc where pc.type = ? and find_in_set('hienthi',pc.status) and pc.slugvi in ($categoryPlaceholders))";
                $params[] = 'san-pham';
                foreach($definition['categories'] as $categorySlug) $params[] = $categorySlug;
            }

            if(!empty($definition['keywords']))
            {
                $keywordConds = array();
                $keywordParams = array();
                foreach($definition['keywords'] as $keyword)
                {
                    $keywordConds[] = "($nameCol like ? or $descCol like ?)";
                    $like = '%'.$keyword.'%';
                    $keywordParams[] = $like;
                    $keywordParams[] = $like;
                }
                $keywordSql = '('.implode(' or ', $keywordConds).')';
                /* Với nhóm đèn đã chuẩn hoá theo category, không dùng từ khoá
                   để kéo sản phẩm từ nhóm khác vào cùng bộ lọc. */
                if(!empty($definition['categories']))
                {
                    $keyConditions[] = "(id_list not in (select pl.id from #_product_list pl where pl.type = ? and pl.slugvi = ?) and $keywordSql)";
                    $params[] = 'san-pham';
                    $params[] = 'den-nlmt';
                    $params = array_merge($params, $keywordParams);
                }
                else
                {
                    $keyConditions[] = $keywordSql;
                    $params = array_merge($params, $keywordParams);
                }
            }

            if(!empty($keyConditions)) $conditions[] = '('.implode(' or ', $keyConditions).')';
        }

        if(!empty($conditions)) return array(' and ('.implode(' or ', $conditions).')', $params);
        return array('', array());
    }
}
