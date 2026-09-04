<?php
    $ptsCategoryTitle = 'Sản phẩm năng lượng mặt trời';
    if(!empty($productCat['name'.$lang])) {
        $ptsCategoryTitle = $productCat['name'.$lang];
    } elseif(!empty($productList['name'.$lang])) {
        $ptsCategoryTitle = $productList['name'.$lang];
    }
?>
<section class="pts-category-hero">
    <div class="pts-category-sun" aria-hidden="true"><i class="far fa-sun"></i></div>
    <div class="pts-category-copy">
        <span class="pts-brand-text">PHÚC THỊNH SOLAR</span>
        <h2><?=htmlspecialchars($ptsCategoryTitle, ENT_QUOTES, 'UTF-8')?></h2>
        <p>Thiết bị năng lượng mặt trời chính hãng, tư vấn đúng nhu cầu và đồng hành kỹ thuật trên toàn quốc.</p>
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
<?php $currentSort = (isset($_GET['is_orderby'])) ? $_GET['is_orderby'] : ''; ?>
<div class="filter_product">
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
            <strong><i class="fas fa-filter" aria-hidden="true"></i> Bộ lọc sản phẩm</strong>
            <button type="button" class="pts-filter-drawer-close toggle-sidebar" aria-label="Đóng bộ lọc"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="mb40">
            <?php if(!empty($optsetting['hotline'])) { ?>
            <div class="sidebar-consult-box">
                <span class="sc-label">Cần tư vấn chọn sản phẩm?</span>
                <a class="sc-phone" href="tel:<?=$func->parsePhone($optsetting['hotline'])?>"><?=$optsetting['hotline']?></a>
                <a class="sc-btn" href="lien-he/">Yêu cầu tư vấn miễn phí</a>
            </div>
            <?php } ?>
            <?php /* Nhóm bộ lọc sản phẩm trong một card dùng chung. */ ?>
            <div class="pts-filter-card">
            <?php if(count($splist)){ ?>
            <div class="aside-item aside-filter collection-category">
                <div class="aside-title aside-title-f">
                    <h3 class="title-head margin-top-0"><span>Danh mục</span></h3>
                </div>
                <div class="aside-hidden-mobile" style="">
                    <div class="filter-container">
                        <aside class="aside-item filter-cat">
                            <div class="aside-content filter-group">
                                <ul>
                                    <?php if(count($splist)){foreach($splist as $k => $v){ ?>
                                    <li class="filter-item filter-item--check-box filter-item--green <?=(isset($slug_param)&& $slug_param == $v[$sluglang])?'checked':''?>">
                                        <a href="danh-muc-san-pham/<?=$v[$sluglang]?>/">
                                            <span>
                                                <label for="">
                                                <?=$v['name'.$lang]?>
                                                </label>
                                            </span>
                                        </a>
                                    </li>
                                    <?php }} ?>
                                </ul>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
            <?php } ?>

            <?php /* Thương hiệu / Công suất / Loại sản phẩm luôn hiển thị; chỉ "Mức giá" phụ thuộc chế độ giá công khai. */ ?>
            <div class="product-pages__price mb20">
                <div class="product-pages__price-aside">
                    <span class="product-pages__price-name">Thương hiệu</span>
                </div>

                <div class="wrappers-aside__filter-scroll">
                    <ul class="product-pages__price-nav">
                        <?php
                            $brand_get = [];
                            if(isset($_GET['is_brand'])){
                                $brand_get = explode(",", $_GET['is_brand']);
                            }
                        ?>
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

            <?php if(count($congSuatList)) { ?>
            <div class="product-pages__price mb20">
                <div class="product-pages__price-aside">
                    <span class="product-pages__price-name">Công suất</span>
                </div>

                <div class="wrappers-aside__filter-scroll">
                    <ul class="product-pages__price-nav">
                        <?php
                            $congsuat_get = [];
                            if(isset($_GET['is_congsuat'])){
                                $congsuat_get = explode(",", $_GET['is_congsuat']);
                            }
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
                $catFilterListId = (!empty($productList['id'])) ? $productList['id'] : 0;
                $catFilterList = ($catFilterListId && empty($id_cat)) ? $d->rawQuery("select pc.name$lang, pc.id, pc.slugvi, pc.slugen, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id=pc.id_list where pc.id_list = ? and find_in_set('hienthi',pc.status) and exists (select 1 from #_product p where p.id_cat = pc.id and p.type = ? and find_in_set('hienthi',p.status)) order by pc.numb,pc.id desc",array($catFilterListId,'san-pham')) : array();
            ?>
            <?php if(count($catFilterList)) { ?>
            <div class="product-pages__price mb20">
                <div class="product-pages__price-aside">
                    <span class="product-pages__price-name">Loại sản phẩm</span>
                </div>

                <div class="wrappers-aside__filter-scroll">
                    <ul class="product-pages__price-nav">
                        <?php
                            $cat_get = [];
                            if(isset($_GET['is_cat'])){
                                $cat_get = explode(",", $_GET['is_cat']);
                            }
                        ?>
                        <?php foreach($catFilterList as $k => $v) { ?>
                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=(in_array($v['id'],$cat_get))?'checked':''?> name="car_cat" id="car_cat<?=$k?>"
                                    value="<?=$v['id']?>" data-url="<?=publicProductCategoryPath($v,'cat',$lang)?>">
                                <label class="mg0 checked-fake" for="car_cat<?=$k?>"></label>
                                <span class=" ml10" for="car_cat<?=$k?>"><?=$v['name'.$lang]?></span>
                            </label>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>

            <?php if(publicProductPricesVisible()) { ?>
            <div class="product-pages__price mb20">
                <div class="product-pages__price-aside">
                    <span class="product-pages__price-name">Mức giá</span>
                </div>

                <div class="wrappers-aside__filter-scroll">
                    <ul class="product-pages__price-nav">
                        <?php
                            $price_get = [];
                            if(isset($_GET['is_price'])){
                                $price_get = explode(",", $_GET['is_price']);
                            }
                            $priceOptions = array(
                                '1' => 'Dưới 500.000đ',
                                '2' => '500.000đ - 1.000.000đ',
                                '3' => '1.000.000đ - 2.000.000đ',
                                '4' => 'Trên 2.000.000đ',
                            );
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
<?php $productFilterPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/san-pham/', PHP_URL_PATH); ?>
<input type="hidden" name="keywords" id="keyword" value="<?=htmlspecialchars(isset($_GET['keyword']) ? $_GET['keyword'] : '',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="alias" value="<?=htmlspecialchars($productFilterPath ?: '/san-pham/',ENT_QUOTES,'UTF-8')?>">
<input type="hidden" name="id_list" value="<?= (isset($id_list)) ? $id_list : 0 ?>">
<input type="hidden" name="id_cat" value="<?= (isset($id_cat)) ? $id_cat : 0 ?>">
<button type="button" class="mask-sidebar toggle-sidebar" aria-label="Đóng bộ lọc" tabindex="-1"></button>
