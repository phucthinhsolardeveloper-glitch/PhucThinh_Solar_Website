<div class="inf_default_news_page">
    <?php $news_list = $cache->get("select name$lang, slugvi, slugen, id, photo from #_news_list where type = ? and find_in_set('hienthi',status) order by numb,id desc", array($type), 'result', 7200); ?>
    <?php if(count($news_list)){ ?>
    <section class="CRlist_term_blog">
        <div class="container">
            <div class="CRlist_term_blog_box row inf_dots">
                <?php foreach($news_list as $k => $v){ ?>
                <div class="CRitem_term_blog">
                    <div class="content <?=(isset($newsList[$sluglang])&& $newsList[$sluglang] == $v[$sluglang]) ? 'active' : ''?>">
                        <a title="<?=$v['name'.$lang]?>" href="category/<?=$v[$sluglang]?>/"></a>
                        <div class="ico">
                            <?=$func->getImage(['class' => 'lazy','size-error' => '60x60x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                        </div>
                        <h2><?=$v['name'.$lang]?></h2>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>
</div>
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
                                    <p class="author_post"><i class="fa fa-user-o" aria-hidden="true"></i>Admin Dev</p>
                                </div>
                                <h3><?=$v['name'.$lang]?></h3>
                            </div>
                        </div>
                    </div>
                    <?php } } else { ?>
                    <div class="col-12 pts-grid-full">
                        <div class="pts-empty-state">
                            <i class="fas fa-newspaper"></i>
                            <p><?=khongtimthayketqua?></p>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="col-12 pts-grid-full">
                        <div class="pagination-home w-100"><?=(!empty($paging)) ? $paging : ''?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
