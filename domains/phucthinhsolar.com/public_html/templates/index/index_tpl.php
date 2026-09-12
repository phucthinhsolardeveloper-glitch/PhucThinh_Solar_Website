<!-- Bốn hành trình theo mockup 2026-09-12. Hai hành trình đầu chuyển đúng tab
     giải pháp đang có; Dự án và Đối tác dùng route public hiện hữu. -->
<nav class="pts-need-nav" aria-label="Chọn hành trình theo nhu cầu">
    <div class="uk-container">
        <div class="pts-need-grid">
            <a class="pts-need-card" href="#giai-phap" data-pts-solution-name="Hộ gia đình">
                <span class="pts-need-icon"><i class="fas fa-home" aria-hidden="true"></i></span>
                <span class="pts-need-copy"><span class="pts-need-title">Cho hộ gia đình</span>
                <span class="pts-need-desc">Tiết kiệm điện, sống xanh hơn</span></span>
                <i class="fas fa-arrow-right pts-need-arrow" aria-hidden="true"></i>
            </a>
            <a class="pts-need-card" href="#giai-phap" data-pts-solution-name="Doanh nghiệp">
                <span class="pts-need-icon"><i class="fas fa-building" aria-hidden="true"></i></span>
                <span class="pts-need-copy"><span class="pts-need-title">Cho doanh nghiệp</span>
                <span class="pts-need-desc">Tối ưu chi phí vận hành</span></span>
                <i class="fas fa-arrow-right pts-need-arrow" aria-hidden="true"></i>
            </a>
            <a class="pts-need-card" href="du-an-da-thuc-hien/">
                <span class="pts-need-icon"><i class="fas fa-city" aria-hidden="true"></i></span>
                <span class="pts-need-copy"><span class="pts-need-title">Cho dự án</span>
                <span class="pts-need-desc">Giải pháp trọn gói, hiệu quả</span></span>
                <i class="fas fa-arrow-right pts-need-arrow" aria-hidden="true"></i>
            </a>
            <a class="pts-need-card" href="dai-ly">
                <span class="pts-need-icon"><i class="fas fa-handshake" aria-hidden="true"></i></span>
                <span class="pts-need-copy"><span class="pts-need-title">Cho đối tác</span>
                <span class="pts-need-desc">Hợp tác cùng phát triển</span></span>
                <i class="fas fa-arrow-right pts-need-arrow" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</nav>

<?php /* Giới thiệu ngắn (MỚI, 2026-09-12) - trang chủ trước đây chưa có khối About
     teaser. $tomtat (table_static type=tom-tat-gioi-thieu) là dữ liệu demo cũ
     của template gốc (nói về 1 công ty máy tính khác, không phải PTS, không
     có ảnh) - KHÔNG dùng. Copy dưới đây lấy nguyên văn câu mô tả đã có sẵn và
     được duyệt ở Hero (templates/layout/slide.php, layout 1/2), không tự viết
     nội dung/số liệu mới; 4 gạch đầu dòng tái dùng nguyên .trust-badge-strip
     cũ (chỉ đổi cách trình bày, gộp vào khối này thay vì đứng riêng 1 dải).
     Ảnh dùng lại đúng 1 trong 4 ảnh thật của Hero carousel (upload/photo có
     sẵn, không cần ảnh mới). */ ?>
<section class="pts-about-teaser uk-section-large">
    <div class="uk-container">
        <div class="pts-about-teaser-grid">
            <div class="pts-about-teaser-media">
                <?=$func->getImage(['class' => 'lazy', 'sizes' => '860x680x1', 'upload' => UPLOAD_PHOTO_L, 'image' => '1-27050.png', 'alt' => 'Công trình điện mặt trời Phúc Thịnh Solar'])?>
            </div>
            <div class="pts-about-teaser-copy">
                <span class="pts-about-teaser-eyebrow">VỀ PHÚC THỊNH SOLAR</span>
                <h3>Đồng hành cùng khách hàng trên hành trình năng lượng sạch</h3>
                <p>Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời chính hãng, thiết bị điện mặt trời và giải pháp thi công cho gia đình, đại lý và công trình.</p>
                <ul class="pts-about-teaser-list">
                    <li><i class="fas fa-certificate" aria-hidden="true"></i><span>Cam kết chính hãng</span></li>
                    <li><i class="fas fa-tools" aria-hidden="true"></i><span>Đội ngũ kỹ thuật giàu kinh nghiệm</span></li>
                    <li><i class="fas fa-shield-alt" aria-hidden="true"></i><span>Chính sách bảo hành rõ ràng</span></li>
                    <li><i class="fas fa-truck" aria-hidden="true"></i><span>Giao hàng toàn quốc</span></li>
                </ul>
                <a class="pts-btn pts-btn-gold" href="ve-chung-toi/">Tìm hiểu về chúng tôi <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- San pham -->
