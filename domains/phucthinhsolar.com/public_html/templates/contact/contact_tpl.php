<!-- <div class="wrap-info-contact">
    <div class="row">
        <div class="col-md-4">
            <div class="item-info-contact">
                <div class="icon-contact">
                    <i class="fa fa-phone"></i>
                </div>
                <div class="info-contact">
                    <span>Điện thoại</span>
                    <p><?=$optsetting['hotline']?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="item-info-contact">
                <div class="icon-contact">
                    <i class="fa fa-map-marker-alt" aria-hidden="true"></i>
                </div>
                <div class="info-contact">
                    <span>Địa chỉ</span>
                    <p><?=$optsetting['address']?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="item-info-contact">
                <div class="icon-contact">
                    <i class="fa fa-envelope" aria-hidden="true"></i>
                </div>
                <div class="info-contact">
                    <span>Email</span>
                    <p><?=$optsetting['email']?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?=$flash->getMessages("frontend")?>

<div class="content-main">
    <div class="box-contact">
        <div class="contact-article row justify-content-center">
            <div class="contact-text col-lg-5">
                <div class="slogan-about">Liên hệ</div>
                <?=htmlspecialchars_decode($lienhe['content'.$lang])?>
            </div>
            <div class="col-lg-7">
                <form class="contact-form validation-contact" method="post" action=""
                    enctype="multipart/form-data">
                    <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                    <div class="form-row">
                        <div class="contact-input col-sm-6">
                            <input type="text" class="form-control text-sm" id="fullname-contact"
                                name="dataContact[fullname]" placeholder="<?=hoten?>"
                                value="<?=$flash->get('fullname')?>" required />
                            <div class="invalid-feedback"><?=vuilongnhaphoten?></div>
                        </div>
                        <div class="contact-input col-sm-6">
                            <input type="number" class="form-control text-sm" id="phone-contact"
                                name="dataContact[phone]" placeholder="<?=sodienthoai?>"
                                value="<?=$flash->get('phone')?>" required />
                            <div class="invalid-feedback"><?=vuilongnhapsodienthoai?></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="contact-input col-sm-6">
                            <input type="text" class="form-control text-sm" id="address-contact"
                                name="dataContact[address]" placeholder="<?=diachi?>"
                                value="<?=$flash->get('address')?>" required />
                            <div class="invalid-feedback"><?=vuilongnhapdiachi?></div>
                        </div>
                        <div class="contact-input col-sm-6">
                            <input type="email" class="form-control text-sm" id="email-contact"
                                name="dataContact[email]" placeholder="Email" value="<?=$flash->get('email')?>"
                                required />
                            <div class="invalid-feedback"><?=vuilongnhapdiachiemail?></div>
                        </div>
                    </div>
                    <div class="contact-input">
                        <input type="text" class="form-control text-sm" id="subject-contact" name="dataContact[subject]"
                            placeholder="<?=chude?>" value="<?=$flash->get('subject')?>" required />
                        <div class="invalid-feedback"><?=vuilongnhapchude?></div>
                    </div>
                    <div class="contact-input">
                        <textarea class="form-control text-sm" id="content-contact" name="dataContact[content]"
                            placeholder="<?=noidung?>" required /><?=$flash->get('content')?></textarea>
                        <div class="invalid-feedback"><?=vuilongnhapnoidung?></div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2 btn-contact" name="submit-contact" value="Gửi đi">GỬI ĐI</button>
                    <input type="hidden" name="recaptcha_response_contact" id="recaptchaResponseContact">
                </form>
            </div>
        </div>
        <div class="contact-map"><?=htmlspecialchars_decode($optsetting['coords_iframe'])?></div>
    </div>
    
