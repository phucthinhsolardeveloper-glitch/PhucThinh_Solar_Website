<div class="row justify-content-center row-servoce-detail">
    <div class="col-lg-9">
        <div class="box-detail-news">
            <div class="title-main"><span class=""><?=$rowDetail['name'.$lang]?></span></div>
            <div class="time-main">
                <i class="fas fa-clock"></i><span><?=date("M d, Y",$rowDetail['date_created'])?></span>
                <i class="fas fa-user ml-4"></i><span>Phúc Thịnh Solar</span>
            </div>
            <?php if(!empty($rowDetail['content'.$lang])) { ?>
            <div class="meta-toc">
                <div class="box-readmore">
                    <p><i class="fa fa-bars" aria-hidden="true"></i>NỘI  DUNG BÀI VIẾT</p>
                    <ul class="toc-list" data-toc="article" data-toc-headings="h1, h2, h3"></ul>
                </div>
            </div>
            <div class="content-main w-clear" id="toc-content"><?=htmlspecialchars_decode($rowDetail['content'.$lang])?></div>
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