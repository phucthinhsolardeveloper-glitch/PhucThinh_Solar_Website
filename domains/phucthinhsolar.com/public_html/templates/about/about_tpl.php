<div class="inf_intro_page">
    <?php if(count($lichsu)){ ?>
    <?php foreach($lichsu as $k => $v){ ?>
    <section id="<?=($k%2==0)?'inf_about':'inf_history'?>" class="inf_about_history">
        <div class="container-fluid">
            <div class="row">
                <div class="left col-xl-6 col-lg-6 col-md-12 col-sm-12 ">
                    <div class="content aos-init aos-animate" data-aos="fade-right" data-aos-duration="1500"
                        data-aos-delay="300">
                        <?=$func->getImage(['class' => '', 'sizes' => '1000x700x3', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                    </div>
                </div>
                <div class="right col-xl-6 col-lg-6 col-md-12 col-sm-12 ">
                    <div class="content aos-init aos-animate" data-aos="fade-up" data-aos-duration="1500"
                        data-aos-delay="300">
                        <h2><?=$v['name'.$lang]?></h2>
                        <div class="des">
                            <?=htmlspecialchars_decode($v['content'.$lang])?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>
    <?php } ?>

    <?php if(count($lydo)){ ?>
    <section id="CRintro_home">
        <div class="container">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="content">
                        <h2 class="CRstyle_title">Tại sao nên chọn <span class="pts-brand-text">Phúc Thịnh Solar</span>?</h2>
                        <div id="CRicos_intro_home" class="inf_dots">
                            <?php foreach($lydo as $k => $v){ ?>
                            <div class="inf_ico_intro_home">
                                <div class="wrap">
                                    <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '366x156x3', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                    <p><?=$v['name'.$lang]?></p>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <?php } ?>

    <?php if(!empty($sumenh_title)){ ?>
    <section class="rocket-lazyload lazyloaded" id="inf_vision_intro"
        style="background-image: url(assets/images/2-layers-6.png);" data-ll-status="loaded">
        <div class="container">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <h2 class="CRstyle_title"><?=$sumenh_title['name'.$lang]?></h2>
                </div>
            </div>
            <div id="CRlist_vision" class="row">
                <div class="col-12">
                    <div class="content inf_dots">
                        <?php if(count($sumenh)){foreach($sumenh as $k => $v){ ?>
                        <div class="CRgr_item_vision inf_item_vision">
                            <div class="wrap">
                                <?=$func->getImage(['class' => '', 'sizes' => '120x120x3', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                <h3><?=$v['name'.$lang]?></h3>
                                <div class="content"><?=htmlspecialchars_decode($v['content'.$lang])?></div>
                            </div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>


    <section class="rocket-lazyload" id="inf_partner_cert" style="background-image: url(assets/images/2-layers-7.png);">
        <?php if(count($partner)){ ?>
        <section id="inf_partner">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                        <h2 class="CRstyle_title">Đối tác chiến lược</h2>
                        <div id="inf_list_partner" class="inf_dots"><?php /* Theme V2: lưới logo tĩnh (flex-wrap), không dùng slick. */ ?>
                            <?php foreach($partner as $k => $v){ ?>
                            <div class="inf_item_partner">
                                <div class="wrap">
                                    <?=$func->getImage(['isLazy' => false,'sizes' => '300x128x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                </div>
                                <?php if(!empty($v['name'.$lang])){ ?>
                                <p class="inf_partner_caption"><?=$v['name'.$lang]?></p>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php } ?>
        <?php if(count($chungnhan)){ ?>
        <section id="CRcertification_intro">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                        <h2 class="CRstyle_title">Chứng nhận</h2>
                    </div>
                </div>
                <div id="inf_list_certification_intro" class="row active_slider inf_dots">
                    <?php foreach($chungnhan as $k => $v){ ?>
                    <div class="CRfix_width_slider CRgroup_item_personnel col-xl-4 col-lg-4 col-md-6 col-sm-12">
                        <div class="CRitem_certification inf_item_certification">
                            <div class="CRcertification wrap">
                                <div class="img_personnel">
                                    <?=$func->getImage(['isLazy' => false,'sizes' => '642x900x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                </div>
                                <p class="des"><?=$v['name'.$lang]?></p>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </section>
        <?php } ?>
    </section>

</div>
