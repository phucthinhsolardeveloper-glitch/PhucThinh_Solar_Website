<nav id="menu">
    <ul>
            <li>
                <a class="has-child <?php if($com=='san-pham') echo 'active'; ?> transition" href="san-pham/" title="Danh mục sản phẩm">Danh mục sản phẩm</a>
                <?php if(count($splist)) { ?>
                    <ul>
                        <li><a class="transition" title="Xem tất cả sản phẩm" href="san-pham/">Xem tất cả sản phẩm</a></li>
                        <?php foreach($splist as $klist => $vlist) {
                            $spcat = isset($productMenuCats[(int)$vlist['id']]) ? $productMenuCats[(int)$vlist['id']] : array(); ?>
                            <li>
                                <a class="transition" title="<?=$vlist['name'.$lang]?>" href="danh-muc-san-pham/<?=$vlist[$sluglang]?>/">
                                    <?=$vlist['name'.$lang]?>
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
                <a class="<?php if($com=='gioi-thieu' || $com=='ve-chung-toi' || $com=='lien-he') echo 'active'; ?> transition" href="gioi-thieu/" title="Giới thiệu">Giới thiệu</a>
                <ul>
                    <li><a href="gioi-thieu/#ve-phuc-thinh">Về Phúc Thịnh Solar</a></li>
                    <li><a href="gioi-thieu/#lich-su-phat-trien">Lịch sử phát triển</a></li>
                    <li><a href="gioi-thieu/#tam-nhin-su-menh">Tầm nhìn - Sứ mệnh</a></li>
                    <li><a href="gioi-thieu/#about-certificates">Năng lực &amp; chứng nhận</a></li>
                    <li><a href="lien-he/#he-thong-chi-nhanh">Hệ thống chi nhánh</a></li>
                    <li><a href="lien-he/#yeu-cau-tu-van">Liên hệ</a></li>
                </ul>
            </li>

            <li>
                <a class="has-child <?php if($com=='giai-phap') echo 'active'; ?> transition" href="giai-phap/" title="Giải pháp">Giải pháp</a>
                <?php if(count($solutionMenuLinks)) { ?>
                    <ul>
                        <li><a title="Tất cả giải pháp" href="giai-phap/">Tất cả giải pháp</a></li>
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

            <li><a class="<?php if($com=='du-an' || $com=='du-an-da-thuc-hien') echo 'active'; ?> transition" href="du-an-da-thuc-hien/" title="Dự án">Dự án</a></li>
     
            <li class="menu-knowledge">
                <a class="<?php if($com=='tin-tuc') echo 'active'; ?> transition" href="tin-tuc/" title="Kiến thức">Kiến thức</a>
                <?php if(count($ttlist)) { ?>
                    <ul>
                        <li><a title="Tất cả bài viết" href="tin-tuc/">Tất cả bài viết</a></li>
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
                <a class="<?php if($com=='dai-ly') echo 'active'; ?> transition" href="dai-ly" title="Hợp tác">Hợp tác</a>
                <ul>
                    <li><a href="dai-ly#dang-ky-dai-ly">Đăng ký đại lý</a></li>
                    <li><a href="dai-ly#nang-luc-phan-phoi">Năng lực phân phối</a></li>
                    <li><a href="dai-ly#ho-tro-dai-ly">Chính sách &amp; hỗ trợ đại lý</a></li>
                    <li><a href="lien-he/#yeu-cau-tu-van">Liên hệ kinh doanh</a></li>
                </ul>
            </li>

            <li class="mm-lang mm-listitem_nosubitems">
                <span class="mm-lang-title">Ngôn ngữ</span>
                <?php /* Render sẵn để Mmenu sao chép cả link. Trước đây JS chèn
                    link sau khi plugin clone nav nên drawer nhìn thấy bị rỗng. */ ?>
                <span class="mm-lang-links" data-gt-source="vi">
                    <a href="#" data-gtlang="vi">VI</a>
                    <a href="#" data-gtlang="en">EN</a>
                    <a href="#" data-gtlang="zh-CN">中文</a>
                    <a href="#" data-gtlang="ko">한국어</a>
                    <a href="#" data-gtlang="ja">日本語</a>
                </span>
            </li>
    </ul>
</nav>
<script>
(function(){
    function currentLanguage(){
        return (document.cookie.match(/(?:^|;\s*)googtrans=\/[a-zA-Z-]+\/([a-zA-Z-]+)/) || [])[1] || 'vi';
    }
    function refreshLanguageLinks(){
        var current = currentLanguage();
        document.querySelectorAll('.mm-lang-links [data-gtlang]').forEach(function(link){
            var active = link.getAttribute('data-gtlang') === current;
            link.classList.toggle('active', active);
            if(active) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
    }
    function setTranslateCookie(language, source){
        var host = location.hostname;
        var value = '/' + source + '/' + language;
        var expires = language === source ? ';expires=Thu, 01 Jan 1970 00:00:00 GMT' : ';max-age=31536000';
        document.cookie = 'googtrans=' + (language === source ? '' : value) + ';path=/' + expires + ';SameSite=Lax';
        if(host.indexOf('.') !== -1 && !/^\d+\.\d+\.\d+\.\d+$/.test(host)) {
            document.cookie = 'googtrans=' + (language === source ? '' : value) + ';path=/;domain=.' + host + expires + ';SameSite=Lax';
        }
    }
    document.addEventListener('DOMContentLoaded', refreshLanguageLinks);
    document.addEventListener('click', function(event){
        var link = event.target.closest && event.target.closest('.mm-lang-links [data-gtlang]');
        if(!link) return;
        event.preventDefault();
        var sourceBox = link.closest('.mm-lang-links');
        var source = sourceBox ? sourceBox.getAttribute('data-gt-source') || 'vi' : 'vi';
        setTranslateCookie(link.getAttribute('data-gtlang'), source);
        location.reload();
    });
})();
</script>
