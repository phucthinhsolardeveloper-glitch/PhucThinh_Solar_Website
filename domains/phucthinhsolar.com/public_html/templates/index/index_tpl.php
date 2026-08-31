<!-- San pham -->
<section class="home-3 home-products-featured uk-section-large" style="background-image: url();">
    <div class="uk-container">
        <div class="Module Module-324">
            <div class="ModuleContent">
                <div class="section-title uk-text-center">
                    <p>CHẤT LƯỢNG, HIỆN ĐẠI, AN TOÀN</p>
                    <h3>SẢN PHẨM NỔI BẬT </h3>
                </div>
                <div class="tab-switcher">
                    <div class="swiper-container sw-tab-cat">
                        <ul class="swiper-wrapper" uk-switcher="connect: .product-tabs-container">
                            <?php if(count($splistnb)){foreach($splistnb as $k => $v){ ?>
                            <li class="swiper-slide">
                                <a href="javascript:" class="get-product-category" data-list="<?=$v['id']?>">
                                    <span><?=$v['name'.$lang]?></span>
                                </a>
                            </li>
                            <?php }} ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="product-tabs-container" style="touch-action: pan-y pinch-zoom;">
            <div class="paging-product-category" data-list="<?=$splistnb[0]['id']?>"></div>
        </div>
    </div>
</section>

<div class="trust-badge-strip">
    <div class="uk-container">
        <div class="row">
            <div class="col-md-3 trust-badge-item"><i class="fas fa-certificate"></i><span>Cam kết chính hãng</span></div>
            <div class="col-md-3 trust-badge-item"><i class="fas fa-tools"></i><span>Đội ngũ kỹ thuật giàu kinh nghiệm</span></div>
            <div class="col-md-3 trust-badge-item"><i class="fas fa-shield-alt"></i><span>Chính sách bảo hành rõ ràng</span></div>
            <div class="col-md-3 trust-badge-item"><i class="fas fa-truck"></i><span>Giao hàng toàn quốc</span></div>
        </div>
    </div>
</div>

