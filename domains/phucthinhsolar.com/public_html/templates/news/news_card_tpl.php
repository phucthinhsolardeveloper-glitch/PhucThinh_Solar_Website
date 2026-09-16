<?php
$nhName=solutionHubField($nhArticle,'name',$nhLang);
$nhText=solutionHubField($nhArticle,'content',$nhLang);
$nhExcerpt=solutionHubField($nhArticle,'desc',$nhLang) ?: $nhText;
$nhExcerpt=mb_strlen($nhExcerpt,'UTF-8')>180 ? mb_substr($nhExcerpt,0,177,'UTF-8').'…' : $nhExcerpt;
$nhMinutes=$nhText==='' ? 0 : max(1,(int)ceil(count(preg_split('/\s+/u',$nhText,-1,PREG_SPLIT_NO_EMPTY))/220));
$nhGroup=isset($nhCategoryMap[(int)$nhArticle['id_list']]) ? $nhCategoryMap[(int)$nhArticle['id_list']] : array();
$nhPhoto=solutionHubImage($nhArticle['photo'],UPLOAD_NEWS_L);
$nhImageSize=strpos($nhCardClass,'nh-lead')!==false ? '900x506x1' : '600x338x1';
?>
<a class="nh-card<?=$nhCardClass?>" href="<?=$nhEscape($nhArticle['slugvi'].'/')?>">
    <div class="nh-media">
        <?php if($nhPhoto) { echo $func->getImage(array_merge(array('class'=>'nh-image','isLazy'=>false,'sizes'=>$nhImageSize,'upload'=>UPLOAD_NEWS_L,'image'=>$nhArticle['photo'],'alt'=>$nhName),watermarkImageArgs('news',$nhImageSize))); } else { ?><span class="nh-placeholder" aria-hidden="true"><i class="fas fa-solar-panel"></i></span><?php } ?>
        <?php if($nhGroup) { ?><span class="nh-badge"><?=$nhEscape(solutionHubField($nhGroup,'name',$nhLang))?></span><?php } ?>
    </div>
    <div class="nh-card-copy">
        <div class="nh-meta">
            <?php if(!empty($nhArticle['date_created'])) { ?><span><i class="far fa-calendar-alt" aria-hidden="true"></i><time datetime="<?=date('Y-m-d',(int)$nhArticle['date_created'])?>"><?=date('d/m/Y',(int)$nhArticle['date_created'])?></time></span><?php } ?>
            <?php if($nhMinutes) { ?><span><i class="far fa-clock" aria-hidden="true"></i><?=$nhMinutes?> <?=$nhEscape($nhUi['read'])?></span><?php } ?>
        </div>
        <h2><?=$nhEscape($nhName)?></h2>
        <?php if($nhExcerpt !== '') { ?><p><?=$nhEscape($nhExcerpt)?></p><?php } ?>
        <span class="nh-arrow" aria-hidden="true">→</span>
    </div>
</a>
