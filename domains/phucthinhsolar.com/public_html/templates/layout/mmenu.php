<nav id="menu">
    <ul>
            <li class="mmenu-search-item mm-listitem_nosubitems">
                <label class="hdr-sr-only" for="ptsMobileMenuSearch">Tìm sản phẩm</label>
                <form class="mmenu-search-control" role="search" onsubmit="onSearch('ptsMobileMenuSearch'); return false;">
                    <i class="fas fa-search" aria-hidden="true"></i><input type="search" id="ptsMobileMenuSearch" name="ptsMobileMenuSearch" placeholder="Tìm sản phẩm..." autocomplete="off">
                </form>
            </li>
            <li class="mmenu-product-root">
                <a class="has-child <?php if($com=='san-pham') echo 'active'; ?> transition" href="/san-pham/" title="Danh mục sản phẩm">Danh mục sản phẩm</a>
                <?php if(count($splist)) { ?>
                    <ul>
                        <li><a class="transition" title="Xem tất cả sản phẩm" href="/san-pham/">Xem tất cả sản phẩm</a></li>
                        <?php foreach($splist as $klist => $vlist) {
                            $spcat = isset($productMenuCats[(int)$vlist['id']]) ? $productMenuCats[(int)$vlist['id']] : array(); ?>
                            <?php
                                $mobileCategoryName = mb_strtolower((string)$vlist['name'.$lang], 'UTF-8');
                                $mobileCategorySlug = strtolower((string)$vlist[$sluglang]);
                                $mobileCategoryIcon = 'cog';
                                if (strpos($mobileCategorySlug, 'san-vuon') !== false || strpos($mobileCategoryName, 'sân vườn') !== false) $mobileCategoryIcon = 'garden';
                                elseif (strpos($mobileCategorySlug, 'tru-den') !== false || strpos($mobileCategoryName, 'trụ đèn') !== false) $mobileCategoryIcon = 'street';
                                elseif (strpos($mobileCategorySlug, 'quat') !== false || strpos($mobileCategoryName, 'quạt') !== false) $mobileCategoryIcon = 'fan';
                                elseif (strpos($mobileCategorySlug, 'tam-pin') !== false || strpos($mobileCategoryName, 'tấm pin') !== false) $mobileCategoryIcon = 'panel';
                                elseif (strpos($mobileCategorySlug, 'bom') !== false || strpos($mobileCategoryName, 'bơm') !== false) $mobileCategoryIcon = 'pump';
                                elseif (strpos($mobileCategorySlug, 'bien-tan') !== false || strpos($mobileCategoryName, 'biến tần') !== false || strpos($mobileCategoryName, 'inverter') !== false) $mobileCategoryIcon = 'inverter';
                                elseif (strpos($mobileCategorySlug, 'pin-luu-tru') !== false || strpos($mobileCategoryName, 'pin lưu trữ') !== false) $mobileCategoryIcon = 'battery';
                                elseif (strpos($mobileCategorySlug, 'thiet-bi') !== false || strpos($mobileCategoryName, 'thiết bị') !== false || strpos($mobileCategoryName, 'phụ kiện') !== false) $mobileCategoryIcon = 'cog';
                                elseif (strpos($mobileCategoryName, 'đèn') !== false) $mobileCategoryIcon = 'panel';
                            ?>
                            <li class="pts-mobile-product-category">
                                <a class="transition" title="<?=$vlist['name'.$lang]?>" href="/danh-muc-san-pham/<?=$vlist[$sluglang]?>/">
                                    <?=$menuCatalogRenderIcon($mobileCategoryIcon, 'pts-mobile-menu-icon pts-mobile-menu-icon-svg')?><?=$vlist['name'.$lang]?>
                                </a>
                                <?php if(!empty($spcat)) { ?>
                                    <ul>
                                        <?php foreach($spcat as $kcat => $vcat) {
                                            $spitem = isset($productMenuItems[(int)$vcat['id']]) ? $productMenuItems[(int)$vcat['id']] : array(); ?>
                                            <li>
                                                <a class="transition" title="<?=$vcat['name'.$lang]?>" href="<?=publicProductCategoryPath($vcat,'cat',$lang)?>"><?=$vcat['name'.$lang]?></a>
                                                <?php if(!empty($spitem)) { ?>
                                                    <ul>
                                                        <?php foreach($spitem as $kitem => $vitem) {
                                                            $spsub = isset($productMenuSubs[(int)$vitem['id']]) ? $productMenuSubs[(int)$vitem['id']] : array(); ?>
                                                            <li>
                                                                <a class="transition" title="<?=$vitem['name'.$lang]?>" href="<?=publicProductCategoryPath($vitem,'item',$lang)?>"><?=$vitem['name'.$lang]?></a>
                                                                <?php if(!empty($spsub)) { ?>
                                                                    <ul>
                                                                        <?php foreach($spsub as $ksub => $vsub) { ?>
                                                                            <li>
                                                                                <a class="transition" title="<?=$vsub['name'.$lang]?>" href="<?=publicProductCategoryPath($vsub,'sub',$lang)?>"><?=$vsub['name'.$lang]?></a>
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

            <li class="menu-about">
                <a class="<?php if($com=='gioi-thieu' || $com=='ve-chung-toi' || $com=='lien-he') echo 'active'; ?> transition" href="gioi-thieu/" title="Giới thiệu"><i class="far fa-building pts-mobile-menu-icon" aria-hidden="true"></i>Giới thiệu</a>
                <ul>
                    <li><a href="gioi-thieu/#ve-phuc-thinh">Về Phúc Thịnh Solar</a></li>
                    <li><a href="gioi-thieu/#lich-su-phat-trien">Lịch sử phát triển</a></li>
                    <li><a href="gioi-thieu/#tam-nhin-su-menh">Tầm nhìn - Sứ mệnh</a></li>
                    <li><a href="gioi-thieu/#about-certificates">Năng lực &amp; chứng nhận</a></li>
                    <li><a href="lien-he/#he-thong-chi-nhanh">Hệ thống chi nhánh</a></li>
                    <li><a href="lien-he/#yeu-cau-tu-van">Liên hệ</a></li>
                </ul>
            </li>

            <li class="menu-solutions">
                <a class="has-child <?php if($com=='giai-phap') echo 'active'; ?> transition" href="/giai-phap/" title="Giải pháp"><i class="far fa-lightbulb pts-mobile-menu-icon" aria-hidden="true"></i>Giải pháp</a>
                <?php if(count($solutionMenuLinks)) { ?>
                    <ul>
                        <li><a title="Tất cả giải pháp" href="/giai-phap/">Tất cả giải pháp</a></li>
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

            <li class="menu-projects"><a class="<?php if($com=='du-an' || $com=='du-an-da-thuc-hien') echo 'active'; ?> transition" href="/du-an-da-thuc-hien/" title="Dự án"><i class="far fa-file-alt pts-mobile-menu-icon" aria-hidden="true"></i>Dự án</a></li>
     
            <li class="menu-knowledge">
                <a class="<?php if($com=='tin-tuc') echo 'active'; ?> transition" href="/tin-tuc/" title="Kiến thức"><?=$menuCatalogRenderIcon('book', 'pts-mobile-menu-icon pts-mobile-menu-icon-svg')?>Kiến thức</a>
                <?php if(count($ttlist)) { ?>
                    <ul>
                        <li><a title="Tất cả bài viết" href="/tin-tuc/">Tất cả bài viết</a></li>
                        <?php foreach($ttlist as $klist => $vlist) {
                            $ttcat = isset($newsMenuCats[(int)$vlist['id']]) ? $newsMenuCats[(int)$vlist['id']] : array(); ?>
                            <li>
                                <a class="has-child transition" title="<?=$vlist['name'.$lang]?>" href="category/<?=$vlist[$sluglang]?>/"><?=$vlist['name'.$lang]?></a>
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
                <a class="<?php if($com=='dai-ly') echo 'active'; ?> transition" href="dai-ly" title="Hợp tác"><?=$menuCatalogRenderIcon('users', 'pts-mobile-menu-icon pts-mobile-menu-icon-svg')?>Hợp tác</a>
                <ul>
                    <li><a href="dai-ly#dang-ky-dai-ly">Đăng ký đại lý</a></li>
                    <li><a href="dai-ly#nang-luc-phan-phoi">Năng lực phân phối</a></li>
                    <li><a href="dai-ly#ho-tro-dai-ly">Chính sách &amp; hỗ trợ đại lý</a></li>
                    <li><a href="lien-he/#yeu-cau-tu-van">Liên hệ kinh doanh</a></li>
                </ul>
            </li>

    </ul>
</nav>
