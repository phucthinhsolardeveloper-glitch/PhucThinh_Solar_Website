<?php 
    $bg_page = $cache->get("select photo from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('background-breadcrumb','photo_static'), 'fetch', 7200);
    $banner_page = $cache->get("select photo from #_photo where type = ? and act = ? limit 0,1", array('banner-'.$com.'','photo_static'), 'fetch', 7200);
?>
<?php if(!empty($banner_page)){ ?>
    <style>
        .page-title{
            background-image: url(<?=UPLOAD_PHOTO_L.$banner_page['photo']?>);
            background-size: cover;
            background-position: center;
        }
    </style>
<?php }else{ ?>
    <style>
        .page-title{
            background-image: url(<?=UPLOAD_PHOTO_L.$bg_page['photo']?>);
            background-size: cover;
            background-position: center;
        }
    </style>
<?php } ?>
<div class="page-title">
    <div class="wrap-content2 wrap-content">
        <div class="title-page">
            <span>
                <?php if(isset($static['name'.$lang])){ ?>
                    <?=$static['name'.$lang]?>
                <?php }else{ ?>
                    <?=(!empty($titleCate)) ? $titleCate : @$titleMain?>
                <?php } ?>
            </span>
        </div>
        <div class="line-page"></div>
        <nav class="breadCrumbs" aria-label="Breadcrumb"><div><?=$breadcrumbs?></div></nav>
    </div>
</div>
