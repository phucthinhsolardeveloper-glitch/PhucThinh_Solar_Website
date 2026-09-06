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
require_once LIBRARIES.'product_content_blocks.php';
$structuredSpecResult = productStructuredSpecs($rowDetail, $lang);
$structuredSpecs = $structuredSpecResult['specs'];
$useLegacySpecs = $structuredSpecResult['use_legacy'];
$certificateContent = (!empty($rowDetail['certificate'.$lang])) ? htmlspecialchars_decode($rowDetail['certificate'.$lang]) : '';
/* Mô tả sản phẩm = MỘT loại: chip thông số (tự động) + khối content_section
   (kế thừa sản phẩm > hãng > danh mục) + đoạn văn tự sinh khi chưa có khối. */
$productSpecHighlights = productAutoHighlights($rowDetail, $lang, isset($type) ? $type : '');
$productLegacySpecs = trim(isset($rowDetail['thongsokythuat'.$lang]) ? htmlspecialchars_decode($rowDetail['thongsokythuat'.$lang]) : '');
$productPolicyOverride = trim(isset($rowDetail['chinhsachmuahang'.$lang]) ? htmlspecialchars_decode($rowDetail['chinhsachmuahang'.$lang]) : '');
$productSections = isset($productSections) ? $productSections : array('blocks' => array(), 'source' => '');
$productSectionsHtml = (!empty($productSections['blocks'])) ? productRenderSections($productSections['blocks'], UPLOAD_PRODUCT_L, $func) : '';
$productSpecParagraph = ($productSectionsHtml === '' && $productSections['source'] !== 'hide') ? productAutoDescription($rowDetail, $lang, isset($type) ? $type : '') : '';
$hasProductDescription = $productSpecHighlights || $productSectionsHtml !== '' || $productSpecParagraph !== '';
$hasProductSpecs = !empty($structuredSpecs) || ($useLegacySpecs && $productLegacySpecs !== '');
$hasProductPolicy = $productPolicyOverride !== '' || !empty($policy);
?>
<div class="box-detail">
    <div class="">
        <div class="">
            <div class="grid-pro-detail w-clear">
                <div class="row">
                    <div class="left-pro-detail col-md-5 col-lg-5">
                        <?php $detailZoomHref = !empty($rowDetail['photo']) ? UPLOAD_PRODUCT_L.$rowDetail['photo'] : 'assets/images/noimage.png'; ?>
                        <a id="Zoom-1" class="MagicZoom"
                            data-options="zoomMode: on; hint: off; rightClick: true; selectorTrigger: hover; expandCaption: false; history: false;"
                            href="<?=$detailZoomHref?>" title="<?=$rowDetail['name'.$lang]?>">
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
                                        href="<?=$detailZoomHref?>"
                                        title="<?=$rowDetail['name'.$lang]?>">
                                        <?=$func->getImage(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $rowDetail['photo'], 'alt' => $rowDetail['name'.$lang]])?>
                                    </a>
                                </div>
                                <?php foreach($rowDetailPhoto as $v) { $thumbAlt = !empty($v['caption']) ? $v['caption'] : $rowDetail['name'.$lang]; ?>
                                <div>
                                    <a class="thumb-pro-detail" data-zoom-id="Zoom-1"
                                        href="<?=!empty($v['photo']) ? UPLOAD_PRODUCT_L.$v['photo'] : 'assets/images/noimage.png'?>" title="<?=htmlspecialchars($thumbAlt, ENT_QUOTES, 'UTF-8')?>">
                                        <?=$func->getImage(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $thumbAlt])?>
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
                                    <?php if(publicProductRequiresQuote($rowDetail)) { ?>
                                    <span class="price-new-pro-detail"><?=lienhe?></span>
                                    <?php } elseif($rowDetail['sale_price']) { $discountPercent = ($rowDetail['regular_price'] > $rowDetail['sale_price']) ? round((($rowDetail['regular_price'] - $rowDetail['sale_price']) / $rowDetail['regular_price']) * 100) : 0; ?>
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
                            /* Chuyển sang CTA báo giá khi giá công khai bị ẩn. */
                            $isContactPrice = publicProductRequiresQuote($rowDetail);
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
                            <a href="#exampleModal" data-toggle="modal" data-target="#exampleModal" title="Gửi yêu cầu báo giá">Gửi yêu cầu báo giá</a>
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

<?php if($hasProductDescription || $hasProductSpecs || !empty($videoEmbedUrl) || !empty($certificateContent) || $hasProductPolicy) { $activeProductTab = $hasProductDescription ? 'info' : ($hasProductSpecs ? 'specs' : (!empty($videoEmbedUrl) ? 'video' : (!empty($certificateContent) ? 'certificate' : 'policy'))); ?>
<div class="box-detail mg-top-30">
    <div class="tabs-pro-detail">
        <ul class="nav nav-tabs" id="tabsProDetail" data-click-scroll="center" role="tablist">
            <?php if($hasProductDescription) { ?><li class="nav-item">
                <a class="nav-link <?=$activeProductTab==='info'?'active':''?>" id="info-pro-detail-tab" data-toggle="tab" href="#info-pro-detail"
                    role="tab">Mô tả sản phẩm</a>
            </li><?php } ?>
            <?php if($hasProductSpecs) { ?><li class="nav-item">
                <a class="nav-link <?=$activeProductTab==='specs'?'active':''?>" id="tskt-pro-detail-tab" data-toggle="tab" href="#tskt-pro-detail" role="tab">Thông
                    số kỹ thuật</a>
            </li><?php } ?>
            <?php if(!empty($videoEmbedUrl)) { ?>
            <li class="nav-item">
                <a class="nav-link <?=$activeProductTab==='video'?'active':''?>" id="video-pro-detail-tab" data-toggle="tab" href="#video-pro-detail"
                    role="tab">Video thực tế</a>
            </li>
            <?php } ?>
            <?php if(!empty($certificateContent)) { ?>
            <li class="nav-item">
                <a class="nav-link <?=$activeProductTab==='certificate'?'active':''?>" id="certificate-pro-detail-tab" data-toggle="tab" href="#certificate-pro-detail"
                    role="tab">Chứng nhận</a>
            </li>
            <?php } ?>
            <?php if($hasProductPolicy) { ?><li class="nav-item">
                <a class="nav-link <?=$activeProductTab==='policy'?'active':''?>" id="cs-pro-detail-tab" data-toggle="tab" href="#cs-pro-detail" role="tab">Chính sách
                    mua hàng</a>
            </li><?php } ?>
        </ul>
        <div class="tab-content pt-4 pb-4" id="tabsProDetailContent">
            <?php if($hasProductDescription) { ?><div class="tab-pane fade <?=$activeProductTab==='info'?'show active':''?> pts-product-content" id="info-pro-detail" role="tabpanel">
            <div class="pts-product-desc">
                <?php if($productSpecHighlights) { ?>
                <section>
                    <h2 class="pts-desc-title">Điểm nổi bật</h2>
                    <ul class="pts-spec-chips">
                        <?php foreach($productSpecHighlights as $chip) { ?><li><?=htmlspecialchars($chip,ENT_QUOTES,'UTF-8')?></li><?php } ?>
                    </ul>
                </section>
                <?php } ?>
                <?php if($productSpecParagraph !== '') { ?>
                <section class="pts-desc-auto">
                    <p class="pts-desc-para"><?=htmlspecialchars($productSpecParagraph, ENT_QUOTES, 'UTF-8')?></p>
                </section>
                <?php } ?>
                <?php if($productSectionsHtml !== '') { ?>
                <section class="pts-desc-sections"><?=$productSectionsHtml?></section>
                <?php } ?>
            </div>
            </div><?php } ?>
            <?php if($hasProductSpecs) { ?><div class="tab-pane fade <?=$activeProductTab==='specs'?'show active':''?>" id="tskt-pro-detail" role="tabpanel">
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
                    <?=$productLegacySpecs?>
                <?php } ?>
            </div><?php } ?>
            <?php if(!empty($videoEmbedUrl)) { ?>
            <div class="tab-pane fade <?=$activeProductTab==='video'?'show active':''?>" id="video-pro-detail" role="tabpanel">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="<?=htmlspecialchars($videoEmbedUrl, ENT_QUOTES, 'UTF-8')?>"
                        title="Video thực tế <?=$rowDetail['name'.$lang]?>" loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
            </div>
            <?php } ?>
            <?php if(!empty($certificateContent)) { ?>
            <div class="tab-pane fade <?=$activeProductTab==='certificate'?'show active':''?> pts-product-content" id="certificate-pro-detail" role="tabpanel">
                <?=$certificateContent?>
            </div>
            <?php } ?>
            <?php if($hasProductPolicy) { ?><div class="tab-pane fade <?=$activeProductTab==='policy'?'show active':''?> pts-product-content" id="cs-pro-detail" role="tabpanel">
                <?php if($productPolicyOverride !== '') { ?><div class="alert alert-info py-2"><strong>Chính sách riêng của sản phẩm</strong></div><?=$productPolicyOverride?><?php } else { ?><p>Áp dụng chính sách chung hiện hành của Phúc Thịnh Solar:</p><div class="pts-policy-links"><?php foreach($policy as $policyItem) { ?><a href="ho-tro/<?=$policyItem[$sluglang]?>/"><i class="fas fa-shield-alt mr-2"></i><?=htmlspecialchars($policyItem['name'.$lang],ENT_QUOTES,'UTF-8')?></a><?php } ?></div><?php } ?>
            </div><?php } ?>
        </div>
    </div>
</div>
<?php } ?>

