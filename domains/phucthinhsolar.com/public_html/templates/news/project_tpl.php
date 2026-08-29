<section class="CRlist_term_blog inf_default_news_page">
    <div class="container">
        <div class="CRlist_term_blog_box row inf_dots">
            <?php if (count($dalist)) {
                foreach ($dalist as $v) { ?>
                    <div class="CRitem_term_blog">
                        <div class="content <?=(isset($newsList[$sluglang])&& $newsList[$sluglang] == $v[$sluglang]) ? 'active' : ''?>">
                            <a title="<?= $v['name' . $lang] ?>" href="danh-muc-du-an/<?= $v[$sluglang] ?>/"></a>
                            <div class="ico">
                                <?= $func->getImage(['class' => 'lazy w-100', 'size-error' => '60x60x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
                            </div>
                            <h2><?= $v['name' . $lang] ?></h2>
                        </div>
                    </div>
            <?php }
            } ?>
        </div>
    </div>
</section>
<section class="CRlist_item_blog">
    <div class="container">
        <div class="CRrow_term_blog_box">
            <div class="CRrow_term_bottom">
                <div id="inf_loadmore_post" class="row">
                    <?php if(count($news)) { foreach($news as $v) { ?>
                    <div class="inf_item_project col-lg-4 col-md-4 col-sm-6 col-12">
                        <div class="wrap">
                            <a title="<?=$v['name'.$lang]?>" href="store-manager/<?=$v[$sluglang]?>/"></a>
                            <div class="thumbnail">
                                <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                            </div>
                            <div class="content">
                                <div class="box_title">
                                    <h4 class="title"><?=$v['name'.$lang]?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } } else { ?>
                    <div class="col-12">
                        <div class="pts-empty-state">
                            <i class="fas fa-image"></i>
                            <p><?=khongtimthayketqua?></p>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>