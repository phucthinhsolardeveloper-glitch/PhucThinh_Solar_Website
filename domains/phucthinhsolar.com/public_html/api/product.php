<?php 
	include "config.php";

	/* Paginations */
	include LIBRARIES."class/class.PaginationsAjax.php";
	$pagingAjax = new PaginationsAjax();
	$pagingAjax->perpage = (!empty($_GET['perpage'])) ? htmlspecialchars($_GET['perpage']) : 1;
	$eShow = htmlspecialchars($_GET['eShow']);
	$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;
	$p = (!empty($_GET['p'])) ? htmlspecialchars($_GET['p']) : 1;
	$start = ($p-1) * $pagingAjax->perpage;
	$pageLink = "api/product.php?perpage=".$pagingAjax->perpage;
	$tempLink = "";
	$where = "";
	$params = array();

	$tempLink .= "&p=";
	$pageLink .= $tempLink;

	/* Get data */
	$sql = "select name$lang, slugvi, slugen, id, photo, photo2, regular_price, sale_price, discount, type,khoanggia from #_product where type='san-pham' $where and find_in_set('hienthi',status) and find_in_set('banchay',status) order by numb,id desc";
	$sqlCache = $sql." limit $start, $pagingAjax->perpage";
    $items = $cache->get($sqlCache, $params, 'result', 7200);
	/* Count all data */
	$countItems = count($cache->get($sql, $params, 'result', 7200));
	/* Get page result */
	$pagingItems = $pagingAjax->getAllPageLinks($countItems, $pageLink, $eShow);
?>
<?php if($countItems) { ?>
<div class="grid-product">
    <?php foreach($items as $k => $v) { ?>
    <div class="product-item wow animate__fadeInUp animate__animated" data-wow-delay="<?=$k * 0.1?>s">
        <div class="product-block item loop-border">
            <div class="product-img  has-hover" data-frame="">
                <a href="<?=$v[$sluglang]?>" title="<?=$v['name'.$lang]?>" class="image-resize ">
                    <?=$func->getImage(['class' => 'lazy dt-width-100 img-first bigloaded','sizes' => '300x300x1', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                    <?php if(!empty($v['photo2'])){ ?>
					<?=$func->getImage(['class' => 'lazy dt-width-100 img-hover hidden-xs bigloaded','sizes' => '300x300x1', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo2'], 'alt' => $v['name'.$lang]])?>
                	<?php } ?>
				</a>
				<?php if(publicProductPricesVisible() && $v['discount']) { ?>
				<div class="product-sale"><span><?='-'.$v['discount'].'%'?></span></div>
				<?php } ?>
            </div>
            <div class="product-detail">
                <h3 class="pro-name"><a href="<?=$v[$sluglang]?>" title="<?=$v['name'.$lang]?>"><?=$v['name'.$lang]?></a></h3>
                <div class="box-pro-prices">
                    <p class="pro-price">
						<?php if(!publicProductPricesVisible()) { ?>
							<span><?=lienhe?></span>
						<?php } elseif($v['discount']) { ?>
							<span><?=$func->formatMoney($v['sale_price'],'đ')?> </span>
							<del class="compare-price"><?=$func->formatMoney($v['regular_price'],'đ')?></del>
						<?php } else { ?>
							<?php if(!empty($v['khoanggia'])) { ?>
								<span><?=$v['khoanggia']?></span>
							<?php }else{ ?>
								<span><?=($v['regular_price']) ? $func->formatMoney($v['regular_price'],'đ') : lienhe?></span>
							<?php } ?>
						<?php } ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<?php if(count($items) < $countItems) { ?>
<div class="text-center btn-view-all-tab">
    <a href="san-pham" class="btn btn-all-tab">
        Xem thêm »
    </a>
</div>
<?php } ?>
<?php } ?>
