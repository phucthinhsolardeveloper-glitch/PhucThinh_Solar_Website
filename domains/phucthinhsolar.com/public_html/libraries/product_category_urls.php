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
    return '';
}
?>