<div class="box-detail mg-top-30">
    <?php include TEMPLATE."product/comment.php"; ?>
</div>
<?php if(!empty($productRelatedProjects)) { ?>
<div class="box-detail mg-top-30 pts-related-showcase pts-product-projects">
    <div class="title-product-orther"><span>Dự án thực tế sử dụng sản phẩm</span></div>
    <div class="pts-related-grid">
        <?php foreach($productRelatedProjects as $relatedProject) { ?>
            <a class="pts-related-card" href="<?=$relatedProject[$sluglang]?>" title="<?=$relatedProject['name'.$lang]?>">
                <span class="pts-project-media">
                    <?=$func->getImage(['class' => 'w-100 pts-project-media-main', 'sizes' => '397x340x1', 'upload' => UPLOAD_NEWS_L, 'image' => $relatedProject['photo'], 'alt' => $relatedProject['name'.$lang]])?>
                    <?php $relatedGallery = isset($productProjectGallery[(int)$relatedProject['id']]) ? array_slice($productProjectGallery[(int)$relatedProject['id']], 0, 3) : array(); ?>
                    <?php if($relatedGallery) { ?>
                        <span class="pts-project-media-thumbs" aria-label="Ảnh thực tế dự án">
                            <?php foreach($relatedGallery as $projectPhoto) { ?>
                                <?=$func->getImage(['class' => 'w-100', 'sizes' => '160x120x1', 'upload' => UPLOAD_NEWS_L, 'image' => $projectPhoto, 'alt' => $relatedProject['name'.$lang]])?>
                            <?php } ?>
                        </span>
                    <?php } ?>
                </span>
                <span class="pts-related-card-body">
                    <strong><?=$relatedProject['name'.$lang]?></strong>
                    <?php if(!empty($relatedProject['address'])) { ?><small><i class="fas fa-map-marker-alt"></i> <?=$relatedProject['address']?></small><?php } ?>
                </span>
            </a>
        <?php } ?>
    </div>
</div>
<?php } ?>
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

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="quoteRequestModalLabel" aria-hidden="true">
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
                <p class="title-pro-detail" id="quoteRequestModalLabel">Tư vấn & báo giá</p>
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
