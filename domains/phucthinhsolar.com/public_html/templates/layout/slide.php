<?php
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
?>
<section class="pts-home-hero<?=$heroCerts ? ' pts-home-hero-split' : ''?>">
    <div class="pts-hero-glow" aria-hidden="true"></div>
    <div class="pts-hero-glow-cool" aria-hidden="true"></div>
    <div class="pts-hero-texture" aria-hidden="true"></div>
    <div class="pts-hero-accent" aria-hidden="true"></div>
    <div class="wrap-content pts-hero-inner">
        <div class="pts-hero-copy">
            <span class="pts-hero-eyebrow"><i class="fas fa-sun"></i> Đèn dự án · Đèn công trình</span>
            <h1>NĂNG LƯỢNG MẶT TRỜI<br><strong>CHO MỌI CÔNG TRÌNH</strong></h1>
            <p><span class="pts-brand-text">Phúc Thịnh Solar</span> — thiết bị năng lượng mặt trời chính hãng, thi công trọn gói và đồng hành kỹ thuật trên toàn quốc.</p>
            <div class="pts-hero-actions">
                <a class="pts-btn pts-btn-gold" href="san-pham/">MUA NGAY</a>
                <a class="pts-btn pts-btn-ghost" href="giai-phap/">XEM GIẢI PHÁP</a>
            </div>
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
<?php /* Theme V2 không render slider legacy. */ ?>
<?php if(false && count($slider)) { ?>
<div class="slideshow">
    <div id="jssor_1"
        style="position:relative;margin:0 auto;top:0px;left:0px;width:1920px;height:780px;overflow:hidden;visibility:hidden;">
        <div data-u="loading" class="jssorl-009-spin"
            style="position:absolute;top:0px;left:0px;width:100%;height:100%;text-align:center;background-color:rgba(0,0,0,0.7);">
            <img style="margin-top:-19px;position:relative;top:50%;width:38px;height:38px;"
                src="assets/images/spin.svg" />
        </div>
        <div data-u="slides"
            style="cursor:default;position:relative;top:0px;left:0px;width:1920px;height:780px;overflow:hidden;">
            <?php foreach($slider as $v) { ?>
            <div>
                <a href="<?=$v['link']?>" title="<?=$v['name'.$lang]?>">
                    <?php if ($v['show_stt'] == 1) { ?>
                        <video class="video-slider" data-html="true" autoplay muted loop playsinline>
                            <source src="<?= UPLOAD_FILE_L . $v['video'] ?>" type="video/mp4">
                        </video>
                    <?php } else { ?>
                        <img onerror="this.src='thumbs/1440x560x1/assets/images/noimage.png';" data-u="image" src="<?=UPLOAD_PHOTO_L.$v['photo']?>" />
                    <?php } ?>
                </a>
            </div>
            <?php } ?>
        </div>
        <!-- <div data-u="navigator" class="jssorb032" style="position:absolute;bottom:12px;right:12px;" data-autocenter="1"
                data-scale="0.5" data-scale-bottom="0.75">
                <div data-u="prototype" class="i" style="width:16px;height:16px;">
                    <svg viewBox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
                        <circle class="b" cx="8000" cy="8000" r="5800"></circle>
                    </svg>
                </div>
            </div>
        <div data-u="arrowleft" class="jssora051" style="width:65px;height:65px;top:0px;left:25px;" data-autocenter="2"
            data-scale="0.75" data-scale-left="0.75">
            <svg viewBox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
                <polyline class="a" points="11040,1920 4960,8000 11040,14080 "></polyline>
            </svg>
        </div>
        <div data-u="arrowright" class="jssora051" style="width:65px;height:65px;top:0px;right:25px;"
            data-autocenter="2" data-scale="0.75" data-scale-right="0.75">
            <svg viewBox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
                <polyline class="a" points="4960,1920 11040,8000 4960,14080 "></polyline>
            </svg>
        </div> -->
    </div>
</div>
<?php } ?>
