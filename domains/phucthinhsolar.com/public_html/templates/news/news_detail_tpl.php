<div class="row justify-content-center row-servoce-detail">
    <div class="col-lg-12">
        <div class="box-detail-news">
            <div class="title-main"><span class=""><?=$rowDetail['name'.$lang]?></span></div>
            <div class="time-main">
                <i class="fas fa-clock"></i><span><?=date("M d, Y",$rowDetail['date_created'])?></span>
                <i class="fas fa-user ml-4"></i><span><?=htmlspecialchars(newsAuthorName($d, isset($rowDetail['id_user']) ? $rowDetail['id_user'] : 0), ENT_QUOTES, 'UTF-8')?></span>
            </div>
            <?php if(!empty($rowDetail['photo'])) { ?>
            <div class="pts-news-detail-cover">
                <?=$func->getImage(['class' => 'w-100', 'isLazy' => false, 'sizes' => '1000x560x1', 'upload' => UPLOAD_NEWS_L, 'image' => $rowDetail['photo'], 'alt' => $rowDetail['name'.$lang]])?>
            </div>
            <?php } ?>
            <?php if(!empty($rowDetail['content'.$lang])) { ?>
            <div class="meta-toc">
                <div class="box-readmore">
                    <p><i class="fa fa-bars" aria-hidden="true"></i>NỘI DUNG BÀI VIẾT</p>
                    <ul class="toc-list" data-toc="article" data-toc-headings="h1, h2, h3"></ul>
                </div>
            </div>
            <div class="content-main w-clear" id="toc-content"><?=htmlspecialchars_decode($rowDetail['content'.$lang])?>
            </div>
            <?php if(!empty($rowDetail['content1'.$lang])) { ?>
            <div class="CRpost_cont">
                <blockquote>
                    <?=htmlspecialchars_decode($rowDetail['content1'.$lang])?>
                </blockquote>
            </div>
            <?php } ?>
            <div class="share">
                <div class="social-plugin w-clear">
                    <div class="social-product">
                        <ul>
                            <li>
                                <button class="sharer sharer1 btn btn-primary btn-lg" data-sharer="twitter"
                                    data-title="<?=$rowDetail['name'.$lang]?>"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-twitter-square"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer2 btn btn-primary btn-lg" data-sharer="facebook"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-facebook-square"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer3 btn btn-primary btn-lg" data-sharer="linkedin"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i class="fab fa-linkedin"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer4 btn btn-primary btn-lg" data-sharer="email"
                                    data-title="<?=$rowDetail['name'.$lang]?>"
                                    data-url="<?=$func->getCurrentPageURL()?>"
                                    data-subject="<?=$rowDetail['name'.$lang]?>" data-to=""><i
                                        class="fas fa-envelope-square"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer5 btn btn-primary btn-lg" data-sharer="pinterest"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-pinterest-square"></i></button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php } else { ?>
            <div class="alert alert-warning w-100" role="alert">
                <strong><?=noidungdangcapnhat?></strong>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php if(count($news)){ ?>
<div id="CRsingle_blog_related">
    <div class="related-heading">
        <div>
            <span class="related-eyebrow">Khám phá thêm</span>
            <h2 class="title">Tin liên quan</h2>
        </div>
        <div class="CRcustom_arrow_related" aria-label="Điều hướng tin liên quan">
            <div class="box">
                <button type="button" class="customm_arrow left" aria-label="Tin trước"><i class="fa fa-angle-left" aria-hidden="true"></i></button>
                <button type="button" class="customm_arrow right" aria-label="Tin tiếp theo"><i class="fa fa-angle-right" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
    <div id="CRsingle_list_related" class="row active_slider CRstyle_arrow">
        <?php foreach($news as $k => $v){ ?>
        <div data-id="<?=$v['id']?>" class="CRfix_width_slider hover_ef_img item_post col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="item_cont_post">
                <a title="<?=$v['name'.$lang]?>" href="<?=$v[$sluglang]?>"></a>
                <div class="ef_img img_item_cont_post">
                    <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </div>
                <div class="content_item_cont_post">
                    <div class="CRbox">
                        <p class="date_content"><i class="fa fa-clock-o" aria-hidden="true"></i><?=date('d/m/Y', $v['date_created'])?></p>
                        <p>-</p>
                        <p class="author_post"><i class="fa fa-user-o" aria-hidden="true"></i><?=htmlspecialchars(newsAuthorName($d, isset($v['id_user']) ? $v['id_user'] : 0), ENT_QUOTES, 'UTF-8')?>
                        </p>
                    </div>
                    <h3><?=$v['name'.$lang]?></h3>
                    <div class="des"><?=$v['desc'.$lang]?></div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php } ?>
