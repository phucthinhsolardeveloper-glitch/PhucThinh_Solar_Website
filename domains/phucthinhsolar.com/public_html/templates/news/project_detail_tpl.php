<div id="inf_single_project">
    <div class="">
        <div class="">
            <div class="left">
                <div class="content">
                    <div class="title_wrap">
                        <p>Dự án</p>
                        <h1>
                            <?=$rowDetail['name'.$lang]?>
                        </h1>
                    </div>
                    <div class="client_wrap">
                        <?php if(!empty($newsList['name'.$lang])) { ?>
                        <div class="category">
                            <p>Danh mục<b>
                                    <?=$newsList['name'.$lang]?>
                                </b></p>
                        </div>
                        <?php } ?>
                        <div class="name">
                            <p>Khách hàng<b>
                                    <?=$rowDetail['tenkhachhang']?>
                                </b></p>
                        </div>
                        <div class="address">
                            <p>Địa chỉ<b>
                                    <?=$rowDetail['address']?>
                                </b></p>
                        </div>
                        <?php if(!empty($rowDetail['project_start_date']) || !empty($rowDetail['project_end_date'])) { ?>
                        <div class="date">
                            <p>Thời gian thực hiện<b>
                                <?=!empty($rowDetail['project_start_date']) ? date('d/m/Y', strtotime($rowDetail['project_start_date'])) : 'Đang cập nhật'?>
                                <?php if(!empty($rowDetail['project_end_date'])) { ?> – <?=date('d/m/Y', strtotime($rowDetail['project_end_date']))?><?php } ?>
                            </b></p>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="des_wrap">
                        <?=htmlspecialchars_decode($rowDetail['content'.$lang])?>
                    </div>
                </div>
            </div>
            <div class="right">
                <div class="tabs-pro-detail">
                    <ul class="nav nav-tabs" id="tabsProDetail" data-click-scroll="center" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="info-pro-detail-tab" data-toggle="tab" href="#info-pro-detail"
                                role="tab">
                                <i class="fas fa-images" aria-hidden="true"></i>
                                Hình ảnh
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="commentfb-pro-detail-tab" data-toggle="tab" href="#commentfb-pro-detail"
                                role="tab">
                                <i class="fas fa-play-circle" aria-hidden="true"></i>
                                Video
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content pt-4 pb-4" id="tabsProDetailContent">
                        <div class="tab-pane fade show active" id="info-pro-detail" role="tabpanel">

                            <div class="album-gallery pts-project-gallery">
                                <?php if(!empty($rowDetailPhoto)) { foreach($rowDetailPhoto as $k => $v) { ?>
                                    <div class="album-item none album-gallery-photo">
                                        <a class="album-image mb-0"  href="<?=ASSET.UPLOAD_NEWS_L.$v['photo']?>" title="<?=$rowDetail['name'.$lang]?>">
                                            <?=$func->getImage(['class' => 'w-100', 'sizes' => '873x654x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $rowDetail['name'.$lang]])?>
                                        </a>
                                    </div>
                                <?php }} ?>
                            </div>
                            
                        </div>
                        <div class="tab-pane fade" id="commentfb-pro-detail" role="tabpanel">
                            <div class="row row-project">
                                <?php if(!empty($rowDetailVideo)) { foreach($rowDetailVideo as $k => $v) { ?>
                                    <div class="video col-12 col-md-4 col-lg-4 col-xl-4" data-fancybox="video" data-src="<?=$v['link_video']?>">
                                        <div class="video-image scale-img">
                                            <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '480x360x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                                            <div class="btn-play">
                                                <svg fill="#fff" width="25px" height="25px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                    <title>icn/play</title>
                                                    <path d="M5.008 12.897a.644.644 0 0 1-.91-.227.719.719 0 0 1-.098-.364V3.693C4 3.31 4.296 3 4.662 3a.64.64 0 0 1 .346.103l6.677 4.306a.713.713 0 0 1 0 1.182l-6.677 4.306z" id="a"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="video-name text-split"><?=$v['name'.$lang]?></h3>
                                    </div>
                                <?php }} ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($projectRelatedProducts)) { ?>
<section class="pts-related-showcase pts-project-products">
    <div class="wrap-content">
        <div class="title-product-orther"><span>Sản phẩm sử dụng trong dự án</span></div>
        <div class="pts-related-grid">
            <?php foreach($projectRelatedProducts as $relatedProduct) { ?>
                <a class="pts-related-card" href="<?=$relatedProduct[$sluglang]?>" title="<?=$relatedProduct['name'.$lang]?>">
                    <?=$func->getImage(['class' => 'w-100', 'sizes' => '300x300x1', 'upload' => UPLOAD_PRODUCT_L, 'image' => $relatedProduct['photo'], 'alt' => $relatedProduct['name'.$lang]])?>
                    <span class="pts-related-card-body">
                        <strong><?=$relatedProduct['name'.$lang]?></strong>
                        <?php if(!empty($relatedProduct['code'])) { ?><small>Mã: <?=$relatedProduct['code']?></small><?php } ?>
                    </span>
                </a>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php /* Dự án khác cùng danh mục (2026-09-16). Chủ project: trang chi tiết nên
         có khối "dự án tương tự" như trang bài viết đã có "Tin liên quan".

         Chọn "cùng danh mục" thay vì "đã xem": đúng ngữ cảnh hơn (khách xem dự
         án hộ gia đình thường muốn xem thêm dự án hộ gia đình khác), luôn có nội
         dung, và không cần lưu hành vi người dùng vào máy họ.

         $news do sources/news.php dựng sẵn - đã lọc cùng id_list, loại chính bài
         đang xem, chỉ lấy bài đang hiển thị. Không cần query thêm. */ ?>
<?php if(!empty($news)) { ?>
<section class="pts-project-hub pts-project-related" aria-labelledby="project-related-title">
    <div class="uk-container">
        <div class="pts-retail-section-head">
            <div class="section-title">
                <h3 id="project-related-title">Dự án khác</h3>
            </div>
            <a class="pts-retail-text-link" href="/du-an-da-thuc-hien/">Xem tất cả dự án <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="pts-ph-grid">
            <?php foreach(array_slice($news, 0, 3) as $relatedProject) {
                $relatedName = !empty($relatedProject['name'.$lang]) ? $relatedProject['name'.$lang] : '';
                if($relatedName === '') continue;
            ?>
            <a class="pts-ph-card" href="<?=htmlspecialchars($relatedProject[$sluglang].'/', ENT_QUOTES, 'UTF-8')?>" title="<?=htmlspecialchars($relatedName, ENT_QUOTES, 'UTF-8')?>">
                <div class="pts-ph-card-media">
                    <?=$func->getImage(['class' => '', 'sizes' => '640x400x1', 'upload' => UPLOAD_NEWS_L, 'image' => $relatedProject['photo'], 'alt' => $relatedName])?>
                </div>
                <div class="pts-ph-card-body">
                    <h2><?=htmlspecialchars($relatedName, ENT_QUOTES, 'UTF-8')?></h2>
                    <?php if(!empty($relatedProject['address'])) { ?>
                    <div class="pts-ph-meta">
                        <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?=htmlspecialchars($relatedProject['address'], ENT_QUOTES, 'UTF-8')?></span>
                    </div>
                    <?php } ?>
                    <span class="pts-ph-arrow">Xem dự án <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </div>
            </a>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php include TEMPLATE.'news/project_cta_tpl.php'; ?>
