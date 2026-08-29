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
        <span>PHÚC THỊNH SOLAR</span>
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
<div class="filter_product">
    <div class="filter-heading">
        <span class="filter-heading-icon" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>
        <span>
            <strong class="name_title">Sản phẩm</strong>
            <small>Sắp xếp danh sách theo nhu cầu</small>
        </span>
    </div>
    <div class="sort-pill-group" role="group" aria-label="Sắp xếp sản phẩm">
        <?php
            $sortOptions = array(
                '' => 'Sắp xếp',
                'abc' => 'Tên A → Z',
                'abc-desc' => 'Tên Z → A',
                'price' => 'Giá tăng dần',
                'price-desc' => 'Giá giảm dần',
            );
            $currentSort = (isset($_GET['is_orderby'])) ? $_GET['is_orderby'] : '';
        ?>
        <?php foreach($sortOptions as $sk => $sv) { ?>
        <a href="javascript:void(0)" class="sort-pill <?=($currentSort==$sk)?'active':''?>" data-sort="<?=$sk?>" aria-pressed="<?=($currentSort==$sk)?'true':'false'?>"><?=$sv?></a>
        <?php } ?>
    </div>
    <select name="orderby" onchange="fetch_product()" class="orderby" aria-label="Đơn hàng của cửa hàng" style="display:none;">
        <option value="">Sắp xếp</option>
        <option value="abc">A → Z</option>
        <option value="abc-desc">Z → A</option>
        <option value="price">Giá tăng dần</option>
        <option value="price-desc">Giá giảm dần</option>
        <option value="new">Hàng mới nhất</option>
        <option value="new-desc">Hàng cũ nhất</option>
    </select>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var pills = document.querySelectorAll('.sort-pill');
    var select = document.querySelector('select[name="orderby"]');
    if(!select) return;
    pills.forEach(function(p){
        p.addEventListener('click', function(){
            pills.forEach(function(x){ x.classList.remove('active'); });
            p.classList.add('active');
            pills.forEach(function(x){ x.setAttribute('aria-pressed', x === p ? 'true' : 'false'); });
            select.value = p.getAttribute('data-sort');
            if(typeof fetch_product === 'function') fetch_product();
        });
    });
});
</script>
<div class="product-row row">
    <div class="product-left col-lg-3">
        <div class="mb40">
            <?php if(!empty($optsetting['hotline'])) { ?>
            <div class="sidebar-consult-box">
                <span class="sc-label">Cần tư vấn chọn sản phẩm?</span>
                <a class="sc-phone" href="tel:<?=$func->parsePhone($optsetting['hotline'])?>"><?=$optsetting['hotline']?></a>
                <a class="sc-btn" href="lien-he/">Yêu cầu tư vấn miễn phí</a>
            </div>
            <?php } ?>
            <?php /* Gộp toàn bộ khối filter vào 1 thẻ card duy nhất thay vì mỗi
                khối một khung viền riêng (5 viền chồng nhau nhìn rối) - dùng
                đường phân cách mảnh giữa các khối, đúng như bản thiết kế mẫu. */ ?>
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
                                    <?php if(count($splist)){foreach($splist as $k => $v){$spcat = $d->rawQuery("select name$lang, slugvi, slugen, id from #_product_cat where id_list = ? and find_in_set('hienthi',status) order by numb,id desc",array($v['id'])); ?>
                                    <li class="filter-item filter-item--check-box filter-item--green <?=(isset($slug_param)&& $slug_param == $v[$sluglang])?'checked':''?>">
                                        <a href="danh-muc-san-pham/<?=$v[$sluglang]?>/">
                                            <span>
                                                <label for="">
                                                <?=$v['name'.$lang]?>
                                                </label>
                                            </span>
                                        </a>
                                        <?php if(count($spcat)){ ?>
                                        <ul>
                                            <?php foreach($spcat as $k => $c){ ?>
                                            <li><a href="<?= $c[$sluglang] ?>"><?= $c['name'.$lang] ?></a></li>
                                            <?php } ?>
                                        </ul>
                                        <?php } ?>
                                    </li>
                                    <?php }} ?>
                                </ul>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
            <?php } ?>

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
                        ?>
                        <?php foreach($congSuatList as $k => $v) { $congSuatValue = rtrim(rtrim(number_format($v['cong_suat'],1,'.',''),'0'),'.'); ?>
                        <li class="product-pages__price-items">
                            <label class="mg0 cs-pointer checker product-pages__price-label d-flex p-relative">
                                <input type="checkbox" onchange="fetch_product()" <?=(in_array($v['cong_suat'],$congsuat_get))?'checked':''?> name="car_congsuat" id="car_congsuat<?=$k?>"
                                    value="<?=$v['cong_suat']?>">
                                <label class="mg0 checked-fake" for="car_congsuat<?=$k?>"></label>
                                <span class=" ml10" for="car_congsuat<?=$k?>"><?=$congSuatValue?>W</span>
                            </label>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>

            <?php
                $catFilterListId = (!empty($productList['id'])) ? $productList['id'] : 0;
                $catFilterList = ($catFilterListId) ? $d->rawQuery("select name$lang, id from #_product_cat where id_list = ? and find_in_set('hienthi',status) order by numb,id desc",array($catFilterListId)) : array();
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
                                    value="<?=$v['id']?>">
                                <label class="mg0 checked-fake" for="car_cat<?=$k?>"></label>
                                <span class=" ml10" for="car_cat<?=$k?>"><?=$v['name'.$lang]?></span>
                            </label>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php } ?>

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
<input type="hidden" name="keywords" id="keyword" value="0">
<input type="hidden" name="href" value="aHR0cHM6Ly9waHV0dW5nb3RvbXQuY29tL3Nhbi1waGFt">
<input type="hidden" name="alias" value="danh-muc-san-pham/<?= (isset($slug_param)) ? $slug_param : 'san-pham' ?>">
<input type="hidden" name="com-tpl" value="danh-muc-san-pham/<?= (isset($slug_param)) ? $slug_param : 'san-pham' ?>">
<input type="hidden" name="idl-tpl" value="">
<input type="hidden" name="id_list" value="<?= (isset($id_list)) ? $id_list : 0 ?>">
<input type="hidden" name="id_cat" value="<?= (isset($id_cat)) ? $id_cat : 0 ?>">
<div class="mask-sidebar toggle-sidebar"></div>
