<?php
    /* Hero trang chủ có 2 chế độ, admin chọn ở Cài đặt chung > "Hero trang chủ"
       (options['home_hero_mode'], mặc định 'slider'):
       - 'slider': banner slide ảnh/video thật, quản lý qua "Slideshow"
         (index.php?com=photo&act=man_photo&type=slide) - hạ tầng đã có sẵn từ
         trước (upload, sắp xếp, bật/tắt, link riêng từng slide), chỉ là bị tắt
         render ở Theme V2.
       - 'text': Hero chữ tĩnh trước đó (headline, số liệu tin cậy, chứng nhận) -
         giữ lại làm phương án dự phòng, không xoá hẳn (audit UX 2026-09-08,
         yêu cầu chủ site "có thể giữ nguyên như hiện tại hoặc bật dạng slide"). */
    $heroMode = (isset($optsetting['home_hero_mode']) && $optsetting['home_hero_mode'] === 'text') ? 'text' : 'slider';
    $heroSlides = (!empty($slider) && is_array($slider)) ? $slider : array();
    if($heroMode === 'slider' && !count($heroSlides)) $heroMode = 'text'; /* chưa có slide nào thì tự rơi về bản chữ, tránh trang chủ trống đầu trang */
?>
<?php if($heroMode === 'slider') { ?>
<section class="pts-hero-slider">
    <div class="owl-page owl-carousel owl-theme" data-xsm-items="1:0" data-sm-items="1:0" data-md-items="1:0"
        data-lg-items="1:0" data-xlg-items="1:0" data-rewind="1" data-autoplay="1" data-loop="1" data-lazyload="0"
        data-mousedrag="1" data-touchdrag="1" data-smartspeed="600" data-autoplayspeed="600" data-autoplaytimeout="5500"
        data-dots="1" data-nav="1"
        data-navtext="
        <svg fill='#fff' width='20px' height='20px' viewBox='-78.5 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>left</title><path d='M257 64L291 98 128 262 291 426 257 460 61 262 257 64Z' /></svg>
        |
        <svg fill='#fff' width='20px' height='20px' viewBox='-77 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>right</title><path d='M98 460L64 426 227 262 64 98 98 64 294 262 98 460Z' /></svg>"
        data-navcontainer=".pts-hero-slider-nav">
        <?php foreach($heroSlides as $heroSlide) {
            $heroSlideLink = trim((string)$heroSlide['link']);
            $heroSlideModalTarget = in_array($heroSlideLink, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($heroSlideLink, '#') : '';
            $heroSlideAlt = htmlspecialchars((string)$heroSlide['name'.$lang], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="pts-hero-slide">
            <?php if($heroSlideLink !== '') { ?>
            <a href="<?=htmlspecialchars($heroSlideLink, ENT_QUOTES, 'UTF-8')?>" title="<?=$heroSlideAlt?>"<?php if($heroSlideModalTarget !== '') { ?> data-toggle="modal" data-target="#<?=$heroSlideModalTarget?>"<?php } ?>>
            <?php } ?>
                <?php if((int)$heroSlide['show_stt'] === 1 && !empty($heroSlide['video'])) { ?>
                <video class="pts-hero-slide-media" autoplay muted loop playsinline>
                    <source src="<?=UPLOAD_FILE_L.$heroSlide['video']?>" type="video/mp4">
                </video>
                <?php } else { ?>
                <?=$func->getImage(['isLazy' => false, 'class' => 'pts-hero-slide-media', 'sizes' => '1920x712x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $heroSlide['photo'], 'alt' => $heroSlideAlt])?>
                <?php } ?>
            <?php if($heroSlideLink !== '') { ?></a><?php } ?>
        </div>
        <?php } ?>
    </div>
    <div class="pts-hero-slider-nav control-owl transition"></div>
</section>

<div class="pts-hero-substrip">
    <div class="wrap-content pts-hero-substrip-inner">
        <div class="pts-hero-substrip-actions">
            <a class="pts-btn pts-btn-gold" href="san-pham/">XEM SẢN PHẨM</a>
            <a class="pts-btn pts-btn-ghost" href="giai-phap/">XEM GIẢI PHÁP</a>
        </div>
        <div class="pts-hero-substrip-stats">
            <div>
                <strong>10.000+</strong>
                <span>Khách hàng tin dùng</span>
            </div>
            <div>
                <strong>50+</strong>
                <span>Nhân sự đồng hành</span>
            </div>
            <div>
                <strong>5+</strong>
                <span>Năm kinh nghiệm</span>
            </div>
        </div>
    </div>
</div>
<?php } else {
    /* Chứng nhận uỷ quyền: tối đa 3 tấm xếp chồng, tên hãng lấy từ toàn bộ danh sách. */
    $heroCerts = array();
    $heroCertBrands = array();
    if(!empty($chungnhan) && is_array($chungnhan))
    {
        foreach($chungnhan as $heroCert)
        {
            if(empty($heroCert['photo'])) continue;
            if(count($heroCerts) < 3) $heroCerts[] = $heroCert;
            $heroCertName = trim((string)$heroCert['name'.$lang]);
            if($heroCertName !== '') $heroCertBrands[] = $heroCertName;
        }
    }
    /* Ảnh nền tuỳ chọn (Admin > Ảnh nền Hero trang chủ) thay cho gradient mặc
       định - lớp phủ tối pts-hero-photo-overlay giữ chữ/hiệu ứng vàng-navy
       đọc được trên nền ảnh thật, không đổi bố cục hay hiệu ứng phía trên.
       Nhiều ảnh -> xếp chồng nhiều layer rồi JS đổi class is-active theo chu
       kỳ, CSS lo phần fade (transition opacity) - không dùng chế độ
       "slider" toàn banner vì chưa hợp gu, đây chỉ đổi ảnh phía sau. */
    $heroBgUrls = array();
    if(!empty($heroBgList) && is_array($heroBgList))
    {
        foreach($heroBgList as $heroBgItem)
        {
            if(empty($heroBgItem['photo'])) continue;
            $heroBgUrls[] = THUMBS.'/1920x900x1/'.UPLOAD_PHOTO_L.$heroBgItem['photo'];
        }
    }
?>
<section class="pts-home-hero<?=$heroCerts ? ' pts-home-hero-split' : ''?><?=$heroBgUrls ? ' pts-home-hero-photo' : ''?>">
    <?php if($heroBgUrls) { ?>
    <div class="pts-hero-photo-layers" data-pts-hero-cycle="6000" aria-hidden="true">
        <?php foreach($heroBgUrls as $heroBgIndex => $heroBgUrl) { ?>
        <div class="pts-hero-photo-layer<?=$heroBgIndex === 0 ? ' is-active' : ''?>" style="background-image:url('<?=htmlspecialchars($heroBgUrl, ENT_QUOTES, 'UTF-8')?>')"></div>
        <?php } ?>
    </div>
    <div class="pts-hero-photo-overlay" aria-hidden="true"></div>
    <?php } ?>
    <div class="pts-hero-glow" aria-hidden="true"></div>
    <div class="pts-hero-glow-cool" aria-hidden="true"></div>
    <div class="pts-hero-texture" aria-hidden="true"></div>
    <div class="wrap-content pts-hero-inner">
        <div class="pts-hero-copy">
            <span class="pts-hero-eyebrow"><i class="fas fa-sun"></i> Đèn năng lượng mặt trời là nhóm sản phẩm chủ lực</span>
            <h1>THIẾT BỊ VÀ GIẢI PHÁP<br><strong>NĂNG LƯỢNG MẶT&nbsp;TRỜI</strong></h1>
            <p><span class="pts-brand-text">Phúc Thịnh Solar</span> cung cấp đèn năng lượng mặt trời chính hãng, thiết bị điện mặt trời và giải pháp thi công cho gia đình, đại lý và công trình.</p>
            <div class="pts-hero-actions">
                <a class="pts-btn pts-btn-gold" href="san-pham/">XEM SẢN PHẨM</a>
                <a class="pts-btn pts-btn-ghost" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal">NHẬN TƯ VẤN</a>
            </div>
            <p class="pts-hero-dealer-link">Bạn muốn kinh doanh? <a href="#ptsDealerModal" data-toggle="modal" data-target="#ptsDealerModal">Đăng ký đại lý <i class="fas fa-angle-right"></i></a></p>
            <div class="pts-hero-stats">
                <div>
                    <strong>10.000+</strong>
                    <span>Khách hàng tin dùng</span>
                </div>
                <div>
                    <strong>50+</strong>
                    <span>Nhân sự đồng hành</span>
                </div>
                <div>
                    <strong>5+</strong>
                    <span>Năm kinh nghiệm</span>
                </div>
            </div>
        </div>
        <?php if($heroCerts) { ?>
        <aside class="pts-hero-trust">
            <span class="pts-hero-trust-label"><i class="fas fa-award"></i> Nhà phân phối uỷ quyền</span>
            <div class="pts-hero-cert-stack">
                <?php foreach($heroCerts as $heroCertIndex => $heroCert) { ?>
                    <figure class="pts-hero-cert pts-hero-cert-<?=(int)$heroCertIndex?>">
                        <?=$func->getImage(array('isLazy' => false, 'class' => '', 'sizes' => '300x420x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $heroCert['photo'], 'alt' => 'Chứng nhận uỷ quyền '.trim((string)$heroCert['name'.$lang])))?>
                    </figure>
                <?php } ?>
            </div>
            <?php if($heroCertBrands) { ?>
                <ul class="pts-hero-trust-brands">
                    <?php foreach($heroCertBrands as $heroCertBrand) { ?>
                        <li><?=htmlspecialchars($heroCertBrand, ENT_QUOTES, 'UTF-8')?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
            <a class="pts-hero-trust-link" href="ve-chung-toi/">Xem chứng nhận <i class="fas fa-arrow-right"></i></a>
        </aside>
        <?php } ?>
    </div>
</section>
<?php } ?>
