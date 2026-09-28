<?php
    $ptsCategoryTitle = 'Sản phẩm năng lượng mặt trời';
    if(!empty($productCat['name'.$lang])) {
        $ptsCategoryTitle = $productCat['name'.$lang];
    } elseif(!empty($productList['name'.$lang])) {
        $ptsCategoryTitle = $productList['name'.$lang];
    }
    $ptsCategoryPath = isset($productCategoryPath) ? (string)$productCategoryPath : '';
    $ptsCategoryDescription = 'Giải pháp năng lượng phù hợp cho gia đình, doanh nghiệp và công trình';
    if(strpos($ptsCategoryPath, 'den-duong') !== false) $ptsCategoryDescription = 'Chiếu sáng ổn định cho đường phố, lối đi và khu vực công cộng';
    elseif(strpos($ptsCategoryPath, 'den-pha') !== false) $ptsCategoryDescription = 'Chiếu sáng mạnh mẽ cho sân bãi, nhà xưởng và công trình';
    elseif(strpos($ptsCategoryPath, 'den-san-vuon') !== false) $ptsCategoryDescription = 'Tạo điểm nhấn ánh sáng cho sân vườn, lối đi và khuôn viên';
    elseif(strpos($ptsCategoryPath, 'den-op-tran') !== false) $ptsCategoryDescription = 'Chiếu sáng tiện nghi cho hiên nhà, hành lang và không gian sinh hoạt';
    elseif(strpos($ptsCategoryPath, 'den-') !== false) $ptsCategoryDescription = 'Lựa chọn chiếu sáng phù hợp cho đường phố, sân nhà và khuôn viên';
    elseif(strpos($ptsCategoryPath, 'tam-pin') !== false) $ptsCategoryDescription = 'Tạo nguồn điện sạch, ổn định cho gia đình và công trình';
    elseif(strpos($ptsCategoryPath, 'pin-luu-tru') !== false) $ptsCategoryDescription = 'Lưu trữ năng lượng an toàn, chủ động cho mọi nhu cầu sử dụng';
    elseif(strpos($ptsCategoryPath, 'quat-') !== false) $ptsCategoryDescription = 'Giải pháp làm mát tiết kiệm điện cho gia đình và không gian mở';
    elseif(strpos($ptsCategoryPath, 'bien-tan') !== false) $ptsCategoryDescription = 'Chuyển đổi điện năng hiệu quả, vận hành ổn định và bền bỉ';
    $productFilterPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/san-pham/', PHP_URL_PATH);
    $ptsFilterQueryKeys = array('is_brand', 'is_origin', 'is_congsuat', 'is_price', 'is_cat', 'is_need', 'keyword', 'is_orderby');
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
        <div class="pts-category-copy-content">
            <h1><?=htmlspecialchars($ptsCategoryTitle, ENT_QUOTES, 'UTF-8')?></h1>
            <p><?=htmlspecialchars($ptsCategoryDescription, ENT_QUOTES, 'UTF-8')?></p>
        </div>
    </div>
    <div class="trust-badge-strip category-trust-strip">
        <div class="row">
            <div class="col-md-3 trust-badge-item">
                <i class="fas fa-lightbulb" aria-hidden="true"></i>
                <span class="trust-badge-copy"><strong>Tư vấn lựa chọn</strong><small>Đúng nhu cầu, hiệu quả</small></span>
            </div>
            <div class="col-md-3 trust-badge-item">
                <i class="fas fa-tools" aria-hidden="true"></i>
                <span class="trust-badge-copy"><strong>Hỗ trợ kỹ thuật</strong><small>Trong suốt quá trình sử dụng</small></span>
            </div>
            <div class="col-md-3 trust-badge-item">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span class="trust-badge-copy"><strong>Chính sách rõ ràng</strong><small>Minh bạch, dễ hiểu</small></span>
            </div>
            <div class="col-md-3 trust-badge-item">
                <i class="fas fa-truck" aria-hidden="true"></i>
                <span class="trust-badge-copy"><strong>Giao hàng toàn quốc</strong><small>Nhanh chóng, an toàn</small></span>
            </div>
        </div>
    </div>
</section>

