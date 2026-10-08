<?php 
    $list_menu_all = $d->rawQuery("select name, id, type,display_order from #_sortable where status = 1 order by display_order desc");
    foreach ($list_menu_all as $key_mn => $value_mn){ 
        $order_mn['type'][$value_mn['type']] = $value_mn['display_order'];    
    }
 ?>
<?php
    $menuCatalogEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
    /* Dùng chung icon SVG nét mảnh cho mega menu desktop và danh mục mobile. */
    $menuCatalogIconSvg = array(
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>',
        'light' => '<path d="M7 3h10l3 13H4L7 3Zm3 14v2a2 2 0 0 0 4 0v-2M6 7h12"/>',
        'street' => '<path d="M8 22V7l8-3m-9 4 1-3 7-2 4 2-1 2-6 1M5 22h6"/>',
        'flood' => '<rect x="4" y="3" width="16" height="13" rx="2"/><rect x="7" y="6" width="10" height="7" rx="1"/><path d="m8 17-2 4h12l-2-4"/>',
        'ufo' => '<path d="M9 3h6v3l3 5H6l3-5V3Zm-3 8h12l2 4H4l2-4Zm2 7h8M10 15v3m4-3v3"/>',
        'ceiling' => '<ellipse cx="12" cy="10" rx="9" ry="5"/><path d="M3 10v3c0 7 18 7 18 0v-3"/>',
        'garden' => '<path d="m4 7 8-4 8 4H4Zm2 1 2 9h8l2-9M8 10h8M12 8v13M8 22h8"/>',
        'panel' => '<path d="m5 3-3 13h20L19 3H5ZM3 10h18M4 6h16M9 3 8 16m7-13 1 13M12 16v5m-5 0h10"/>',
        'inverter' => '<rect x="5" y="2" width="14" height="20" rx="2"/><rect x="8" y="5" width="8" height="5" rx="1"/><path d="m13 12-3 4h4l-3 4"/>',
        'battery' => '<path d="M9 3V1h6v2"/><rect x="5" y="3" width="14" height="19" rx="2"/><path d="m13 7-4 6h6l-4 5"/>',
        'storage' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 12h8M8 17h5"/><circle cx="17" cy="17" r="1"/>',
        'rack' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 12h8M8 17h8M6 7h.01M6 12h.01M6 17h.01"/>',
        'pump' => '<path d="M7 5h10v15H7V5Zm3 0V2h4v3m-4 15v2m4-2v2M7 14H3v-4h4m10 4h4v5"/><circle cx="12" cy="12" r="2"/>',
        'pump-controller' => '<path d="M12 2s6 7 6 12a6 6 0 0 1-12 0c0-5 6-12 6-12Z"/><path d="m13 8-3 5h3l-1 4 3-5h-3l1-4Z"/>',
        'fan' => '<circle cx="12" cy="10" r="8"/><circle cx="12" cy="10" r="1.5"/><path d="M12 8c-5-8-8 2-2 2m4 0c9 0 3-9 0-2m-2 4c-3 8 7 6 2 0M12 18v4m-5 0h10"/>',
        'controller' => '<rect x="4" y="3" width="16" height="18" rx="2"/><rect x="7" y="6" width="10" height="4" rx="1"/><path d="M8 14h2m2 0h2m2 0h2M8 18h8"/>',
        'controller-mppt' => '<rect x="4" y="3" width="16" height="18" rx="2"/><rect x="7" y="6" width="10" height="4" rx="1"/><path d="m9 16 2-3 2 3 2-3"/>',
        'controller-pwm' => '<rect x="4" y="3" width="16" height="18" rx="2"/><rect x="7" y="6" width="10" height="4" rx="1"/><path d="M8 16h2l1-3 2 5 2-3h1"/>',
        'cable' => '<path d="M4 5h5v5H4zM15 14h5v5h-5zM9 7h3a4 4 0 0 1 4 4v3"/><path d="M6 10v3m12 1v-3"/>',
        'clamp' => '<path d="M4 5h7v7H4zM13 12h7v7h-7zM11 8h2a4 4 0 0 1 4 4v1"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4m-6-7 3-3 2 2 4-4"/>',
        'accessory' => '<path d="m13 2-9 12h7l-1 8 9-12h-7l1-8Z"/>',
        'ground' => '<path d="M12 3v10m-7 0h14M7 17h10m-5-4v8M3 9h18"/>',
        'supply' => '<path d="M4 7h16v13H4zM7 4h10v3M8 11h8M8 15h5"/>',
        'pole' => '<path d="M10 22V7l8-3m-8 4h8l3 3H10M5 22h12"/>',
        'decorative-pole' => '<path d="M8 6h8l2 4H6l2-4Zm2-3h4v3m-5 4v7m6-7v7M7 17h10m-8 0v4m4-4v4M5 22h14"/>',
        'mount' => '<path d="M12 3v12m-5 0h10m-8 0-2 6m8-6 2 6M5 21h14"/>',
        'base' => '<path d="M12 4v12m-5 0h10M8 16l-3 5m11-5 3 5M4 21h16M9 4h6"/>',
        'gate-lamp' => '<path d="M7 6h10l2 6H5l2-6Zm2 0V3h6v3M8 12v7m8-7v7M6 19h12"/>',
        'wall-lamp' => '<path d="M5 3v18m0-14h7a4 4 0 0 1 0 8H5m7-8h5v8h-5"/>',
        'bollard' => '<rect x="8" y="8" width="8" height="13" rx="1"/><path d="M6 8h12M9 4h6l1 4H8l1-4M5 21h14"/>',
        'spike-light' => '<path d="M8 3h8l2 5H6l2-5Zm4 5v9m-3 0h6m-5 0-2 5m6-5 2 5"/>',
        'tools' => '<path d="m14 5 4-3 1 5 3 2-3 4-4-1L6 21l-3-3 9-9-1-4 3-3Z"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M10 2h4l.7 2.4a8 8 0 0 1 1.5.9l2.4-.7 2 3.5-1.8 1.7a8 8 0 0 1 0 1.8l1.8 1.7-2 3.5-2.4-.7a8 8 0 0 1-1.5.9L14 22h-4l-.7-2.4a8 8 0 0 1-1.5-.9l-2.4.7-2-3.5 1.8-1.7a8 8 0 0 1 0-1.8L3.4 10.7l2-3.5 2.4.7a8 8 0 0 1 1.5-.9L10 2Z"/>',
        'book' => '<path d="M12 6c-2-2-6-3-9-2v15c3-1 7 0 9 2m0-15c2-2 6-3 9-2v15c-3-1-7 0-9 2M12 6v15"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5.5a3 3 0 0 1 0 5.8m2 2.2a5 5 0 0 1 3 4.5V20"/>',
        'headset' => '<path d="M4 13v-3a8 8 0 0 1 16 0v3M4 12H2v7h4v-7H4Zm16 0h2v7h-4v-7h2ZM19 19v2h-7"/>'
    );
    $menuCatalogRenderIcon = static function($name, $class = 'pts-catalog-icon') use ($menuCatalogIconSvg) {
        if(!isset($menuCatalogIconSvg[$name])) return '';
        return '<svg class="'.$class.'" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$menuCatalogIconSvg[$name].'</svg>';
    };
    $menuCatalogGroups = array(
        'lighting' => array('title' => 'Chiếu sáng', 'subtitle' => 'Đèn năng lượng mặt trời', 'icon' => 'light', 'href' => '/danh-muc-san-pham/den-nlmt/', 'items' => array()),
        'solar' => array('title' => 'Điện mặt trời', 'subtitle' => '', 'icon' => 'panel', 'href' => '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/', 'items' => array()),
        'devices' => array('title' => 'Thiết bị ứng dụng', 'subtitle' => '', 'icon' => 'cog', 'href' => '/danh-muc-san-pham/bien-tan-bom/', 'items' => array())
    );
    $menuCatalogListsBySlug = array();
    foreach((array)$splist as $menuCatalogList) {
        if(!empty($menuCatalogList['slugvi'])) $menuCatalogListsBySlug[$menuCatalogList['slugvi']] = $menuCatalogList;
    }
    $menuCatalogSlots = array(
        array('group'=>'lighting', 'list'=>'den-nlmt', 'cat'=>'den-pha-nang-luong-mat-troi', 'label'=>'Đèn pha năng lượng mặt trời', 'icon'=>'flood'),
        array('group'=>'lighting', 'list'=>'den-nlmt', 'cat'=>'den-duong', 'label'=>'Đèn đường năng lượng mặt trời', 'icon'=>'street'),
        array('group'=>'lighting', 'list'=>'den-nlmt', 'cat'=>'den-san-vuon', 'label'=>'Đèn sân vườn', 'icon'=>'garden'),
        array('group'=>'lighting', 'list'=>'den-nlmt', 'cat'=>'den-ufo', 'label'=>'Đèn UFO năng lượng mặt trời', 'icon'=>'ufo'),
        array('group'=>'lighting', 'list'=>'den-nlmt', 'cat'=>'den-op-tran', 'label'=>'Đèn ốp trần', 'icon'=>'ceiling'),
        array('group'=>'solar', 'list'=>'tam-pin-nang-luong-mat-troi', 'label'=>'Tấm pin năng lượng mặt trời', 'icon'=>'panel'),
        array('group'=>'solar', 'list'=>'bien-tan-inverter', 'label'=>'Biến tần / Inverter', 'icon'=>'inverter'),
        array('group'=>'solar', 'list'=>'pin-luu-tru', 'label'=>'Pin lưu trữ', 'icon'=>'battery'),
        array('group'=>'solar', 'list'=>'thiet-bi-dien-nang-luong-mat-troi', 'label'=>'Thiết bị & phụ kiện', 'icon'=>'cog'),
        array('group'=>'devices', 'list'=>'bien-tan-bom', 'label'=>'Bơm nước năng lượng mặt trời', 'icon'=>'pump'),
        array('group'=>'devices', 'list'=>'quat-nang-luong-mat-troi', 'label'=>'Quạt năng lượng mặt trời', 'icon'=>'fan')
    );
    foreach($menuCatalogSlots as $menuCatalogSlot) {
        if(empty($menuCatalogListsBySlug[$menuCatalogSlot['list']])) continue;
        $menuCatalogList = $menuCatalogListsBySlug[$menuCatalogSlot['list']];
        $menuCatalogHref = '/danh-muc-san-pham/'.$menuCatalogList[$sluglang].'/';
        $menuCatalogSearch = $menuCatalogSlot['label'];
        $menuCatalogChildren = array();
        if(!empty($menuCatalogSlot['cat'])) {
            $menuCatalogHref = '';
            foreach((array)(isset($productMenuCats[(int)$menuCatalogList['id']]) ? $productMenuCats[(int)$menuCatalogList['id']] : array()) as $menuCatalogCat) {
                if(isset($menuCatalogCat['slugvi']) && $menuCatalogCat['slugvi'] === $menuCatalogSlot['cat']) {
                    $menuCatalogHref = publicProductCategoryPath($menuCatalogCat, 'cat', $lang);
                    $menuCatalogSearch .= ' '.$menuCatalogCat['name'.$lang];
                    foreach((array)(isset($productMenuItems[(int)$menuCatalogCat['id']]) ? $productMenuItems[(int)$menuCatalogCat['id']] : array()) as $menuCatalogChild) {
                        $menuCatalogChildren[] = array('label' => $menuCatalogChild['name'.$lang], 'href' => publicProductCategoryPath($menuCatalogChild, 'item', $lang));
                        $menuCatalogSearch .= ' '.$menuCatalogChild['name'.$lang];
                    }
                    break;
                }
            }
            if($menuCatalogHref === '') continue;
        } else {
            $menuCatalogSearch .= ' '.$menuCatalogList['name'.$lang];
            foreach((array)(isset($productMenuCats[(int)$menuCatalogList['id']]) ? $productMenuCats[(int)$menuCatalogList['id']] : array()) as $menuCatalogCat) {
                $menuCatalogSearch .= ' '.$menuCatalogCat['name'.$lang];
                $menuCatalogChildren[] = array('label' => $menuCatalogCat['name'.$lang], 'href' => publicProductCategoryPath($menuCatalogCat, 'cat', $lang));
            }
        }
        $menuCatalogGroups[$menuCatalogSlot['group']]['items'][] = array(
            'label' => $menuCatalogSlot['label'],
            'href' => $menuCatalogHref,
            'icon' => $menuCatalogSlot['icon'],
            'search' => $menuCatalogSearch,
            'children' => $menuCatalogChildren
        );
    }
