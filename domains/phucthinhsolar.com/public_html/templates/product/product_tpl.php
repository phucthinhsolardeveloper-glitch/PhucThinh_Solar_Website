<div class="filter_product">
    <span class="name_title">Sản phẩm</span>
    <select name="orderby" onchange="fetch_product()" class="orderby" aria-label="Đơn hàng của cửa hàng">
        <option value="">Sắp xếp</option>
        <option value="abc">A → Z</option>
        <option value="abc-desc">Z → A</option>
        <option value="price">Giá tăng dần</option>
        <option value="price-desc">Giá giảm dần</option>
        <option value="new">Hàng mới nhất</option>
        <option value="new-desc">Hàng cũ nhất</option>
    </select>
</div>
<div class="product-row row">
    <div class="product-left col-lg-3">
        <div class="mb40">
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

        </div>
    </div>
    <div class="product-right col-lg-9">
        <div class="content-main w-clear">
            <div id="show-product">
                <?php if(!empty($product)) {?>
                <div class="grid-product grid-product-3">
                    <?php foreach($product as $k => $v) {  ?>
                    <div>
                        <a class="product-item" href="product/<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
                            <div class="img">
                                <?= $func->getImage(['class' => 'lazy w-100', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
                            </div>
                            <div class="caption">
                                <p class="name"><?=$v['name'.$lang]?></p>
                            </div>
                            <p class="price-product">
                                <?php if($v['sale_price']) { ?>
                                    <span class="price-new"><?=$func->formatMoney($v['sale_price'])?></span>
                                    <span class="price-old"><?=$func->formatMoney($v['regular_price'])?></span>
                                <?php } else { ?>
                                    <span class="price-new"><?=($v['regular_price']) ? $func->formatMoney($v['regular_price']) : lienhe?></span>
                                <?php } ?>
                            </p>
                        </a>
                    </div>
                    <?php } ?>
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