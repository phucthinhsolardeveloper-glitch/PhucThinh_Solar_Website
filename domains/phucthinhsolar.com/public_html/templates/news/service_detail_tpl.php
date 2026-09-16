<?php
$solutionEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$solutionDetailName = solutionHubField($rowDetail, 'name', $lang);
$solutionDetailDesc = solutionHubField($rowDetail, 'desc', $lang);
$solutionDetailImage = solutionHubImage(isset($rowDetail['photo1']) ? $rowDetail['photo1'] : '', UPLOAD_NEWS_L)
    ?: solutionHubImage(isset($rowDetail['photo']) ? $rowDetail['photo'] : '', UPLOAD_NEWS_L);
$solutionDetailFallbackKey = solutionHubField($rowDetail, 'slug', 'vi');
if(!$solutionDetailImage && isset($solutionFallbackImages[$solutionDetailFallbackKey]) && is_file(ROOT.$solutionFallbackImages[$solutionDetailFallbackKey])) {
    $solutionDetailImage = $solutionFallbackImages[$solutionDetailFallbackKey];
}
$solutionDetailContent = !empty($rowDetail['content'.$lang])
    ? htmlspecialchars_decode($rowDetail['content'.$lang], ENT_QUOTES)
    : (!empty($rowDetail['contentvi']) ? htmlspecialchars_decode($rowDetail['contentvi'], ENT_QUOTES) : '');
$solutionDetailExtra = !empty($rowDetail['content1'.$lang])
    ? htmlspecialchars_decode($rowDetail['content1'.$lang], ENT_QUOTES)
    : (!empty($rowDetail['content1vi']) ? htmlspecialchars_decode($rowDetail['content1vi'], ENT_QUOTES) : '');
