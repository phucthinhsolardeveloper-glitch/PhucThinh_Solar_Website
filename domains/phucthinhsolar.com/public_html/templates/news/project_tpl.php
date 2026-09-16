<?php $projectEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<main class="pts-project-hub">
    <?php if($projectShowIntro) { ?>
    <section class="pts-ph-heading" aria-labelledby="project-hub-title">
        <div class="uk-container">
            <div class="pts-ph-mark" aria-hidden="true"><span></span><i class="fas fa-bolt"></i><span></span></div>
            <?php if($projectHeroEyebrow !== '') { ?><span class="pts-ph-eyebrow"><?=$projectEscape($projectHeroEyebrow)?></span><?php } ?>
            <h1 id="project-hub-title"><?=$projectEscape($projectHeroTitle)?></h1>
            <p><?=$projectEscape($projectHeroDesc)?></p>
        </div>
    </section>
    <?php } else { ?>
    <h1 class="pts-ph-sr-only"><?=$projectEscape($projectUi['title'])?></h1>
    <?php } ?>

    <nav class="pts-ph-filters" aria-label="<?=$projectEscape($projectUi['filters'])?>">
        <div class="uk-container">
            <a class="pts-ph-filter<?=empty($newsList['id']) ? ' is-active' : ''?>" href="du-an-da-thuc-hien/">
                <i class="fas fa-th-large" aria-hidden="true"></i><span><?=$projectEscape($projectUi['all'])?></span>
            </a>
            <?php foreach($projectFilterRows as $projectFilter) {
                $projectFilterName = solutionHubField($projectFilter, 'name', $lang);
                $projectFilterSlug = solutionHubField($projectFilter, 'slug', $lang);
                $projectFilterImage = solutionHubImage($projectFilter['photo'], UPLOAD_NEWS_L);
                if($projectFilterSlug === '') continue;
            ?>
            <a class="pts-ph-filter<?=(!empty($newsList['id']) && (int)$newsList['id'] === (int)$projectFilter['id']) ? ' is-active' : ''?>" href="<?=$projectEscape('danh-muc-du-an/'.$projectFilterSlug.'/')?>">
                <?php if($projectFilterImage) { ?><img src="<?=$projectEscape($func->getThumbUrl('48x48x2', $projectFilterImage))?>" alt="" width="22" height="22"><?php } else { ?><i class="fas fa-solar-panel" aria-hidden="true"></i><?php } ?>
                <span><?=$projectEscape($projectFilterName)?></span>
            </a>
            <?php } ?>
        </div>
    </nav>

    <section class="pts-ph-results" aria-label="<?=$projectEscape($projectHeroTitle)?>">
        <div class="uk-container">
            <?php if($projectFeatured) {
                $projectFeaturedName = isset($projectFeatured['name'.$lang]) ? solutionHubText($projectFeatured['name'.$lang]) : '';
                $projectFeaturedSlug = isset($projectFeatured[$sluglang]) ? solutionHubText($projectFeatured[$sluglang]) : '';
                $projectFeaturedPhoto = solutionHubImage($projectFeatured['photo'], UPLOAD_NEWS_L);
                $projectFeaturedAddress = solutionHubText(isset($projectFeatured['address']) ? $projectFeatured['address'] : '');
                $projectFeaturedDesc = solutionHubField($projectFeatured, 'desc', $lang);
            ?>
            <?php if($projectFeaturedSlug !== '') { ?>
            <article class="pts-ph-featured">
                <div class="pts-ph-featured-media<?=$projectFeaturedPhoto ? '' : ' is-placeholder'?>">
                    <?php if($projectFeaturedPhoto) { ?><img src="<?=$projectEscape($func->getThumbUrl('1200x900x1', $projectFeaturedPhoto))?>" alt="<?=$projectEscape($projectFeaturedName)?>" width="1200" height="900" loading="lazy" decoding="async"><?php } else { ?><span class="pts-ph-placeholder" aria-hidden="true">01</span><?php } ?>
                    <?php if($projectFeaturedCategoryName !== '') { ?>
                    <span class="pts-ph-badge"><?php if($projectFeaturedCategoryImage) { ?><img src="<?=$projectEscape($func->getThumbUrl('48x48x2', $projectFeaturedCategoryImage))?>" alt="" width="20" height="20"><?php } else { ?><i class="fas fa-solar-panel" aria-hidden="true"></i><?php } ?><?=$projectEscape($projectFeaturedCategoryName)?></span>
                    <?php } ?>
                </div>
                <div class="pts-ph-featured-body">
                    <span class="pts-ph-eyebrow"><?=$projectEscape($projectUi['featured_eyebrow'])?></span>
                    <h2><?=$projectEscape($projectFeaturedName)?></h2>
                    <div class="pts-ph-meta">
                        <?php if($projectFeaturedAddress !== '') { ?><span><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?=$projectEscape($projectFeaturedAddress)?></span><?php } ?>
                        <?php if($projectFeaturedCategoryName !== '') { ?><span><i class="fas fa-bolt" aria-hidden="true"></i><?=$projectEscape($projectFeaturedCategoryName)?></span><?php } ?>
                    </div>
                    <?php if($projectFeaturedDesc !== '') { ?><p class="pts-ph-featured-desc"><?=$projectEscape($projectFeaturedDesc)?></p><?php } ?>
                    <ul class="pts-ph-featured-list">
                        <?php foreach($projectBenefitItems as $projectBenefit) { ?>
                        <li><?php if($projectBenefit['icon'] !== '') { ?><img src="<?=$projectEscape(ASSET.$projectBenefit['icon'])?>" alt="" width="22" height="22" loading="lazy"><?php } else { ?><i class="fas fa-bolt" aria-hidden="true"></i><?php } ?><span><?=$projectEscape($projectBenefit['label'])?></span></li>
                        <?php } ?>
                    </ul>
                    <a class="pts-ph-featured-cta" href="<?=$projectEscape($projectFeaturedSlug.'/')?>"><?=$projectEscape($projectUi['featured_cta'])?> <span aria-hidden="true">→</span></a>
                </div>
            </article>
            <?php } ?>
            <?php } ?>
            <?php if($projectGridRows) { ?>
            <div class="pts-ph-grid">
                <?php foreach($projectGridRows as $projectIndex => $projectRow) {
                    $projectName = isset($projectRow['name'.$lang]) ? solutionHubText($projectRow['name'.$lang]) : '';
                    $projectSlug = isset($projectRow[$sluglang]) ? solutionHubText($projectRow[$sluglang]) : '';
                    $projectPhoto = solutionHubImage($projectRow['photo'], UPLOAD_NEWS_L);
                    $projectAddress = solutionHubText(isset($projectRow['address']) ? $projectRow['address'] : '');
                    $projectCategory = isset($projectCategoryById[(int)$projectRow['id_list']]) ? $projectCategoryById[(int)$projectRow['id_list']] : array();
                    $projectCategoryName = $projectCategory ? solutionHubField($projectCategory, 'name', $lang) : '';
                    $projectCategoryImage = $projectCategory ? solutionHubImage($projectCategory['photo'], UPLOAD_NEWS_L) : '';
                    $projectDesc = solutionHubField($projectRow, 'desc', $lang);
                    if($projectSlug === '') continue;
                ?>
                <a class="pts-ph-card" href="<?=$projectEscape($projectSlug.'/')?>">
                    <div class="pts-ph-card-media<?=$projectPhoto ? '' : ' is-placeholder'?>">
                        <?php if($projectPhoto) { ?><img src="<?=$projectEscape($func->getThumbUrl('960x600x1', $projectPhoto))?>" alt="<?=$projectEscape($projectName)?>" width="960" height="600" loading="lazy" decoding="async"><?php } else { ?><span class="pts-ph-placeholder" aria-hidden="true"><?=str_pad((string)($projectIndex + 1), 2, '0', STR_PAD_LEFT)?></span><?php } ?>
                        <?php if($projectCategoryName !== '') { ?>
                        <span class="pts-ph-badge"><?php if($projectCategoryImage) { ?><img src="<?=$projectEscape($func->getThumbUrl('48x48x2', $projectCategoryImage))?>" alt="" width="20" height="20"><?php } else { ?><i class="fas fa-solar-panel" aria-hidden="true"></i><?php } ?><?=$projectEscape($projectCategoryName)?></span>
                        <?php } ?>
                    </div>
                    <div class="pts-ph-card-body">
                        <h2><?=$projectEscape($projectName)?></h2>
                        <div class="pts-ph-meta">
                            <?php if($projectAddress !== '') { ?><span><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?=$projectEscape($projectAddress)?></span><?php } ?>
                            <?php if($projectCategoryName !== '') { ?><span><i class="fas fa-bolt" aria-hidden="true"></i><?=$projectEscape($projectCategoryName)?></span><?php } ?>
                        </div>
                        <?php if($projectDesc !== '') { ?><p class="pts-ph-card-desc"><?=$projectEscape($projectDesc)?></p><?php } ?>
                        <?php /* 2026-09-16: mũi tên cũ là vòng tròn viền vàng thuần trang trí.
                                 Chuyển sang text-link có nhãn thật (khớp .pts-retail-text-link ở
                                 trang chủ) - mũi tên đứng một mình không nói cho người đọc biết
                                 bấm vào sẽ đi đâu. */ ?>
                        <span class="pts-ph-arrow">Xem dự án <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </div>
                </a>
                <?php } ?>
            </div>
            <?php } ?>
            <?php if(!empty($paging)) { ?><div class="pagination-home w-100"><?=$paging?></div><?php } ?>
            <?php if(!$news) { ?>
            <div class="pts-ph-empty"><i class="far fa-images" aria-hidden="true"></i><p><?=$projectEscape($projectUi['empty'])?></p><a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$projectEscape($projectCtaButton)?> →</a></div>
            <?php } ?>
        </div>
    </section>

    <?php include TEMPLATE.'news/project_cta_tpl.php'; ?>
</main>
