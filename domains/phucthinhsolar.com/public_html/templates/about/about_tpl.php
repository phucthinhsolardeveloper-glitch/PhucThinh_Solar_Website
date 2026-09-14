<?php
    $aboutEscape = static function($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };
    $aboutStoryImage = !empty($aboutHistoryRecord['photo']) ? $aboutHistoryRecord['photo'] : (!empty($aboutStoryRecord['photo']) ? $aboutStoryRecord['photo'] : '');
?>
<main class="pts-about-page">
    <section class="pts-about-story" aria-labelledby="about-story-title">
        <div class="uk-container">
            <div class="pts-about-story-grid">
                <div class="pts-about-story-copy">
                    <span class="pts-about-eyebrow">HÀNH TRÌNH PHÁT TRIỂN</span>
                    <h1 id="about-story-title">Câu chuyện Phúc Thịnh Solar</h1>
                    <?php if($aboutStoryIntro !== '') { ?>
                    <p class="pts-about-lead"><?=$aboutEscape($aboutStoryIntro)?></p>
                    <?php } ?>

                    <?php if(!empty($aboutTimeline)) { ?>
                    <ol class="pts-about-timeline" style="--milestone-count:<?=count($aboutTimeline)?>" aria-label="Các mốc phát triển của Phúc Thịnh Solar">
                        <?php foreach($aboutTimeline as $index => $milestone) { ?>
                        <li class="pts-about-milestone">
                            <span class="pts-about-milestone-icon" aria-hidden="true"><i class="fas <?=$index === count($aboutTimeline) - 1 ? 'fa-flag' : 'fa-rocket'?>"></i></span>
                            <strong class="pts-about-year"><?=$aboutEscape($milestone['year'])?></strong>
                            <?php if($milestone['title'] !== '') { ?><h2><?=$aboutEscape($milestone['title'])?></h2><?php } ?>
                            <?php if($milestone['description'] !== '') { ?><p><?=$aboutEscape($milestone['description'])?></p><?php } ?>
                        </li>
                        <?php } ?>
                    </ol>
                    <?php } ?>
                </div>

                <?php if($aboutStoryImage !== '') { ?>
                <figure class="pts-about-story-media">
                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '780x600x1', 'upload' => UPLOAD_NEWS_L, 'image' => $aboutStoryImage, 'alt' => 'Không gian trưng bày của Phúc Thịnh Solar'])?>
                </figure>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="pts-about-people" aria-labelledby="about-people-title">
        <div class="uk-container">
            <div class="pts-about-people-grid">
                <?php if($aboutPeopleImage !== '') { ?>
                <figure class="pts-about-people-media">
                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '780x620x1', 'upload' => $aboutPeopleUpload, 'image' => $aboutPeopleImage, 'alt' => 'Đội ngũ Phúc Thịnh Solar'])?>
                </figure>
                <?php } ?>

                <div class="pts-about-people-copy">
                    <span class="pts-about-eyebrow">CON NGƯỜI LÀ NỀN TẢNG</span>
                    <h2 id="about-people-title">Con người Phúc Thịnh Solar</h2>
                    <?php if($aboutPeopleIntro !== '') { ?><p class="pts-about-lead"><?=$aboutEscape($aboutPeopleIntro)?></p><?php } ?>
                    <div class="pts-about-principles">
                        <?php foreach($aboutPrinciples as $principle) { ?>
                        <article class="pts-about-principle">
                            <span class="pts-about-principle-icon" aria-hidden="true"><i class="fas <?=$aboutEscape($principle['icon'])?>"></i></span>
                            <h3><?=$aboutEscape($principle['title'])?></h3>
                            <?php if($principle['description'] !== '') { ?><p><?=$aboutEscape($principle['description'])?></p><?php } ?>
                        </article>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pts-about-trust" aria-labelledby="about-trust-title">
        <div class="uk-container">
            <header class="pts-about-section-heading">
                <span class="pts-about-eyebrow">GIÁ TRỊ KHÁC BIỆT</span>
                <h2 id="about-trust-title">Tại sao khách hàng lựa chọn chúng tôi?</h2>
            </header>
            <div class="pts-about-trust-grid">
                <?php foreach($aboutTrustItems as $item) { ?>
                <article class="pts-about-trust-card">
                    <span class="pts-about-trust-icon" aria-hidden="true"><i class="fas <?=$aboutEscape($item['icon'])?>"></i></span>
                    <h3><?=$aboutEscape($item['title'])?></h3>
                    <p><?=$aboutEscape($item['description'])?></p>
                </article>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php if(!empty($aboutCompass)) { ?>
    <section class="pts-about-compass" aria-labelledby="about-compass-title">
        <div class="uk-container">
            <header class="pts-about-section-heading">
                <span class="pts-about-eyebrow">KIM CHỈ NAM CHO TƯƠNG LAI</span>
                <h2 id="about-compass-title">Tầm nhìn – Sứ mệnh – Giá trị cốt lõi</h2>
            </header>
            <div class="pts-about-compass-grid">
                <?php foreach($aboutCompass as $item) { ?>
                <article class="pts-about-compass-item">
                    <span class="pts-about-compass-icon" aria-hidden="true"><i class="fas <?=$aboutEscape($item['icon'])?>"></i></span>
                    <div>
                        <h3><?=$aboutEscape($item['title'])?></h3>
                        <?php if(!empty($item['keywords'])) { ?>
                        <ul class="pts-about-keywords" aria-label="Các giá trị cốt lõi">
                            <?php foreach($item['keywords'] as $keyword) { ?><li><?=$aboutEscape($keyword)?></li><?php } ?>
                        </ul>
                        <?php } elseif($item['description'] !== '') { ?>
                        <p><?=$aboutEscape($item['description'])?></p>
                        <?php } ?>
                    </div>
                </article>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if(!empty($chungnhan)) { ?>
    <section class="pts-about-certificates" id="about-certificates" aria-labelledby="about-certificates-title">
        <div class="uk-container">
            <header class="pts-about-certificates-heading">
                <div>
                    <span class="pts-about-eyebrow">NỀN TẢNG CỦA NIỀM TIN</span>
                    <h2 id="about-certificates-title">Chứng nhận &amp; năng lực</h2>
                    <p>Cam kết chất lượng được thể hiện bằng những chứng nhận đang được công bố.</p>
                </div>
                <button type="button" class="pts-about-view-certificates" data-about-cert-open>Xem tất cả chứng nhận <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </header>

            <div class="pts-about-certificate-grid album-gallery">
                <?php foreach($aboutCertificates as $certificate) { ?>
                <article class="pts-about-certificate-card album-item">
                    <a class="album-image pts-about-certificate-thumb" href="<?=ASSET.UPLOAD_PHOTO_L.$aboutEscape($certificate['photo'])?>" title="<?=$aboutEscape($certificate['name'.$lang])?>">
                        <?=$func->getImage(['isLazy' => false, 'sizes' => '260x360x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $certificate['photo'], 'alt' => $certificate['name'.$lang]])?>
                    </a>
                    <div class="pts-about-certificate-copy">
                        <span>CHỨNG NHẬN</span>
                        <h3><?=$aboutEscape($certificate['name'.$lang])?></h3>
                        <?php if(!empty($certificate['desc'.$lang])) { ?><p><?=$aboutEscape($aboutPlainText($certificate['desc'.$lang]))?></p><?php } ?>
                        <button type="button" class="pts-about-certificate-link" data-about-cert-item-open>Xem chi tiết <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </article>
                <?php } ?>

                <?php foreach(array_slice($chungnhan, count($aboutCertificates)) as $certificate) { ?>
                <span class="pts-about-certificate-hidden album-item" aria-hidden="true"><a class="album-image" tabindex="-1" href="<?=ASSET.UPLOAD_PHOTO_L.$aboutEscape($certificate['photo'])?>" title="<?=$aboutEscape($certificate['name'.$lang])?>"></a></span>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="pts-about-contact" aria-labelledby="about-contact-title">
        <div class="uk-container">
            <div class="pts-about-contact-band">
                <span class="pts-about-contact-icon" aria-hidden="true"><i class="fas fa-headset"></i></span>
                <div class="pts-about-contact-copy">
                    <h2 id="about-contact-title">Cần tư vấn giải pháp năng lượng mặt trời?</h2>
                    <p>Đội ngũ Phúc Thịnh Solar luôn sẵn sàng đồng hành cùng bạn.</p>
                </div>
                <div class="pts-about-contact-actions">
                    <?php if(!empty($optsetting['hotline'])) { ?>
                    <a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline'])?>" class="pts-about-contact-button is-hotline"><i class="fas fa-phone-alt" aria-hidden="true"></i> <?=$aboutEscape($optsetting['hotline'])?></a>
                    <?php } ?>
                    <?php if(!empty($optsetting['zalo'])) { ?>
                    <a href="https://zalo.me/<?=$func->parsePhone($optsetting['zalo'])?>" target="_blank" rel="noopener" class="pts-about-contact-button is-zalo">Zalo</a>
                    <?php } ?>
                    <a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal" class="pts-about-contact-button is-primary">Nhận tư vấn ngay <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>
</main>
