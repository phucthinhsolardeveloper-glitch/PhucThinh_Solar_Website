<nav id="menu">
    <ul>
        <li><a class="<?php if($com=='' || $com=='index') echo 'active'; ?> transition" href="" title="<?=trangchu?>"><?=trangchu?></a></li>

            <li><a class="<?php if($com=='gioi-thieu' || $com=='ve-chung-toi') echo 'active'; ?> transition" href="ve-chung-toi/" title="Về chúng tôi">Về chúng tôi</a></li>

            <li>
                <a class="has-child <?php if($com=='san-pham') echo 'active'; ?> transition" href="san-pham/" title="Sản phẩm">Sản phẩm</a>
                <?php if(count($splist)) { ?>
                    <ul>
                        <?php foreach($splist as $klist => $vlist) {
                            $spcat = isset($productMenuCats[(int)$vlist['id']]) ? $productMenuCats[(int)$vlist['id']] : array(); ?>
                            <li>
                                <a class="transition" title="<?=$vlist['name'.$lang]?>" href="danh-muc-san-pham/<?=$vlist[$sluglang]?>/">
                                    <?=$vlist['name'.$lang]?>
                                    <?php if(!empty($spcat)) { ?>
                                        <span class="child-menu"></span>
                                    <?php } ?>
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

            <li>
                <a class="has-child <?php if($com=='giai-phap/') echo 'active'; ?> transition" href="giai-phap/" title="Giải pháp">Giải pháp</a>
                <?php if(count($gplist)) { ?>
                    <ul>
                        <?php foreach($gplist as $klist => $vlist) {
                            $ttcat = isset($newsMenuCats[(int)$vlist['id']]) ? $newsMenuCats[(int)$vlist['id']] : array(); ?>
                            <li>
                                <a class="has-child transition" title="<?=$vlist['name'.$lang]?>" href="<?=$vlist[$sluglang]?>"><?=$vlist['name'.$lang]?></a>
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

            <li><a class="<?php if($com=='du-an' || $com=='du-an-da-thuc-hien') echo 'active'; ?> transition" href="du-an-da-thuc-hien/" title="Dự án">Dự án</a></li>
     
            <li>
                <a class="<?php if($com=='tin-tuc') echo 'active'; ?> transition" href="tin-tuc/" title="<?=tintuc?>"><?=tintuc?></a>
                <?php if(count($ttlist)) { ?>
                    <ul>
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

            <li><a class="<?php if($com=='dai-ly') echo 'active'; ?> transition" href="dai-ly" title="Đại lý">Đại lý</a></li>

            <li><a class="<?php if($com=='lien-he') echo 'active'; ?> transition" href="lien-he" title="<?=lienhe?>"><?=lienhe?></a></li>

            <li class="mm-lang mm-listitem_nosubitems">
                <span class="mm-lang-title">Ngôn ngữ</span>
                <span class="mm-lang-links" id="mm-lang-links"></span>
            </li>
    </ul>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var LABELS = { 'vi':'VI', 'en':'EN', 'zh-CN':'中文', 'zh-TW':'中文', 'ko':'한국어', 'ja':'日本語',
                   'fr':'FR', 'de':'DE', 'ru':'RU', 'th':'ไทย', 'es':'ES', 'ja-JP':'日本語' };
    var langs = (window.gtranslateSettings && window.gtranslateSettings.languages) ? window.gtranslateSettings.languages : ['vi','en'];
    var src = (window.gtranslateSettings && window.gtranslateSettings.default_language) ? window.gtranslateSettings.default_language : 'vi';
    var cur = (document.cookie.match(/googtrans=\/[a-zA-Z-]+\/([a-zA-Z-]+)/) || [])[1] || src;
    var box = document.getElementById('mm-lang-links');
    if(!box) return;
    langs.forEach(function(l){
        var a = document.createElement('a');
        a.href = 'javascript:void(0)';
        a.setAttribute('data-gtlang', l);
        a.textContent = LABELS[l] || l.toUpperCase();
        if(l === cur) a.className = 'active';
        a.addEventListener('click', function(){
            var val = '/' + src + '/' + l;
            var host = location.hostname;
            document.cookie = 'googtrans=' + val + ';path=/';
            document.cookie = 'googtrans=' + val + ';path=/;domain=.' + host;
            location.reload();
        });
        box.appendChild(a);
    });
});
</script>