<section class="home-3 home-products-featured uk-section-large pts-ecosystem" style="background-image: url();">
    <div class="uk-container">
        <div class="Module Module-324">
            <div class="ModuleContent">
                <?php /* Lưới tĩnh 4 lớn + 6 nhỏ (2026-09-12, bản 2 - đổi từ thanh cuộn
                         ngang sang lưới 2 hàng cố định theo hướng thiết kế mới chủ
                         project đưa - "vẫn hơi hướng layout cũ", thanh cuộn ngang với
                         icon nhỏ đúng là gần giống bản gốc). Bấm card -> AJAX đổi lưới
                         sản phẩm bên dưới (api/product2.php + apps.js, không đổi JS).
                         Tách theo cờ pl.status có 'major' (Đèn NLMT, Trụ đèn & phụ
                         kiện, Đèn sân vườn, Biến tần - chủ project chọn), render
                         trước để nổi bật, giữ đủ cả 10 danh mục. Lưới 12 cột: 4 card
                         major span 3 (đủ 1 hàng), 6 card minor span 2 (đủ 1 hàng) -
                         không cần JS, tự xuống hàng đúng theo CSS grid. */
                    $splistMajor = array();
                    $splistMinor = array();
                    foreach($splistnb as $splistRow) {
                        if(!empty($splistRow['status']) && strpos(','.$splistRow['status'].',', ',major,') !== false) $splistMajor[] = $splistRow;
                        else $splistMinor[] = $splistRow;
                    }
                    $splistOrdered = array_merge($splistMajor, $splistMinor);
                ?>
                <div class="pts-ecosystem-head">
                    <div class="section-title">
                        <p>SẢN PHẨM</p>
                        <h3>HỆ SINH THÁI THIẾT BỊ</h3>
                    </div>
                    <a class="pts-ecosystem-viewall" href="san-pham/">Xem tất cả sản phẩm <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="tab-switcher">
                    <ul class="pts-ecosystem-grid" uk-switcher="connect: .product-tabs-container">
                        <?php if(count($splistOrdered)){foreach($splistOrdered as $k => $v){
                            $isMajorCategory = !empty($v['status']) && strpos(','.$v['status'].',', ',major,') !== false;
                        ?>
                        <li class="<?=$isMajorCategory ? 'is-major' : ''?>">
                            <a href="javascript:void(0)" class="get-product-category" data-list="<?=$v['id']?>">
                                <?php if($isMajorCategory) { ?><span class="pts-home-category-badge">HOT</span><?php } ?>
                                <span class="pts-home-category-image">
                                    <?php if(!empty($v['photo'])) { ?>
                                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '220x220x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                    <?php } else {
                                        $categoryIcon = 'fa-lightbulb';
                                        $categorySlug = isset($v[$sluglang]) ? $v[$sluglang] : '';
                                        if(strpos($categorySlug, 'tam-pin') !== false) $categoryIcon = 'fa-solar-panel';
                                        else if(strpos($categorySlug, 'tru-den') !== false) $categoryIcon = 'fa-broadcast-tower';
                                        else if(strpos($categorySlug, 'san-vuon') !== false) $categoryIcon = 'fa-seedling';
                                        else if(strpos($categorySlug, 'quat') !== false) $categoryIcon = 'fa-fan';
                                    ?>
                                    <i class="fas <?=$categoryIcon?>" aria-hidden="true"></i>
                                    <?php } ?>
                                </span>
                                <span class="pts-ecosystem-name"><?=$v['name'.$lang]?><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                            </a>
                        </li>
                        <?php }} ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="product-tabs-container" style="touch-action: pan-y pinch-zoom;">
            <div class="paging-product-category" data-list="<?=$splistOrdered[0]['id']?>"></div>
        </div>
    </div>
