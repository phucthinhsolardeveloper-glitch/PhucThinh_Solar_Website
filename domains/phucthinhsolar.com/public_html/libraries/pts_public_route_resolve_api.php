<?php
/** Read-only helpers for resolving legacy public bare-slug ownership. */

function ptsPublicRouteResolveMap()
{
    return array(
        array('tbl'=>'product_list','field'=>'idl','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product_cat','field'=>'idc','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product_item','field'=>'idi','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product_sub','field'=>'ids','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product_brand','field'=>'idb','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product','field'=>'id','source'=>'product','com'=>'san-pham','type'=>'san-pham'),
        array('tbl'=>'product','field'=>'id','source'=>'product','com'=>'thu-vien-anh','type'=>'thu-vien-anh'),
        array('tbl'=>'news_list','field'=>'idl','source'=>'news','com'=>'tin-tuc','type'=>'tin-tuc'),
        array('tbl'=>'news_cat','field'=>'idc','source'=>'news','com'=>'tin-tuc','type'=>'tin-tuc'),
        array('tbl'=>'news_item','field'=>'idi','source'=>'news','com'=>'tin-tuc','type'=>'tin-tuc'),
        array('tbl'=>'news_sub','field'=>'ids','source'=>'news','com'=>'tin-tuc','type'=>'tin-tuc'),
        array('tbl'=>'news','field'=>'id','source'=>'news','com'=>'tin-tuc','type'=>'tin-tuc'),
        array('tbl'=>'news_list','field'=>'idl','source'=>'news','com'=>'giai-phap','type'=>'giai-phap'),
        array('tbl'=>'news_cat','field'=>'idc','source'=>'news','com'=>'giai-phap','type'=>'giai-phap'),
        array('tbl'=>'news_item','field'=>'idi','source'=>'news','com'=>'giai-phap','type'=>'giai-phap'),
        array('tbl'=>'news_sub','field'=>'ids','source'=>'news','com'=>'giai-phap','type'=>'giai-phap'),
        array('tbl'=>'news','field'=>'id','source'=>'news','com'=>'giai-phap','type'=>'giai-phap'),
        array('tbl'=>'news_list','field'=>'idl','source'=>'news','com'=>'du-an','type'=>'du-an'),
        array('tbl'=>'news_cat','field'=>'idc','source'=>'news','com'=>'du-an','type'=>'du-an'),
        array('tbl'=>'news_item','field'=>'idi','source'=>'news','com'=>'du-an','type'=>'du-an'),
        array('tbl'=>'news_sub','field'=>'ids','source'=>'news','com'=>'du-an','type'=>'du-an'),
        array('tbl'=>'news','field'=>'id','source'=>'news','com'=>'du-an','type'=>'du-an'),
        array('tbl'=>'news','field'=>'id','source'=>'news','com'=>'chinh-sach','type'=>'chinh-sach')
    );
}

