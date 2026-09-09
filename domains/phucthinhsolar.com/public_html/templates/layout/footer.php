<!-- <div class="fixed-stuff">
    <div class="fixed-list">
        <div class="fixed-social">
            <button class="trigger-fixed-list"><br>
                <i class="fas fa-headset"></i><br>
            </button>
            <p></p>
            <div class="fixed-social-list active">
                <ul>
                    <li><a href=""><br>
                            <img decoding="async" src="assets/images/Icon_of_Zalo-2-3.webp"> </a></li>
                    <li><a href="<?=$optsetting['fanpage']?>"><br>
                            <i class="fab fa-facebook-messenger"></i><br>
                        </a></li>
                    <li><a href="tel:<?=$func->parsePhone($optsetting['hotline'])?>"><br>
                            <i class="fa fa-phone"></i><br>
                        </a></li>
                </ul>
            </div>
        </div>
    </div>
</div> -->
<footer style="background-image: url('assets/images/bg-footer.png')">
    <div class="footer_top">
        <div class="container">
            <div class="row">
                <div class="col-md-2">
                    <div id="footer_logo" style="max-width: 141px; margin-bottom: 29px">
                        <a href="">
                            <?=$func->getImage(['class' => 'lazy','sizes' => '368x144x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $logof['photo'], 'alt' => $setting['name'.$lang]])?>
                        </a>
                    </div>
                </div>
                <div class="ft_mn ft_mn_left col-md-5">
                    <div class="item">
                        <p class="footer-title pts-brand-text">
                            <?=$footer['name'.$lang]?>
                        </p>
                        <div class="CRcont_nav_mobile">
                            <div class="CRlist_office_center">
                                <?=htmlspecialchars_decode($footer['content'.$lang])?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ft_mn ft_mn_right col-md-5">
                    <div class="content row justify-content-between">
                        <?php /*
                        <div class="item col-md-3">
                            <p class="footer-title">Thông tin</p>

                            <div class="CRcont_nav_mobile">
                                <nav class="footer-link">
                                    <div class="menu-info-footer-container">
                                        <ul id="menu-info-footer" class="about_menu">
                                            <li id="menu-item-55186"
                                                class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home current-menu-item page_item page-item-51716 current_page_item CRcustomMenu CRcustomMenu2 menu-item-55186 active ">
                                                <a href="" aria-current="page" itemprop="url"><span
                                                        itemprop="name">Trang chủ</span></a>
                                            </li>
                                            <li id="menu-item-55188"
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-55188">
                                                <a href="tin-tuc/" itemprop="url"><span itemprop="name">Tin
                                                        tức</span></a>
                                            </li>
                                            <li id="menu-item-55189"
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-55189">
                                                <a href="lien-he/" itemprop="url"><span itemprop="name">Liên
                                                        hệ</span></a>
                                            </li>
                                            <li id="menu-item-56206"
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-56206">
                                                <a href="dai-ly/" itemprop="url"><span itemprop="name">Đại
                                                        lý</span></a>
                                            </li>
                                            <li id="menu-item-56208"
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-56208">
                                                <a href="ve-chung-toi/" itemprop="url"><span
                                                        itemprop="name">Giới thiệu</span></a>
                                            </li>
                                            <li id="menu-item-56209"
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-56209">
                                                <a href="san-pham/" itemprop="url"><span itemprop="name">Sản
                                                        phẩm</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </nav>
                            </div>
                        </div>*/?>
                        <div class="item col-md-5">
                            <p class="footer-title">Hướng dẫn</p>
                            <div class="CRcont_nav_mobile">
                                <nav class="footer-link">
                                    <div class="menu-policy-menu-container">
                                        <ul id="menu-policy-menu" class="policy_menu">
                                            <?php if(count($policy)){foreach($policy as $k => $v){ ?>
                                            <li
                                                class="menu-item menu-item-type-post_type menu-item-object-page CRcustomMenu CRcustomMenu2 menu-item-50629">
                                                <a href="ho-tro/<?=$v[$sluglang]?>/" itemprop="url"><span
                                                        itemprop="name">
                                                        <?=$v['name'.$lang]?>
                                                    </span></a>
                                            </li>
                                            <?php }} ?>
                                        </ul>
                                    </div>
                                </nav>
                            </div>
                            <?php if(!empty($bct)){ ?>
                            <a href="<?=$bct['link']?>">
                                <?=$func->getImage(['class' => 'lazy','sizes' => '400x152x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $bct['photo'], 'alt' => $setting['name'.$lang]])?>
                            </a>
                            <?php } ?>
                        </div>
                        <div class="item col-md-7">
                            <p class="footer-title">
                                Chi nhánh </p>
                            <div class="content_cn">
                                <?php foreach($cn as $k => $v){ ?>
                                <div class="item_cn">
                                    <a href="<?=$v['ggmap']?>" target="_blank">
                                        <h3 class="name_cn">
                                            <i class="fa fa-home" aria-hidden="true"></i>
                                            <?=$v['name'.$lang]?>
                                        </h3>
                                        <span class="address">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?=$v['address']?>
                                        </span>
                                    </a>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="container">
            <div class="box text-center">
                <div class="copyright">© <?=date('Y')?>
                    <?=$setting['name'.$lang]?> <br />
                </div>
            </div>
            <div class="CRcont_nav_mobile">
                <div class="CRlist_social">
                    <ul>
                        <li><a href="<?=$optsetting['fanpage']?>"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24">
                                    <path
                                        d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z" />
                                </svg></a>
                        </li>
                        <li><a href="<?=$optsetting['youtube']?>"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24">
                                    <path
                                        d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                                </svg></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</footer>

<div id="button-contact-vr" class="">
    <div id="gom-all-in-one">
        <div id="fanpage-vr" class="button-contact">
            <div class="phone-vr">
                <div class="phone-vr-circle-fill"></div>
                <div class="phone-vr-img-circle">
                    <a target="_blank" href="<?=$optsetting['fanpage']?>">
                        <img alt="Fanpage" src="assets/images/Facebook.png"
                            class="lazyloaded" data-ll-status="loaded">
                    </a>
                </div>
            </div>
        </div>
        <!-- end fanpage -->

        <!-- zalo -->
        <div id="zalo-vr" class="button-contact">
            <div class="phone-vr">
                <div class="phone-vr-circle-fill"></div>
                <div class="phone-vr-img-circle">

                    <a target="_blank" href="https://zalo.me/<?=$func->parsePhone($optsetting['zalo'])?>">
                        <img alt="Zalo" src="assets/images/zalo.png"
                            class="lazyloaded" data-ll-status="loaded">
                    </a>
                </div>
            </div>
        </div>
        <!-- end zalo -->


        <!-- Phone -->
        <div id="phone-vr" class="button-contact">
            <div class="phone-vr">
                <div class="phone-vr-circle-fill"></div>
                <div class="phone-vr-img-circle">
                    <a href="tel:<?=$func->parsePhone($optsetting['hotline'])?>">
                        <img alt="Phone" src="assets/images/phone.png"
                            class="lazyloaded" data-ll-status="loaded">
                    </a>
                </div>
            </div>
        </div>
        <!-- end phone -->

    </div>
</div>
