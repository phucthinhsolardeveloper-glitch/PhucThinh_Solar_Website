<?php
    $ptsCategoryTitle = 'Sản phẩm năng lượng mặt trời';
    if(!empty($productCat['name'.$lang])) {
        $ptsCategoryTitle = $productCat['name'.$lang];
    } elseif(!empty($productList['name'.$lang])) {
        $ptsCategoryTitle = $productList['name'.$lang];
    }
    $ptsCategoryDescription = !empty($productCategoryDescription) ? $productCategoryDescription : 'Chọn thiết bị phù hợp với nhu cầu chiếu sáng, phát điện và vận hành công trình.';
    $productFilterPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/san-pham/', PHP_URL_PATH);
    $ptsFilterQueryKeys = array('is_brand', 'is_origin', 'is_congsuat', 'is_price', 'is_cat', 'keyword', 'is_orderby');
    $ptsHasActiveFilter = false;
    foreach($ptsFilterQueryKeys as $ptsFilterQueryKey) {
        if(isset($_GET[$ptsFilterQueryKey]) && is_scalar($_GET[$ptsFilterQueryKey]) && trim((string)$_GET[$ptsFilterQueryKey]) !== '') {
            $ptsHasActiveFilter = true;
            break;
        }
    }
?>
<section class="pts-category-hero<?=!empty($productCategoryHero) ? ' has-image' : ''?>">
    <?php if(!empty($productCategoryHero)) { ?>
    <figure class="pts-category-hero-media" aria-hidden="true">
        <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '1800x600x1', 'upload' => $productCategoryHero['upload'], 'image' => $productCategoryHero['photo'], 'alt' => ''])?>
    </figure>
    <span class="pts-category-hero-shade" aria-hidden="true"></span>
    <?php } ?>
    <div class="pts-category-copy">
        <h1><?=htmlspecialchars($ptsCategoryTitle, ENT_QUOTES, 'UTF-8')?></h1>
        <p><?=htmlspecialchars($ptsCategoryDescription, ENT_QUOTES, 'UTF-8')?></p>
        <?php if(!empty($optsetting['hotline'])) { ?>
        <a href="tel:<?=$func->parsePhone($optsetting['hotline'])?>"><i class="fas fa-phone-alt"></i> Gọi ngay <?=$optsetting['hotline']?></a>
        <?php } ?>
    </div>
</section>

<div class="trust-badge-strip category-trust-strip">
    <div class="row">
        <div class="col-md-3 trust-badge-item"><i class="fas fa-certificate"></i><span>Cam kết chính hãng</span></div>
        <div class="col-md-3 trust-badge-item"><i class="fas fa-tools"></i><span>Đội ngũ kỹ thuật giàu kinh nghiệm</span></div>
        <div class="col-md-3 trust-badge-item"><i class="fas fa-shield-alt"></i><span>Chính sách bảo hành rõ ràng</span></div>
        <div class="col-md-3 trust-badge-item"><i class="fas fa-truck"></i><span>Giao hàng toàn quốc</span></div>
    </div>
</div>

<?php
    /* Vùng ghim thứ 2 (dưới dải cam kết) chỉ nên có MỘT hàng: dải "Loại sản
       phẩm" nếu danh mục đang xem có nhóm con, ngược lại dùng chính dải danh
       mục cấp 1 này - tránh để trống hoặc ghim chồng 2 hàng cùng lúc. */
    $ptsHasSubcategoryRail = !empty($productSubcategoryNav) && count($productSubcategoryNav) > 1;
