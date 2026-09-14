<?php
if(!isset($projectEscape)) $projectEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
if($projectShowCta) {
?>
<section class="pts-ph-contact" aria-labelledby="project-contact-title">
    <div class="uk-container"><div class="pts-ph-contact-band">
        <?php if($projectCtaImage) { ?><img class="pts-ph-contact-bg" src="<?=$projectEscape(ASSET.$projectCtaImage)?>" alt="" loading="lazy" decoding="async"><?php } ?>
        <div class="pts-ph-contact-copy">
            <span><?=$projectEscape($projectCtaEyebrow)?></span><h2 id="project-contact-title"><?=$projectEscape($projectCtaTitle)?></h2><p><?=$projectEscape($projectCtaDesc)?></p>
            <?php if(!empty($projectTrustItems)) { ?>
            <ul class="pts-ph-contact-trust">
                <?php foreach($projectTrustItems as $projectTrustItem) { ?>
                <li><?php if($projectTrustItem['icon'] !== '') { ?><img src="<?=$projectEscape(ASSET.$projectTrustItem['icon'])?>" alt="" width="18" height="18" loading="lazy"><?php } else { ?><i class="fas <?=$projectEscape(!empty($projectTrustItem['faIcon']) ? $projectTrustItem['faIcon'] : 'fa-check')?>" aria-hidden="true"></i><?php } ?><span><?=$projectEscape($projectTrustItem['label'])?></span></li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
        <div class="pts-ph-contact-actions">
            <?php if($projectHotlineTel !== '') { ?><a class="pts-ph-phone" href="tel:<?=$projectEscape($projectHotlineTel)?>"><i class="fas fa-phone-alt" aria-hidden="true"></i><span><small><?=$projectEscape($projectUi['contact'])?></small><strong><?=$projectEscape($projectHotline)?></strong></span></a><?php } ?>
            <a class="pts-ph-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$projectEscape($projectCtaButton)?> <span aria-hidden="true">→</span></a>
        </div>
    </div></div>
</section>
<?php } ?>
