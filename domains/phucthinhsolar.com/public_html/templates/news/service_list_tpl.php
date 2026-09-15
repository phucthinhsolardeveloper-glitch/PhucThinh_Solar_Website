<?php $solutionEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<main class="pts-solution-hub">
    <?php if($solutionShowIntro) { ?>
    <section class="pts-sh-intro" aria-labelledby="solution-hub-title">
        <div class="uk-container">
            <div class="pts-sh-intro-grid<?=$solutionHeroImage ? '' : ' is-text-only'?>">
                <?php if($solutionHeroImage) { ?>
                <figure class="pts-sh-intro-image"><img src="<?=$solutionEscape(ASSET.THUMBS.'/1200x750x1/'.$solutionHeroImage)?>" alt="<?=$solutionEscape($solutionHeroTitle)?>" width="1200" height="750" fetchpriority="high"></figure>
                <?php } ?>
                <div class="pts-sh-intro-copy">
                    <h1 id="solution-hub-title"><?=$solutionEscape($solutionHeroTitle)?></h1>
                    <p><?=$solutionEscape($solutionHeroDesc)?></p>
                    <div class="pts-sd-actions">
                        <a class="pts-sh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionUi['consult'])?> <span aria-hidden="true">→</span></a>
                        <a class="pts-sd-explore" href="#cac-giai-phap">Khám phá các giải pháp</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } else { ?>
    <h1 class="pts-sh-sr-only"><?=$solutionEscape($solutionUi['title'])?></h1>
    <?php } ?>
    <section class="pts-sh-choices" id="cac-giai-phap" aria-label="<?=$solutionEscape($solutionUi['choices'])?>">
        <div class="uk-container">
            <?php if($solutionCards) { ?>
            <div class="pts-sh-choice-grid">
                <?php foreach($solutionCards as $solutionIndex => $solutionCard) {
                    $solutionRepresentative = $solutionCard['_representative'];
                    $solutionName = solutionHubNavName($solutionRepresentative, $lang);
                    $solutionDescription = solutionHubField($solutionRepresentative, 'desc', $lang);
                    $solutionSlug = solutionHubField($solutionCard, 'slug', $lang);
                    $solutionPhoto = solutionHubImage($solutionRepresentative['photo1'], UPLOAD_NEWS_L) ?: solutionHubImage($solutionRepresentative['photo'], UPLOAD_NEWS_L) ?: solutionHubImage($solutionCard['photo'], UPLOAD_NEWS_L);
                    $solutionTarget = solutionHubPublicPath($solutionRepresentative, $lang);
                    if($solutionSlug === '' || $solutionTarget === 'giai-phap/') continue;
                ?>
                <a class="pts-sh-choice" href="<?=$solutionEscape($solutionTarget)?>">
                    <div class="pts-sh-choice-media<?=$solutionPhoto ? '' : ' is-placeholder'?>">
                        <?php if($solutionPhoto) { ?><img src="<?=$solutionEscape(ASSET.THUMBS.'/800x500x1/'.$solutionPhoto)?>" alt="" width="800" height="500" loading="lazy" decoding="async"><?php } else { ?><span aria-hidden="true"><?=str_pad((string)($solutionIndex+1), 2, '0', STR_PAD_LEFT)?></span><?php } ?>
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
    <?php if($solutionEditorialHtml !== '') { ?>
    <section class="pts-sh-editorial" aria-label="Nội dung tư vấn giải pháp">
        <div class="uk-container">
            <div class="pts-sh-editorial-grid">
                <aside class="pts-sd-toc meta-toc" aria-label="Mục lục trang Giải pháp">
                    <strong>Trong trang này</strong>
                    <ol class="toc-list" data-toc="article" data-toc-headings="h2"></ol>
                </aside>
                <div class="pts-solution-editorial content-main w-clear" id="toc-content"><?=$solutionEditorialHtml?></div>
            </div>
        </div>
    </section>
    <?php } ?>
    <?php if($solutionEquipment) { ?>
    <section class="pts-sh-equipment" aria-labelledby="solution-equipment-title">
        <div class="uk-container">
            <div class="pts-sh-equipment-surface">
                <header class="pts-sh-equipment-heading">
                    <div><h2 id="solution-equipment-title"><?=$solutionEscape($solutionUi['equipment'])?></h2></div>
                    <a href="san-pham/" class="pts-sh-all-products"><?=$solutionEscape($solutionUi['all_products'])?> <span aria-hidden="true">→</span></a>
                </header>
                <div class="pts-sh-equipment-grid">
                    <?php foreach($solutionEquipment as $solutionCategory) {
                        $solutionCategoryLang = !empty($solutionCategory['slug'.$lang]) ? $lang : 'vi';
                        $solutionCategoryImage = solutionHubImage($solutionCategory['photo'], UPLOAD_PRODUCT_L);
                    ?>
                    <a class="pts-sh-equipment-link" href="<?=$solutionEscape(publicProductCategoryPath($solutionCategory, 'list', $solutionCategoryLang))?>">
                        <?php if($solutionCategoryImage) { ?><img src="<?=$solutionEscape(ASSET.THUMBS.'/160x160x2/'.$solutionCategoryImage)?>" alt="" width="80" height="80" loading="lazy" decoding="async"><?php } ?>
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
                <?php if($solutionCtaImage) { ?><img class="pts-sh-contact-background" src="<?=$solutionEscape(ASSET.THUMBS.'/1600x500x1/'.$solutionCtaImage)?>" alt="" width="1600" height="500" loading="lazy" decoding="async"><?php } ?>
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