?>
<?php if(!empty($productCategoryNav)) { ?>
<nav class="pts-category-tabs pts-category-tabs--primary<?=$ptsHasSubcategoryRail ? '' : ' is-sticky-row'?>" aria-label="Danh mục sản phẩm">
    <?php foreach($productCategoryNav as $ptsCategoryNavItem) {
        $ptsCategoryNavName = !empty($ptsCategoryNavItem['nav_name']) ? $ptsCategoryNavItem['nav_name'] : (!empty($ptsCategoryNavItem['name'.$lang]) ? $ptsCategoryNavItem['name'.$lang] : '');
        if($ptsCategoryNavName === '') continue;
        $ptsCategoryNavUrl = !empty($ptsCategoryNavItem['url']) ? $ptsCategoryNavItem['url'] : publicProductCategoryPath($ptsCategoryNavItem, 'cat', $lang);
        $ptsCategoryNavActive = !empty($ptsCategoryNavItem['is_active']) || (!empty($productCat['id']) && !empty($ptsCategoryNavItem['id']) && (int)$productCat['id'] === (int)$ptsCategoryNavItem['id']);
    ?>
    <a href="<?=$ptsCategoryNavUrl?>" class="pts-category-tab<?=$ptsCategoryNavActive ? ' is-active' : ''?>"<?=$ptsCategoryNavActive ? ' aria-current="page"' : ''?>>
        <span class="pts-category-tab-media">
            <?php if(!empty($ptsCategoryNavItem['photo'])) { ?>
            <?=$func->getImage(['isLazy' => true, 'class' => '', 'sizes' => '96x96x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $ptsCategoryNavItem['photo'], 'alt' => $ptsCategoryNavName])?>
            <?php } else { ?>
            <i class="fas fa-th-large" aria-hidden="true"></i>
            <?php } ?>
        </span>
        <span class="pts-category-tab-name"><?=htmlspecialchars($ptsCategoryNavName, ENT_QUOTES, 'UTF-8')?></span>
    </a>
    <?php } ?>
</nav>
<?php } ?>

<?php $currentSort = (isset($_GET['is_orderby'])) ? $_GET['is_orderby'] : ''; ?>
<div class="filter_product<?=$ptsHasSubcategoryRail ? ' is-sticky-row' : ''?>">
    <div class="filter-heading">
        <span class="filter-heading-icon"><i class="fas fa-solar-panel" aria-hidden="true"></i></span>
        <span>
            <strong class="name_title">Danh sách sản phẩm</strong>
            <small><span class="pts-product-result-count"><?=number_format((int)$total, 0, ',', '.')?></span> sản phẩm đang hiển thị</small>
        </span>
    </div>
    <?php if(!empty($productSubcategoryNav) && count($productSubcategoryNav) > 1) { ?>
    <div class="pts-subcategory-rail">
        <nav class="pts-subcategory-tabs" aria-label="Loại sản phẩm trong danh mục">
            <?php foreach($productSubcategoryNav as $ptsSubcategoryItem) {
                $ptsSubcategoryName = !empty($ptsSubcategoryItem['nav_name']) ? $ptsSubcategoryItem['nav_name'] : (!empty($ptsSubcategoryItem['name'.$lang]) ? $ptsSubcategoryItem['name'.$lang] : '');
                if($ptsSubcategoryName === '') continue;
            ?>
            <a href="<?=$ptsSubcategoryItem['url']?>" class="pts-subcategory-tab<?=!empty($ptsSubcategoryItem['is_active']) ? ' is-active' : ''?>"<?=!empty($ptsSubcategoryItem['is_active']) ? ' aria-current="page"' : ''?>>
                <span class="pts-subcategory-media">
                    <?php if(!empty($ptsSubcategoryItem['nav_photo']) && empty($ptsSubcategoryItem['nav_name'])) { ?>
                    <?=$func->getImage(['isLazy' => true, 'class' => '', 'sizes' => '64x64x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $ptsSubcategoryItem['nav_photo'], 'alt' => $ptsSubcategoryName])?>
                    <?php } else { ?>
                    <i class="fas fa-th-large" aria-hidden="true"></i>
                    <?php } ?>
                </span>
                <span><?=htmlspecialchars($ptsSubcategoryName, ENT_QUOTES, 'UTF-8')?></span>
            </a>
            <?php } ?>
        </nav>
    </div>
    <?php } ?>
    <button type="button" class="btn-filter-product toggle-sidebar" id="opensideBar-product" aria-controls="product-filter-drawer" aria-expanded="false">
        <i class="fas fa-filter" aria-hidden="true"></i> Bộ lọc
    </button>
    <div class="fp-sort">
        <label for="pts-sort">Sắp xếp</label>
        <select id="pts-sort" name="orderby" onchange="fetch_product()" class="orderby" aria-label="Sắp xếp sản phẩm">
            <?php
                $sortOptions = array(
                    ''         => 'Mặc định',
                    'abc'      => 'Tên A → Z',
                    'abc-desc' => 'Tên Z → A',
                    'new'      => 'Mới nhất',
                    'new-desc' => 'Cũ nhất',
                );
                if(publicProductPricesVisible()) {
                    $sortOptions['price']      = 'Giá tăng dần';
                    $sortOptions['price-desc'] = 'Giá giảm dần';
                }
            ?>
            <?php foreach($sortOptions as $sk => $sv) { ?>
            <option value="<?=$sk?>" <?=($currentSort==$sk)?'selected':''?>><?=$sv?></option>
            <?php } ?>
        </select>
    </div>
</div>
<div class="product-row row">
    <div class="product-left col-lg-3" id="product-filter-drawer" aria-hidden="false">
        <div class="pts-filter-drawer-head">
            <strong><i class="fas fa-filter" aria-hidden="true"></i> Bộ lọc</strong>
            <button type="button" class="pts-filter-drawer-close toggle-sidebar" aria-label="Đóng bộ lọc"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="mb40">
            <?php /* Đã bỏ box "Cần tư vấn chọn sản phẩm?" ở đây - trùng với nút
                     "Gọi ngay" đã có sẵn ở banner đầu trang danh mục (audit 2026-09-10). */ ?>
            <?php /* Nhóm bộ lọc sản phẩm trong một card dùng chung. */ ?>
            <div class="pts-filter-card">
            <?php /* Thương hiệu / Công suất luôn hiển thị; chỉ "Mức giá" phụ thuộc chế độ giá công khai.
                     Mỗi nhóm có thể tự thu gọn (accordion, xem apps.js .pts-filter-toggle) nhưng
                     mặc định MỞ SẴN hết - đóng mặc định bị phản hồi là bất tiện (audit 2026-09-10). */ ?>
            <?php
                $brand_get = [];
                if(isset($_GET['is_brand'])){
                    $brand_get = explode(",", $_GET['is_brand']);
                }
            ?>
            <div class="product-pages__price mb20 pts-filter-group pts-filter-open">
                <div class="product-pages__price-aside pts-filter-toggle" role="button" tabindex="0" aria-expanded="true">
                    <span class="product-pages__price-name">Thương hiệu</span>
                    <i class="fas fa-chevron-down pts-filter-chevron" aria-hidden="true"></i>
                </div>

                <div class="wrappers-aside__filter-scroll pts-filter-body">
                    <ul class="product-pages__price-nav">
                        <?php if(count($brand)){foreach($brand as $k => $v){ ?>

                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=(in_array($v['id'],$brand_get))?'checked':''?> name="car_brand" id="car_brand<?=$k?>"
                                    value="<?=$v['id']?>">
                                <label class="mg0 checked-fake" for="car_brand<?=$k?>"></label>
                                <span class=" ml10" for="car_brand<?=$k?>"><?=$v['name'.$lang]?></span>
                            </label>
                        </li>
                        <?php }} ?>
                    </ul>
                </div>
            </div>

            <?php
                $congsuat_get = [];
                if(isset($_GET['is_congsuat'])){
                    $congsuat_get = explode(",", $_GET['is_congsuat']);
                }
            ?>
            <?php if(count($congSuatList)) { ?>
            <div class="product-pages__price mb20 pts-filter-group pts-filter-open">
                <div class="product-pages__price-aside pts-filter-toggle" role="button" tabindex="0" aria-expanded="true">
                    <span class="product-pages__price-name">Công suất</span>
                    <i class="fas fa-chevron-down pts-filter-chevron" aria-hidden="true"></i>
                </div>

                <div class="wrappers-aside__filter-scroll pts-filter-body">
                    <ul class="product-pages__price-nav">
                        <?php
                            $commonPowerValues = array(60,100,150,200,300,400,500,600,800,1000);
                            $availablePowerValues = array();
                            foreach($congSuatList as $powerRow) {
                                $availablePowerValues[(int)$powerRow['cong_suat']] = true;
                            }
                            $powerOptions = array('under-60' => 'Dưới 60W');
                            foreach($commonPowerValues as $powerValue) {
                                if(isset($availablePowerValues[$powerValue])) $powerOptions[(string)$powerValue] = $powerValue.'W';
                            }
                            $powerOptions['over-1000'] = 'Trên 1000W';
                        ?>
                        <?php foreach($powerOptions as $powerKey => $powerLabel) { ?>
                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=in_array($powerKey,$congsuat_get,true)?'checked':''?> name="car_congsuat" id="car_congsuat-<?=$powerKey?>" value="<?=$powerKey?>">
                                <label class="mg0 checked-fake" for="car_congsuat-<?=$powerKey?>"></label>
                                <span class="ml10"><?=htmlspecialchars($powerLabel, ENT_QUOTES, 'UTF-8')?></span>
                            </label>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>

            <?php
                $price_get = [];
                if(isset($_GET['is_price'])){
                    $price_get = explode(",", $_GET['is_price']);
                }
            ?>
            <?php if(publicProductPricesVisible()) { ?>
            <div class="product-pages__price mb20 pts-filter-group pts-filter-open">
                <div class="product-pages__price-aside pts-filter-toggle" role="button" tabindex="0" aria-expanded="true">
                    <span class="product-pages__price-name">Mức giá</span>
                    <i class="fas fa-chevron-down pts-filter-chevron" aria-hidden="true"></i>
                </div>

                <div class="wrappers-aside__filter-scroll pts-filter-body">
                    <ul class="product-pages__price-nav">
                        <?php
                            $priceOptions = array();
                            foreach(productPriceFilterRanges() as $pOptKey => $pOptRange) $priceOptions[$pOptKey] = $pOptRange[2];
                        ?>
                        <?php foreach($priceOptions as $pk => $pv) { ?>
                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=(in_array($pk,$price_get))?'checked':''?> name="car_price" id="car_price<?=$pk?>"
                                    value="<?=$pk?>">
                                <label class="mg0 checked-fake" for="car_price<?=$pk?>"></label>
                                <span class=" ml10" for="car_price<?=$pk?>"><?=$pv?></span>
                            </label>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>
            </div>

            <a class="pts-filter-reset" href="<?=htmlspecialchars($productFilterPath ?: '/san-pham/', ENT_QUOTES, 'UTF-8')?>" data-filter-reset<?=$ptsHasActiveFilter ? '' : ' hidden'?> aria-hidden="<?=$ptsHasActiveFilter ? 'false' : 'true'?>"><i class="fas fa-undo-alt" aria-hidden="true"></i> Xóa bộ lọc</a>

        </div>
    </div>
    <div class="product-right col-lg-9">
        <div class="content-main w-clear">
            <div id="show-product">
                <?php if(!empty($product)) {?>
                <div class="grid-product grid-product-3">
                    <?php foreach($product as $k => $v) { include ROOT."templates/product/_product_card.php"; } ?>
                </div>
                <?php } else { ?>
                <div class="col-12">
                    <div class="alert alert-warning w-100" role="alert">
                        <strong><?=khongtimthayketqua?></strong>
                    </div>
                </div>
                <?php } ?>
                <div class="clear"></div>
                <div class="col-12">
                    <div class="pagination-home w-100"><?=(!empty($paging)) ? $paging : ''?></div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
    /* Nội dung riêng của danh mục (soạn ở admin > Khối nội dung mô tả > Danh mục
       cấp 1/2), hiện CỐ ĐỊNH dưới lưới sản phẩm — không đổi theo bộ lọc AJAX vì
       đây là nội dung mô tả danh mục, không phải kết quả lọc (học theo Kitawa). */
    $categorySectionHtml = (!empty($categorySectionBlocks)) ? productRenderSections($categorySectionBlocks, UPLOAD_PRODUCT_L, $func) : '';
?>
<?php if($categorySectionHtml !== '') { ?>
<section class="pts-category-content">
    <div class="pts-category-content-inner">
        <?=$categorySectionHtml?>
    </div>
</section>
<?php } ?>
<?php
    /* Câu hỏi thường gặp: soạn ở admin > Khối nội dung mô tả > Danh mục cấp
       1/2 (khối kiểu "Câu hỏi thường gặp"), cùng nơi soạn nội dung mô tả ở
       trên. Không hiện gì nếu Admin chưa nhập khối FAQ nào cho danh mục. */
    $categoryFaqHtml = (!empty($categoryFaqBlocks)) ? productRenderFaqBlocks($categoryFaqBlocks) : '';
?>
<?php if($categoryFaqHtml !== '') { ?>
<section class="pts-category-faq" aria-labelledby="pts-category-faq-title">
    <h2 id="pts-category-faq-title">Câu hỏi thường gặp</h2>
    <div class="pts-category-faq-list"><?=$categoryFaqHtml?></div>
</section>
<?php } ?>
<?php if(!empty($productCatalogSupport)) {
    $productCatalogSupportLink = !empty($productCatalogSupport['link']) ? $productCatalogSupport['link'] : 'lien-he/';
    $productCatalogSupportModal = in_array($productCatalogSupportLink, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($productCatalogSupportLink, '#') : '';
?>
<section class="pts-catalog-support" aria-labelledby="pts-catalog-support-title">
    <span class="pts-catalog-support-media" aria-hidden="true"><?=$func->getImage(['isLazy' => true, 'class' => '', 'sizes' => '1600x500x1', 'upload' => $productCatalogSupport['upload'], 'image' => $productCatalogSupport['photo'], 'alt' => ''])?></span>
    <span class="pts-catalog-support-shade" aria-hidden="true"></span>
    <div class="pts-catalog-support-copy">
        <div><h2 id="pts-catalog-support-title"><?=htmlspecialchars($productCatalogSupport['name'], ENT_QUOTES, 'UTF-8')?></h2><p><?=htmlspecialchars($productCatalogSupport['desc'], ENT_QUOTES, 'UTF-8')?></p></div>
        <a href="<?=htmlspecialchars($productCatalogSupportLink, ENT_QUOTES, 'UTF-8')?>"<?=$productCatalogSupportModal !== '' ? ' data-toggle="modal" data-target="#'.$productCatalogSupportModal.'"' : ''?>>Nhận tư vấn <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>
<?php } ?>
<input type="hidden" name="keywords" id="keyword" value="<?=htmlspecialchars(isset($_GET['keyword']) ? $_GET['keyword'] : '',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="alias" value="<?=htmlspecialchars($productFilterPath ?: '/san-pham/',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="id_list" value="<?= (isset($id_list)) ? $id_list : 0 ?>">
<input type="hidden" name="id_cat" value="<?= (isset($id_cat)) ? $id_cat : 0 ?>">
<button type="button" class="mask-sidebar toggle-sidebar" aria-label="Đóng bộ lọc" tabindex="-1"></button>
