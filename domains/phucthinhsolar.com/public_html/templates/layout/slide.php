<section class="pts-home-hero">
    <div class="pts-hero-glow" aria-hidden="true"></div>
    <div class="pts-hero-accent" aria-hidden="true"></div>
    <div class="wrap-content pts-hero-inner">
        <div class="pts-hero-copy">
            <span class="pts-hero-eyebrow"><i class="fas fa-sun"></i> Đèn dự án · Đèn công trình</span>
            <h1>NĂNG LƯỢNG MẶT TRỜI<br><strong>CHO MỌI CÔNG TRÌNH</strong></h1>
            <p>Phúc Thịnh Solar — thiết bị năng lượng mặt trời chính hãng, thi công trọn gói và đồng hành kỹ thuật trên toàn quốc.</p>
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
    </div>
</section>
<?php /* Slider legacy được giữ lại để rollback, Theme V2 không render. */ ?>
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