</section>

<?php /* Banner carousel cũ giữ lại để rollback; giao diện mới tách CTA đại lý
     về đúng vị trí sau dải thương hiệu và dùng CTA tư vấn ở cuối trang. */ ?>
<?php if(false && count($adv)){ ?>
    <div class="wrap-adv w-100">
        <div class="uk-container">
            <div class="owl-page owl-carousel owl-theme" data-xsm-items="1:20" data-sm-items="1:20"
                data-md-items="1:20" data-lg-items="1:20" data-xlg-items="1:30" data-rewind="1" data-autoplay="1"
                data-loop="0" data-lazyload="0" data-mousedrag="1" data-touchdrag="1" data-smartspeed="500"
                data-autoplayspeed="3500" data-dots="0" data-nav="1"
                data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-arrow-narrow-left' width='50' height='37' viewBox='0 0 24 24' stroke-width='1' stroke='#ffffff' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><line x1='5' y1='12' x2='19' y2='12' /><line x1='5' y1='12' x2='9' y2='16' /><line x1='5' y1='12' x2='9' y2='8' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-arrow-narrow-right' width='50' height='37' viewBox='0 0 24 24' stroke-width='1' stroke='#ffffff' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><line x1='5' y1='12' x2='19' y2='12' /><line x1='15' y1='16' x2='19' y2='12' /><line x1='15' y1='8' x2='19' y2='12' /></svg>"
                data-navcontainer=".control-adv">
                <?php foreach($adv as $v) {
                    /* Màn hình sửa banner (photo_edit_tpl.php) có dropdown "Khi bấm
                       nút" ghi thẳng #ptsContactModal / #ptsDealerModal vào link —
                       nhận đúng 2 giá trị đặc biệt này để mở popup dùng chung toàn
                       site, các banner khác (link tự do) giữ nguyên hành vi điều
                       hướng bình thường. */
                    $advModalTarget = in_array($v['link'], array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($v['link'], '#') : '';
                ?>
                <div class="adv-item">
                    <a class="text-decoration-none" href="<?=$advModalTarget !== '' ? '#'.$advModalTarget : $v['link']?>"<?=$advModalTarget !== '' ? ' data-toggle="modal" data-target="#'.$advModalTarget.'"' : ''?> title="<?=$v['name'.$lang]?>">
                        <div class="adv-photo">
                            <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '735x378x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </div>
                        <div class="adv-info">
                            <div class="name-adv"><?=htmlspecialchars_decode($v['name'.$lang])?></div>
                            <div class="desc-adv"><?=htmlspecialchars_decode($v['desc'.$lang])?></div>
                            <div class="btn-style">
                                Đăng ký ngay
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 24 24" width="24"><path d="m19.71 12.71-6 6c-.0932.0932-.2039.1672-.3258.2177-.1218.0504-.2523.0764-.3842.0764s-.2624-.026-.3842-.0764c-.1219-.0505-.2326-.1245-.3258-.2177s-.1672-.2039-.2177-.3257c-.0504-.1219-.0764-.2524-.0764-.3843s.026-.2624.0764-.3842c.0505-.1219.1245-.2326.2177-.3258l4.3-4.29h-11.59c-.26522 0-.51957-.1054-.70711-.2929-.18753-.1875-.29289-.4419-.29289-.7071s.10536-.5196.29289-.7071c.18754-.1875.44189-.2929.70711-.2929h11.59l-4.3-4.29c-.1883-.1883-.2941-.4437-.2941-.71s.1058-.52169.2941-.71c.1883-.1883.4437-.29409.71-.29409s.5217.10579.71.29409l6 6c.0937.093.1681.2036.2189.3254.0508.1219.0769.2526.0769.3846s-.0261.2627-.0769.3846c-.0508.1218-.1252.2324-.2189.3254z" fill="rgb(0,0,0)"></path></svg>
                            </div>
                        </div>
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php } ?>

<?php if(count($gplist)){ ?>
<!-- Giai phap -->
<section class="home-2 uk-section-large" id="giai-phap">
    <div class="uk-container">
        <div class="Module Module-321">
            <div class="ModuleContent">
                <div class="heading uk-flex">
                    <div class="section-title">
                        <p>Chọn đúng giải pháp theo nhu cầu sử dụng điện của bạn</p>
                        <h3>GIẢI PHÁP <span class="pts-brand-text">PHÚC THỊNH SOLAR</span></h3>
                    </div>
                    <div class="heading-right">
                        <div class="tab-switcher">
                            <ul class="pts-solution-tabs" uk-switcher="connect: .tabs-container">
                                <?php if(count($gplist)){foreach($gplist as $k => $v){ ?>
                                <li class="<?=($k==0)?'uk-active':''?>"> <a href="javascript:"
                                        aria-expanded=""><?=$v['name'.$lang]?></a></li>
                                <?php }} ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tabs-container uk-switcher" style="touch-action: pan-y pinch-zoom;">
        <?php if(count($gplist)){foreach($gplist as $k => $v){
            $gp_tab = isset($solutionTabs[(int)$v['id']]) ? $solutionTabs[(int)$v['id']] : array();
        ?>
        <div class="tab-content <?=($k==0)?'uk-active':''?>">
            <div class="Module Module-322">
                <div class="ModuleContent">
                    <div class="uk-position-relative">
                        <div uk-slider=" " class="uk-slider uk-slider-container">
                            <ul class="uk-slider-items uk-grid uk-child-width-1-1">
                                <?php if(count($gp_tab)){foreach($gp_tab as $kk => $c){
                                $rowDetailPhoto = isset($solutionGallery[(int)$c['id']]) ? $solutionGallery[(int)$c['id']] : array();
                                ?>
                                <li tabindex="-1" class="<?=($kk == 0)?'uk-active':''?>">
                                    <div class="item uk-grid uk-grid-collapse uk-child-width-1-2@l uk-grid-match"
                                        uk-grid="">
                                        <div class="wrapper uk-first-column">
                                            <div class="content rocket-lazyload"
                                                style="background-image: url(assets/images/bg-3.png);">
                                                <div class="inner text-white">
                                                    <p class="title"><?=$c['title_index'.$lang]?></p>
                                                    <p class="brief"><?=$c['desc'.$lang]?></p>
                                                    <div class="fullcontent">
                                                        <ul uk-margin="uk-margin-small-top">
                                                            <?php if(count($rowDetailPhoto)){foreach($rowDetailPhoto as $kkk => $i){ ?>
                                                            <li
                                                                class="uk-first-column <?=($kkk==2 && $kkk==3)?'uk-margin-small-top':''?>">
                                                                <div class="icon">
                                                                    <?=$func->getImage(['class' => 'lazy','sizes' => '48x48x1', 'upload' => UPLOAD_NEWS_L, 'image' => $i['photo'], 'alt' => $i['name'.$lang]])?>
                                                                </div>
                                                                <span class="small-text"><?=$i['name'.$lang]?>:</span>
                                                                <p><span class="number"><?=$i['solieu'.$lang]?><small>
                                                                            <?=$i['donvi'.$lang]?></small></span>
                                                                </p>
                                                            </li>
                                                            <?php }} ?>

                                                        </ul>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="btn-view-all">
                                                        <a class="" href="danh-muc-giai-phap/<?=$c[$sluglang]?>/"
                                                            title="<?=$c['name'.$lang]?>">Xem
                                                            chi tiết<span
                                                                class="link_arrow lnr lnr-arrow-right"></span></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="wrapper">
                                            <div class="img">
                                                <?=$func->getImage(['class' => 'lazy','size-error' => '600x600x1', 'upload' => UPLOAD_NEWS_L, 'image' => $c['photo1'], 'alt' => $c['name'.$lang]])?>
                                                <a class="view-more" href="danh-muc-giai-phap/<?=$c[$sluglang]?>/"
                                                    title="<?=$c['name'.$lang]?>">
                                                    <span class="text">Xem chi tiết</span>
                                                    <span class="icon">
                                                        <em class="lnr lnr-arrow-right">
                                                        </em>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php }} ?>
                            </ul>
                            <a class="uk-position-center-left uk-position-small uk-icon uk-slidenav-previous uk-slidenav"
                                href="javascript:" uk-slidenav-previous="" uk-slider-item="previous">
                                <svg fill="rgba(51, 51, 51, 0.5)" width="20px" height="20px" viewBox="-78.5 0 512 512"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <title>left</title>
                                    <path d="M257 64L291 98 128 262 291 426 257 460 61 262 257 64Z"></path>
                                </svg>
                            </a>
                            <a class="uk-position-center-right uk-position-small uk-icon uk-slidenav-next uk-slidenav"
                                href="javascript:" uk-slidenav-next="" uk-slider-item="next">
                                <svg fill="rgba(51, 51, 51, 0.5)" width="20px" height="20px" viewBox="-77 0 512 512"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <title>right</title>
                                    <path d="M98 460L64 426 227 262 64 98 98 64 294 262 98 460Z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php }} ?>
    </div>
</section>
<?php } ?>

<?php /* Ẩn tạm sơ đồ lợi ích khỏi trang chủ (2026-09-12): nội dung trùng với
     các tín hiệu tin cậy ở khối Giới thiệu, còn giao diện vòng tròn làm trang
     dài và không có trong hướng thiết kế mới. Giữ nguyên code/query/data để
     có thể bật lại sau khi đổi "false &&" về điều kiện gốc. */ ?>
<?php if(false && count($loiich)){ ?>
<!-- Loi ich -->
<div class="Module Module-320">
    <div class="ModuleContent">
        <section class="home_loi_ich">
            <div class="uk-container">
                <div class="section-title uk-text-center">
                    <p><?=$loiich_title['desc'.$lang]?></p>
                    <h3><?=$loiich_title['name'.$lang]?></h3>
                </div>
                <div class="container">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="utilities">
                                <div class="round">
                                    <div class="group_content">
                                        <div class="inner text-center">
                                            <h3 class="title m-bottom-10"><?=$loiich[0]['name'.$lang]?></h3>
                                            <div class="content fz-20"><?=$loiich[0]['desc'.$lang]?></div>
                                        </div>
                                    </div>
                                    <div class="list-utilities">
                                        <?php foreach($loiich as $k => $v){ ?>
                                        <div class="item-utility">
                                            <div title="<?=$v['name'.$lang]?>" data-link=""
                                                data-title="<?=$v['name'.$lang]?>"
                                                class="icon-utility <?=($k==0)?'active':''?>"
                                                data-content="<?=$v['desc'.$lang]?>">
                                                <div class="icon">
                                                    <?=$func->getImage(['class' => 'lazy attachment-full size-full','size-error' => '59x58x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>

                                                    <?=$func->getImage(['class' => 'lazy active','size-error' => '59x58x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo1'], 'alt' => $v['name'.$lang]])?>
                                                </div>
                                            </div>
                                            <div class="group_content mobile">
                                                <div class="inner text-center">
                                                    <h3 class="title m-bottom-10"><?=$v['name'.$lang]?></h3>
                                                    <div class="content fz-20"><?=$v['desc'.$lang]?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div id="inf_circle_utilities_destroy">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="content pts-benefit-grid">
                                                <?php foreach($loiich as $k => $v){ ?>
                                                <div class="item_utilities_destroy">
                                                    <div class="wrap">
                                                        <div class="icon">
                                                            <div><?=$func->getImage(['class' => 'lazy attachment-full size-full','size-error' => '59x58x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo1'], 'alt' => $v['name'.$lang]])?>
                                                            </div>
                                                        </div>
                                                        <div class="des"><?=$v['desc'.$lang]?></div>
                                                        <h4 class="title_wrap"><?=$v['name'.$lang]?></h4>
                                                    </div>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>

<?php /* Ẩn tạm khối "Con số ấn tượng" (2026-09-12) - số liệu hiện tại
     (1.000.000 tấm pin đã cung cấp, 1.000 inverter, 1.000 diện tích...) là số
     tròn rõ ràng chưa xác minh, đúng cảnh báo lặp lại ở cả 3 tài liệu rà soát
     (BAN-GIAO.md, Codex Sep-11, đặc tả tiếng Anh). KHÔNG xoá dữ liệu/truy vấn -
     chỉ ẩn hiển thị cho tới khi chủ project xác nhận số liệu thật, đổi lại
     "false &&" thành điều kiện gốc khi có số liệu đã duyệt. */ ?>
<?php if(false && count($solieu)){ ?>
<!-- Con so -->
<div class="Module Module-327">
    <div class="ModuleContent">
        <section class="home-4 uk-section-large rocket-lazyload"
            style="background-image: url(assets/images/bg-conso.png);">
            <div class="uk-container">
                <div class="section-title section-title-white uk-text-center">
                    <p>Không Ngừng Phát Triển</p>
                    <h3>NHỮNG CON SỐ ẤN TƯỢNG</h3>
                </div>
                <div class="inf_dots uk-grid uk-child-width-1-4@l uk-child-width-1-2@s" uk-grid="">
                    <?php foreach($solieu as $k => $v){ ?>
                    <div class="wrapper uk-first-column">
                        <div class="item">
                            <div class="icon">
                                <?=$func->getImage(['class' => 'lazy','size-error' => '100x100x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                            </div>
                            <div class="caption">
                                <span class="number so" data-count="<?=$v['solieu']?>">0</span>
                                <span class="text"><?=$v['name'.$lang]?></span>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="text-center con-so-cta">
                    <a href="ve-chung-toi/" class="btn-gold">Xem hành trình của chúng tôi</a>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>

<?php /* Khối Đối tác dạng section lớn legacy đã bỏ. Nguồn $partner được tái sử
     dụng một lần ở dải logo Thương hiệu hợp tác gọn sau khối Dự án; trang Về
     chúng tôi vẫn giữ phần Đối tác chiến lược đầy đủ. */ ?>

	        <?php /* Section "Sự hài lòng của khách hàng, đối tác" đã bỏ khỏi trang chủ
	     theo yêu cầu chủ site (2026-09-08) - dồn hết vào việc gộp/rút gọn để tránh
	     trang chủ dài, không cần làm lại giao diện riêng cho phần này nữa. */ ?>

<?php if(count($quytrinh)){ ?>
<!-- Quy trinh -->
<div class="Module Module-332">
    <div class="ModuleContent">
        <section class="home-7 uk-section-large">
            <div class="uk-container">
                <div class="section-title uk-text-center">
                    <p>THI CÔNG AN TOÀN, LẮP ĐẶT NHANH CHÓNG</p>
                    <h3>QUY TRÌNH CHUYÊN NGHIỆP</h3>
                </div>
                <?php /* Lưới tĩnh 4 cột (2026-09-12) - trước là 2 Swiper (icon-slider
                         "tab" đồng bộ với process-slider ảnh lớn) cho đúng 4 bước
                         $quytrinh, phải tương tác (bấm/vuốt) mới đọc hết. Dữ liệu đã
                         đủ đúng 4 dòng nên hiện tĩnh luôn, đọc được không cần thao
                         tác - JS Swiper cho .home-7 đã được guard querySelector nên
                         không khởi tạo khi markup này không còn .swiper-container.
                         Phần mô tả dài vẫn giữ trong DB nhưng không render tại trang
                         chủ; trang này chỉ cần cho khách nắm nhanh bốn bước. */ ?>
                <div class="pts-process-grid">
                    <?php foreach($quytrinh as $k => $v){ ?>
                    <div class="pts-process-step">
                        <span class="pts-process-step-num"><?=($k<10)?'0'.($k+1):($k+1)?></span>
                        <div class="pts-process-step-img">
                            <?=$func->getImage(['class' => 'lazy','size-error' => '500x500x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </div>
                        <h4 class="pts-process-step-title"><?=$v['name'.$lang]?></h4>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>

<?php if(count($dalist)){ ?>
<!-- Du an -->
<section class="home-6">
    <div class="uk-container">
        <div class="Module Module-329">
            <div class="ModuleContent">
                <div class="heading uk-flex">
                    <div class="section-title">
                        <p>MANG ĐẾN CÁC SẢN PHẨM CHẤT LƯỢNG ĐẠT CHUẨN</p>
                        <h3>DỰ ÁN ĐÃ THỰC HIỆN</h3>
                    </div>
                    <div class="heading-right">
                        <div class="tab-switcher">
                            <ul uk-switcher="connect: .projects-switcher; swiping: false">
                                <?php if(count($dalist)){foreach($dalist as $k => $v){ ?>
                                <li class="<?=($k==0)?'uk-active':''?> tab-project"> <a href="javascript:"
                                        aria-expanded="true"><?=$v['name'.$lang]?></a></li>
                                <?php }} ?>
                            </ul>
                        </div>
                        <div class="desktop">
                            <a class="btn-all" href="du-an-da-thuc-hien/">Xem tất cả <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="slider-6">
        <div class="uk-container">
            <div class="projects-switcher uk-switcher">
                <?php if(count($dalist)){foreach($dalist as $k => $v){
                    $projectnb = isset($featuredProjects[(int)$v['id']]) ? $featuredProjects[(int)$v['id']] : array();
                ?>
                <div class="tab-content <?=($k==0)?'uk-active':''?>">
                    <div class=" Module Module-330">
                        <div class="ModuleContent">
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <div class="slider_6_slick">
                                        <?php if(count($projectnb)){foreach($projectnb as $kk => $c){ ?>
                                        <div class="item none">
                                            <div class="project">
                                                <div class="img">
                                                    <a href="store-manager/<?=$c[$sluglang]?>/"
                                                        title="<?=$c['name'.$lang]?>">
                                                        <?=$func->getImage(['isLazy' => false, 'size-error' => '183x78x1', 'upload' => UPLOAD_NEWS_L, 'image' => $c['photo'], 'alt' => $c['name'.$lang]])?>
                                                    </a>
                                                </div>
                                                <div class="group_content">
                                                    <p class="name"><a
                                                            href="store-manager/<?=$c[$sluglang]?>/"><?=$c['name'.$lang]?></a>
                                                    </p>
                                                    <div class="content">
                                                        <ul>
                                                            <?php if(!empty($c['tenkhachhang'])){ ?>
                                                            <li>
                                                                <p><b>Khách hàng: </b><?=$c['tenkhachhang']?></p>
                                                            </li>
                                                            <?php } ?>
                                                            <?php if(!empty($c['address'])){ ?>
                                                            <li>
                                                                <p><b>Địa chỉ: </b><?=$c['address']?></p>
                                                            </li>
                                                            <?php } ?>
                                                            <?php if(!empty($c['ngayhopdong'])){ ?>
                                                            <li>
                                                                <p><b>Ngày hợp đồng: </b><?=$c['ngayhopdong']?></p>
                                                            </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                    <a class="link_project" href="store-manager/<?=$c[$sluglang]?>/"
                                                        title="<?=$c['name'.$lang]?>"></a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php }} ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }} ?>
            </div>
        </div>
        <div class="mobile text-center">
            <a class="btn-all" href="du-an-da-thuc-hien/">Xem tất cả <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>
</section>
<?php } ?>

<?php /* Thương hiệu hợp tác: dùng đúng toàn bộ record type "doitac" đang bật.
     Đây là nguồn logo được Admin quản lý và cũng dùng ở trang Về chúng tôi;
     không dùng ảnh giấy chứng nhận (type "chungnhan") thay cho logo. */ ?>
<?php if(count($partner)){ ?>
<section class="pts-brands uk-section-large">
    <div class="uk-container">
        <div class="section-title uk-text-center">
            <p>ĐỒNG HÀNH CÙNG THƯƠNG HIỆU UY TÍN</p>
            <h3>THƯƠNG HIỆU HỢP TÁC</h3>
        </div>
        <div class="pts-brands-rail" data-pts-horizontal-rail>
            <button class="pts-brands-arrow pts-brands-arrow--prev" type="button" aria-label="Xem thương hiệu trước" aria-controls="pts-brand-track">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="pts-brands-grid" id="pts-brand-track" role="list" aria-label="Danh sách thương hiệu hợp tác" tabindex="0">
                <?php foreach($partner as $v){
                    $brandAlt = !empty($v['name'.$lang]) ? $v['name'.$lang] : 'Logo thương hiệu hợp tác';
                ?>
                <div class="pts-brands-item" role="listitem">
                    <?php if(!empty($v['photo'])){ ?>
                    <span class="pts-brands-logo">
                        <?=$func->getImage(['isLazy' => false, 'sizes' => '300x128x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $brandAlt])?>
                    </span>
                    <?php } else { ?>
                    <span class="pts-brands-name"><?=$brandAlt?></span>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            <button class="pts-brands-arrow pts-brands-arrow--next" type="button" aria-label="Xem thương hiệu tiếp theo" aria-controls="pts-brand-track">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        <div class="pts-brand-services">
            <a href="san-pham/">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <span><strong>Sản phẩm chính hãng</strong><small>Thông tin rõ theo từng mã hàng</small></span>
            </a>
            <a href="ho-tro/chinh-sach-giao-hang-va-kiem-hang/">
                <i class="fas fa-truck" aria-hidden="true"></i>
                <span><strong>Giao hàng toàn quốc</strong><small>Kiểm tra điều kiện vận chuyển</small></span>
            </a>
            <a href="ho-tro/chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat/">
                <i class="fas fa-headset" aria-hidden="true"></i>
                <span><strong>Hỗ trợ kỹ thuật</strong><small>Tra cứu chính sách bảo hành</small></span>
            </a>
        </div>
    </div>
</section>
<?php } ?>

<?php
    $dealerAdv = array();
    $contactAdv = array();
    foreach($adv as $advItem)
    {
        if(!isset($advItem['link'])) continue;
        if($advItem['link'] === '#ptsDealerModal' && !$dealerAdv) $dealerAdv = $advItem;
        if($advItem['link'] === '#ptsContactModal' && !$contactAdv) $contactAdv = $advItem;
    }
?>
<?php if($dealerAdv){ ?>
<section class="pts-dealer-banner">
    <div class="pts-dealer-banner-media" aria-hidden="true">
        <?=$func->getImage(['class' => 'lazy', 'sizes' => '760x260x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $dealerAdv['photo'], 'alt' => ''])?>
    </div>
    <div class="uk-container pts-dealer-banner-inner">
        <div class="pts-dealer-banner-copy">
            <span>HỢP TÁC CÙNG PHÚC THỊNH SOLAR</span>
            <h3><?=htmlspecialchars_decode($dealerAdv['name'.$lang])?></h3>
            <div><?=htmlspecialchars_decode($dealerAdv['desc'.$lang])?></div>
        </div>
        <a class="pts-btn pts-btn-gold" href="#ptsDealerModal" data-toggle="modal" data-target="#ptsDealerModal">Đăng ký hợp tác <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>
<?php } ?>

<?php if(count($newsnb)){ ?>
<!-- Kiến thức năng lượng; dự án đã có section riêng phía trên. -->
<div class="Module Module-333">
    <div class="ModuleContent">
        <section class="home-8 uk-section-large">
            <div class="uk-container">
                <div class="pts-news-heading">
                    <div class="section-title">
                        <p>CẬP NHẬT MỚI NHẤT</p>
                        <h3>KIẾN THỨC NĂNG LƯỢNG</h3>
                    </div>
                    <a href="tin-tuc/" class="pts-news-viewall">Xem tất cả bài viết <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="pts-news-grid">
                    <?php foreach($newsnb as $v) { ?>
                    <a class="pts-news-card" href="<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
                        <span class="pts-news-thumb">
                            <?=$func->getImage(['class' => 'lazy', 'size-error' => '420x250x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </span>
                        <span class="pts-news-info">
                            <span class="pts-news-date"><?=date('d/m/Y',$v['date_created'])?></span>
                            <span class="pts-news-title"><?=$v['name'.$lang]?></span>
                            <span class="pts-news-link">Đọc bài viết <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </span>
                    </a>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>

<!-- CTA tư vấn chốt trang; CTA đại lý đã được đặt riêng trước khối Kiến thức. -->
<section class="pts-home-closing-cta">
    <div class="uk-container">
        <div class="pts-closing-cta-box">
            <div class="pts-closing-cta-main">
                <h3><?=$contactAdv ? strip_tags(htmlspecialchars_decode($contactAdv['name'.$lang])) : 'Chưa biết chọn sản phẩm phù hợp?'?></h3>
                <p><?=$contactAdv ? strip_tags(htmlspecialchars_decode($contactAdv['desc'.$lang])) : 'Để lại số điện thoại hoặc nhắn Zalo, Phúc Thịnh Solar tư vấn miễn phí ngay.'?></p>
                <div class="pts-closing-cta-actions">
                    <a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline'])?>" class="pts-btn pts-btn-gold"><i class="fas fa-phone-alt" aria-hidden="true"></i> Gọi ngay</a>
                    <?php if(!empty($optsetting['zalo'])) { ?>
                    <a href="https://zalo.me/<?=$func->parsePhone($optsetting['zalo'])?>" target="_blank" class="pts-btn pts-btn-ghost">Zalo tư vấn</a>
                    <?php } ?>
                    <a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal" class="pts-btn pts-btn-ghost">Để lại số điện thoại</a>
                </div>
            </div>
        </div>
    </div>
</section>
