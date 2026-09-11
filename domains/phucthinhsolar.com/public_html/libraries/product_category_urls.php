<?php
function publicProductCategoryPath(array $row, $level = 'list', $lang = 'vi')
{
    $slug = 'slug'.($lang === 'en' ? 'en' : 'vi');
    $parts = array('danh-muc-san-pham');
    foreach(array('list','cat','item','sub') as $part)
    {
        $key = $part === $level ? $slug : $part.'_'.$slug;
        if(!empty($row[$key])) $parts[] = trim($row[$key], '/');
        if($part === $level) break;
    }
    return implode('/', $parts).'/';
}

function publicResolveProductCategoryPath($d, array $segments, $lang = 'vi')
{
    $segments = array_values(array_filter(array_map('trim', $segments)));
    if(count($segments) < 1 || count($segments) > 4) return array();
    $slug = 'slug'.($lang === 'en' ? 'en' : 'vi');
    $list = $d->rawQueryOne("select id from #_product_list where $slug = ? and type = ? and find_in_set('hienthi',status) limit 1", array($segments[0], 'san-pham'));
    if(empty($list)) return array();
    $resolved = array('idl' => (int)$list['id']);
    $parentId = (int)$list['id'];
    $levels = array(1 => array('product_cat','idc','id_list'), 2 => array('product_item','idi','id_cat'), 3 => array('product_sub','ids','id_item'));
    for($i = 1; $i < count($segments); $i++)
    {
        list($table,$field,$parentField) = $levels[$i];
        $row = $d->rawQueryOne("select id from #_$table where $slug = ? and $parentField = ? and type = ? and find_in_set('hienthi',status) limit 1", array($segments[$i], $parentId, 'san-pham'));
        if(empty($row)) return array();
        $resolved[$field] = $parentId = (int)$row['id'];
    }
    return $resolved;
}

function publicProductEntityPath($d, $table, $id, $lang = 'vi')
{
    $slug = 'slug'.($lang === 'en' ? 'en' : 'vi');
    if($table === 'product') {
        $row = $d->rawQueryOne("select $slug from #_product where id = ? and type = ? limit 1", array((int)$id,'san-pham'));
        return !empty($row[$slug]) ? 'san-pham/'.$row[$slug] : '';
    }
    if($table === 'product_list') {
        $row = $d->rawQueryOne("select $slug from #_product_list where id = ? and type = ? limit 1", array((int)$id,'san-pham'));
        return !empty($row) ? publicProductCategoryPath($row,'list',$lang) : '';
    }
    if($table === 'product_cat') {
        $row = $d->rawQueryOne("select pc.$slug, pl.$slug list_$slug from #_product_cat pc join #_product_list pl on pl.id=pc.id_list where pc.id=? and pc.type=? limit 1", array((int)$id,'san-pham'));
        return !empty($row) ? publicProductCategoryPath($row,'cat',$lang) : '';
    }
    if($table === 'product_item') {
        $row = $d->rawQueryOne("select pi.$slug, pc.$slug cat_$slug, pl.$slug list_$slug from #_product_item pi join #_product_cat pc on pc.id=pi.id_cat join #_product_list pl on pl.id=pi.id_list where pi.id=? and pi.type=? limit 1", array((int)$id,'san-pham'));
        return !empty($row) ? publicProductCategoryPath($row,'item',$lang) : '';
    }
    if($table === 'product_sub') {
        $row = $d->rawQueryOne("select ps.$slug, pi.$slug item_$slug, pc.$slug cat_$slug, pl.$slug list_$slug from #_product_sub ps join #_product_item pi on pi.id=ps.id_item join #_product_cat pc on pc.id=ps.id_cat join #_product_list pl on pl.id=ps.id_list where ps.id=? and ps.type=? limit 1", array((int)$id,'san-pham'));
        return !empty($row) ? publicProductCategoryPath($row,'sub',$lang) : '';
    }
    if($table === 'news_chinh_sach') {
        $row = $d->rawQueryOne("select $slug from #_news where id = ? and type = ? limit 1", array((int)$id,'chinh-sach'));
        return !empty($row[$slug]) ? 'ho-tro/'.$row[$slug].'/' : '';
    }
    return '';
}

/**
 * Đường dẫn chuẩn (canonical) của route công khai đã resolve.
 *
 * Router chấp nhận cùng một entity ở nhiều dạng URL: bare slug, tiền tố trang
 * trí (`category/`, `ho-tro/`, `solution/`...) và dạng phân cấp. Trước đây thẻ
 * canonical lấy thẳng REQUEST_URI nên mọi biến thể đều tự nhận là canonical.
 * Hàm này trả về dạng chuẩn duy nhất cho entity; chuỗi rỗng nghĩa là route
 * không map tới entity đã biết và caller giữ nguyên hành vi cũ.
 */
function publicCanonicalEntityPath($d, $com, array $params, $lang = 'vi')
{
    $slug = 'slug'.($lang === 'en' ? 'en' : 'vi');

    $productRoutes = array('id' => 'product', 'ids' => 'product_sub', 'idi' => 'product_item', 'idc' => 'product_cat', 'idl' => 'product_list');
    if($com === 'san-pham')
    {
        foreach($productRoutes as $key => $table)
        {
            if(empty($params[$key])) continue;
            return publicProductEntityPath($d, $table, $params[$key], $lang);
        }
        return 'san-pham/';
    }

    /* Tin tức/giải pháp/dự án: template dùng nhiều tiền tố trang trí khác nhau
       (category/, solution/, danh-muc-du-an/...) cho cùng một entity. Chuẩn hoá
       về bare slug — đúng dạng sitemap đang công bố. Chỉ áp dụng khi URL trỏ tới
       một entity cụ thể; trang danh sách giữ nguyên hành vi cũ. */
    $newsComs = array('tin-tuc', 'giai-phap', 'du-an', 'du-an-da-thuc-hien', 'chinh-sach', 'he-thong-dai-ly');
    if(in_array($com, $newsComs, true))
    {
        $newsRoutes = array('id' => 'news', 'ids' => 'news_sub', 'idi' => 'news_item', 'idc' => 'news_cat', 'idl' => 'news_list');
        foreach($newsRoutes as $key => $table)
        {
            if(empty($params[$key])) continue;
            $row = $d->rawQueryOne("select $slug from #_$table where id = ? limit 1", array((int) $params[$key]));
            return !empty($row[$slug]) ? $row[$slug] : '';
        }
    }
    return '';
}
?>
