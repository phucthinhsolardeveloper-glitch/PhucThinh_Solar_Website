<?php
/**
 * Read-only public navigation projection. Inputs are already filtered by the
 * same published queries used by sources/allpage.php; this file only shapes
 * and sanitizes the DTO.
 */
function ptsPublicNavScalar($value)
{
    return is_scalar($value) ? trim((string)$value) : '';
}

function ptsPublicNavText($value)
{
    $value = ptsPublicNavScalar($value);
    if($value === '') return '';
    // Decode CMS-escaped rich-text first, then strip all markup. Never render it as HTML.
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/?p|/?div|/?li)\b[^>]*>~i', ' ', $value);
    return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', strip_tags($value)));
}

function ptsPublicNavUrl($value, $fallback = '')
{
    $value = ptsPublicNavScalar($value);
    if($value === '' || preg_match('~^(?:javascript|data|vbscript):~i', $value)) return $fallback;
    if(preg_match('~[\x00-\x20<>"\']~', $value) || strpos($value, '..') !== false) return $fallback;
    if(preg_match('~^(?:https?:)?//~i', $value)) return preg_match('~^https?://~i', $value) ? $value : $fallback;
    return '/'.ltrim($value, '/');
}

function ptsPublicNavAsset($value, $upload, $root)
{
    $value = ptsPublicNavScalar($value);
    $upload = trim((string)$upload, '/');
    if($value === '' || $upload === '' || basename($value) !== $value) return '';
    if(!preg_match('/\.(?:png|jpe?g|gif|svg|webp)$/i', $value)) return '';
    $relative = $upload.'/'.$value;
    return is_file(rtrim($root, '/').'/'.$relative) ? '/'.$relative : '';
}

function ptsPublicNavName(array $row, $lang = 'vi')
{
    $key = 'name'.$lang;
    return ptsPublicNavText(isset($row[$key]) ? $row[$key] : (isset($row['namevi']) ? $row['namevi'] : ''));
}

function ptsPublicNavPublished(array $row)
{
    if(!array_key_exists('status', $row)) return true;
    return in_array('hienthi', explode(',', ptsPublicNavScalar($row['status'])), true);
}

function ptsPublicNavSlug(array $row, $lang = 'vi')
{
    $key = 'slug'.$lang;
    return trim(ptsPublicNavScalar(isset($row[$key]) ? $row[$key] : (isset($row['slugvi']) ? $row['slugvi'] : '')), '/');
}

function ptsPublicNavProductIndexes(array $lists, array $cats, array $items, array $subs)
{
    $indexes = array('list'=>array(), 'cat'=>array(), 'item'=>array(), 'sub'=>array());
    foreach(array('list'=>$lists, 'cat'=>$cats, 'item'=>$items, 'sub'=>$subs) as $level=>$rows)
    {
        foreach($rows as $row)
        {
            if(is_array($row) && ptsPublicNavPublished($row) && (int)($row['id'] ?? 0) > 0)
                $indexes[$level][(int)$row['id']] = $row;
        }
    }
    return $indexes;
}

function ptsPublicNavProductPath(array $row, $level, array $indexes, $lang = 'vi')
{
    $id = (int)($row['id'] ?? 0);
    if($id <= 0 || !isset($indexes[$level][$id])) return '';

    $chain = array();
    if($level === 'list') $chain = array($indexes['list'][$id]);
    elseif($level === 'cat')
    {
        $listId = (int)($indexes['cat'][$id]['id_list'] ?? 0);
        if(!isset($indexes['list'][$listId])) return '';
        $chain = array($indexes['list'][$listId], $indexes['cat'][$id]);
    }
    elseif($level === 'item')
    {
        $catId = (int)($indexes['item'][$id]['id_cat'] ?? 0);
        $listId = (int)($indexes['cat'][$catId]['id_list'] ?? 0);
        if(!isset($indexes['cat'][$catId]) || !isset($indexes['list'][$listId])) return '';
        $chain = array($indexes['list'][$listId], $indexes['cat'][$catId], $indexes['item'][$id]);
    }
    elseif($level === 'sub')
    {
        $itemId = (int)($indexes['sub'][$id]['id_item'] ?? 0);
        $catId = (int)($indexes['item'][$itemId]['id_cat'] ?? 0);
        $listId = (int)($indexes['cat'][$catId]['id_list'] ?? 0);
        if(!isset($indexes['item'][$itemId]) || !isset($indexes['cat'][$catId]) || !isset($indexes['list'][$listId])) return '';
        $chain = array($indexes['list'][$listId], $indexes['cat'][$catId], $indexes['item'][$itemId], $indexes['sub'][$id]);
    }
    else return '';

    $parts = array('danh-muc-san-pham');
    foreach($chain as $ancestor)
    {
        $slug = ptsPublicNavSlug($ancestor, $lang);
        if($slug === '') return '';
        $parts[] = $slug;
    }
    return implode('/', $parts).'/';
}