?>
<div class="menu">
    <div class="">
        <ul class="d-flex align-items-center justify-content-between">
            
            <li class="menu-catalog">
                <a class="menu-catalog-trigger <?php if($com=='san-pham') echo 'active'; ?> transition" href="/san-pham/" title="Danh mục sản phẩm" aria-expanded="false" aria-controls="ptsCatalogOverlay">
                    <i class="fas fa-bars" aria-hidden="true"></i><span>Danh mục sản phẩm</span><i class="fas fa-chevron-down menu-catalog-chevron" aria-hidden="true"></i>
                </a>
            </li>

            <li class="menu-about">
                <a class="has-child <?php if($com=='gioi-thieu' || $com=='ve-chung-toi' || $com=='lien-he') echo 'active'; ?> transition" href="gioi-thieu/" title="Giới thiệu">Giới thiệu</a>
                <ul>
                    <li><a class="transition" href="gioi-thieu/#ve-phuc-thinh" title="Về Phúc Thịnh Solar">Về Phúc Thịnh Solar</a></li>
                    <li><a class="transition" href="gioi-thieu/#lich-su-phat-trien" title="Lịch sử phát triển">Lịch sử phát triển</a></li>
                    <li><a class="transition" href="gioi-thieu/#tam-nhin-su-menh" title="Tầm nhìn - Sứ mệnh">Tầm nhìn - Sứ mệnh</a></li>
                    <li><a class="transition" href="gioi-thieu/#about-certificates" title="Năng lực và chứng nhận">Năng lực &amp; chứng nhận</a></li>
                    <li><a class="transition" href="lien-he/#he-thong-chi-nhanh" title="Hệ thống chi nhánh">Hệ thống chi nhánh</a></li>
                    <li><a class="transition" href="lien-he/#yeu-cau-tu-van" title="Liên hệ">Liên hệ</a></li>
                </ul>
            </li>

            <li>
                <a class="has-child <?php if($com=='giai-phap') echo 'active'; ?> transition" href="/giai-phap/" title="Giải pháp">Giải pháp</a>
                <?php if(count($solutionMenuLinks)) { ?>
                    <ul>
                        <li><a class="transition" title="Tất cả giải pháp" href="/giai-phap/">Tất cả giải pháp</a></li>
                        <?php foreach($solutionMenuLinks as $solutionMenuLink) {
                            $solutionMenuHref = 'giai-phap/'.$solutionMenuLink[$sluglang].'/';
                            $solutionMenuLabel = !empty($solutionMenuLink['title_index'.$lang]) ? $solutionMenuLink['title_index'.$lang] : $solutionMenuLink['name'.$lang]; ?>
                            <li>
                                <a class="transition" title="<?=$solutionMenuLink['name'.$lang]?>" href="<?=$solutionMenuHref?>"><?=$solutionMenuLabel?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </li>

            <li><a class="<?php if($com=='du-an' || $com=='du-an-da-thuc-hien') echo 'active'; ?> transition" href="/du-an-da-thuc-hien/" title="Dự án">Dự án</a></li>
     
            <li class="menu-knowledge">
                <a class="has-child <?php if($com=='tin-tuc') echo 'active'; ?> transition" href="/tin-tuc/" title="Kiến thức">Kiến thức</a>
                <?php if(count($ttlist)) { ?>
                    <ul>
                        <li><a class="transition" title="Tất cả bài viết" href="/tin-tuc/">Tất cả bài viết</a></li>
                        <?php foreach($ttlist as $klist => $vlist) {
                            $ttcat = isset($newsMenuCats[(int)$vlist['id']]) ? $newsMenuCats[(int)$vlist['id']] : array(); ?>
                            <li>
                                <a class="<?php if(!empty($ttcat)) echo 'has-child '; ?>transition" title="<?=$vlist['name'.$lang]?>" href="category/<?=$vlist[$sluglang]?>/"><?=$vlist['name'.$lang]?></a>
                                <?php if(!empty($ttcat)) { ?>
                                    <ul>
                                        <?php foreach($ttcat as $kcat => $vcat) {
                                            $ttitem = isset($newsMenuItems[(int)$vcat['id']]) ? $newsMenuItems[(int)$vcat['id']] : array(); ?>
                                            <li>
                                                <a class="has-child transition" title="<?=$vcat['name'.$lang]?>" href="<?=$vcat[$sluglang]?>"><?=$vcat['name'.$lang]?></a>
                                                <?php if(!empty($ttitem)) { ?>
                                                    <ul>
                                                        <?php foreach($ttitem as $kitem => $vitem) {
                                                            $ttsub = isset($newsMenuSubs[(int)$vitem['id']]) ? $newsMenuSubs[(int)$vitem['id']] : array(); ?>
                                                            <li>
                                                                <a class="has-child transition" title="<?=$vitem['name'.$lang]?>" href="<?=$vitem[$sluglang]?>"><?=$vitem['name'.$lang]?></a>
                                                                <?php if(!empty($ttsub)) { ?>
                                                                    <ul>
                                                                        <?php foreach($ttsub as $ksub => $vsub) { ?>
                                                                            <li>
                                                                                <a class="transition" title="<?=$vsub['name'.$lang]?>" href="<?=$vsub[$sluglang]?>"><?=$vsub['name'.$lang]?></a>
                                                                            </li>
                                                                        <?php } ?>
                                                                    </ul>
                                                                <?php } ?>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </li>

            <li class="menu-cooperation">
                <a class="has-child <?php if($com=='dai-ly') echo 'active'; ?> transition" href="dai-ly" title="Hợp tác">Hợp tác</a>
                <ul>
                    <li><a class="transition" href="dai-ly#dang-ky-dai-ly" title="Đăng ký đại lý">Đăng ký đại lý</a></li>
                    <li><a class="transition" href="dai-ly#nang-luc-phan-phoi" title="Năng lực phân phối">Năng lực phân phối</a></li>
                    <li><a class="transition" href="dai-ly#ho-tro-dai-ly" title="Chính sách và hỗ trợ đại lý">Chính sách &amp; hỗ trợ đại lý</a></li>
                    <li><a class="transition" href="lien-he/#yeu-cau-tu-van" title="Liên hệ kinh doanh">Liên hệ kinh doanh</a></li>
                </ul>
            </li>

        </ul>
    </div>
</div>

<?php /*

<div class="menu">
    <div class="wrap-content">
        <ul class="d-flex align-items-center justify-content-between">
            <li><a class="<?php if($com=='' || $com=='index') echo 'active'; ?> transition" href="" title="<?=trangchu?>"><?=trangchu?></a></li>
            <li class="line"></li>
            <li><a class="<?php if($com=='gioi-thieu') echo 'active'; ?> transition" href="gioi-thieu" title="<?=gioithieu?>"><?=gioithieu?></a></li>
            <li class="line"></li>
            <li>
                <a class="has-child <?php if($com=='san-pham') echo 'active'; ?> transition" href="san-pham" title="<?=sanpham?>"><?=sanpham?></a>
                <?php if(count($splist)) { ?>
                    <ul>
                        <?php foreach($splist as $klist => $vlist) {
                            $spcat = $d->rawQuery("select name$lang, slugvi, slugen, id from #_product_cat where id_list = ? and find_in_set('hienthi',status) order by numb,id desc",array($vlist['id'])); ?>
                            <li>
                                <a class="has-child transition" title="<?=$vlist['name'.$lang]?>" href="<?=$vlist[$sluglang]?>"><?=$vlist['name'.$lang]?></a>
                                <?php if(!empty($spcat)) { ?>
                                    <ul>
                                        <?php foreach($spcat as $kcat => $vcat) {
                                            $spitem = $d->rawQuery("select name$lang, slugvi, slugen, id from #_product_item where id_cat = ? and find_in_set('hienthi',status) order by numb,id desc",array($vcat['id'])); ?>
                                            <li>
                                                <a class="has-child transition" title="<?=$vcat['name'.$lang]?>" href="<?=$vcat[$sluglang]?>"><?=$vcat['name'.$lang]?></a>
                                                <?php if(!empty($spitem)) { ?>
                                                    <ul>
                                                        <?php foreach($spitem as $kitem => $vitem) {
                                                            $spsub = $d->rawQuery("select name$lang, slugvi, slugen, id from #_product_sub where id_item = ? and find_in_set('hienthi',status) order by numb,id desc",array($vitem['id'])); ?>
                                                            <li>
                                                                <a class="has-child transition" title="<?=$vitem['name'.$lang]?>" href="<?=$vitem[$sluglang]?>"><?=$vitem['name'.$lang]?></a>
                                                                <?php if(!empty($spsub)) { ?>
                                                                    <ul>
                                                                        <?php foreach($spsub as $ksub => $vsub) { ?>
                                                                            <li>
                                                                                <a class="transition" title="<?=$vsub['name'.$lang]?>" href="<?=$vsub[$sluglang]?>"><?=$vsub['name'.$lang]?></a>
                                                                            </li>
                                                                        <?php } ?>
                                                                    </ul>
                                                                <?php } ?>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </li>
            <li class="line"></li>
            <li>
                <a class="has-child <?php if($com=='tin-tuc') echo 'active'; ?> transition" href="tin-tuc" title="<?=tintuc?>"><?=tintuc?></a>
                <?php if(count($ttlist)) { ?>
                    <ul>
                        <?php foreach($ttlist as $klist => $vlist) {
                            $ttcat = $d->rawQuery("select name$lang, slugvi, slugen, id from #_news_cat where id_list = ? and find_in_set('hienthi',status) order by numb,id desc",array($vlist['id'])); ?>
                            <li>
                                <a class="has-child transition" title="<?=$vlist['name'.$lang]?>" href="<?=$vlist[$sluglang]?>"><?=$vlist['name'.$lang]?></a>
                                <?php if(!empty($ttcat)) { ?>
                                    <ul>
                                        <?php foreach($ttcat as $kcat => $vcat) {
                                            $ttitem = $d->rawQuery("select name$lang, slugvi, slugen, id from #_news_item where id_cat = ? and find_in_set('hienthi',status) order by numb,id desc",array($vcat['id'])); ?>
                                            <li>
                                                <a class="has-child transition" title="<?=$vcat['name'.$lang]?>" href="<?=$vcat[$sluglang]?>"><?=$vcat['name'.$lang]?></a>
                                                <?php if(!empty($ttitem)) { ?>
                                                    <ul>
                                                        <?php foreach($ttitem as $kitem => $vitem) {
                                                            $ttsub = $d->rawQuery("select name$lang, slugvi, slugen, id from #_news_sub where id_item = ? and find_in_set('hienthi',status) order by numb,id desc",array($vitem['id'])); ?>
                                                            <li>
                                                                <a class="has-child transition" title="<?=$vitem['name'.$lang]?>" href="<?=$vitem[$sluglang]?>"><?=$vitem['name'.$lang]?></a>
                                                                <?php if(!empty($ttsub)) { ?>
                                                                    <ul>
                                                                        <?php foreach($ttsub as $ksub => $vsub) { ?>
                                                                            <li>
                                                                                <a class="transition" title="<?=$vsub['name'.$lang]?>" href="<?=$vsub[$sluglang]?>"><?=$vsub['name'.$lang]?></a>
                                                                            </li>
                                                                        <?php } ?>
                                                                    </ul>
                                                                <?php } ?>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </li>
            <li class="line"></li>
            <li><a class="<?php if($com=='tuyen-dung') echo 'active'; ?> transition" href="tuyen-dung" title="<?=tuyendung?>"><?=tuyendung?></a></li>
            <li class="line"></li>
            <li><a class="<?php if($com=='thu-vien-anh') echo 'active'; ?> transition" href="thu-vien-anh" title="<?=thuvienanh?>"><?=thuvienanh?></a></li>
            <li class="line"></li>
            <li><a class="<?php if($com=='video') echo 'active'; ?> transition" href="video" title="Video">Video</a></li>
            <li class="line"></li>
            <li><a class="<?php if($com=='lien-he') echo 'active'; ?> transition" href="lien-he" title="<?=lienhe?>"><?=lienhe?></a></li>
            <li class="ml-auto">
                <div class="search w-clear">
                    <input type="text" id="keyword" placeholder="<?=nhaptukhoatimkiem?>" onkeypress="doEnter(event,'keyword');"/>
                    <p onclick="onSearch('keyword');"><i class="fas fa-search"></i></p>
                </div>
            </li>
        </ul>
    </div>
</div>
*/ ?>
