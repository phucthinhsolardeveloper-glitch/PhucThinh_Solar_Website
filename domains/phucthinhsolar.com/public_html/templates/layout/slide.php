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
       "slider" toàn banner vì chưa hợp gu, đây chỉ đổi ảnh phía sau. Có từ
       2 ảnh trở lên thì thêm nút mũi tên + chấm để tự bấm chuyển, không chỉ
       chờ tự động (yêu cầu 2026-09-11). */
    $heroBgUrls = array();
    if(!empty($heroBgList) && is_array($heroBgList))
    {
        foreach($heroBgList as $heroBgItem)
        {
            if(empty($heroBgItem['photo'])) continue;
            $heroBgUrls[] = THUMBS.'/1920x900x1/'.UPLOAD_PHOTO_L.$heroBgItem['photo'];
        }
    }

    /* Bản 2 (yêu cầu 2026-09-11, ĐẶT MẶC ĐỊNH): hero dạng "ảnh full-bleed +
       form tư vấn nổi bên phải + thanh số liệu kính mờ", tham khảo layout
       đối thủ. Bản 1 (ảnh crossfade + cột chứng nhận, section ngay dưới)
       giờ là bản phụ, ẩn mặc định. Nút .pts-hero-layout-toggle cho khách
       bấm đổi qua lại - JS lưu lựa chọn vào localStorage nên lần sau ghé
       lại vẫn giữ bản đã chọn. Form dùng LẠI đúng field/route xử lý với
       modal #ptsContactModal (dataContact[...], form_csrf, submit-contact ở
       sources/allpage.php) - không tạo bảng/API mới. content không có ô
       nhập ở layout này (theo tham khảo) nên đặt hidden field cố định để
       qua được validate bắt buộc "Nội dung không được trống" phía server.
       reCAPTCHA cần id hidden field RIÊNG (recaptchaResponseHero) vì
       #recaptchaResponseContact đã dùng cho modal - trùng id thì
       generateCaptcha() chỉ set được 1 trong 2. */
    $heroBgUrl2 = !empty($heroBgUrls) ? $heroBgUrls[0] : '';

    /* LAYOUT 3 (2026-09-12): carousel 4-slide thật, thêm SONG SONG 2 layout
       cũ theo yêu cầu chủ project "vẫn giữ layout hero cũ, k có bỏ, vì sẽ
       dùng song song, nó có nút chuyển mà" - không xoá/viết lại layout 1-2,
       chỉ mở rộng .pts-hero-layout-toggle từ chu kỳ 2 trạng thái (1<->2)
       thành 3 trạng thái (1->2->3->1). Dữ liệu tập trung 1 chỗ ở
       libraries/home_hero_carousel.php (getHeroCarouselSlides()); khi nào
       có màn Admin quản lý riêng, thay hàm này bằng query DB, giữ nguyên
       shape mảng để không phải sửa lại phần render bên dưới. */
    $heroCarouselSlides = getHeroCarouselSlides();
    $heroSlideCount = count($heroCarouselSlides);