<?php if(count($adv)){ ?>
    <div class="wrap-adv w-100">
        <div class="uk-container">
            <div class="owl-page owl-carousel owl-theme" data-xsm-items="1:20" data-sm-items="1:20"
                data-md-items="1:20" data-lg-items="1:20" data-xlg-items="1:30" data-rewind="1" data-autoplay="1"
                data-loop="0" data-lazyload="0" data-mousedrag="1" data-touchdrag="1" data-smartspeed="500"
                data-autoplayspeed="3500" data-dots="0" data-nav="1"
                data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-arrow-narrow-left' width='50' height='37' viewBox='0 0 24 24' stroke-width='1' stroke='#ffffff' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><line x1='5' y1='12' x2='19' y2='12' /><line x1='5' y1='12' x2='9' y2='16' /><line x1='5' y1='12' x2='9' y2='8' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-arrow-narrow-right' width='50' height='37' viewBox='0 0 24 24' stroke-width='1' stroke='#ffffff' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><line x1='5' y1='12' x2='19' y2='12' /><line x1='15' y1='16' x2='19' y2='12' /><line x1='15' y1='8' x2='19' y2='12' /></svg>"
                data-navcontainer=".control-adv">
                <?php foreach($adv as $v) { ?>
                <div class="adv-item">
                    <a class="text-decoration-none" href="<?=$v['link']?>" title="<?=$v['name'.$lang]?>">
                        <div class="adv-photo">
                            <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '735x378x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </div>
                        <div class="adv-info">
                            <div class="name-adv"><?=htmlspecialchars_decode($v['name'.$lang])?></div>
                            <div class="desc-adv"><?=htmlspecialchars_decode($v['desc'.$lang])?></div>
                            <div class="btn-style">
                                Tìm hiểu thêm
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
<section class="home-2 uk-section-large">
    <div class="uk-container">
        <div class="Module Module-321">
            <div class="ModuleContent">
                <div class="heading uk-flex">
                    <div class="section-title">
                        <p><span class="pts-brand-text">PHÚC THỊNH SOLAR</span> CUNG CẤP GIẢI PHÁP TỐI ƯU</p>
                        <h3>GIẢI PHÁP <span class="pts-brand-text">PHÚC THỊNH SOLAR</span></h3>
                    </div>
                    <div class="heading-right">
                        <div class="tab-switcher">
                            <ul uk-switcher="connect: .tabs-container">
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
            $gp_tab = $cache->get("select name$lang, title_index$lang, slugvi, slugen, desc$lang, date_created, id, photo1 from #_news where id_list = ".$v['id']." and type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap'), 'result', 7200);
        ?>
        <div class="tab-content <?=($k==0)?'uk-active':''?>">
            <div class="Module Module-322">
                <div class="ModuleContent">
                    <div class="uk-position-relative">
                        <div uk-slider=" " class="uk-slider uk-slider-container">
                            <ul class="uk-slider-items uk-grid uk-child-width-1-1"
                                style="transform: translate3d(-1230px, 0px, 0px);">
                                <?php if(count($gp_tab)){foreach($gp_tab as $kk => $c){
                                $rowDetailPhoto = $d->rawQuery("select photo, id, solieu$lang, donvi$lang, name$lang from #_gallery where id_parent = ? and com='news' and type = ? and kind='man' and val = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4",array($c['id'],'giai-phap','thongtinphu'));
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

<div class="wrap-promo-banners">
    <div class="uk-container">
        <div class="row">
            <div class="col-12">
                <a href="dai-ly/" class="promo-banner promo-banner-gold promo-banner-single">
                    <span class="promo-copy">
                        <span class="promo-eyebrow">Trở thành đối tác phân phối</span>
                        <span class="promo-title">Tuyển đại lý toàn quốc</span>
                    </span>
                    <span class="promo-btn">Đăng ký ngay</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if(count($loiich)){ ?>
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

<?php if(count($solieu)){ ?>
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

<?php if(count($partner)){ ?>
<!-- Doi tac -->
<div class="Module Module-327">
    <div class="ModuleContent">
        <section data-bg="" class="home-5 uk-section-large rocket-lazyload" style="">
            <div class="uk-container">
                <div class="section-title uk-text-center">
                    <p>TỰ HÀO LÀ DOANH NGHIỆP DẪN ĐẦU TRONG LĨNH VỰC ĐIỆN MẶT TRỜI </p>
                    <h3>ĐỐI TÁC</h3>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col">
                        <div id="inf_list_partner" class="active_slider inf_dots">
                            <?php foreach($partner as $v){ ?>
                            <div class="inf_item_partner">
                                <div class="wrap">
                                    <?=$func->getImage(['isLazy' => false,'size-error' => '183x78x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>

<?php if(count($testimonial)){ ?>
<!-- Testimonial - phản hồi đại lý/khách hàng thật đã có sẵn trong DB -->
<section class="wrap-testimonial uk-section-large">
    <div class="uk-container">
        <div class="section-title uk-text-center">
            <p>Khách hàng nói gì</p>
            <h3>Sự hài lòng của khách hàng, đối tác</h3>
        </div>
        <div class="row">
            <?php foreach($testimonial as $v) { ?>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <p class="testimonial-name"><?=$v['name'.$lang]?></p>
                    <p class="testimonial-role"><?=trim($v['desc'.$lang])?></p>
                    <div class="testimonial-content"><?=htmlspecialchars_decode($v['content'.$lang])?></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
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
                            <a class="btn-all" href="du-an-da-thuc-hien/">Xem tất cả</a>
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
                    $projectnb = $d->rawQuery("select name$lang, address, ngayhopdong, tenkhachhang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where id_list = ".$v['id']." and type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('du-an'));
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
            <a class="btn-all" href="du-an-da-thuc-hien/">Xem tất cả</a>
        </div>
    </div>
</section>
<?php } ?>

<?php if(count($quytrinh)){ ?>
<!-- Quy trinh -->
<div class="Module Module-332">
    <div class="ModuleContent">
        <section class="home-7 uk-section-large rocket-lazyload"
            style="background-image: url(assets/images/bg-1-1.png);">
            <div class="uk-container">
                <div class="section-title text-white uk-text-center">
                    <p>THI CÔNG AN TOÀN, LẮP ĐẶT NHANH CHÓNG</p>
                    <h3 class=" text-white">QUY TRÌNH CHUYÊN NGHIỆP</h3>
                </div>
                <div class="icon-slider">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            <?php foreach($quytrinh as $k => $v){ ?>
                            <div class="swiper-slide">
                                <div class="item">
                                    <div class="content">
                                        <div class="text">
                                            <span><?=($k<10)?'0'.($k+1):($k+1)?>. <?=$v['name'.$lang]?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                    </div>
                </div>
                <div class="process-slider">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            <?php foreach($quytrinh as $k => $v){ ?>
                            <div class="swiper-slide">
                                <div class="item">
                                    <div class="inner">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="group_content">
                                                    <div class="title">
                                                        <div class="icon">
                                                            <img class="lazy" data-src="assets/images/solar-panels.png"
                                                                alt="<?=$v['name'.$lang]?>">
                                                        </div>
                                                        <p class="text"><?=($k<10)?'0'.($k+1):($k+1)?>.
                                                            <?=$v['name'.$lang]?></p>
                                                    </div>
                                                    <div class="content text-white">
                                                        <?=htmlspecialchars_decode($v['desc'.$lang])?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="img">
                                                    <?=$func->getImage(['class' => 'lazy','size-error' => '500x500x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                        </div>
                    </div>
                    <a class="uk-position-center-left uk-position-small uk-slidenav swiper-prev" href="#" tabindex="0"
                        role="button" aria-label="Previous slide">
                        <svg fill="rgba(51, 51, 51, 0.5)" width="20px" height="20px" viewBox="-78.5 0 512 512"
                            xmlns="http://www.w3.org/2000/svg">
                            <title>left</title>
                            <path d="M257 64L291 98 128 262 291 426 257 460 61 262 257 64Z"></path>
                        </svg>
                    </a>
                    <a class="uk-position-center-right uk-position-small uk-slidenav swiper-next" href="#" tabindex="0"
                        role="button" aria-label="Next slide">
                        <svg fill="rgba(51, 51, 51, 0.5)" width="20px" height="20px" viewBox="-77 0 512 512"
                            xmlns="http://www.w3.org/2000/svg">
                            <title>right</title>
                            <path d="M98 460L64 426 227 262 64 98 98 64 294 262 98 460Z"></path>
                        </svg>
                    </a>
                </div>
        </section>
    </div>
</div>
<?php } ?>

<?php if(count($newsnb) || count($dahoatdong)){ ?>
<!-- Tin tuc + Hoat dong du an (2 cot, mix theo demo) -->
<div class="Module Module-333">
    <div class="ModuleContent">
        <section class="home-8 uk-section-large">
            <div class="uk-container">
                <div class="section-title uk-text-center">
                    <p>CẬP NHẬT MỚI NHẤT</p>
                    <h3>Hoạt động &amp; kiến thức <span class="pts-brand-text">Phúc Thịnh Solar</span></h3>
                </div>
                <div class="row blog-2col-row">
                    <?php if(count($dahoatdong)) { ?>
                    <div class="col-md-6 blog-2col-item">
                        <div class="blog-2col-heading">
                            <h4>Hoạt động dự án</h4>
                            <a href="du-an-da-thuc-hien/" class="blog-2col-viewall">Xem tất cả</a>
                        </div>
                        <?php foreach($dahoatdong as $v) { ?>
                        <a class="blog-2col-row-item" href="store-manager/<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
                            <div class="blog-2col-thumb">
                                <?=$func->getImage(['class' => 'lazy', 'size-error' => '120x90x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                            </div>
                            <div class="blog-2col-info">
                                <p class="blog-2col-title"><?=$v['name'.$lang]?></p>
                                <span class="blog-2col-date"><?=date('d/m/Y',$v['date_created'])?></span>
                            </div>
                        </a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                    <?php if(count($newsnb)) { ?>
                    <div class="col-md-6 blog-2col-item">
                        <div class="blog-2col-heading">
                            <h4>Kiến thức năng lượng</h4>
                            <a href="tin-tuc/" class="blog-2col-viewall">Xem tất cả</a>
                        </div>
                        <?php foreach($newsnb as $v) { ?>
                        <a class="blog-2col-row-item" href="<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
                            <div class="blog-2col-thumb">
                                <?=$func->getImage(['class' => 'lazy', 'size-error' => '120x90x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                            </div>
                            <div class="blog-2col-info">
                                <p class="blog-2col-title"><?=$v['name'.$lang]?></p>
                                <span class="blog-2col-date"><?=date('d/m/Y',$v['date_created'])?></span>
                            </div>
                        </a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
</div>
<?php } ?>
