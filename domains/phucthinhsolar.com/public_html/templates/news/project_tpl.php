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
                <?php if($projectFilterImage) { ?><img src="<?=$projectEscape(ASSET.$projectFilterImage)?>" alt="" width="22" height="22"><?php } else { ?><i class="fas fa-solar-panel" aria-hidden="true"></i><?php } ?>
                <span><?=$projectEscape($projectFilterName)?></span>
            </a>
            <?php } ?>
        </div>
    </nav>

    <section class="pts-ph-results" aria-label="<?=$projectEscape($projectHeroTitle)?>">
        <div class="uk-container">
            <?php if($news) { ?>
            <div class="pts-ph-grid">
                <?php foreach($news as $projectIndex => $projectRow) {
                    $projectName = isset($projectRow['name'.$lang]) ? solutionHubText($projectRow['name'.$lang]) : '';
                    $projectSlug = isset($projectRow[$sluglang]) ? solutionHubText($projectRow[$sluglang]) : '';
                    $projectPhoto = solutionHubImage($projectRow['photo'], UPLOAD_NEWS_L);
                    $projectAddress = solutionHubText(isset($projectRow['address']) ? $projectRow['address'] : '');
                    $projectCategory = isset($projectCategoryById[(int)$projectRow['id_list']]) ? $projectCategoryById[(int)$projectRow['id_list']] : array();
                    $projectCategoryName = $projectCategory ? solutionHubField($projectCategory, 'name', $lang) : '';
                    $projectCategoryImage = $projectCategory ? solutionHubImage($projectCategory['photo'], UPLOAD_NEWS_L) : '';
                    if($projectSlug === '') continue;
                ?>
                <a class="pts-ph-card" href="<?=$projectEscape($projectSlug.'/')?>">
                    <div class="pts-ph-card-media<?=$projectPhoto ? '' : ' is-placeholder'?>">
                        <?php if($projectPhoto) { ?><img src="<?=$projectEscape(ASSET.$projectPhoto)?>" alt="<?=$projectEscape($projectName)?>" width="760" height="480" loading="lazy" decoding="async"><?php } else { ?><span class="pts-ph-placeholder" aria-hidden="true"><?=str_pad((string)($projectIndex + 1), 2, '0', STR_PAD_LEFT)?></span><?php } ?>
                        <?php if($projectCategoryName !== '') { ?>
                        <span class="pts-ph-badge"><?php if($projectCategoryImage) { ?><img src="<?=$projectEscape(ASSET.$projectCategoryImage)?>" alt="" width="20" height="20"><?php } else { ?><i class="fas fa-solar-panel" aria-hidden="true"></i><?php } ?><?=$projectEscape($projectCategoryName)?></span>
                        <?php } ?>
                    </div>
                    <div class="pts-ph-card-body">
                        <h2><?=$projectEscape($projectName)?></h2>
                        <div class="pts-ph-meta">
                            <?php if($projectAddress !== '') { ?><span><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?=$projectEscape($projectAddress)?></span><?php } ?>
                            <?php if($projectCategoryName !== '') { ?><span><i class="fas fa-bolt" aria-hidden="true"></i><?=$projectEscape($projectCategoryName)?></span><?php } ?>
                        </div>
                        <span class="pts-ph-arrow" aria-hidden="true">→</span>
                    </div>
                </a>
                <?php } ?>
            </div>
            <?php if(!empty($paging)) { ?><div class="pagination-home w-100"><?=$paging?></div><?php } ?>
            <?php } else { ?>
            <div class="pts-ph-empty"><i class="far fa-images" aria-hidden="true"></i><p><?=$projectEscape($projectUi['empty'])?></p><a href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$projectEscape($projectCtaButton)?> →</a></div>
            <?php } ?>
        </div>
    </section>

    <?php include TEMPLATE.'news/project_cta_tpl.php'; ?>
</main>
