<?php
$videoEmbedUrl = '';
if(!empty($rowDetail['video_url']))
{
    $videoUrl = trim(htmlspecialchars_decode($rowDetail['video_url'], ENT_QUOTES));
    $videoParts = parse_url($videoUrl);
    $videoHost = (!empty($videoParts['host'])) ? strtolower($videoParts['host']) : '';
    $videoId = '';

    if(in_array($videoHost, array('youtu.be', 'www.youtu.be'), true))
    {
        $videoId = trim((!empty($videoParts['path'])) ? $videoParts['path'] : '', '/');
    }
    else if(in_array($videoHost, array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'), true))
    {
        if(!empty($videoParts['query']))
        {
            parse_str($videoParts['query'], $videoQuery);
            $videoId = (!empty($videoQuery['v'])) ? $videoQuery['v'] : '';
        }

        if(empty($videoId) && !empty($videoParts['path']) && preg_match('~/(?:embed|shorts)/([A-Za-z0-9_-]{11})~', $videoParts['path'], $videoMatches))
            $videoId = $videoMatches[1];
    }

    if(preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId))
        $videoEmbedUrl = 'https://www.youtube-nocookie.com/embed/'.$videoId;
}

require_once LIBRARIES.'product_specs.php';
$structuredSpecResult = productStructuredSpecs($rowDetail, $lang);
$structuredSpecs = $structuredSpecResult['specs'];
$useLegacySpecs = $structuredSpecResult['use_legacy'];
$certificateContent = (!empty($rowDetail['certificate'.$lang])) ? htmlspecialchars_decode($rowDetail['certificate'.$lang]) : '';
?>
<div class="box-detail">
    <div class="">
        <div class="">
            <div class="grid-pro-detail w-clear">
                <div class="row">
                    <div class="left-pro-detail col-md-5 col-lg-5">
                        <a id="Zoom-1" class="MagicZoom"
                            data-options="zoomMode: on; hint: off; rightClick: true; selectorTrigger: hover; expandCaption: false; history: false;"
                            href="<?=UPLOAD_PRODUCT_L.$rowDetail['photo']?>" title="<?=$rowDetail['name'.$lang]?>">
                            <?=$func->getImage(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $rowDetail['photo'], 'alt' => $rowDetail['name'.$lang]])?>
                        </a>
                        <?php if($rowDetailPhoto) { if(count($rowDetailPhoto) > 0) { ?>
                        <div class="gallery-thumb-pro">
                            <div class="owl-page owl-carousel owl-theme owl-pro-detail" data-xsm-items="5:10"
                                data-sm-items="5:10" data-md-items="5:10" data-lg-items="5:10" data-xlg-items="5:10"
                                data-nav="1"
                                data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-left' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='15 6 9 12 15 18' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-right' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='9 6 15 12 9 18' /></svg>"
                                data-navcontainer=".control-pro-detail">
                                <div>
                                    <a class="thumb-pro-detail" data-zoom-id="Zoom-1"
                                        href="<?=UPLOAD_PRODUCT_L.$rowDetail['photo']?>"
                                        title="<?=$rowDetail['name'.$lang]?>">
                                        <?=$func->getImage(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $rowDetail['photo'], 'alt' => $rowDetail['name'.$lang]])?>
                                    </a>
                                </div>
                                <?php foreach($rowDetailPhoto as $v) { ?>
                                <div>
                                    <a class="thumb-pro-detail" data-zoom-id="Zoom-1"
                                        href="<?=UPLOAD_PRODUCT_L.$v['photo']?>" title="<?=$rowDetail['name'.$lang]?>">
                                        <?=$func->getImage(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $rowDetail['name'.$lang]])?>
                                    </a>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } } ?>
                    </div>

                    <div class="right-pro-detail col-md-7 col-lg-7">
                        <p class="title-pro-detail"><?=$rowDetail['name'.$lang]?></p>
                        <div class="desc-pro-detail">
                            <?=(!empty($rowDetail['desc'.$lang])) ? htmlspecialchars_decode($rowDetail['desc'.$lang]) : ''?>
                        </div>
                        <ul class="attr-pro-detail">
                            <li class="w-clear">
                                <label class="attr-label-pro-detail"><?=gia?>:</label>
                                <div class="attr-content-pro-detail">
                                    <?php if($rowDetail['sale_price']) { $discountPercent = ($rowDetail['regular_price'] > $rowDetail['sale_price']) ? round((($rowDetail['regular_price'] - $rowDetail['sale_price']) / $rowDetail['regular_price']) * 100) : 0; ?>
                                    <span
                                        class="price-new-pro-detail"><?=$func->formatMoney($rowDetail['sale_price'])?></span>
                                    <span
                                        class="price-old-pro-detail"><?=$func->formatMoney($rowDetail['regular_price'])?></span>
                                    <?php if($discountPercent > 0) { ?>
                                    <span class="badge-discount badge-discount-inline">-<?=$discountPercent?>%</span>
                                    <?php } ?>
                                    <?php } else { ?>
                                    <span
                                        class="price-new-pro-detail"><?=($rowDetail['regular_price']) ? $func->formatMoney($rowDetail['regular_price']) : lienhe?></span>
                                    <?php } ?>
                                </div>
                            </li>
                            <?php if(!empty($rowDetail['code'])) { ?>
                            <li class="w-clear">
                                <label class="attr-label-pro-detail"><?=masp?>:</label>
                                <div class="attr-content-pro-detail"><?=$rowDetail['code']?></div>
                            </li>
                            <?php } ?>
                            <?php if(!empty($rowSize)) { ?>
                            <li class="size-block-pro-detail w-clear">
                                <label class="attr-label-pro-detail d-block"><?=kichthuoc?>:</label>
                                <div class="attr-content-pro-detail d-block">
                                    <?php foreach($rowSize as $k => $v) { ?>
                                    <label for="size-pro-detail-<?=$v['id']?>"
                                        class="size-pro-detail text-decoration-none">
                                        <input type="radio" value="<?=$v['id']?>" id="size-pro-detail-<?=$v['id']?>"
                                            name="size-pro-detail">
                                        <?=$v['name'.$lang]?>
                                    </label>
                                    <?php } ?>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php
                            /* Sản phẩm chưa niêm yết giá (hiển thị "Liên hệ"): không cho đặt
                               số lượng và không hiện nút mua - thay bằng CTA gọi báo giá,
                               tránh khách bấm mua rồi giỏ hàng hiện giá 0đ. */
                            $isContactPrice = empty($rowDetail['sale_price']) && empty($rowDetail['regular_price']);
                        ?>
                        <?php if(!$isContactPrice) { ?>
                        <div class="quantity-pro-detail">
                            <label class="attr-label-pro-detail d-block">Số lượng:</label>
                            <span>-</span>
                            <input type="number" class="qty-pro" value="1" min="1">
                            <span>+</span>
                        </div>
                        <?php } ?>
                        <div class=" product-info">
                            <?php if(!empty($productList)){ ?>
                            <div class="pro-type">
                                <span>Danh mục: </span>
                                <a class="title" rel="nofollow" href="javascript:"><?=$productList['name'.$lang]?>
                                    <?=(!empty($productCat))?', '.$productCat['name'.$lang]:''?></a>
                            </div>
                            <?php } ?>
                            <?php if(!empty($productBrand)){ ?>
                            <div class="pro-type">
                                <span>Thương hiệu: </span>
                                <a class="title" rel="nofollow" href="javascript:"><?=$productBrand['name'.$lang]?></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php if($isContactPrice) { ?>
                        <div class="btn-product-detail btn-contact-price">
                            <a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline'])?>" title="Gọi tư vấn báo giá"><i class="fas fa-phone-alt"></i> Gọi báo giá: <?=$optsetting['hotline']?></a>
                            <a href="lien-he/" title="Gửi yêu cầu báo giá">Gửi yêu cầu báo giá</a>
                        </div>
                        <?php } else { ?>
                        <div class="btn-product-detail">
                            <a href="javascript:void(0)" class="addcart" data-id="<?=$rowDetail['id']?>" data-action="buynow" title="Mua ngay">Mua ngay</a>
                            <a href="javascript:void(0)" class="addcart" data-id="<?=$rowDetail['id']?>" data-action="addnow" title="Thêm vào giỏ hàng">Thêm giỏ hàng</a>
                        </div>
                        <?php } ?>
                        <div class="btn-product-detail">
                            <a href="dai-ly/" target="_blank" title="Liên hệ" style="width:100%;">Đăng ký đại lý</a>
                        </div>
                        <?php if(count($tc)){ ?>
                        <div id="CRlist_endow_sidebar" class="CRdef_sidebar_pd">
                            <ul>
                                <?php foreach($tc as $v){ ?>
                                <li>
                                    <?= $func->getImage(['class' => '', 'sizes' => '20x20x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
                                    <p><?=$v['name'.$lang]?></p>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <?php } ?>
                        <?php if(count($ck)){ ?>
                        <div class="CRnew_content_def_pd">
                            <div class="CRnew_list_icon_def_pd CRcustom_dots CRcustom_dots_dif">
                                <?php foreach($ck as $k => $v){ ?>
                                <div class="item_icon_box">
                                    <div class="content_icon_box">
                                        <div class="img_icon_box_sec">
                                            <?= $func->getImage(['class' => '', 'size-error' => '20x20x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
                                        </div>
                                        <div class="cont_icon_box_sec">
                                            <p><?=$v['name'.$lang]?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="box-detail mg-top-30">
    <div class="tabs-pro-detail">
        <ul class="nav nav-tabs" id="tabsProDetail" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="info-pro-detail-tab" data-toggle="tab" href="#info-pro-detail"
                    role="tab">Mô tả sản phẩm</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tskt-pro-detail-tab" data-toggle="tab" href="#tskt-pro-detail" role="tab">Thông
                    số kỹ thuật</a>
            </li>
            <?php if(!empty($videoEmbedUrl)) { ?>
            <li class="nav-item">
                <a class="nav-link" id="video-pro-detail-tab" data-toggle="tab" href="#video-pro-detail"
                    role="tab">Video thực tế</a>
            </li>
            <?php } ?>
            <?php if(!empty($certificateContent)) { ?>
            <li class="nav-item">
                <a class="nav-link" id="certificate-pro-detail-tab" data-toggle="tab" href="#certificate-pro-detail"
                    role="tab">Chứng nhận</a>
            </li>
            <?php } ?>
            <li class="nav-item">
                <a class="nav-link" id="cs-pro-detail-tab" data-toggle="tab" href="#cs-pro-detail" role="tab">Chính sách
                    mua hàng</a>
            </li>
        </ul>
        <div class="tab-content pt-4 pb-4" id="tabsProDetailContent">
            <div class="tab-pane fade show active" id="info-pro-detail" role="tabpanel">
                <?=htmlspecialchars_decode($rowDetail['content'.$lang])?>
            </div>
            <div class="tab-pane fade" id="tskt-pro-detail" role="tabpanel">
                <?php if(!empty($structuredSpecs)) { ?>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php foreach($structuredSpecs as $spec) { ?>
                                    <tr>
                                        <th style="width:35%;"><?=htmlspecialchars($spec['label'], ENT_QUOTES, 'UTF-8')?></th>
                                        <td><?=htmlspecialchars(htmlspecialchars_decode($spec['value'], ENT_QUOTES), ENT_QUOTES, 'UTF-8')?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
                <?php if($useLegacySpecs) { ?>
                    <?=htmlspecialchars_decode($rowDetail['thongsokythuat'.$lang])?>
                <?php } ?>
            </div>
            <?php if(!empty($videoEmbedUrl)) { ?>
            <div class="tab-pane fade" id="video-pro-detail" role="tabpanel">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="<?=htmlspecialchars($videoEmbedUrl, ENT_QUOTES, 'UTF-8')?>"
                        title="Video thực tế <?=$rowDetail['name'.$lang]?>" loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
            </div>
            <?php } ?>
            <?php if(!empty($certificateContent)) { ?>
            <div class="tab-pane fade" id="certificate-pro-detail" role="tabpanel">
                <?=$certificateContent?>
            </div>
            <?php } ?>
            <div class="tab-pane fade" id="cs-pro-detail" role="tabpanel">
                <?=htmlspecialchars_decode($rowDetail['chinhsachmuahang'.$lang])?>
            </div>
        </div>
    </div>
</div>

<div class="box-detail mg-top-30">
    <?php include TEMPLATE."product/comment.php"; ?>
</div>
<?php if(!empty($product)) {?>
<div class="box-detail mg-top-30">
    <div class="title-product-orther"><span>Sản phẩm liên quan</span></div>
    <?php if(!empty($product)) {?>
    <div class="position-relative">
        <div class="owl-page owl-carousel owl-theme" data-xsm-items="2:15" data-sm-items="2:15" data-md-items="3:15"
            data-lg-items="3:24" data-autoplay="1" data-xlg-items="4:24" data-nav="1" data-mousedrag="1"
            data-touchdrag="1"
            data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-left' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='15 6 9 12 15 18' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-right' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='9 6 15 12 9 18' /></svg>"
            data-navcontainer=".control-pro-detail2">
            <?php $pcNoLazy = true; /* card nằm trong Owl carousel - xem chú thích ở partial */ ?>
            <?php foreach($product as $k => $v) { include ROOT."templates/product/_product_card.php"; } ?>
        </div>
        <div class="control-pro-detail2 control-owl transition"></div>
    </div>
    <?php } ?>
</div>
<?php } ?>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
                <p class="title-pro-detail">Tư vấn & báo giá</p>
                <div class=" w-100">
                    <div class="">
                        <div class="form-register">
                            <form class="validation-newsletter" novalidate method="post" action=""
                                enctype="multipart/form-data">
                                <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
                                <div class="newsletter-input">
                                    <label for="fullname-newsletter">Tên của bạn*</label>
                                    <input type="text" class="form-control" id="fullname-newsletter"
                                        name="dataNewsletter[fullname]" placeholder="VD: Nguyễn Văn A" required />
                                </div>
                                <div class="newsletter-input">
                                    <label for="phone-newsletter">Số điện thoại*</label>
                                    <input type="text" class="form-control" id="phone-newsletter"
                                        name="dataNewsletter[phone]" placeholder="0987 XXX XXX *" required />
                                </div>
                                <div class="newsletter-input">
                                    <label for="email-newsletter">Email*</label>
                                    <input type="email" class="form-control" id="email-newsletter"
                                        name="dataNewsletter[email]" placeholder="VD: nguyenvana@gmail.com" required />
                                </div>

                                <div class="newsletter-input">
                                    <label for="email-newsletter">Bạn là...*</label>
                                    <div class="radio-user custom-control custom-radio">
                                        <input type="radio" id="canhan" name="dataNewsletter[loaihinh]" class="custom-control-input"
                                            value="Người dùng cá nhân" required>
                                        <label class="custom-control-label" for="canhan">Người dùng cá nhân</label>
                                    </div>
                                    <div class="radio-user custom-control custom-radio">
                                        <input type="radio" id="doanhnghiep" name="dataNewsletter[loaihinh]"
                                            class="custom-control-input" value="Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…"
                                            required>
                                        <label class="custom-control-label" for="doanhnghiep">Đại lý/Nhà phân phối, bán
                                            lẻ/Nhà thầu…</label>
                                    </div>
                                </div>

                                <div class="newsletter-input">
                                    <label for="congsuat-newsletter">Công suất, số lượng, ứng dụng hoặc mã sản phẩm...
                                        *</label>
                                    <input type="text" class="form-control" id="congsuat-newsletter"
                                        name="dataNewsletter[congsuat]" placeholder="20 tấm 550, 1 cont tấm 440..."
                                        required />
                                </div>

                                <div class="newsletter-input">
                                    <label for="address">Tỉnh/Thành phố *</label>
                                    <select class="form-control" name="dataNewsletter[address]" id="address" required>
                                        <?php if(count($city)){foreach($city as $k => $v){ ?>
                                        <option value="<?=$v['name']?>"><?=$v['name']?></option>
                                        <?php }} ?>
                                    </select>
                                </div>

                                <div class="newsletter-button">
                                    <input type="submit" class="btn btn-sm btn-danger" name="submit-newsletter"
                                        value="Gửi yêu cầu" disabled>
                                    <input type="hidden" class="btn btn-sm btn-danger w-100"
                                        name="recaptcha_response_newsletter" id="recaptchaResponseNewsletter">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
