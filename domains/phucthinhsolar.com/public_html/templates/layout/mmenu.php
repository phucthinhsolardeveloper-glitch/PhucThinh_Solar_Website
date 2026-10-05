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
                        <?php $mobileMenuIconSvg = array(
                            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>',
                            'street' => '<path d="M8 22V7l8-3m-9 4 1-3 7-2 4 2-1 2-6 1M5 22h6"/>',
                            'garden' => '<path d="m4 7 8-4 8 4H4Zm2 1 2 9h8l2-9M8 10h8M12 8v13M8 22h8"/>',
                            'panel' => '<path d="m5 3-3 13h20L19 3H5ZM3 10h18M4 6h16M9 3 8 16m7-13 1 13M12 16v5m-5 0h10"/>',
                            'inverter' => '<rect x="5" y="2" width="14" height="20" rx="2"/><rect x="8" y="5" width="8" height="5" rx="1"/><path d="m13 12-3 4h4l-3 4"/>',
                            'battery' => '<path d="M9 3V1h6v2"/><rect x="5" y="3" width="14" height="19" rx="2"/><path d="m13 7-4 6h6l-4 5"/>',
                            'pump' => '<path d="M7 5h10v15H7V5Zm3 0V2h4v3m-4 15v2m4-2v2M7 14H3v-4h4m10 4h4v5"/><circle cx="12" cy="12" r="2"/>',
                            'fan' => '<circle cx="12" cy="10" r="8"/><circle cx="12" cy="10" r="1.5"/><path d="M12 8c-5-8-8 2-2 2m4 0c9 0 3-9 0-2m-2 4c-3 8 7 6 2 0M12 18v4m-5 0h10"/>',
                            'tools' => '<path d="m14 5 4-3 1 5 3 2-3 4-4-1L6 21l-3-3 9-9-1-4 3-3Z"/>'
                        ); ?>
                        <?php foreach($splist as $klist => $vlist) {
                            $spcat = isset($productMenuCats[(int)$vlist['id']]) ? $productMenuCats[(int)$vlist['id']] : array(); ?>
                            <?php
                                $mobileCategoryName = mb_strtolower((string)$vlist['name'.$lang], 'UTF-8');
                                $mobileCategorySlug = strtolower((string)$vlist[$sluglang]);
                                $mobileCategoryIcon = 'tools';
                                if (strpos($mobileCategorySlug, 'san-vuon') !== false || strpos($mobileCategoryName, 'sân vườn') !== false) $mobileCategoryIcon = 'garden';
                                elseif (strpos($mobileCategorySlug, 'tru-den') !== false || strpos($mobileCategoryName, 'trụ đèn') !== false) $mobileCategoryIcon = 'street';
                                elseif (strpos($mobileCategorySlug, 'quat') !== false || strpos($mobileCategoryName, 'quạt') !== false) $mobileCategoryIcon = 'fan';
                                elseif (strpos($mobileCategorySlug, 'tam-pin') !== false || strpos($mobileCategoryName, 'tấm pin') !== false) $mobileCategoryIcon = 'panel';
                                elseif (strpos($mobileCategorySlug, 'bien-tan') !== false || strpos($mobileCategoryName, 'biến tần') !== false || strpos($mobileCategoryName, 'inverter') !== false) $mobileCategoryIcon = 'inverter';
                                elseif (strpos($mobileCategorySlug, 'pin-luu-tru') !== false || strpos($mobileCategoryName, 'pin lưu trữ') !== false) $mobileCategoryIcon = 'battery';
                                elseif (strpos($mobileCategorySlug, 'bom') !== false || strpos($mobileCategoryName, 'bơm') !== false) $mobileCategoryIcon = 'pump';
                                elseif (strpos($mobileCategorySlug, 'thiet-bi') !== false || strpos($mobileCategoryName, 'thiết bị') !== false || strpos($mobileCategoryName, 'phụ kiện') !== false) $mobileCategoryIcon = 'tools';
                                elseif (strpos($mobileCategoryName, 'đèn') !== false) $mobileCategoryIcon = 'sun';
                            ?>
                            <li class="pts-mobile-product-category">
                                <a class="transition" title="<?=$vlist['name'.$lang]?>" href="/danh-muc-san-pham/<?=$vlist[$sluglang]?>/">
                                    <svg class="pts-mobile-menu-icon pts-mobile-menu-icon-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?=$mobileMenuIconSvg[$mobileCategoryIcon]?></svg><?=$vlist['name'.$lang]?>
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
                <a class="<?php if($com=='tin-tuc') echo 'active'; ?> transition" href="/tin-tuc/" title="Kiến thức"><i class="fas fa-book-open pts-mobile-menu-icon" aria-hidden="true"></i>Kiến thức</a>
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
                <a class="<?php if($com=='dai-ly') echo 'active'; ?> transition" href="dai-ly" title="Hợp tác"><i class="fas fa-users pts-mobile-menu-icon" aria-hidden="true"></i>Hợp tác</a>
                <ul>
                    <li><a href="dai-ly#dang-ky-dai-ly">Đăng ký đại lý</a></li>
                    <li><a href="dai-ly#nang-luc-phan-phoi">Năng lực phân phối</a></li>
                    <li><a href="dai-ly#ho-tro-dai-ly">Chính sách &amp; hỗ trợ đại lý</a></li>
                    <li><a href="lien-he/#yeu-cau-tu-van">Liên hệ kinh doanh</a></li>
                </ul>
            </li>

    </ul>
</nav>