?>
<div class="pts-hero-layouts">
<section class="pts-home-hero<?=$heroCerts ? ' pts-home-hero-split' : ''?><?=$heroBgUrls ? ' pts-home-hero-photo' : ''?> pts-hero-layout-panel" data-pts-hero-layout="1" hidden>
    <?php if($heroBgUrls) { ?>
    <div class="pts-hero-photo-layers" data-pts-hero-cycle="6000" aria-hidden="true">
        <?php foreach($heroBgUrls as $heroBgIndex => $heroBgUrl) { ?>
        <div class="pts-hero-photo-layer<?=$heroBgIndex === 0 ? ' is-active' : ''?>" style="background-image:url('<?=htmlspecialchars($heroBgUrl, ENT_QUOTES, 'UTF-8')?>')"></div>
        <?php } ?>
    </div>
    <div class="pts-hero-photo-overlay" aria-hidden="true"></div>
    <?php if(count($heroBgUrls) > 1) { ?>
    <div class="pts-hero-photo-nav" role="group" aria-label="Chuyển ảnh nền">
        <button type="button" class="pts-hero-photo-nav-btn pts-hero-photo-nav-prev" aria-label="Ảnh trước"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
        <div class="pts-hero-photo-dots">
            <?php foreach($heroBgUrls as $heroBgIndex => $heroBgUrl) { ?>
            <button type="button" class="pts-hero-photo-dot<?=$heroBgIndex === 0 ? ' is-active' : ''?>" data-pts-hero-index="<?=(int)$heroBgIndex?>" aria-label="Ảnh nền <?=(int)$heroBgIndex + 1?>"></button>
            <?php } ?>
        </div>
        <button type="button" class="pts-hero-photo-nav-btn pts-hero-photo-nav-next" aria-label="Ảnh tiếp theo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
    </div>
    <?php } ?>
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
                <a class="pts-btn pts-btn-gold pts-btn-lg" href="san-pham/">XEM SẢN PHẨM</a>
                <a class="pts-btn pts-btn-ghost pts-btn-lg" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal">NHẬN TƯ VẤN</a>
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

<section class="pts-hero2 pts-hero-layout-panel is-active" data-pts-hero-layout="2">
    <?php if($heroBgUrl2 !== '') { ?>
    <div class="pts-hero2-bg" style="background-image:url('<?=htmlspecialchars($heroBgUrl2, ENT_QUOTES, 'UTF-8')?>')" aria-hidden="true"></div>
    <?php } ?>
    <div class="pts-hero2-overlay" aria-hidden="true"></div>
    <div class="wrap-content pts-hero2-inner">
        <div class="pts-hero2-main">
            <div class="pts-hero2-copy">
                <span class="pts-hero2-eyebrow"><i class="fas fa-sun" aria-hidden="true"></i> Giải pháp năng lượng sạch cho mọi nhà</span>
                <h1 class="pts-hero2-title">ĐIỆN MẶT TRỜI<br><strong>CHO CUỘC SỐNG<br>BỀN VỮNG HƠN</strong></h1>
                <p class="pts-hero2-desc"><span class="pts-brand-text">Phúc Thịnh Solar</span> cung cấp giải pháp điện mặt trời trọn gói cho hộ gia đình, doanh nghiệp và công trình, giúp bạn tiết kiệm chi phí, chủ động nguồn điện và bảo vệ môi trường.</p>
                <div class="pts-hero2-benefits">
                    <div class="pts-hero2-benefit">
                        <i class="fas fa-certificate" aria-hidden="true"></i>
                        <div><strong>Sản phẩm chính hãng</strong><span>Từ các thương hiệu hàng đầu</span></div>
                    </div>
                    <div class="pts-hero2-benefit">
                        <i class="fas fa-tools" aria-hidden="true"></i>
                        <div><strong>Thi công chuyên nghiệp</strong><span>Đảm bảo an toàn, đúng tiến độ</span></div>
                    </div>
                    <div class="pts-hero2-benefit">
                        <i class="fas fa-headset" aria-hidden="true"></i>
                        <div><strong>Hỗ trợ trọn đời</strong><span>Bảo hành dài hạn, đồng hành cùng khách hàng</span></div>
                    </div>
                </div>
                <div class="pts-hero2-actions">
                    <a class="pts-btn pts-btn-gold pts-btn-lg" href="#pts-hero2-form"><i class="fas fa-phone-alt" aria-hidden="true"></i> NHẬN TƯ VẤN NGAY</a>
                    <a class="pts-btn pts-btn-ghost pts-btn-lg" href="giai-phap/"><i class="fas fa-play" aria-hidden="true"></i> XEM GIẢI PHÁP</a>
                </div>
            </div>
            <aside class="pts-hero2-form-card" id="pts-hero2-form">
                <span class="pts-hero2-form-badge">Tiết kiệm đến<br><strong>70%</strong><br>tiền điện</span>
                <div class="pts-hero2-form-head">
                    <span class="pts-hero2-form-eyebrow"><i class="fas fa-headset" aria-hidden="true"></i> TƯ VẤN MIỄN PHÍ</span>
                    <h3>ĐĂNG KÝ NHẬN TƯ VẤN</h3>
                    <p>Để lại thông tin, chuyên gia Phúc Thịnh Solar sẽ liên hệ tư vấn giải pháp phù hợp nhất cho bạn!</p>
                </div>
                <form action="" method="post" class="pts-hero2-form-el">
                    <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                    <input type="hidden" name="dataContact[content]" class="pts-hero2-content-field" value="Yêu cầu tư vấn từ banner trang chủ">
                    <label class="pts-hero2-field"><i class="fas fa-user" aria-hidden="true"></i><input type="text" name="dataContact[fullname]" placeholder="Họ và tên *" maxlength="100" autocomplete="name" required></label>
                    <label class="pts-hero2-field"><i class="fas fa-phone-alt" aria-hidden="true"></i><input type="tel" name="dataContact[phone]" placeholder="Số điện thoại *" maxlength="20" autocomplete="tel" inputmode="tel" required></label>
                    <label class="pts-hero2-field"><i class="fas fa-envelope" aria-hidden="true"></i><input type="email" name="dataContact[email]" placeholder="Email (nếu có)" maxlength="150" autocomplete="email"></label>
                    <label class="pts-hero2-field pts-hero2-field-select"><i class="fas fa-home" aria-hidden="true"></i>
                        <select name="dataContact[subject]" class="pts-hero2-subject-field">
                            <option value="">Nhu cầu của bạn</option>
                            <option value="Đèn năng lượng mặt trời">Đèn năng lượng mặt trời</option>
                            <option value="Điện mặt trời">Điện mặt trời</option>
                            <option value="Thiết bị (inverter, tấm pin, pin lưu trữ...)">Thiết bị (inverter, tấm pin, pin lưu trữ...)</option>
                            <option value="Đại lý">Đại lý</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </label>
                    <button type="submit" name="submit-contact" class="pts-hero2-submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> GỬI THÔNG TIN NGAY</button>
                    <p class="pts-hero2-privacy"><i class="fas fa-lock" aria-hidden="true"></i> Thông tin của bạn được bảo mật tuyệt đối, chỉ phục vụ cho việc tư vấn.</p>
                    <input type="hidden" name="recaptcha_response_contact" id="recaptchaResponseHero">
                </form>
            </aside>
        </div>
        <div class="pts-hero2-stats">
            <div class="pts-hero2-stat"><i class="fas fa-users" aria-hidden="true"></i><div><strong>10.000+</strong><span>Khách hàng tin tưởng</span></div></div>
            <div class="pts-hero2-stat"><i class="fas fa-solar-panel" aria-hidden="true"></i><div><strong>500+</strong><span>Công trình đã triển khai</span></div></div>
            <div class="pts-hero2-stat"><i class="fas fa-award" aria-hidden="true"></i><div><strong>5+</strong><span>Năm kinh nghiệm</span></div></div>
            <div class="pts-hero2-stat"><i class="fas fa-seedling" aria-hidden="true"></i><div><strong>Giảm hàng nghìn tấn CO₂</strong><span>Vì một Việt Nam xanh hơn</span></div></div>
        </div>
    </div>
</section>

<?php if($heroSlideCount) { ?>
<section class="pts-hero-carousel pts-hero-layout-panel" data-pts-hero-layout="3" hidden aria-roledescription="carousel" aria-label="Giải pháp Phúc Thịnh Solar" id="ptsHeroCarousel">
    <div class="pts-hero-carousel-track">
        <?php foreach($heroCarouselSlides as $heroSlideIndex => $heroSlideData) {
            $heroIsFirst = ($heroSlideIndex === 0);
            $heroSlideAlt = htmlspecialchars((string)$heroSlideData['alt'], ENT_QUOTES, 'UTF-8');
            $heroDesktopSrc = ASSET.THUMBS.'/1920x900x1/'.UPLOAD_PHOTO_L.$heroSlideData['image'];
            $heroMobileSrc = ASSET.THUMBS.'/900x1100x1/'.UPLOAD_PHOTO_L.$heroSlideData['image'];
            /* Layout 3 dùng chung <h1> với layout 1/2 (chỉ 1 layout hiển thị tại
               1 thời điểm) - slide đầu vẫn dùng h1 để giữ đúng 1 H1/trang khi
               layout 3 là bản đang active; các slide sau dùng h2. */
            $heroTitleTag = $heroIsFirst ? 'h1' : 'h2';
        ?>
        <article class="pts-hero-carousel-slide<?=$heroIsFirst ? ' is-active' : ''?><?=!empty($heroSlideData['theme']) && $heroSlideData['theme'] === 'light' ? ' is-theme-light' : ''?>"
            data-hero-slide="<?=(int)$heroSlideIndex?>"
            aria-roledescription="slide"
            aria-label="<?=(int)$heroSlideIndex + 1?> trên <?=$heroSlideCount?>: <?=htmlspecialchars((string)$heroSlideData['eyebrow'], ENT_QUOTES, 'UTF-8')?>"
            <?=$heroIsFirst ? '' : 'aria-hidden="true"'?>>
            <div class="pts-hero-carousel-visual" aria-hidden="true">
                <picture>
                    <source media="(max-width:760px)" srcset="<?=htmlspecialchars($heroMobileSrc, ENT_QUOTES, 'UTF-8')?>">
                    <img class="pts-hero-carousel-img" src="<?=htmlspecialchars($heroDesktopSrc, ENT_QUOTES, 'UTF-8')?>" alt="" width="1920" height="900" <?=$heroIsFirst ? 'fetchpriority="high"' : 'loading="lazy"'?>>
                </picture>
                <div class="pts-hero-carousel-scrim"></div>
            </div>
            <div class="wrap-content pts-hero-carousel-inner">
                <div class="pts-hero-carousel-copy">
                    <span class="pts-hero-carousel-eyebrow"><?=htmlspecialchars((string)$heroSlideData['eyebrow'], ENT_QUOTES, 'UTF-8')?></span>
                    <<?=$heroTitleTag?> class="pts-hero-carousel-title">
                        <span><?=htmlspecialchars((string)$heroSlideData['titleLine1'], ENT_QUOTES, 'UTF-8')?></span>
                        <span class="pts-hero-carousel-title-muted"><?=htmlspecialchars((string)$heroSlideData['titleLine2'], ENT_QUOTES, 'UTF-8')?></span>
                    </<?=$heroTitleTag?>>
                    <p class="pts-hero-carousel-desc"><?=htmlspecialchars((string)$heroSlideData['description'], ENT_QUOTES, 'UTF-8')?></p>
                    <div class="pts-hero-carousel-actions">
                        <?php $heroPrimary = $heroSlideData['primaryCTA']; ?>
                        <a class="pts-btn pts-btn-gold pts-btn-lg" href="<?=htmlspecialchars((string)$heroPrimary['href'], ENT_QUOTES, 'UTF-8')?>"<?php if(!empty($heroPrimary['modal'])) { ?> data-toggle="modal" data-target="#<?=htmlspecialchars((string)$heroPrimary['modal'], ENT_QUOTES, 'UTF-8')?>"<?php } ?>><?=htmlspecialchars((string)$heroPrimary['label'], ENT_QUOTES, 'UTF-8')?></a>
                        <?php if(!empty($heroSlideData['secondaryCTA'])) { $heroSecondary = $heroSlideData['secondaryCTA']; ?>
                        <a class="pts-hero-carousel-text-link" href="<?=htmlspecialchars((string)$heroSecondary['href'], ENT_QUOTES, 'UTF-8')?>"<?php if(!empty($heroSecondary['modal'])) { ?> data-toggle="modal" data-target="#<?=htmlspecialchars((string)$heroSecondary['modal'], ENT_QUOTES, 'UTF-8')?>"<?php } ?>><?=htmlspecialchars((string)$heroSecondary['label'], ENT_QUOTES, 'UTF-8')?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <?php } ?>
                    </div>
                    <?php if(!empty($heroSlideData['tags'])) { ?>
                    <p class="pts-hero-carousel-tags"><?=implode(' <i aria-hidden="true"></i> ', array_map(function($heroTag){ return htmlspecialchars((string)$heroTag, ENT_QUOTES, 'UTF-8'); }, $heroSlideData['tags']))?></p>
                    <?php } ?>
                </div>
            </div>
        </article>
        <?php } ?>
    </div>

    <?php if($heroSlideCount > 1) { ?>
    <div class="wrap-content pts-hero-carousel-control">
        <div class="pts-hero-carousel-pagination" role="group" aria-label="Chọn slide">
            <?php foreach($heroCarouselSlides as $heroSlideIndex => $heroSlideData) { ?>
            <button type="button" class="pts-hero-carousel-dot<?=$heroSlideIndex === 0 ? ' is-active' : ''?>" data-hero-goto="<?=(int)$heroSlideIndex?>" aria-label="Slide <?=(int)$heroSlideIndex + 1?>: <?=htmlspecialchars((string)$heroSlideData['eyebrow'], ENT_QUOTES, 'UTF-8')?>" <?=$heroSlideIndex === 0 ? 'aria-current="true"' : ''?>><?=str_pad((int)$heroSlideIndex + 1, 2, '0', STR_PAD_LEFT)?></button>
            <?php } ?>
        </div>
        <div class="pts-hero-carousel-nav">
            <button type="button" class="pts-hero-carousel-nav-btn" id="ptsHeroPause" aria-label="Dừng chuyển slide" aria-pressed="false"><i class="fas fa-pause" aria-hidden="true"></i></button>
            <button type="button" class="pts-hero-carousel-nav-btn" id="ptsHeroPrev" aria-label="Slide trước"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" class="pts-hero-carousel-nav-btn" id="ptsHeroNext" aria-label="Slide tiếp theo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
    <?php } ?>
</section>
<?php } ?>

<button type="button" class="pts-hero-layout-toggle" data-pts-hero-layout-target="1" aria-label="Xem giao diện banner khác" title="Xem giao diện khác">
    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
</button>
</div>
<?php } ?>