function ptsPublicNavProductTree(array $lists, array $cats, array $items, array $subs, $lang = 'vi')
{
    $indexes = ptsPublicNavProductIndexes($lists, $cats, $items, $subs);
    $byList = $byCat = $byItem = array();
    foreach($cats as $row) if(is_array($row) && ptsPublicNavPublished($row)) $byList[(int)$row['id_list']][] = $row;
    foreach($items as $row) if(is_array($row) && ptsPublicNavPublished($row)) $byCat[(int)$row['id_cat']][] = $row;
    foreach($subs as $row) if(is_array($row) && ptsPublicNavPublished($row)) $byItem[(int)$row['id_item']][] = $row;
    $tree = array();
    foreach($lists as $list) {
        if(!is_array($list) || !ptsPublicNavPublished($list)) continue;
        $listId = (int)$list['id'];
        $listPath = ptsPublicNavProductPath($list, 'list', $indexes, $lang);
        $listNode = array('id' => $listId, 'name' => ptsPublicNavName($list, $lang), 'slug' => ptsPublicNavSlug($list, $lang), 'url' => ptsPublicNavUrl($listPath), 'children' => array());
        if($listNode['id'] <= 0 || $listNode['name'] === '' || $listNode['slug'] === '') continue;
        foreach(isset($byList[$listId]) ? $byList[$listId] : array() as $cat) {
            $catPath = ptsPublicNavProductPath($cat, 'cat', $indexes, $lang);
            $catNode = array('id' => (int)$cat['id'], 'name' => ptsPublicNavName($cat, $lang), 'slug' => ptsPublicNavSlug($cat, $lang), 'url' => ptsPublicNavUrl($catPath), 'children' => array());
            if($catNode['id'] <= 0 || $catNode['name'] === '' || $catNode['slug'] === '') continue;
            foreach(isset($byCat[$catNode['id']]) ? $byCat[$catNode['id']] : array() as $item) {
                $itemPath = ptsPublicNavProductPath($item, 'item', $indexes, $lang);
                $itemNode = array('id' => (int)$item['id'], 'name' => ptsPublicNavName($item, $lang), 'slug' => ptsPublicNavSlug($item, $lang), 'url' => ptsPublicNavUrl($itemPath), 'children' => array());
                if($itemNode['id'] <= 0 || $itemNode['name'] === '' || $itemNode['slug'] === '') continue;
                foreach(isset($byItem[$itemNode['id']]) ? $byItem[$itemNode['id']] : array() as $sub) {
                    $subPath = ptsPublicNavProductPath($sub, 'sub', $indexes, $lang);
                    $subNode = array('id' => (int)$sub['id'], 'name' => ptsPublicNavName($sub, $lang), 'slug' => ptsPublicNavSlug($sub, $lang), 'url' => ptsPublicNavUrl($subPath), 'children' => array());
                    if($subNode['id'] > 0 && $subNode['name'] !== '' && $subNode['slug'] !== '') $itemNode['children'][] = $subNode;
                }
                $catNode['children'][] = $itemNode;
            }
            $listNode['children'][] = $catNode;
        }
        $tree[] = $listNode;
    }
    return $tree;
}

function ptsPublicNavLink($label, $href)
{
    $label = ptsPublicNavText($label);
    $href = ptsPublicNavUrl($href);
    return ($label === '' || $href === '') ? array() : array('label' => $label, 'href' => $href);
}

