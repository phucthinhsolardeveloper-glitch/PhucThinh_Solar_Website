<?php
    $aboutEscape = static function($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };
    $aboutStoryImage = !empty($aboutStoryRecord['photo']) ? $aboutStoryRecord['photo'] : '';
    $aboutCtaHasImage = !empty($aboutCta['photo']);
?>
<main class="pts-about-page">
    <section class="pts-about-story" id="ve-phuc-thinh" aria-labelledby="about-story-title">
        <div class="uk-container">
            <div class="pts-about-story-grid">
                <div class="pts-about-story-copy">
                    <span class="pts-about-eyebrow"><?=$aboutEscape($aboutStoryEyebrow)?></span>
                    <h1 id="about-story-title"><?=$aboutEscape($aboutStoryTitle)?></h1>
                    <?php if($aboutStoryIntro !== '') { ?>
                    <p class="pts-about-lead"><?=$aboutEscape($aboutStoryIntro)?></p>
                    <?php } ?>
                    <?php if($aboutStoryBody !== '') { ?>
                    <p class="pts-about-story-body"><?=$aboutEscape($aboutStoryBody)?></p>
                    <?php } ?>

                </div>
                <?php if($aboutStoryImage !== '') { ?>
                <figure class="pts-about-story-media">
                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '780x600x1', 'upload' => UPLOAD_NEWS_L, 'image' => $aboutStoryImage, 'alt' => $aboutStoryTitle])?>
                </figure>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php if(!empty($aboutTimeline)) { ?>
    <section class="pts-about-history" id="lich-su-phat-trien" aria-labelledby="about-history-title">
        <div class="uk-container">
            <div class="pts-about-history-grid">
                <div class="pts-about-history-copy">
                    <span class="pts-about-eyebrow"><?=$aboutEscape($aboutUi['story_eyebrow'])?></span>
                    <h2 id="about-history-title"><?=$aboutEscape(!empty($aboutHistoryRecord['title_index'.$lang]) ? $aboutHistoryRecord['title_index'.$lang] : $aboutHistoryRecord['name'.$lang])?></h2>
                    <ol class="pts-about-timeline" style="--milestone-count:<?=count($aboutTimeline)?>" aria-label="<?=$aboutEscape($aboutUi['timeline_label'])?>">
                        <?php foreach($aboutTimeline as $index => $milestone) { ?>
                        <li class="pts-about-milestone">
                            <span class="pts-about-milestone-marker" aria-hidden="true"><?=str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)?></span>
                            <strong class="pts-about-year"><?=$aboutEscape($milestone['year'])?></strong>
                            <?php if($milestone['title'] !== '' && count($aboutTimeline) > 1) { ?><h3><?=$aboutEscape($milestone['title'])?></h3><?php } ?>
                            <?php if($milestone['description'] !== '') { ?><p><?=$aboutEscape($milestone['description'])?></p><?php } ?>
                        </li>
                        <?php } ?>
                    </ol>
                </div>
                <?php if(!empty($aboutHistoryRecord['photo'])) { ?>
                <figure class="pts-about-history-media">
                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '780x600x1', 'upload' => UPLOAD_NEWS_L, 'image' => $aboutHistoryRecord['photo'], 'alt' => $aboutHistoryRecord['name'.$lang]])?>
                </figure>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="pts-about-people" aria-labelledby="about-people-title">
        <div class="uk-container">
            <div class="pts-about-people-grid">
                <?php if($aboutPeopleImage !== '') { ?>
                <figure class="pts-about-people-media">
                    <?=$func->getImage(['class' => 'lazy', 'sizes' => '780x620x1', 'upload' => $aboutPeopleUpload, 'image' => $aboutPeopleImage, 'alt' => $aboutPeopleTitle])?>
                </figure>
                <?php } ?>

                <div class="pts-about-people-copy">
                    <span class="pts-about-eyebrow"><?=$aboutEscape($aboutPeopleEyebrow)?></span>
                    <h2 id="about-people-title"><?=$aboutEscape($aboutPeopleTitle)?></h2>
                    <?php if($aboutPeopleIntro !== '') { ?><p class="pts-about-lead"><?=$aboutEscape($aboutPeopleIntro)?></p><?php } ?>
                    <?php if($aboutPeopleBody !== '') { ?><p class="pts-about-people-body"><?=$aboutEscape($aboutPeopleBody)?></p><?php } ?>
                </div>
            </div>

            <?php if(!empty($aboutPrinciples)) { ?>
            <div class="pts-about-principles" aria-label="<?=$aboutEscape($aboutUi['people_eyebrow'])?>">
                <?php foreach($aboutPrinciples as $index => $principle) { ?>
                <article class="pts-about-principle">
                    <?php if($principle['photo'] !== '') { ?>
                    <span class="pts-about-principle-icon" aria-hidden="true"><img src="<?=ASSET.UPLOAD_NEWS_L.$aboutEscape($principle['photo'])?>" alt="" width="56" height="56" loading="lazy" decoding="async"></span>
                    <?php } else { ?>
                    <span class="pts-about-principle-number" aria-hidden="true"><?=str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)?></span>
                    <?php } ?>
                    <div>
                        <h3><?=$aboutEscape($principle['title'])?></h3>
                        <?php if($principle['description'] !== '') { ?><p><?=$aboutEscape($principle['description'])?></p><?php } ?>
                    </div>
                </article>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </section>

    <?php if(!empty($aboutTrustItems)) { ?>
    <section class="pts-about-trust" aria-labelledby="about-trust-title">
        <div class="uk-container">
            <div class="pts-about-trust-layout">
                <header class="pts-about-section-heading">
                    <span class="pts-about-eyebrow"><?=$aboutEscape($aboutUi['trust_eyebrow'])?></span>
                    <h2 id="about-trust-title"><?=$aboutEscape($aboutUi['trust_title'])?></h2>
                </header>
                <div class="pts-about-trust-grid">
                    <?php foreach($aboutTrustItems as $index => $item) { ?>
                    <article class="pts-about-trust-card">
                        <?php if($item['photo'] !== '') { ?>
                        <span class="pts-about-trust-icon" aria-hidden="true"><img src="<?=ASSET.UPLOAD_NEWS_L.$aboutEscape($item['photo'])?>" alt="" width="64" height="64" loading="lazy" decoding="async"></span>
                        <?php } else { ?>
                        <span class="pts-about-trust-number" aria-hidden="true"><?=str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)?></span>
                        <?php } ?>
                        <div>
                            <h3><?=$aboutEscape($item['title'])?></h3>
                            <p><?=$aboutEscape($item['description'])?></p>
                        </div>
                    </article>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if(!empty($aboutCompass)) { ?>
    <section class="pts-about-compass" id="tam-nhin-su-menh" aria-labelledby="about-compass-title">
        <div class="uk-container">
            <header class="pts-about-section-heading">
                <span class="pts-about-eyebrow"><?=$aboutEscape($aboutUi['compass_eyebrow'])?></span>
                <h2 id="about-compass-title"><?=$aboutEscape($aboutUi['compass_title'])?></h2>
            </header>
            <div class="pts-about-compass-grid">
                <?php foreach($aboutCompass as $index => $item) { ?>
                <article class="pts-about-compass-item">
                    <?php if($item['photo'] !== '') { ?>
                    <span class="pts-about-compass-icon" aria-hidden="true"><img src="<?=ASSET.UPLOAD_NEWS_L.$aboutEscape($item['photo'])?>" alt="" width="56" height="56" loading="lazy" decoding="async"></span>
                    <?php } else { ?>
                    <span class="pts-about-compass-number" aria-hidden="true"><?=str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)?></span>
                    <?php } ?>
                    <div>
                        <h3><?=$aboutEscape($item['title'])?></h3>
                        <?php if($item['description'] !== '') { ?><p><?=$aboutEscape($item['description'])?></p><?php } ?>
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
                    <span class="pts-about-eyebrow"><?=$aboutEscape($aboutUi['cert_eyebrow'])?></span>
                    <h2 id="about-certificates-title"><?=$aboutEscape($aboutUi['cert_title'])?></h2>
                    <p><?=$aboutEscape($aboutUi['cert_intro'])?></p>
                </div>
                <button type="button" class="pts-about-view-certificates" data-about-cert-open><?=$aboutEscape($aboutUi['cert_all'])?> <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </header>

            <div class="pts-about-certificate-grid album-gallery">
                <?php foreach($aboutCertificates as $certificate) { ?>
                <article class="pts-about-certificate-card album-item">
                    <a class="album-image pts-about-certificate-thumb" href="<?=ASSET.UPLOAD_PHOTO_L.$aboutEscape($certificate['photo'])?>" title="<?=$aboutEscape($certificate['name'.$lang])?>">
                        <?=$func->getImage(['isLazy' => false, 'sizes' => '260x360x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $certificate['photo'], 'alt' => $certificate['name'.$lang]])?>
                    </a>
                    <div class="pts-about-certificate-copy">
                        <span><?=$aboutEscape($aboutUi['cert_label'])?></span>
                        <h3><?=$aboutEscape($certificate['name'.$lang])?></h3>
                        <?php if(!empty($certificate['desc'.$lang])) { ?><p><?=$aboutEscape($aboutPlainText($certificate['desc'.$lang]))?></p><?php } ?>
                        <button type="button" class="pts-about-certificate-link" data-about-cert-item-open><?=$aboutEscape($aboutUi['cert_detail'])?> <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </article>
                <?php } ?>

                <?php foreach($aboutHiddenCertificates as $certificate) { ?>
                <span class="pts-about-certificate-hidden album-item" aria-hidden="true"><a class="album-image" tabindex="-1" href="<?=ASSET.UPLOAD_PHOTO_L.$aboutEscape($certificate['photo'])?>" title="<?=$aboutEscape($certificate['name'.$lang])?>"></a></span>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="pts-about-contact" aria-labelledby="about-contact-title">
        <div class="uk-container">
            <div class="pts-about-contact-band<?=$aboutCtaHasImage ? ' has-image' : ''?>"<?=$aboutCtaHasImage ? ' style="--about-cta-image:url(\''.ASSET.UPLOAD_PHOTO_L.$aboutEscape($aboutCta['photo']).'\')"' : ''?>>
                <div class="pts-about-contact-copy">
                    <span class="pts-about-contact-kicker">PTS</span>
                    <div>
                        <h2 id="about-contact-title"><?=$aboutEscape($aboutCtaTitle)?></h2>
                        <p><?=$aboutEscape($aboutCtaIntro)?></p>
                    </div>
                </div>
                <div class="pts-about-contact-actions">
                    <?php if(!empty($optsetting['hotline'])) { ?>
                    <a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline'])?>" class="pts-about-contact-button is-hotline"><i class="fas fa-phone-alt" aria-hidden="true"></i> <?=$aboutEscape($optsetting['hotline'])?></a>
                    <?php } ?>
                    <?php if(!empty($optsetting['zalo'])) { ?>
                    <a href="https://zalo.me/<?=$func->parsePhone($optsetting['zalo'])?>" target="_blank" rel="noopener" class="pts-about-contact-button is-zalo">Zalo</a>
                    <?php } ?>
                    <a href="<?=$aboutEscape($aboutCtaLink)?>"<?=$aboutCtaLink === '#ptsContactModal' ? ' data-toggle="modal" data-target="#ptsContactModal"' : ''?> class="pts-about-contact-button is-primary"><?=$aboutEscape($aboutUi['contact_primary'])?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>
</main>
