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
    <div class="uk-container">
        <div class="pts-dl-hero-grid">
            <div class="pts-dl-hero-copy">
                <div class="pts-dl-hero-desc"><?=htmlspecialchars_decode($about1['desc'.$lang])?></div>
            </div>
            <div class="pts-dl-form-card" id="dang-ky-dai-ly">
                <h2>Đăng ký làm đối tác phân phối</h2>
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
                    <button type="submit" name="submit-newsletter2" class="pts-btn pts-btn-gold pts-dl-submit">Đăng ký ngay <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<?php if(!empty($about2)){ ?>
<section class="pts-dl-confidence" id="nang-luc-phan-phoi">
    <div class="uk-container">
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
                    <?=$func->getImage(['isLazy' => false, 'class' => 'w-100', 'sizes' => '800x500x1', 'size-error' => '800x500x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </span>
                <span class="pts-dl-product-name"><?=$v['name'.$lang]?></span>
            </a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

<?php if(!empty($about3)){ ?>
<section class="pts-dl-support" id="ho-tro-dai-ly">
    <div class="uk-container">
        <div class="pts-retail-section-head">
            <div class="section-title">
                <h2><?=htmlspecialchars_decode($about3['name'.$lang])?></h2>
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