function ptsPublicSiteNavigationPayload(array $input)
{
    $lang = 'vi';
    $lists = isset($input['productLists']) && is_array($input['productLists']) ? $input['productLists'] : array();
    $cats = isset($input['productCats']) && is_array($input['productCats']) ? $input['productCats'] : array();
    $items = isset($input['productItems']) && is_array($input['productItems']) ? $input['productItems'] : array();
    $subs = isset($input['productSubs']) && is_array($input['productSubs']) ? $input['productSubs'] : array();
    $tree = ptsPublicNavProductTree($lists, $cats, $items, $subs, $lang);
    $productIndexes = ptsPublicNavProductIndexes($lists, $cats, $items, $subs);
    $listBySlug = array();
    foreach($lists as $row) if(is_array($row) && ptsPublicNavPublished($row)) $listBySlug[ptsPublicNavSlug($row, $lang)] = $row;

    $catalogSlots = array(
        array('group'=>'lighting','label'=>'Đèn pha năng lượng mặt trời','list'=>'den-nlmt','cat'=>'den-pha-nang-luong-mat-troi'),
        array('group'=>'lighting','label'=>'Đèn đường năng lượng mặt trời','list'=>'den-nlmt','cat'=>'den-duong'),
        array('group'=>'lighting','label'=>'Đèn sân vườn','list'=>'den-nlmt','cat'=>'den-san-vuon'),
        array('group'=>'lighting','label'=>'Đèn UFO năng lượng mặt trời','list'=>'den-nlmt','cat'=>'den-ufo'),
        array('group'=>'lighting','label'=>'Đèn ốp trần','list'=>'den-nlmt','cat'=>'den-op-tran'),
        array('group'=>'solar','label'=>'Tấm pin năng lượng mặt trời','list'=>'tam-pin-nang-luong-mat-troi'),
        array('group'=>'solar','label'=>'Biến tần / Inverter','list'=>'bien-tan-inverter'),
        array('group'=>'solar','label'=>'Pin lưu trữ','list'=>'pin-luu-tru'),
        array('group'=>'solar','label'=>'Thiết bị & phụ kiện','list'=>'thiet-bi-dien-nang-luong-mat-troi'),
        array('group'=>'devices','label'=>'Bơm nước năng lượng mặt trời','list'=>'bien-tan-bom'),
        array('group'=>'devices','label'=>'Quạt năng lượng mặt trời','list'=>'quat-nang-luong-mat-troi')
    );
    $catalog = array('lighting'=>array('label'=>'Chiếu sáng','href'=>'','items'=>array()),'solar'=>array('label'=>'Điện mặt trời','href'=>'','items'=>array()),'devices'=>array('label'=>'Thiết bị ứng dụng','href'=>'','items'=>array()));
    foreach($catalogSlots as $slot) {
        if(!isset($listBySlug[$slot['list']])) continue;
        $list = $listBySlug[$slot['list']];
        $href = isset($slot['cat']) ? '' : ptsPublicNavProductPath($list, 'list', $productIndexes, $lang);
        $children = array();
        foreach($cats as $cat) {
            if(!is_array($cat) || !ptsPublicNavPublished($cat) || (int)$cat['id_list'] !== (int)$list['id']) continue;
            if(isset($slot['cat'])) {
                if(ptsPublicNavSlug($cat, $lang) !== $slot['cat']) continue;
                $href = ptsPublicNavProductPath($cat, 'cat', $productIndexes, $lang);
                foreach($items as $item) {
                    if(is_array($item) && ptsPublicNavPublished($item) && (int)$item['id_cat'] === (int)$cat['id']) {
                        $children[] = ptsPublicNavLink(ptsPublicNavName($item, $lang), ptsPublicNavProductPath($item, 'item', $productIndexes, $lang));
                    }
                }
                break;
            }
            $children[] = ptsPublicNavLink(ptsPublicNavName($cat, $lang), ptsPublicNavProductPath($cat, 'cat', $productIndexes, $lang));
        }
        if($href === '') continue; // Legacy menu omits unpublished/missing target category.
        $catalog[$slot['group']]['href'] = ptsPublicNavUrl($catalog[$slot['group']]['href'] ?: $href);
        $catalog[$slot['group']]['items'][] = array('label'=>$slot['label'],'href'=>ptsPublicNavUrl($href),'children'=>array_values(array_filter($children)));
    }

    $about = array_filter(array_map('ptsPublicNavLink', array('Về Phúc Thịnh Solar','Lịch sử phát triển','Tầm nhìn - Sứ mệnh','Năng lực & chứng nhận','Hệ thống chi nhánh','Liên hệ'), array('gioi-thieu/#ve-phuc-thinh','gioi-thieu/#lich-su-phat-trien','gioi-thieu/#tam-nhin-su-menh','gioi-thieu/#about-certificates','lien-he/#he-thong-chi-nhanh','lien-he/#yeu-cau-tu-van')));
    $nav = array(array('label'=>'Danh mục sản phẩm','href'=>'/san-pham/','children'=>array()),array('label'=>'Giới thiệu','href'=>'/gioi-thieu/','children'=>array_values($about)),array('label'=>'Giải pháp','href'=>'/giai-phap/','children'=>array()),array('label'=>'Dự án','href'=>'/du-an-da-thuc-hien/','children'=>array()),array('label'=>'Kiến thức','href'=>'/tin-tuc/','children'=>array()),array('label'=>'Hợp tác','href'=>'/dai-ly/','children'=>array()));
    $solutionRows = isset($input['solutionRows']) && is_array($input['solutionRows']) ? $input['solutionRows'] : array();
    $hasSolutionSelection = false;
    foreach($solutionRows as $row) if(is_array($row) && in_array('solutionhub', explode(',', ptsPublicNavScalar(isset($row['status']) ? $row['status'] : '')), true)) $hasSolutionSelection = true;
    $solutionLinks = array(ptsPublicNavLink('Tất cả giải pháp', '/giai-phap/'));
    foreach($solutionRows as $row) {
        if(!is_array($row) || !in_array('hienthi', explode(',', ptsPublicNavScalar(isset($row['status']) ? $row['status'] : '')), true)) continue;
        if($hasSolutionSelection && !in_array('solutionhub', explode(',', ptsPublicNavScalar($row['status'])), true)) continue;
        $label = ptsPublicNavText(isset($row['title_indexvi']) && $row['title_indexvi'] !== '' ? $row['title_indexvi'] : (isset($row['namevi']) ? $row['namevi'] : ''));
        $link = ptsPublicNavLink($label, 'giai-phap/'.ptsPublicNavSlug($row).'/'); if($link) $solutionLinks[] = $link;
    }
    $nav[2]['children'] = array_values(array_filter($solutionLinks));
    $newsLists = isset($input['newsLists']) && is_array($input['newsLists']) ? $input['newsLists'] : array();
    $newsCats = isset($input['newsCats']) && is_array($input['newsCats']) ? $input['newsCats'] : array();
    $newsItems = isset($input['newsItems']) && is_array($input['newsItems']) ? $input['newsItems'] : array();
    $newsSubs = isset($input['newsSubs']) && is_array($input['newsSubs']) ? $input['newsSubs'] : array();
    $newsChildren = array(ptsPublicNavLink('Tất cả bài viết', '/tin-tuc/'));
    foreach($newsLists as $list) {
        if(!is_array($list) || !ptsPublicNavPublished($list)) continue;
        $listLink = ptsPublicNavLink(ptsPublicNavName($list), 'category/'.ptsPublicNavSlug($list).'/'); if(!$listLink) continue;
        $listLink['children'] = array();
        foreach($newsCats as $cat) if(is_array($cat) && ptsPublicNavPublished($cat) && (int)$cat['id_list'] === (int)$list['id']) {
            $catLink = ptsPublicNavLink(ptsPublicNavName($cat), ptsPublicNavSlug($cat)); if(!$catLink) continue;
            $catLink['children'] = array();
            foreach($newsItems as $item) if(is_array($item) && ptsPublicNavPublished($item) && (int)$item['id_cat'] === (int)$cat['id']) {
                $itemLink = ptsPublicNavLink(ptsPublicNavName($item), ptsPublicNavSlug($item)); if(!$itemLink) continue;
                $itemLink['children'] = array();
                foreach($newsSubs as $sub) if(is_array($sub) && ptsPublicNavPublished($sub) && (int)$sub['id_item'] === (int)$item['id']) { $subLink = ptsPublicNavLink(ptsPublicNavName($sub), ptsPublicNavSlug($sub)); if($subLink) $itemLink['children'][] = $subLink; }
                $catLink['children'][] = $itemLink;
            }
            $listLink['children'][] = $catLink;
        }
        $newsChildren[] = $listLink;
    }
    $nav[4]['children'] = array_values(array_filter($newsChildren));
    $options = isset($input['options']) && is_array($input['options']) ? $input['options'] : array();
    $setting = isset($input['setting']) && is_array($input['setting']) ? $input['setting'] : array();
    $brandName = ptsPublicNavText(isset($setting['namevi']) ? $setting['namevi'] : '') ?: 'Phúc Thịnh Solar';
    $footer = isset($input['footer']) && is_array($input['footer']) ? $input['footer'] : array();
    $footerLinks = array(); foreach(isset($input['policy']) && is_array($input['policy']) ? $input['policy'] : array() as $row) { if(!is_array($row) || !ptsPublicNavPublished($row)) continue; $link = ptsPublicNavLink(ptsPublicNavName($row), 'ho-tro/'.ptsPublicNavSlug($row).'/'); if($link) $footerLinks[] = $link; }
    $locations = array(); foreach(isset($input['locations']) && is_array($input['locations']) ? $input['locations'] : array() as $row) { if(!is_array($row) || !ptsPublicNavPublished($row)) continue; $link = ptsPublicNavUrl(isset($row['ggmap']) ? $row['ggmap'] : ''); $name = ptsPublicNavName($row); $address = ptsPublicNavText(isset($row['address']) ? $row['address'] : ''); if($name !== '' && $address !== '') $locations[] = array('name'=>$name,'address'=>$address,'mapUrl'=>$link); }
    $social = array(); foreach(array('fanpage'=>'Facebook','youtube'=>'YouTube') as $key=>$label) { $link=ptsPublicNavUrl(isset($options[$key])?$options[$key]:''); if($link !== '') $social[] = array('label'=>$label,'href'=>$link); }
    $asset = isset($input['asset']) && is_array($input['asset']) ? $input['asset'] : array();
    $brand = array('name'=>$brandName,'hotline'=>ptsPublicNavText(isset($options['hotline'])?$options['hotline']:''),'logo'=>isset($asset['logo'])?$asset['logo']:'','logoFooter'=>isset($asset['logoFooter'])?$asset['logoFooter']:'');
    $technicalHotline = ptsPublicNavText(isset($options['hotline_technical']) ? $options['hotline_technical'] : '');
    if($technicalHotline !== '') $brand['technicalHotline'] = $technicalHotline;
    $footerDto = array('companyName'=>ptsPublicNavName($footer),'contactText'=>ptsPublicNavText(isset($footer['contentvi'])?$footer['contentvi']:''),'policies'=>$footerLinks,'locations'=>$locations,'socialLinks'=>$social);
    $trustBadge = isset($asset['trustBadge']) && is_array($asset['trustBadge']) ? $asset['trustBadge'] : array();
    $trustBadgeImage = isset($trustBadge['image']) ? ptsPublicNavUrl($trustBadge['image']) : '';
    $trustBadgeHref = isset($trustBadge['href']) ? ptsPublicNavUrl($trustBadge['href']) : '';
    if($trustBadgeImage !== '' && $trustBadgeHref !== '') $footerDto['trustBadge'] = array('image'=>$trustBadgeImage,'href'=>$trustBadgeHref);
    return array('navigationSchemaVersion'=>1,'locale'=>'vi','brand'=>$brand,'navigation'=>$nav,'catalog'=>$catalog,'productCategoryTree'=>$tree,'footer'=>$footerDto);
}