</div> -->
<div id="inf_contact_page">
    <section id="top_content">
        <div class="container">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12">
                    <h2 class="text-center">Công ty TNHH Công Nghệ - Thương Mại và Dịch Vụ <span class="pts-brand-text">Phúc Thịnh Solar</span></h2>
                    <center>
                        <ul class="custContContact mb-4">
                            <li><a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline']);?>">Hotline: <b><?=$optsetting['hotline']?></b></a></li>
                            <li><a href="mailto:<?=$optsetting['email']?>">Email: <b><?=$optsetting['email']?></b></a></li>
                            <li><a href="javascript:void(0)">Thời gian làm việc: <b><?=$optsetting['thoigianlamviec']?></b></a></li>
                        </ul>
                    </center>
                </div>
                <div class="col-xl-12 col-lg-12 col-md-12" id="yeu-cau-tu-van">
                    <div class="content formContact">
                        <div role="form" class="wpcf7" id="wpcf7-f623-o1" lang="en-US" dir="ltr">
                            <div class="screen-reader-response">
                                <p role="status" aria-live="polite" aria-atomic="true"></p>
                                <ul></ul>
                            </div>
                            <form action="" method="post" class="wpcf7-form init" data-status="init">
                                <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                                <div class="inf_default_form">
                                    <h4 class="title">Gửi tin nhắn cho chúng tôi</h4>
                                    <p class="des">Chúng tôi sẵn sàng phục vụ bạn!</p>
                                    <ul>
                                        <li class="inf_default_row inf_row_half">
                                            <div class="inf_input"><label>Họ và tên</label><span
                                                    class="wpcf7-form-control-wrap name-contact"><input type="text"
                                                        name="dataContact[fullname]" value="" size="40"
                                                        class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required"
                                                        placeholder="Nguyễn Văn A" required></span></div>
                                        </li>
                                        <li class="inf_default_row inf_row_half">
                                            <div class="inf_input"><label>Số điện thoại</label><span
                                                    class="wpcf7-form-control-wrap tel-contact"><input type="tel"
                                                        name="dataContact[phone]" value="" size="40"
                                                        class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel"
                                                        placeholder="090xxxxxxx" required></span></div>
                                        </li>
                                        <li class="inf_default_row inf_row_half">
                                            <div class="inf_input"><label>Email</label><span
                                                    class="wpcf7-form-control-wrap mail-contact"><input type="email"
                                                        name="dataContact[email]" value="" size="40"
                                                        class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email"
                                                        placeholder="abc@gmail.com" required></span></div>
                                        </li>
                                        <li class="inf_default_row inf_row_half">
                                            <div class="inf_input"><label>Tiêu đề</label><span
                                                    class="wpcf7-form-control-wrap title-form-contact"><input type="text"
                                                        name="dataContact[subject]" value="" size="40"
                                                        class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required"
                                                        placeholder="Nhập tiêu đề" required></span></div>
                                        </li>
                                        <li class="inf_default_row inf_row_full">
                                            <div class="inf_input"><label>Nội dung liên hệ</label><span
                                                    class="wpcf7-form-control-wrap content-contact"><textarea
                                                        name="dataContact[content]" cols="40" rows="10"
                                                        class="wpcf7-form-control wpcf7-textarea wpcf7-validates-as-required"
                                                        placeholder="Nhập tin nhắn..."></textarea></span></div>
                                        </li>
                                        <li class="inf_default_row inf_row_full">
                                            <div class="inf_input inf_button"><input type="submit" name="submit-contact" value="Gửi ngay"
                                                    class="wpcf7-form-control has-spinner wpcf7-submit"><span
                                                    class="wpcf7-spinner"></span></div>
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" name="recaptcha_response_contact" id="recaptchaResponseContact">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <h2 class="text-center mt-4">Hệ thống cửa hàng</h2>
        </div>
        <div class="hts-wrapper">

            <div class="hts-fixed-wrap">
                <ul class="officeContact">
                    <?php if(count($hethongcuahang)){foreach($hethongcuahang as $k => $v){ ?>
                    <li>
                        <div class="wrap">
                            <p><i class="fa fa-home" aria-hidden="true"></i><b><?=$v['name'.$lang]?></b></p>
                            <p><i class="fa fa-map-marker" aria-hidden="true"></i> <?=$v['address']?></p>
                        </div>
                        <a class="view_maps" data-map="<?=$v['map_iframe']?>">Xem bản đồ<i class="fa fa-angle-right" aria-hidden="true"></i></a>
                    </li>
                    <?php }} ?>
                </ul>
            </div>

            <div id="maps">
                <?=htmlspecialchars_decode($hethongcuahang[0]['map_iframe'])?>
            </div>

        </div>

    </section>
</div>