function ptsPublicRouteResolveCategory($d, $table, $field, $id, $type)
{
    $tables = array(
        'idl'=>array('news_list','id'),
        'idc'=>array('news_cat','id_list'),
        'idi'=>array('news_item','id_cat'),
        'ids'=>array('news_sub','id_item')
    );
    if(!isset($tables[$field]) || $table !== $tables[$field][0]) return null;
    $parentField = $tables[$field][1];
    $row = $d->rawQueryOne("select id,{$parentField} as parent_id,namevi,descvi,slugvi,photo,status from #_{$table} where id=? and type=? and find_in_set('hienthi',status) limit 1", array((int)$id, $type));
    if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;

    $chain = array();
    $currentId = (int)$row['id'];
    $currentTable = $table;
    $parentMap = array('news_sub'=>array('news_item','id_item'), 'news_item'=>array('news_cat','id_cat'), 'news_cat'=>array('news_list','id_list'));
    $chain[] = (string)$row['slugvi'];
    while(isset($parentMap[$currentTable]))
    {
        list($parentTable, $childParentField) = $parentMap[$currentTable];
        $parentId = $currentTable === $table ? (int)($row['parent_id'] ?? 0) : $currentId;
        if($parentId <= 0) return null;
        $parent = $d->rawQueryOne("select id,{$childParentField} as parent_id,slugvi from #_{$parentTable} where id=? and type=? and find_in_set('hienthi',status) limit 1", array($parentId, $type));
        if(empty($parent) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($parent['slugvi'] ?? ''))) return null;
        $chain[] = (string)$parent['slugvi'];
        $currentTable = $parentTable;
        $currentId = (int)$parent['id'];
        if($parentTable === 'news_list') break;
    }
    $chain = array_reverse($chain);
    $row['_pts_public_photo'] = !empty($row['photo']) && defined('ROOT') && is_file(ROOT.UPLOAD_NEWS_L.$row['photo']) ? $row['photo'] : '';
    return array(
        'id'=>(int)$row['id'],
        'slug'=>(string)$row['slugvi'],
        'type'=>$type,
        'level'=>array('idl'=>'list','idc'=>'cat','idi'=>'item','ids'=>'sub')[$field],
        'path'=>implode('/', $chain),
        'name'=>html_entity_decode((string)($row['namevi'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'description'=>html_entity_decode((string)($row['descvi'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'image'=>''
    );
}

function ptsPublicRouteResolveDto($d, array $match, $slug)
{
    $field = isset($match['route_field']) ? (string)$match['route_field'] : '';
    $com = isset($match['route_com']) ? (string)$match['route_com'] : '';
    $id = isset($match['id']) ? (int)$match['id'] : 0;
    if($id <= 0) return null;

    if($com === 'san-pham')
    {
        if(in_array($field, array('idl','idc','idi','ids'), true))
        {
            $table = array('idl'=>'product_list','idc'=>'product_cat','idi'=>'product_item','ids'=>'product_sub')[$field];
            $path = publicProductEntityPath($d, $table, $id, 'vi');
            if($path === '') return null;
            $category = ptsPublicRouteResolveProductCategory($d, $table, $id);
            if($category) $category['path'] = substr(trim($path, '/'), strlen('danh-muc-san-pham/'));
            return $category ? array('schemaVersion'=>1,'entity'=>'product-category','slug'=>$category['slug'],'canonical'=>'/'.$path,'category'=>$category) : null;
        }
        if($field === 'idb')
        {
            $row = $d->rawQueryOne("select id,slugvi,status from #_product_brand where id=? and type=? and find_in_set('hienthi',status) limit 1", array($id,'san-pham'));
            if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;
            return array('schemaVersion'=>1,'entity'=>'brand','slug'=>$row['slugvi'],'canonical'=>'/thuong-hieu/'.$row['slugvi'].'/');
        }
        if($field === 'id')
        {
            $row = $d->rawQueryOne("select slugvi from #_product where id=? and type=? and find_in_set('hienthi',status) limit 1", array($id,'san-pham'));
            if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;
            return array('schemaVersion'=>1,'entity'=>'product','slug'=>$row['slugvi'],'canonical'=>'/san-pham/'.$row['slugvi']);
        }
    }
    if($com === 'thu-vien-anh' && $field === 'id')
    {
        $row = $d->rawQueryOne("select slugvi from #_product where id=? and type=? and find_in_set('hienthi',status) limit 1", array($id,'thu-vien-anh'));
        if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;
        return array('schemaVersion'=>1,'entity'=>'gallery','slug'=>$row['slugvi'],'canonical'=>'/'.$row['slugvi'].'/');
    }
    if($com === 'tin-tuc')
    {
        if(in_array($field, array('idl','idc','idi','ids'), true))
        {
            $table = array('idl'=>'news_list','idc'=>'news_cat','idi'=>'news_item','ids'=>'news_sub')[$field];
            $category = ptsPublicRouteResolveCategory($d,$table,$field,$id,'tin-tuc');
            return $category ? array('schemaVersion'=>1,'entity'=>'news-category','slug'=>$category['slug'],'canonical'=>'/'.$category['slug'].'/','category'=>$category) : null;
        }
        if($field === 'id') return ptsPublicRouteResolveArticle($d,$id,'tin-tuc','news','/'.$slug.'/');
    }
    if($com === 'giai-phap')
    {
        if(in_array($field, array('idl','idc','idi','ids'), true))
        {
            $table = array('idl'=>'news_list','idc'=>'news_cat','idi'=>'news_item','ids'=>'news_sub')[$field];
            $category = ptsPublicRouteResolveCategory($d,$table,$field,$id,'giai-phap');
            return $category ? array('schemaVersion'=>1,'entity'=>'solution-category','slug'=>$category['slug'],'canonical'=>'/'.$category['slug'].'/','category'=>$category) : null;
        }
        if($field === 'id') return ptsPublicRouteResolveArticle($d,$id,'giai-phap','solution','/giai-phap/'.$slug.'/');
    }
    if($com === 'du-an')
    {
        if(in_array($field, array('idl','idc','idi','ids'), true))
        {
            $table = array('idl'=>'news_list','idc'=>'news_cat','idi'=>'news_item','ids'=>'news_sub')[$field];
            $category = ptsPublicRouteResolveCategory($d,$table,$field,$id,'du-an');
            return $category ? array('schemaVersion'=>1,'entity'=>'project-category','slug'=>$category['slug'],'canonical'=>'/'.$category['slug'].'/','category'=>$category) : null;
        }
        if($field === 'id') return ptsPublicRouteResolveArticle($d,$id,'du-an','project','/'.$slug.'/');
    }
    if($com === 'chinh-sach' && $field === 'id') return ptsPublicRouteResolveArticle($d,$id,'chinh-sach','policy','/ho-tro/'.$slug.'/');
    return null;
}

function ptsPublicRouteResolveProductCategory($d, $table, $id)
{
    $map = array('product_list'=>array('id'=>'id','parent'=>'id'),'product_cat'=>array('id'=>'id','parent'=>'id_list'),'product_item'=>array('id'=>'id','parent'=>'id_cat'),'product_sub'=>array('id'=>'id','parent'=>'id_item'));
    if(!isset($map[$table])) return null;
    $row = $d->rawQueryOne("select id,slugvi,namevi,status from #_{$table} where id=? and type=? and find_in_set('hienthi',status) limit 1", array((int)$id,'san-pham'));
    if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;
    return array('id'=>(int)$row['id'],'slug'=>$row['slugvi'],'type'=>'san-pham','level'=>array('product_list'=>'list','product_cat'=>'cat','product_item'=>'item','product_sub'=>'sub')[$table],'path'=>$row['slugvi'],'name'=>html_entity_decode((string)($row['namevi'] ?? ''), ENT_QUOTES, 'UTF-8'),'description'=>'','image'=>'');
}

function ptsPublicRouteResolveArticle($d, $id, $type, $entity, $canonical)
{
    $row = $d->rawQueryOne("select slugvi from #_news where id=? and type=? and find_in_set('hienthi',status) limit 1", array((int)$id,$type));
    if(empty($row) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)($row['slugvi'] ?? ''))) return null;
    return array('schemaVersion'=>1,'entity'=>$entity,'slug'=>$row['slugvi'],'canonical'=>$canonical);
}

function ptsPublicRouteResolveNotFound()
{
    return array('schemaVersion'=>1,'entity'=>'not-found','slug'=>'','canonical'=>'');
}
?>
