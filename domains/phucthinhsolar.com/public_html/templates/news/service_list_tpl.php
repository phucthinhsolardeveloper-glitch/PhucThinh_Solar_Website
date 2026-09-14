<?php $solutionEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<main class="pts-solution-hub">
    <?php if($solutionShowIntro) { ?>
    <section class="pts-sh-intro" aria-labelledby="solution-hub-title">
        <div class="uk-container">
            <div class="pts-sh-intro-grid<?=$solutionHeroImage ? '' : ' is-text-only'?>">
                <?php if($solutionHeroImage) { ?>
                <figure class="pts-sh-intro-image"><img src="<?=$solutionEscape(ASSET.$solutionHeroImage)?>" alt="<?=$solutionEscape($solutionHeroTitle)?>" width="960" height="640" fetchpriority="high"></figure>
                <?php } ?>
                <div class="pts-sh-intro-copy">
                    <span class="pts-sh-eyebrow"><?=$solutionEscape($solutionHeroEyebrow)?></span>
                    <h1 id="solution-hub-title"><?=$solutionEscape($solutionHeroTitle)?></h1>
                    <p><?=$solutionEscape($solutionHeroDesc)?></p>
                </div>
            </div>
        </div>
    </section>
    <?php } else { ?>
    <h1 class="pts-sh-sr-only"><?=$solutionEscape($solutionUi['title'])?></h1>
    <?php } ?>
    <section class="pts-sh-choices" aria-label="<?=$solutionEscape($solutionUi['choices'])?>">
        <div class="uk-container">
            <?php if($solutionCards) { ?>
            <div class="pts-sh-choice-grid">
                <?php foreach($solutionCards as $solutionIndex => $solutionCard) {
                    $solutionName = solutionHubField($solutionCard, 'name', $lang);
                    $solutionRepresentative = $solutionCard['_representative'];
                    $solutionDescription = solutionHubField($solutionCard, 'desc', $lang) ?: solutionHubField($solutionRepresentative, 'desc', $lang);
                    $solutionSlug = solutionHubField($solutionCard, 'slug', $lang);
                    $solutionPhoto = solutionHubImage($solutionRepresentative['photo1'], UPLOAD_NEWS_L) ?: solutionHubImage($solutionRepresentative['photo'], UPLOAD_NEWS_L) ?: solutionHubImage($solutionCard['photo'], UPLOAD_NEWS_L);
                    if($solutionSlug === '') continue;
                ?>
                <a class="pts-sh-choice" href="<?=$solutionEscape('danh-muc-giai-phap/'.$solutionSlug.'/')?>">
                    <div class="pts-sh-choice-media<?=$solutionPhoto ? '' : ' is-placeholder'?>">
                        <?php if($solutionPhoto) { ?><img src="<?=$solutionEscape(ASSET.$solutionPhoto)?>" alt="" width="540" height="440" loading="lazy" decoding="async"><?php } else { ?><span aria-hidden="true"><?=str_pad((string)($solutionIndex+1), 2, '0', STR_PAD_LEFT)?></span><?php } ?>
                    </div>
                    <div class="pts-sh-choice-copy">
                        <h2><?=$solutionEscape($solutionName)?></h2>
                        <?php if($solutionDescription !== '') { ?><p><?=$solutionEscape($solutionDescription)?></p><?php } ?>
                        <span class="pts-sh-choice-action"><?=$solutionEscape($solutionUi['choice'])?><span class="pts-sh-arrow" aria-hidden="true">→</span></span>
                    </div>
                </a>
                <?php } ?>
            </div>
            <?php } else { ?>
            <div class="pts-sh-empty"><p><?=$solutionEscape($solutionUi['empty'])?></p><a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionUi['consult'])?> →</a></div>
            <?php } ?>
        </div>
    </section>
    <?php if($solutionEquipment) { ?>
    <section class="pts-sh-equipment" aria-labelledby="solution-equipment-title">
        <div class="uk-container">
            <div class="pts-sh-equipment-surface">
                <header class="pts-sh-equipment-heading">
                    <div><span class="pts-sh-eyebrow"><?=$solutionEscape($solutionUi['equipment_eyebrow'])?></span><h2 id="solution-equipment-title"><?=$solutionEscape($solutionUi['equipment'])?></h2></div>
                    <a href="san-pham/" class="pts-sh-all-products"><?=$solutionEscape($solutionUi['all_products'])?> <span aria-hidden="true">→</span></a>
                </header>
                <div class="pts-sh-equipment-grid">
                    <?php foreach($solutionEquipment as $solutionCategory) {
                        $solutionCategoryLang = !empty($solutionCategory['slug'.$lang]) ? $lang : 'vi';
                        $solutionCategoryImage = solutionHubImage($solutionCategory['photo'], UPLOAD_PRODUCT_L);
                    ?>
                    <a class="pts-sh-equipment-link" href="<?=$solutionEscape(publicProductCategoryPath($solutionCategory, 'list', $solutionCategoryLang))?>">
                        <?php if($solutionCategoryImage) { ?><img src="<?=$solutionEscape(ASSET.$solutionCategoryImage)?>" alt="" width="76" height="76" loading="lazy" decoding="async"><?php } ?>
                        <span><strong><?=$solutionEscape(solutionHubField($solutionCategory, 'name', $lang))?></strong><?php if(solutionHubField($solutionCategory, 'desc', $lang) !== '') { ?><small><?=$solutionEscape(solutionHubField($solutionCategory, 'desc', $lang))?></small><?php } ?></span>
                        <span class="pts-sh-equipment-arrow" aria-hidden="true">→</span>
                    </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>
    <?php if($solutionShowCta) { ?>
    <section class="pts-sh-contact" aria-labelledby="solution-contact-title">
        <div class="uk-container">
            <div class="pts-sh-contact-band">
                <?php if($solutionCtaImage) { ?><img class="pts-sh-contact-background" src="<?=$solutionEscape(ASSET.$solutionCtaImage)?>" alt="" loading="lazy"><?php } ?>
                <div class="pts-sh-contact-copy"><h2 id="solution-contact-title"><?=$solutionEscape($solutionCtaTitle)?></h2><p><?=$solutionEscape($solutionCtaDesc)?></p></div>
                <div class="pts-sh-contact-actions">
                    <a class="pts-sh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionCtaButton)?> <span aria-hidden="true">→</span></a>
                    <?php if($solutionHotlineTel !== '') { ?><a class="pts-sh-hotline" href="tel:<?=$solutionEscape($solutionHotlineTel)?>"><small><?=$solutionEscape($solutionUi['phone'])?></small><strong><?=$solutionEscape($solutionHotline)?></strong></a><?php } ?>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>
</main>
