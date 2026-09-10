<section class="CRlist_term_blog">
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
<?php if(count($gplist)) { ?>
<section class="CRlist_item_blog">
    <div class="container">
        <?php foreach($gplist as $v){$gp_item = $cache->get("select name$lang, title_index$lang, slugvi, slugen, desc$lang, date_created, id, id_user, photo from #_news where id_list = ".$v['id']." and type = ? and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap'), 'result', 7200); ?>
        <div class="CRrow_term_blog_box">
            <div class="CRrow_term_top row">
                <div class="col-sm">
                    <div class="title_term"><?=$v['name'.$lang]?></div>
                </div>
            </div>
            <div class="CRrow_term_bottom row deactivate pts-card-grid">
                <?php if(count($gp_item)){foreach($gp_item as $c) { ?>
                <div data-id="<?=$c['id']?>" class="inf_item_postSlide item_post_slider col-lg-4 col-md-4 col-sm-6 col-12">
                    <div class="wrap item_cont_post_slider">
                        <a title="<?=$c['name'.$lang]?>"
                            href="solution/<?=$c[$sluglang]?>/"></a>
                        <div itemprop="image" class="CRthumb_post">
                            <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $c['photo'], 'alt' => $c['name'.$lang]])?>
                        </div>
                        <div class="CRcont_post box_cont_post_slider">
                            <div class="CRbox">
                                <p class="date_content"><i class="fa fa-clock-o" aria-hidden="true"></i><?=date('d/m/Y', $c['date_created'])?></p>
                                <p>-</p>
                                <p class="author_post"><i class="fa fa-user-o" aria-hidden="true"></i><?=htmlspecialchars(newsAuthorName($d, isset($c['id_user']) ? $c['id_user'] : 0), ENT_QUOTES, 'UTF-8')?></p>
                            </div>
                            <h3><?=$c['name'.$lang]?></h3>
                        </div>
                    </div>
                </div>
                <?php }} ?>
            </div>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>
<!-- <div class="">
    <div class="">
        <div class="box-dv">

            <div class="">
                <div class="wrap-content2">
                    <?php if(count($news)){ ?>
                    <div class="row row-service">
                        <?php foreach($news as $v) { ?>
                        <div class="col-md-4">
                            <div class="dv-item">
                                <a class="text-decoration-none" href="<?=$v[$sluglang]?>" title="<?=$v['name'.$lang]?>">
                                    <div class="dv-photo">
                                        <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '374x307x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                    </div>
                                    <div class="dv-info">
                                        <div class="name-dv"><?=htmlspecialchars_decode($v['name'.$lang])?></div>
                                        <div class="desc-dv"><?=htmlspecialchars_decode($v['desc'.$lang])?></div>
                                        <div class="btn-style">
                                            Đọc thêm
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24"
                                                viewBox="0 0 24 24" width="24">
                                                <path
                                                    d="m19.71 12.71-6 6c-.0932.0932-.2039.1672-.3258.2177-.1218.0504-.2523.0764-.3842.0764s-.2624-.026-.3842-.0764c-.1219-.0505-.2326-.1245-.3258-.2177s-.1672-.2039-.2177-.3257c-.0504-.1219-.0764-.2524-.0764-.3843s.026-.2624.0764-.3842c.0505-.1219.1245-.2326.2177-.3258l4.3-4.29h-11.59c-.26522 0-.51957-.1054-.70711-.2929-.18753-.1875-.29289-.4419-.29289-.7071s.10536-.5196.29289-.7071c.18754-.1875.44189-.2929.70711-.2929h11.59l-4.3-4.29c-.1883-.1883-.2941-.4437-.2941-.71s.1058-.52169.2941-.71c.1883-.1883.4437-.29409.71-.29409s.5217.10579.71.29409l6 6c.0937.093.1681.2036.2189.3254.0508.1219.0769.2526.0769.3846s-.0261.2627-.0769.3846c-.0508.1218-.1252.2324-.2189.3254z"
                                                    fill="rgb(0,0,0)"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="">
                        <div class="alert alert-warning w-100" role="alert">
                            <strong><?=khongtimthayketqua?></strong>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="">
                        <div class="pagination-home w-100"><?=(!empty($paging)) ? $paging : ''?></div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div> -->
