<?php
$solutionEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$solutionCategoryName = !empty($newsList['name'.$lang]) ? solutionHubText($newsList['name'.$lang]) : $titleCate;
$solutionCategoryDesc = !empty($newsList['desc'.$lang]) ? solutionHubText($newsList['desc'.$lang]) : $solutionUi['category_intro'];
?>
<main class="pts-solution-hub pts-solution-category">
    <section class="pts-sh-category-head" aria-labelledby="solution-category-title">
        <div class="uk-container">
            <span class="pts-sh-eyebrow"><?=$solutionEscape($solutionUi['category_prefix'])?></span>
            <h1 id="solution-category-title"><?=$solutionEscape($solutionCategoryName)?></h1>
            <p><?=$solutionEscape($solutionCategoryDesc)?></p>
            <?php if($gplist) { ?>
            <nav class="pts-sh-category-nav" aria-label="<?=$solutionEscape($solutionUi['choices'])?>">
                <?php foreach($gplist as $solutionGroup) {
                    $solutionGroupSlug = solutionHubField($solutionGroup, 'slug', $lang);
                    $solutionGroupName = solutionHubField($solutionGroup, 'name', $lang);
                    if($solutionGroupSlug === '') continue;
                ?>
                <a class="<?=$newsList[$sluglang] === $solutionGroup[$sluglang] ? 'is-active' : ''?>" href="<?=$solutionEscape('danh-muc-giai-phap/'.$solutionGroupSlug.'/')?>"><?=$solutionEscape($solutionGroupName)?></a>
                <?php } ?>
            </nav>
            <?php } ?>
        </div>
    </section>

    <section class="pts-sh-category-results" aria-label="<?=$solutionEscape($solutionUi['choices'])?>">
        <div class="uk-container">
            <?php if($news) { ?>
            <div class="pts-sh-category-grid">
                <?php foreach($news as $solutionIndex => $solutionItem) {
                    $solutionItemName = solutionHubField($solutionItem, 'name', $lang);
                    $solutionItemDesc = solutionHubField($solutionItem, 'desc', $lang);
                    $solutionItemSlug = solutionHubField($solutionItem, 'slug', $lang);
                    $solutionItemPhoto = solutionHubImage($solutionItem['photo'], UPLOAD_NEWS_L);
                    if($solutionItemSlug === '') continue;
                ?>
                <a class="pts-sh-category-card" href="<?=$solutionEscape('solution/'.$solutionItemSlug.'/')?>">
                    <div class="pts-sh-category-media<?=$solutionItemPhoto ? '' : ' is-placeholder'?>">
                        <?php if($solutionItemPhoto) { ?><img src="<?=$solutionEscape(ASSET.$solutionItemPhoto)?>" alt="" width="640" height="430" loading="lazy" decoding="async"><?php } else { ?><span aria-hidden="true"><?=str_pad((string)($solutionIndex + 1), 2, '0', STR_PAD_LEFT)?></span><?php } ?>
                    </div>
                    <div class="pts-sh-category-copy">
                        <h2><?=$solutionEscape($solutionItemName)?></h2>
                        <?php if($solutionItemDesc !== '') { ?><p><?=$solutionEscape($solutionItemDesc)?></p><?php } ?>
                        <span><?=$solutionEscape($solutionUi['choice'])?> <b aria-hidden="true">→</b></span>
                    </div>
                </a>
                <?php } ?>
            </div>
            <?php if(!empty($paging)) { ?><div class="pagination-home w-100"><?=$paging?></div><?php } ?>
            <?php } else { ?>
            <div class="pts-sh-empty"><p><?=$solutionEscape($solutionUi['empty'])?></p><a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionUi['consult'])?> →</a></div>
            <?php } ?>
        </div>
    </section>

    <?php if($solutionShowCta) { ?>
    <section class="pts-sh-contact" aria-labelledby="solution-contact-title">
        <div class="uk-container"><div class="pts-sh-contact-band">
            <?php if($solutionCtaImage) { ?><img class="pts-sh-contact-background" src="<?=$solutionEscape(ASSET.$solutionCtaImage)?>" alt="" loading="lazy"><?php } ?>
            <div class="pts-sh-contact-copy"><h2 id="solution-contact-title"><?=$solutionEscape($solutionCtaTitle)?></h2><p><?=$solutionEscape($solutionCtaDesc)?></p></div>
            <div class="pts-sh-contact-actions">
                <a class="pts-sh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionCtaButton)?> <span aria-hidden="true">→</span></a>
                <?php if($solutionHotlineTel !== '') { ?><a class="pts-sh-hotline" href="tel:<?=$solutionEscape($solutionHotlineTel)?>"><small><?=$solutionEscape($solutionUi['phone'])?></small><strong><?=$solutionEscape($solutionHotline)?></strong></a><?php } ?>
            </div>
        </div></div>
    </section>
    <?php } ?>
</main>
