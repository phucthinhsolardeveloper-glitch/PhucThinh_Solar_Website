<div class="pts-dealer-hub">

<?php if(!empty($about1)){ ?>
<section class="pts-dl-hero">
    <div class="pts-dl-hero-banner<?=$dealerHero ? '' : ' is-text-only'?>">
        <?php if($dealerHero) { ?>
        <span class="pts-dl-hero-banner-media">
            <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '1920x600x1', 'upload' => $dealerHero['upload'], 'image' => $dealerHero['photo'], 'alt' => $dealerHero['name']])?>
        </span>
        <span class="pts-dl-hero-banner-shade" aria-hidden="true"></span>
        <?php } ?>
        <div class="uk-container">
            <div class="pts-dl-hero-banner-copy">
                <h1><?=$about1['name'.$lang]?></h1>
            </div>
        </div>
    </div>
    <div class="pts-dl-quick-nav-wrap">
        <nav class="uk-container pts-dl-quick-nav" aria-label="Điều hướng trang đại lý">
            <a href="<?=$func->getCurrentPagePath()?>#nang-luc-phan-phoi"><i class="fas fa-solar-panel" aria-hidden="true"></i><span>Sản phẩm phân phối</span></a>
            <a href="<?=$func->getCurrentPagePath()?>#ho-tro-dai-ly"><i class="fas fa-handshake" aria-hidden="true"></i><span>Quyền lợi đại lý</span></a>
            <a href="<?=$func->getCurrentPagePath()?>#dang-ky-dai-ly"><i class="far fa-edit" aria-hidden="true"></i><span>Đăng ký hợp tác</span></a>
        </nav>
    </div>
</section>
<?php } ?>

