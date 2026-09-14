<?php if($nhBlocks['news-cta']['visible']) { ?>
<section class="nh-cta" aria-labelledby="nh-cta-title"><div class="uk-container"><div class="nh-cta-band">
    <?php if($nhCtaImage) { ?><img class="nh-cta-bg" src="<?=$nhEscape(ASSET.$nhCtaImage)?>" alt="" loading="lazy"><?php } ?>
    <span class="nh-cta-icon" aria-hidden="true"><i class="fas fa-phone-volume"></i></span>
    <div class="nh-cta-copy"><span class="nh-eyebrow"><?=$nhEscape($nhCtaLabel)?></span><h2 id="nh-cta-title"><?=$nhEscape($nhCtaTitle)?></h2><p><?=$nhEscape($nhCtaDesc)?></p></div>
    <div class="nh-cta-actions">
        <?php if($nhTel !== '') { ?><a class="nh-phone" href="tel:<?=$nhEscape($nhTel)?>"><small><?=$nhEscape($nhUi['phone'])?></small><strong><?=$nhEscape($nhPhone)?></strong></a><?php } ?>
        <a class="nh-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$nhEscape($nhButton)?> <span aria-hidden="true">→</span></a>
    </div>
</div></div></section>
<?php } ?>

