<?php $video = $cache->get("select id, photo, options, video from #_photo where type = ? and act = ? limit 0,1", array('video','photo_static'), 'fetch', 7200); ?>
<div class="wrap-about-page">
    <div class="box-content-about-page">
        <div class="title-home">
			<span><?=$static['name'.$lang]?></span>
			<p>Ngôi nhà tràn ngập niềm vui và tiếng cười</p>
			<div class="line-title"></div>
		</div>
        <?php if(!empty($static)) { ?>
            <div class="title-main"><span></span></div>
            <div class="content-main w-clear"><?=(!empty($static['content'.$lang])) ? htmlspecialchars_decode($static['content'.$lang]) : ''?></div>
        <?php } else { ?>
            <div class="alert alert-warning w-100" role="alert">
                <strong><?=dangcapnhatdulieu?></strong>
            </div>
        <?php } ?>
    </div>
    <?php if(!empty($video)){ ?>
        <div class="video-about mt-5">
            <video class="video-slider" data-html="true" autoplay muted loop controls playsinline>
                <source src="<?= UPLOAD_FILE_L . $video['video'] ?>" type="video/mp4">
            </video>
        </div>
    <?php } ?>
</div>