<?php if(!empty($photo_daily)){ ?>
<section class="pts-dl-mobile-preview" aria-label="Sản phẩm phân phối">
    <div class="uk-container">
        <div class="pts-dl-mobile-preview-heading">
            <h2>Sản phẩm phân phối</h2>
            <a href="<?=$func->getCurrentPagePath()?>#nang-luc-phan-phoi">Xem tất cả <span aria-hidden="true">→</span></a>
        </div>
        <div class="pts-dl-mobile-preview-grid">
            <?php foreach(array_slice($photo_daily, 0, 2) as $v){
                $previewHref = !empty($v['link']) ? $v['link'] : $func->getCurrentPagePath().'#nang-luc-phan-phoi';
            ?>
            <a href="<?=htmlspecialchars($previewHref, ENT_QUOTES, 'UTF-8')?>" class="pts-dl-mobile-preview-item">
                <span class="pts-dl-mobile-preview-image">
                    <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '480x300x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </span>
                <strong><?=htmlspecialchars($v['name'.$lang], ENT_QUOTES, 'UTF-8')?></strong>
            </a>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if(!empty($about1) || !empty($about2)){ ?>
<section class="pts-dl-confidence pts-dl-overview" id="nang-luc-phan-phoi">
    <div class="uk-container">
        <div class="pts-dl-confidence-layout">
            <div class="pts-dl-form-card" id="dang-ky-dai-ly">
                <h2 id="pts-dl-registration-title">Đăng ký làm đối tác phân phối</h2>
                <p class="pts-dl-form-note">Để lại thông tin, Phúc Thịnh Solar sẽ liên hệ tư vấn chính sách đại lý phù hợp.</p>
                <form action="" method="post" class="pts-dl-form">
                    <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                    <div class="pts-dl-form-field">
                        <label class="pts-dl-label" for="dealer-fullname">Họ và tên</label>
                        <input id="dealer-fullname" type="text" name="dataNewsletter[fullname]" class="pts-dl-input" autocomplete="name" maxlength="100" required placeholder="Nguyễn Văn A">
                    </div>
                    <div class="pts-dl-form-field">
                        <label class="pts-dl-label" for="dealer-phone">Số điện thoại</label>
                        <input id="dealer-phone" type="tel" name="dataNewsletter[phone]" class="pts-dl-input" autocomplete="tel" maxlength="20" inputmode="tel" required placeholder="09xx xxx xxx">
                    </div>
                    <div class="pts-dl-form-field">
                        <label class="pts-dl-label" for="dealer-email">Email</label>
                        <input id="dealer-email" type="email" name="dataNewsletter[email]" class="pts-dl-input" autocomplete="email" maxlength="150" required placeholder="email@doanhnghiep.vn">
                    </div>
                    <div class="pts-dl-form-field">
                        <label class="pts-dl-label" for="devvn_cities">Tỉnh/thành phố</label>
                        <select name="dataNewsletter[address]" class="pts-dl-input pts-dl-select devvn_cities" id="devvn_cities" autocomplete="address-level1" required>
                            <option value="">Chọn tỉnh/thành phố</option>
                            <?php if(count($city)){foreach($city as $k => $v){ ?>
                            <option value="<?=htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php }} ?>
                        </select>
                    </div>
                    <div class="pts-dl-form-field">
                        <label class="pts-dl-label" for="dealer-company">Doanh nghiệp hoặc cửa hàng</label>
                        <input id="dealer-company" type="text" name="dataNewsletter[company]" class="pts-dl-input" autocomplete="organization" maxlength="180" required placeholder="Tên đơn vị của bạn">
                    </div>
                    <input type="hidden" name="recaptcha_response_newsletter2" id="recaptchaResponseNewsletter2">
                    <button type="submit" name="submit-newsletter2" value="1" class="pts-btn pts-btn-gold pts-dl-submit">Đăng ký ngay <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                </form>
            </div>
            <div class="pts-dl-confidence-main">
                <?php if(!empty($about1)){ ?>
                <div class="pts-dl-hero-copy">
                    <div class="pts-dl-hero-desc"><?=htmlspecialchars_decode($about1['desc'.$lang])?></div>
                    <a class="pts-dl-overview-cta" href="<?=$func->getCurrentPagePath()?>#dang-ky-dai-ly">Đăng ký hợp tác <span aria-hidden="true">→</span></a>
                </div>
                <?php } ?>
                <?php if(!empty($about2)){ ?>
                <div class="pts-dl-confidence-content">
                <div class="pts-retail-section-head">
                    <div class="section-title">
                        <h2><?=htmlspecialchars_decode($about2['name'.$lang])?></h2>
                    </div>
                </div>
                <div class="pts-dl-confidence-desc"><?=htmlspecialchars_decode($about2['desc'.$lang])?></div>
                <?php if(count($photo_daily)){ ?>
                <div class="pts-dl-product-grid">
                    <?php foreach($photo_daily as $v){ ?>
                    <a class="pts-dl-product-card" href="<?=$v['link']?>" target="_blank" title="<?=$v['name'.$lang]?>">
                        <span class="pts-dl-product-media">
                            <?php /* 2026-09-22: 480x500 -> 480x300 (giữ đúng tỉ lệ 8:5 của .pts-dl-product-media).
                               Lưới 3 cột hiện hiển thị ảnh này rộng 239-423px tùy màn hình (đo thật 1440-2560px),
                               nhưng thumbnail cũ 800px luôn tải full bất kể khung hiển thị nhỏ hơn nhiều lần -
                               tỉ lệ natural/displayed tới 3.35x ở 1440px, vượt ngưỡng cảnh báo lãng phí băng
                               thông (image-resolution.spec.js, ngưỡng 3.0x). 480px vẫn đủ nét ở 2x DPR cho
                               khung hiển thị phổ biến nhất (1440px), chỉ hơi mềm ở màn 2K/2560px rộng nhất. */ ?>
                            <?=$func->getImage(['isLazy' => false, 'class' => 'w-100', 'sizes' => '480x300x1', 'size-error' => '480x300x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </span>
                        <span class="pts-dl-product-name"><?=$v['name'.$lang]?></span>
                    </a>
                    <?php } ?>
                </div>
                <?php } ?>
                </div>
                <?php } ?>
            </div>

        </div>
    </div>
</section>
<?php } ?>

<?php if(!empty($about3)){ ?>
<section class="pts-dl-support" id="ho-tro-dai-ly">
    <div class="uk-container">
        <div class="pts-retail-section-head">
            <div class="section-title">
                <h2><?=preg_replace('/<\/?br\s*\/?>/i', ' ', htmlspecialchars_decode($about3['name'.$lang]))?></h2>
            </div>
        </div>
        <div class="pts-dl-support-desc"><?=htmlspecialchars_decode($about3['desc'.$lang])?></div>
        <?php if(count($camketdaily)){ ?>
        <div class="pts-dl-support-grid">
            <?php foreach($camketdaily as $v){ ?>
            <div class="pts-dl-support-card">
                <span class="pts-dl-support-icon">
                    <?=$func->getImage(['class' => 'lazy', 'size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </span>
                <h4><?=$v['name'.$lang]?></h4>
                <div class="pts-dl-support-copy"><?=htmlspecialchars_decode($v['desc'.$lang])?></div>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

<?php if($phanhoidaily){ ?>
<section class="pts-dl-testimonials">
    <div class="uk-container">
        <div class="pts-retail-section-head">
            <div class="section-title">
                <h2>Phản hồi từ khách hàng và đối tác</h2>
            </div>
        </div>
        <div class="pts-dl-testimonial-grid">
            <?php foreach($phanhoidaily as $k => $v){ ?>
            <div class="pts-dl-testimonial-card">
                <div class="pts-dl-testimonial-quote"><?=htmlspecialchars_decode($v['content'.$lang])?></div>
                <div class="pts-dl-testimonial-person">
                    <span class="pts-dl-testimonial-avatar">
                        <?=$func->getImage(['class' => 'lazy', 'size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                    </span>
                    <span>
                        <strong><?=$v['name'.$lang]?></strong>
                        <small><?=htmlspecialchars_decode($v['desc'.$lang])?></small>
                    </span>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if(count($solieudaily)){ ?>
<section class="pts-dl-stats">
    <div class="uk-container">
        <div class="pts-dl-stats-head">TỰ HÀO PHÚC THỊNH SOLAR</div>
        <div class="pts-dl-stats-grid">
            <?php foreach($solieudaily as $v){ ?>
            <div class="pts-dl-stats-item">
                <h3><span class="counter so" data-count="<?=$v['solieu']?>">0</span>+</h3>
                <div class="pts-dl-stats-label"><?=$v['name'.$lang]?></div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

</div>
