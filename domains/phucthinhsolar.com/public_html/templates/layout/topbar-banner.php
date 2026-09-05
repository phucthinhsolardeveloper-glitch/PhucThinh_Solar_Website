<?php
    /* Banner quảng cáo đầu trang, chỉ hiện khi Admin bật "Hiển thị" và có ảnh
       (dùng cho các đợt khuyến mãi/sự kiện, tắt đi khi hết đợt). */
    $topbarBanner = $cache->get("select name$lang, photo, link from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('banner-top','photo_static'), 'fetch', 7200);
?>
<?php if(!empty($topbarBanner) && !empty($topbarBanner['photo'])) { ?>
    <div class="pts-topbar-banner">
        <?php if(!empty($topbarBanner['link'])) { ?><a href="<?=$topbarBanner['link']?>" title="<?=$topbarBanner['name'.$lang]?>"><?php } ?>
            <?=$func->getImage(['class' => 'w-100', 'isLazy' => false, 'sizes' => '1900x90x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $topbarBanner['photo'], 'alt' => (!empty($topbarBanner['name'.$lang])) ? $topbarBanner['name'.$lang] : 'Banner khuyến mãi'])?>
        <?php if(!empty($topbarBanner['link'])) { ?></a><?php } ?>
    </div>
<?php } ?>
