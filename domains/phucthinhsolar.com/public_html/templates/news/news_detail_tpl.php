<?php
    require_once LIBRARIES.'news_detail_media.php';
    $newsDetailContent = !empty($rowDetail['content'.$lang]) ? htmlspecialchars_decode($rowDetail['content'.$lang], ENT_QUOTES) : '';
?>
<div class="row justify-content-center row-servoce-detail">
    <div class="col-lg-12">
        <div class="box-detail-news">
            <?php if($type === 'tin-tuc') { ?><h1 class="nh-detail-title"><?=htmlspecialchars($rowDetail['name'.$lang], ENT_QUOTES, 'UTF-8')?></h1><?php } else { ?>
            <div class="title-main"><span class=""><?=$rowDetail['name'.$lang]?></span></div>
            <?php } ?>
            <div class="time-main">
                <i class="fas fa-clock"></i><span><?=date("M d, Y",$rowDetail['date_created'])?></span>
                <i class="fas fa-user ml-4"></i><span><?=htmlspecialchars(newsAuthorName($d, isset($rowDetail['id_user']) ? $rowDetail['id_user'] : 0), ENT_QUOTES, 'UTF-8')?></span>
            </div>
            <?php
                /* Trang chính sách (chinh-sach): giữ ảnh trong dữ liệu (dùng cho
                   card/thumbnail sau này) nhưng không hiển thị to ở đầu trang chi
                   tiết - phản hồi 2026-09-11, ảnh full-width chiếm spotlight quá
                   cho nội dung pháp lý. Không dùng $config['news'][$type]['show_images']
                   vì config-type-news.php chỉ được nạp bên administrator/, không
                   tồn tại ở phía frontend. */
                $showDetailCover = $rowDetail['type'] !== 'chinh-sach'
                    && !newsDetailContentRepeatsCover($newsDetailContent, isset($rowDetail['photo']) ? $rowDetail['photo'] : '');
            ?>
            <?php if(!empty($rowDetail['photo']) && $showDetailCover) { ?>
            <div class="pts-news-detail-cover">
                <?=$func->getImage(array_merge(['class' => 'w-100', 'isLazy' => false, 'sizes' => '1000x560x1', 'upload' => UPLOAD_NEWS_L, 'image' => $rowDetail['photo'], 'alt' => $rowDetail['name'.$lang]], watermarkImageArgs('news')))?>
            </div>
            <?php } ?>
            <?php if($newsDetailContent !== '') { ?>
            <div class="meta-toc">
                <div class="box-readmore">
                    <p><i class="fa fa-bars" aria-hidden="true"></i>NỘI DUNG BÀI VIẾT</p>
                    <ul class="toc-list" data-toc="article" data-toc-headings="h1, h2, h3"></ul>
                </div>
            </div>
            <div class="content-main w-clear" id="toc-content"><?=$newsDetailContent?>
            </div>
            <?php if(!empty($rowDetail['content1'.$lang])) { ?>
            <div class="CRpost_cont">
                <blockquote>
                    <?=htmlspecialchars_decode($rowDetail['content1'.$lang])?>
                </blockquote>
            </div>
            <?php } ?>
            <div class="share pts-share">
                <span class="pts-share-label">Chia sẻ</span>
                <div class="social-plugin w-clear">
                    <div class="social-product">
                        <ul>
                            <li>
                                <button class="sharer sharer1 pts-share-btn" data-sharer="twitter"
                                    data-title="<?=$rowDetail['name'.$lang]?>"
                                    aria-label="Chia sẻ lên Twitter/X"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-twitter" aria-hidden="true"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer2 pts-share-btn" data-sharer="facebook"
                                    aria-label="Chia sẻ lên Facebook"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-facebook-f" aria-hidden="true"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer3 pts-share-btn" data-sharer="linkedin"
                                    aria-label="Chia sẻ lên LinkedIn"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i class="fab fa-linkedin-in" aria-hidden="true"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer4 pts-share-btn" data-sharer="email"
                                    data-title="<?=$rowDetail['name'.$lang]?>"
                                    aria-label="Chia sẻ qua Email"
                                    data-url="<?=$func->getCurrentPageURL()?>"
                                    data-subject="<?=$rowDetail['name'.$lang]?>" data-to=""><i
                                        class="fas fa-envelope" aria-hidden="true"></i></button>
                            </li>
                            <li>
                                <button class="sharer sharer5 pts-share-btn" data-sharer="pinterest"
                                    aria-label="Chia sẻ lên Pinterest"
                                    data-url="<?=$func->getCurrentPageURL()?>"><i
                                        class="fab fa-pinterest-p" aria-hidden="true"></i></button>
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
<?php if($type === 'tin-tuc') include TEMPLATE.'news/news_cta_tpl.php'; ?>
