<main class="pts-news-hub">
<div class="uk-container">
    <header class="nh-heading<?=$nhBlocks['news-intro']['visible'] || $nhCategoryId ? '' : ' nh-sr-only'?>">
        <?php if(solutionHubField($nhIntro,'title_index',$nhLang)!=='') { ?><span class="nh-eyebrow"><?=$nhEscape(solutionHubField($nhIntro,'title_index',$nhLang))?></span><?php } ?>
        <h1><?=$nhEscape($nhTitle)?></h1>
        <?php if($nhDescription!=='') { ?><p><?=$nhEscape($nhDescription)?></p><?php } ?>
    </header>
    <?php if($nhShowFeatured) { ?>
    <section class="nh-featured" aria-label="<?=$nhEscape($nhUi['featured'])?>">
        <?php foreach($nhFeatured as $nhIndex=>$nhArticle) { $nhCardClass=$nhIndex===0 ? ' nh-lead' : ' nh-secondary'; include TEMPLATE.'news/news_card_tpl.php'; } ?>
    </section><?php } ?>
    <div class="nh-toolbar">
        <nav class="nh-filters" aria-label="<?=$nhEscape($nhUi['results'])?>">
            <a class="nh-filter<?=$nhCategoryId ? '' : ' is-active'?>" <?=!$nhCategoryId ? 'aria-current="page"' : ''?> href="<?=$nhEscape($nhUrl('tin-tuc/',$nhQuery))?>"><i class="fas fa-th-large" aria-hidden="true"></i><?=$nhEscape($nhUi['all'])?></a>
            <?php foreach($nhCategories as $nhCategory) { $nhIcon=solutionHubImage($nhCategory['photo'],UPLOAD_NEWS_L); ?>
            <a class="nh-filter<?=$nhCategoryId===(int)$nhCategory['id'] ? ' is-active' : ''?>" <?=$nhCategoryId===(int)$nhCategory['id'] ? 'aria-current="page"' : ''?> href="<?=$nhEscape($nhUrl($nhCategory['slugvi'].'/',$nhQuery))?>">
                <?php if($nhIcon) { ?><img src="<?=$nhEscape(ASSET.$nhIcon)?>" alt="" width="22" height="22"><?php } else { ?><i class="far fa-newspaper" aria-hidden="true"></i><?php } ?><?=$nhEscape(solutionHubField($nhCategory,'name',$nhLang))?>
            </a><?php } ?>
        </nav>
        <form class="nh-search" method="get" action="<?=$nhEscape($nhPath)?>" role="search">
            <label for="nh-search-input" class="nh-sr-only"><?=$nhEscape($nhUi['search'])?></label>
            <input type="search" id="nh-search-input" name="q" value="<?=$nhEscape($nhQuery)?>" placeholder="<?=$nhEscape($nhUi['search'])?>…" maxlength="120">
            <button type="submit" aria-label="<?=$nhEscape($nhUi['search'])?>"><i class="fas fa-search" aria-hidden="true"></i></button>
        </form>
    </div>
    <?php if($nhQuery!=='') { ?><p class="nh-query"><?=$nhEscape($nhUi['results'])?>: <strong><?=$nhTotal?></strong> — “<?=$nhEscape($nhQuery)?>” <a href="<?=$nhEscape($nhPath)?>">× <?=$nhEscape($nhUi['reset'])?></a></p><?php } ?>
    <section class="nh-results" aria-label="<?=$nhEscape($nhUi['results'])?>">
        <?php if($nhArticles) { ?><div class="nh-grid">
            <?php foreach($nhArticles as $nhArticle) { $nhCardClass=''; include TEMPLATE.'news/news_card_tpl.php'; } ?>
        </div><?php } elseif(!$nhShowFeatured) { ?>
        <div class="nh-empty"><i class="far fa-newspaper" aria-hidden="true"></i><p><?=$nhEscape($nhUi['empty'])?></p><a href="/tin-tuc/"><?=$nhEscape($nhUi['reset'])?> →</a></div>
        <?php } ?>
    </section>
    <?php if($nhPages>1) { ?><nav class="nh-pagination" aria-label="<?=$nhEscape($nhUi['pages'])?>">
        <?php if($nhPage>1) { ?><a rel="prev" aria-label="<?=$nhLang==='en' ? 'Previous page' : 'Trang trước'?>" href="<?=$nhEscape($nhUrl($nhPath,$nhQuery,$nhPage-1))?>">‹</a><?php } ?>
        <?php $nhPrevious=0; for($nhNumber=1;$nhNumber<=$nhPages;$nhNumber++) {
            if($nhNumber!==1 && $nhNumber!==$nhPages && abs($nhNumber-$nhPage)>2) continue;
            if($nhPrevious && $nhNumber>$nhPrevious+1) echo '<span>…</span>'; $nhPrevious=$nhNumber;
        ?><a <?=$nhNumber===$nhPage ? 'class="is-active" aria-current="page"' : ''?> href="<?=$nhEscape($nhUrl($nhPath,$nhQuery,$nhNumber))?>"><?=$nhNumber?></a><?php } ?>
        <?php if($nhPage<$nhPages) { ?><a rel="next" aria-label="<?=$nhLang==='en' ? 'Next page' : 'Trang sau'?>" href="<?=$nhEscape($nhUrl($nhPath,$nhQuery,$nhPage+1))?>">›</a><?php } ?>
    </nav><?php } ?>
</div>
<?php include TEMPLATE.'news/news_cta_tpl.php'; ?>
</main>
