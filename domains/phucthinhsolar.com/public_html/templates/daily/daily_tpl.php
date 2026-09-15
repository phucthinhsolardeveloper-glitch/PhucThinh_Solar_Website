<div class="pts-dealer-hub">

<?php if(!empty($about1)){ ?>
<section class="pts-dl-hero" id="dang-ky-dai-ly">
    <?php if($dealerHero) { ?>
    <div class="pts-dl-hero-banner">
        <span class="pts-dl-hero-banner-media">
            <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '1920x600x1', 'upload' => $dealerHero['upload'], 'image' => $dealerHero['photo'], 'alt' => $dealerHero['name']])?>
        </span>
        <span class="pts-dl-hero-banner-shade" aria-hidden="true"></span>
        <div class="uk-container">
            <div class="pts-dl-hero-banner-copy">
                <span class="pts-dl-eyebrow">HỢP TÁC ĐẠI LÝ</span>
                <h1><?=$about1['name'.$lang]?></h1>
            </div>
        </div>
    </div>
    <?php } ?>
    <div class="uk-container">
        <div class="pts-dl-hero-grid">
            <div class="pts-dl-hero-copy">
                <div class="pts-dl-hero-desc"><?=htmlspecialchars_decode($about1['desc'.$lang])?></div>
            </div>
            <div class="pts-dl-form-card">
                <h2>Đăng ký làm đối tác phân phối</h2>
                <p class="pts-dl-form-note">Để lại thông tin, Phúc Thịnh Solar sẽ liên hệ tư vấn chính sách đại lý phù hợp.</p>
                <form action="" method="post" class="pts-dl-form">
                    <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                    <div class="pts-dl-form-field">
                        <input type="text" name="dataNewsletter[fullname]" class="pts-dl-input" required placeholder="Họ và tên*">
                    </div>
                    <div class="pts-dl-form-field">
                        <input type="tel" name="dataNewsletter[phone]" class="pts-dl-input" required placeholder="Số điện thoại*">
                    </div>
                    <div class="pts-dl-form-field">
                        <input type="email" name="dataNewsletter[email]" class="pts-dl-input" required placeholder="Email*">
                    </div>
                    <div class="pts-dl-form-field">
                        <select name="dataNewsletter[address]" class="pts-dl-input pts-dl-select devvn_cities" id="devvn_cities">
                            <option value="Tỉnh thành">Tỉnh thành</option>
                            <?php if(count($city)){foreach($city as $k => $v){ ?>
                            <option value="<?=$v['name']?>"><?=$v['name']?></option>
                            <?php }} ?>
                        </select>
                    </div>
                    <div class="pts-dl-form-field">
                        <input type="text" name="dataNewsletter[company]" class="pts-dl-input" required placeholder="Tên doanh nghiệp/Cửa hàng*">
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
                <h3><?=htmlspecialchars_decode($about2['name'.$lang])?></h3>
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
                <h3><?=htmlspecialchars_decode($about3['name'.$lang])?></h3>
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
                <h3>Khách hàng, đối tác nói gì về Phúc Thịnh Solar</h3>
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
