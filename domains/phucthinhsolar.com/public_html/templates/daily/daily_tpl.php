<?php if(!empty($about1)){ ?>
<section class="agency agency_1 ">
    <div class="container">
        <div class="content formContact form_agency">
            <div role="form" class="wpcf7" id="wpcf7-f56090-o1" lang="en-US" dir="ltr">
                <div class="screen-reader-response">
                    <p role="status" aria-live="polite" aria-atomic="true"></p>
                    <ul></ul>
                </div>
                <form action="" method="post" class="wpcf7-form init" data-status="init">
                    <div class="inf_default_form">
                        <h4 class="title">Đăng ký làm đối tác phân phối Đèn năng lượng mặt trời!</h4>
                        <ul>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input">
                                    <span class="wpcf7-form-control-wrap name-contact">
                                        <input type="text" name="dataNewsletter[fullname]" size="40"
                                            class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" required
                                            placeholder="Họ và tên*"></span>
                                </div>
                            </li>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input"><span class="wpcf7-form-control-wrap tel-contact"><input
                                            type="tel" name="dataNewsletter[phone]" size="40"
                                            class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel"
                                            required placeholder="Số điện thoại"></span></div>
                            </li>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input"><span class="wpcf7-form-control-wrap mail-contact"><input
                                            type="email" name="dataNewsletter[email]" size="40"
                                            class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email"
                                            required placeholder="Email"></span></div>
                            </li>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input"><span class="wpcf7-form-control-wrap cities"><select
                                            name="dataNewsletter[address]"
                                            class="wpcf7-form-control wpcf7-select devvn_cities" id="devvn_cities"
                                            aria-invalid="false">
                                            <option value="Tỉnh thành">Tỉnh thành</option>
                                            <?php if(count($city)){foreach($city as $k => $v){ ?>
                                            <option value="<?=$v['name']?>"><?=$v['name']?></option>
                                            <?php }} ?>
                                        </select>
                                    </span>
                                </div>
                            </li>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input"><span class="wpcf7-form-control-wrap name-company"><input
                                            type="text" name="dataNewsletter[company]" size="40"
                                            class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" required
                                            placeholder="Tên doanh nghiệp/Cửa hàng"></span></div>
                            </li>
                            <li class="inf_default_row inf_row_full">
                                <div class="inf_input inf_button"><input type="submit" name="submit-newsletter2"
                                        value="Đăng ký ngay" class="wpcf7-form-control has-spinner wpcf7-submit"><span
                                        class="wpcf7-spinner"></span></div>
                            </li>
                        </ul>
                    </div>
                    <input type="hidden" class="btn btn-sm btn-danger w-100" name="recaptcha_response_newsletter2"
                        id="recaptchaResponseNewsletter2">
                </form>
            </div>
        </div>
        <div class="inner_content rocket-lazyload lazyloaded"
            style="background-image: url(<?=UPLOAD_NEWS_L.$about1['photo']?>);" data-ll-status="loaded">
            <div class="inner">
                <div class="content text">
                    <h3><?=$about1['name'.$lang]?></h3>
                    <?=htmlspecialchars_decode($about1['desc'.$lang])?>
                </div>
            </div>

        </div>
    </div>
</section>
<?php } ?>

<?php if(!empty($about2)){ ?>
<section class="agency agency_2">
    <div class="container">
        <div class="section-title uk-text-center">
            <h3 class="CRstyle_title"><?=htmlspecialchars_decode($about2['name'.$lang])?></h3>
        </div>
        <div class="content">
            <?=htmlspecialchars_decode($about2['desc'.$lang])?>
        </div>
        <div class="list_item">
            <?php if(count($photo_daily)){foreach($photo_daily as $v){ ?>
            <div class="item wrap">
                <a href="<?=$v['link']?>" target="_blank" title="<?=$v['name'.$lang]?>"></a>
                <div class="item_img">
                    <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </div>
                <div class="item_cont"><?=$v['name'.$lang]?></div>
            </div>
            <?php }} ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if(!empty($about3)){ ?>
<section class="agency agency_3">
    <div class="container">
        <div class="section-title uk-text-center">
            <h3 class="CRstyle_title"><?=htmlspecialchars_decode($about3['name'.$lang])?></h3>
        </div>
        <div class="content">
            <?=htmlspecialchars_decode($about3['desc'.$lang])?>
        </div>
        <div class="list_item">
            <?php if(count($camketdaily)){foreach($camketdaily as $v){ ?>
            <div class="item ">
                <div class="item_icon">
                    <?=$func->getImage(['class' => 'lazy','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </div>
                <div class="item_cont">
                    <h4><?=$v['name'.$lang]?></h4>
                    <div class="cont">
                        <?=htmlspecialchars_decode($v['desc'.$lang])?>
                    </div>
                </div>
            </div>
            <?php }} ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if($phanhoidaily){ ?>
<section class="agency agency_4">
    <div class="container">
        <div class="section-title uk-text-center">
            <h3 class="CRstyle_title">SỰ HÀI LÒNG CỦA KHÁCH HÀNG, ĐỐI TÁC LÀ <br>
                THÀNH CÔNG CỦA Phú Thịnh SOLAR</h3>
        </div>
        <div class="list_item">
            <?php foreach($phanhoidaily as $k => $v){ ?>
            <div class="item">
                <div class="item_img">
                    <?=$func->getImage(['class' => 'lazy','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                </div>
                <div class="item_cont">
                    <h3 class="name"><?=$v['name'.$lang]?></h3>
                    <h5 class="sub"><?=htmlspecialchars_decode($v['desc'.$lang])?></h5>
                    <div class="cont"><?=htmlspecialchars_decode($v['content'.$lang])?></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if(count($solieudaily)){ ?>
<section class="agency agency_5 rocket-lazyload lazyloaded Module-327"
    style="background-image: url(assets/images/banner_home-20210519094516.jpg);"
    data-ll-status="loaded">
    <div class="container">
        <div class="section-title uk-text-center">
            <h3 class="CRstyle_title">TỰ HÀO Phúc Thịnh SOLAR!</h3>
        </div>
        <div class="list_item">
            <?php foreach($solieudaily as $v){ ?>
            <div class="item">
                <div class="item_cont">
                    <h3 class="name"><span class="counter so" data-count="<?=$v['solieu']?>">0</span> +</h3>
                    <div class="cont"><?=$v['name'.$lang]?></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>