<section class="CRlist_term_blog inf_default_news_page">
    <div class="container">
        <div class="CRlist_term_blog_box row inf_dots">
            <?php if(count($gplist)){foreach($gplist as $v) { ?>
            <div class="CRitem_term_blog">
                <div class="content <?=(isset($newsList[$sluglang])&& $newsList[$sluglang] == $v[$sluglang]) ? 'active' : ''?>">
                    <a title="<?=$v['name'.$lang]?>" href="danh-muc-giai-phap/<?=$v[$sluglang]?>/"></a>
                    <div class="ico">
                        <?=$func->getImage(['class' => 'lazy w-100','size-error' => '60x60x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                    </div>
                    <h2><?=$v['name'.$lang]?></h2>
                </div>
            </div>
            <?php }} ?>
        </div>
    </div>
</section>
<section class="CRlist_item_blog">
    <div class="container">
        <div class="CRrow_term_blog_box">
            <div class="CRrow_term_bottom">
                <div id="inf_loadmore_post" class="row pts-card-grid">
                    <?php if(count($news)) { foreach($news as $v) { ?>
                    <div data-id="<?=$v['id']?>" class="inf_item_postSlide item_post_slider col-lg-4 col-md-4 col-sm-6 col-12">
                        <div class="wrap item_cont_post_slider">
                            <a title="<?=$v['name'.$lang]?>"
                                href="solution/<?=$v[$sluglang]?>/"></a>
                            <div itemprop="image" class="CRthumb_post">
                                <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                            </div>
                            <div class="CRcont_post box_cont_post_slider">
                                <div class="CRbox">
                                    <p class="date_content"><i class="fa fa-clock-o" aria-hidden="true"></i><?=date('d/m/Y', $v['date_created'])?></p>
                                    <p>-</p>
                                    <p class="author_post"><i class="fa fa-user-o" aria-hidden="true"></i><?=htmlspecialchars(newsAuthorName($d, isset($v['id_user']) ? $v['id_user'] : 0), ENT_QUOTES, 'UTF-8')?></p>
                                </div>
                                <h3><?=$v['name'.$lang]?></h3>
                            </div>
                        </div>
                    </div>
                    <?php } } else { ?>
                    <div class="col-12 pts-grid-full">
                        <div class="pts-empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p><?=khongtimthayketqua?></p>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>