?>
<main class="pts-solution-hub pts-solution-detail">
    <section class="pts-sd-hero" aria-labelledby="solution-detail-title">
        <div class="uk-container">
            <div class="pts-sd-hero-grid<?=$solutionDetailImage ? '' : ' is-text-only'?>">
                <div class="pts-sd-hero-copy">
                    <h1 id="solution-detail-title"><?=$solutionEscape($solutionDetailName)?></h1>
                    <?php if($solutionDetailDesc !== '') { ?><p class="pts-sd-lead"><?=$solutionEscape($solutionDetailDesc)?></p><?php } ?>
                    <div class="pts-sd-actions">
                        <a class="pts-sh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal">Nhận tư vấn giải pháp <span aria-hidden="true">→</span></a>
                        <?php if($solutionDetailContent !== '') { ?><a class="pts-sd-explore" href="#noi-dung-giai-phap">Tìm hiểu phương án</a><?php } ?>
                    </div>
                </div>
                <?php if($solutionDetailImage) { ?>
                <figure class="pts-sd-hero-media">
                    <img src="<?=$solutionEscape($func->getThumbUrl('1600x1000x1', $solutionDetailImage))?>" alt="<?=$solutionEscape($solutionDetailName)?>" width="1600" height="1000" fetchpriority="high">
                </figure>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php if($solutionCards) { ?>
    <nav class="pts-sd-switcher" aria-label="Các giải pháp khác">
        <div class="uk-container">
            <a class="pts-sd-switcher-all" href="giai-phap/">Tất cả giải pháp</a>
            <?php foreach($solutionCards as $solutionCard) {
                $solutionRepresentative = $solutionCard['_representative'];
                $solutionSwitchName = solutionHubNavName($solutionRepresentative, $lang);
            ?>
            <a class="<?=(int)$solutionRepresentative['id'] === (int)$rowDetail['id'] ? 'is-active' : ''?>" href="<?=$solutionEscape(solutionHubPublicPath($solutionRepresentative, $lang))?>"><?=$solutionEscape($solutionSwitchName)?></a>
            <?php } ?>
        </div>
    </nav>
    <?php } ?>

    <section class="pts-sd-body" id="noi-dung-giai-phap">
        <div class="uk-container">
            <?php if($solutionDetailContent !== '') { ?>
            <div class="pts-sd-content-grid">
                <aside class="pts-sd-toc meta-toc" aria-label="Mục lục nội dung">
                    <strong>Nội dung chính</strong>
                    <ol class="toc-list" data-toc="article" data-toc-headings="h2, h3"></ol>
                </aside>
                <article class="pts-solution-editorial content-main w-clear" id="toc-content"><?=$solutionDetailContent?></article>
            </div>
            <?php } else { ?>
            <p class="pts-sd-empty"><?=noidungdangcapnhat?></p>
            <?php } ?>

            <?php if($solutionDetailExtra !== '') { ?>
            <section class="pts-sd-extra pts-solution-editorial content-main w-clear" aria-label="Thông tin bổ sung"><?=$solutionDetailExtra?></section>
            <?php } ?>

            <?php if(!empty($rowDetailPhoto)) { ?>
            <section class="pts-sd-gallery" aria-label="Hình ảnh giải pháp">
                <?php foreach($rowDetailPhoto as $solutionGalleryImage) { ?>
                <a href="<?=$solutionEscape(ASSET.UPLOAD_NEWS_L.$solutionGalleryImage['photo'])?>" data-fancybox="solution-gallery" title="<?=$solutionEscape($solutionDetailName)?>">
                    <?=$func->getImage(array('class' => 'w-100', 'isLazy' => true, 'sizes' => '960x600x1', 'upload' => UPLOAD_NEWS_L, 'image' => $solutionGalleryImage['photo'], 'alt' => $solutionDetailName))?>
                </a>
                <?php } ?>
            </section>
            <?php } ?>
        </div>
    </section>

    <?php if($solutionEquipment) { ?>
    <section class="pts-sh-equipment" aria-labelledby="solution-detail-equipment-title">
        <div class="uk-container">
            <div class="pts-sh-equipment-surface">
                <header class="pts-sh-equipment-heading">
                    <div><h2 id="solution-detail-equipment-title">Thiết bị cho một hệ thống hoàn chỉnh</h2></div>
                    <a href="san-pham/" class="pts-sh-all-products">Xem tất cả sản phẩm <span aria-hidden="true">→</span></a>
                </header>
                <div class="pts-sh-equipment-grid">
                    <?php foreach($solutionEquipment as $solutionCategory) {
                        $solutionCategoryLang = !empty($solutionCategory['slug'.$lang]) ? $lang : 'vi';
                        $solutionCategoryImage = solutionHubImage($solutionCategory['photo'], UPLOAD_PRODUCT_L);
                    ?>
                    <a class="pts-sh-equipment-link" href="<?=$solutionEscape(publicProductCategoryPath($solutionCategory, 'list', $solutionCategoryLang))?>">
                        <?php if($solutionCategoryImage) { ?><img src="<?=$solutionEscape($func->getThumbUrl('160x160x2', $solutionCategoryImage))?>" alt="" width="80" height="80" loading="lazy" decoding="async"><?php } ?>
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
        <div class="uk-container"><div class="pts-sh-contact-band">
            <?php if($solutionCtaImage) { ?><img class="pts-sh-contact-background" src="<?=$solutionEscape($func->getThumbUrl('1600x500x1', $solutionCtaImage))?>" alt="" width="1600" height="500" loading="lazy" decoding="async"><?php } ?>
            <div class="pts-sh-contact-copy"><h2 id="solution-contact-title"><?=$solutionEscape($solutionCtaTitle)?></h2><p><?=$solutionEscape($solutionCtaDesc)?></p></div>
            <div class="pts-sh-contact-actions">
                <a class="pts-sh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$solutionEscape($solutionCtaButton)?> <span aria-hidden="true">→</span></a>
                <?php if($solutionHotlineTel !== '') { ?><a class="pts-sh-hotline" href="tel:<?=$solutionEscape($solutionHotlineTel)?>"><small><?=$solutionEscape($solutionUi['phone'])?></small><strong><?=$solutionEscape($solutionHotline)?></strong></a><?php } ?>
            </div>
        </div></div>
    </section>
    <?php } ?>
</main>