<?php
    /* Chỉ hiện MỘT dải ở vị trí này, không bao giờ chồng 2 dải cùng lúc (đây
       là điều sếp thấy rối trước đây - danh mục con vốn LUÔN có
       $productCategoryNav không rỗng nên dải cấp 1 hiện song song với dải
       "Loại sản phẩm" bên dưới). Quy tắc: trang gốc /san-pham/ (chưa chọn
       danh mục, $productSubcategoryNav rỗng vì không có $productList) hiện
       dải danh mục cấp 1; vào hẳn 1 danh mục có nhóm con thì dải cấp 1 biến
       mất, thay bằng dải "Loại sản phẩm" (danh mục con) đúng vị trí đó, dùng
       chung 1 kiểu hiển thị to/nổi bật (xác nhận với sếp 2026-09-26). */
    $ptsHasSubcategoryRail = !empty($productSubcategoryNav) && count($productSubcategoryNav) > 1;
?>
<?php if($ptsHasSubcategoryRail) { ?>
<nav class="pts-category-tabs pts-category-tabs--primary is-sticky-row" aria-label="Loại sản phẩm trong danh mục">
    <?php foreach($productSubcategoryNav as $ptsSubcategoryItem) {
        $ptsSubcategoryName = !empty($ptsSubcategoryItem['nav_name']) ? $ptsSubcategoryItem['nav_name'] : (!empty($ptsSubcategoryItem['name'.$lang]) ? $ptsSubcategoryItem['name'.$lang] : '');
        if($ptsSubcategoryName === '') continue;
    ?>
    <a href="<?=$ptsSubcategoryItem['url']?>" class="pts-category-tab<?=!empty($ptsSubcategoryItem['is_active']) ? ' is-active' : ''?>"<?=!empty($ptsSubcategoryItem['is_active']) ? ' aria-current="page"' : ''?>>
        <span class="pts-category-tab-media">
            <?php if(!empty($ptsSubcategoryItem['nav_photo']) && empty($ptsSubcategoryItem['nav_name'])) { ?>
            <?=$func->getImage(['isLazy' => true, 'class' => '', 'sizes' => '96x96x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $ptsSubcategoryItem['nav_photo'], 'alt' => $ptsSubcategoryName])?>
            <?php } else { ?>
            <i class="fas fa-th-large" aria-hidden="true"></i>
            <?php } ?>
        </span>
        <span class="pts-category-tab-name"><?=htmlspecialchars($ptsSubcategoryName, ENT_QUOTES, 'UTF-8')?></span>
    </a>
    <?php } ?>
</nav>
<?php } elseif(!empty($productCategoryNav)) { ?>
<nav class="pts-category-tabs pts-category-tabs--primary is-sticky-row" aria-label="Danh mục sản phẩm">
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
<div class="filter_product">
    <div class="filter-heading">
        <span>
            <strong class="name_title">Danh sách sản phẩm</strong>
        </span>
    </div>
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
            <?php /* Thương hiệu luôn hiển thị; chỉ "Mức giá" phụ thuộc chế độ giá công khai.
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
                /* "Nhu cầu sử dụng" chỉ áp dụng nhóm đèn - suy từ từ khoá tên/mô
                   tả (xem libraries/product_need_filter.php), không phải field
                   thật nên chỉ hiện đúng ngữ cảnh, tránh gây hiểu nhầm ở nhóm
                   sản phẩm khác. "Loại đèn" đã có dải tab riêng phía trên nên
                   không lặp lại ở sidebar. */
                $ptsIsLightingCategory = strpos($ptsCategoryPath, 'den-') !== false || strpos($ptsCategoryPath, 'tru-den') !== false;
                $need_get = [];
                if(isset($_GET['is_need'])){
                    $need_get = explode(",", $_GET['is_need']);
                }
            ?>
            <?php if($ptsIsLightingCategory) { ?>
            <div class="product-pages__price mb20 pts-filter-group pts-filter-open">
                <div class="product-pages__price-aside pts-filter-toggle" role="button" tabindex="0" aria-expanded="true">
                    <span class="product-pages__price-name">Nhu cầu sử dụng</span>
                    <i class="fas fa-chevron-down pts-filter-chevron" aria-hidden="true"></i>
                </div>
                <div class="wrappers-aside__filter-scroll pts-filter-body">
                    <ul class="product-pages__price-nav">
                        <?php
                            $needOptions = array(
                                'duong-giao-thong' => 'Đường giao thông',
                                'san-nha' => 'Sân nhà & sân vườn',
                                'khuon-vien' => 'Khuôn viên & công trình'
                            );
                        ?>
                        <?php foreach($needOptions as $nk => $nv) { ?>
                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=(in_array($nk,$need_get))?'checked':''?> name="car_need" id="car_need<?=$nk?>"
                                    value="<?=$nk?>">
                                <label class="mg0 checked-fake" for="car_need<?=$nk?>"></label>
                                <span class=" ml10" for="car_need<?=$nk?>"><?=$nv?></span>
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
<?php
    /* "Dự án tiêu biểu" và "Lý do chọn Phúc Thịnh Solar" trước khối CTA cuối
       trang (yêu cầu sếp 2026-09-28) - dùng chung nguồn dữ liệu và markup với
       trang chi tiết sản phẩm (xem product_detail_tpl.php) để đồng nhất thiết
       kế, không dựng nội dung riêng cho từng danh mục. */
?>
<?php if(!empty($productFeaturedProjects)) { ?>
<section class="box-detail pts-related-showcase pts-product-projects pts-pdp-projects" aria-labelledby="pts-category-projects-title">
    <div class="title-product-orther" id="pts-category-projects-title"><span>Dự án tiêu biểu đã triển khai</span></div>
    <div class="pts-related-grid">
        <?php foreach($productFeaturedProjects as $featuredProject) { ?>
            <a class="pts-related-card" href="<?=$featuredProject[$sluglang]?>" title="<?=$featuredProject['name'.$lang]?>">
                <span class="pts-project-media">
                    <?=$func->getImage(['class' => 'w-100 pts-project-media-main', 'sizes' => '397x340x1', 'upload' => UPLOAD_NEWS_L, 'image' => $featuredProject['photo'], 'alt' => $featuredProject['name'.$lang]])?>
                </span>
                <span class="pts-related-card-body">
                    <strong><?=$featuredProject['name'.$lang]?></strong>
                    <?php if(!empty($featuredProject['address'])) { ?><small><i class="fas fa-map-marker-alt"></i> <?=$featuredProject['address']?></small><?php } ?>
                </span>
            </a>
        <?php } ?>
    </div>
</section>
<?php } ?>
<?php if(!empty($productWhyChooseUs)) { ?>
<section class="box-detail pts-why-choose-us" aria-labelledby="pts-category-why-choose-title">
    <h2 class="title-product-orther" id="pts-category-why-choose-title"><span>Lý do chọn Phúc Thịnh Solar</span></h2>
    <div class="pts-why-choose-grid">
        <?php foreach($productWhyChooseUs as $whyChooseItem) { ?>
        <div class="pts-why-choose-item">
            <span class="pts-why-choose-icon"><?=$func->getImage(['class' => '', 'sizes' => '64x64x2', 'upload' => UPLOAD_NEWS_L, 'image' => $whyChooseItem['photo'], 'alt' => ''])?></span>
            <strong><?=$whyChooseItem['name'.$lang]?></strong>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>
<?php if(!empty($productCatalogSupport)) {
    $productCatalogSupportLink = !empty($productCatalogSupport['link']) ? $productCatalogSupport['link'] : 'lien-he/';
    $productCatalogSupportModal = in_array($productCatalogSupportLink, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($productCatalogSupportLink, '#') : '';
?>
<section class="pts-catalog-support" aria-labelledby="pts-catalog-support-title">
    <div class="pts-catalog-support-copy">
        <div class="pts-catalog-support-text"><i class="fas fa-comment-dots pts-catalog-support-icon" aria-hidden="true"></i><div><h2 id="pts-catalog-support-title"><?=htmlspecialchars($productCatalogSupport['name'], ENT_QUOTES, 'UTF-8')?></h2><p><?=htmlspecialchars($productCatalogSupport['desc'], ENT_QUOTES, 'UTF-8')?></p></div></div>
        <a href="<?=htmlspecialchars($productCatalogSupportLink, ENT_QUOTES, 'UTF-8')?>"<?=$productCatalogSupportModal !== '' ? ' data-toggle="modal" data-target="#'.$productCatalogSupportModal.'"' : ''?>>Nhận tư vấn <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>
<?php } ?>
<input type="hidden" name="keywords" id="keyword" value="<?=htmlspecialchars(isset($_GET['keyword']) ? $_GET['keyword'] : '',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="alias" value="<?=htmlspecialchars($productFilterPath ?: '/san-pham/',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="id_list" value="<?= (isset($id_list)) ? $id_list : 0 ?>">
<input type="hidden" name="id_cat" value="<?= (isset($id_cat)) ? $id_cat : 0 ?>">
<button type="button" class="mask-sidebar toggle-sidebar" aria-label="Đóng bộ lọc" tabindex="-1"></button>
