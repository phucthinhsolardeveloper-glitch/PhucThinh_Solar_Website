<?php 
	include "config.php";

	/* Paginations */
	include LIBRARIES."class/class.PaginationsAjax.php";
	$pagingAjax = new PaginationsAjax();
	$pagingAjax->perpage = (!empty($_GET['perpage'])) ? htmlspecialchars($_GET['perpage']) : 1;
	$eShow = htmlspecialchars($_GET['eShow']);
	$idList = (!empty($_GET['idList'])) ? htmlspecialchars($_GET['idList']) : 0;
	$p = (!empty($_GET['p'])) ? htmlspecialchars($_GET['p']) : 1;
	$start = ($p-1) * $pagingAjax->perpage;
	$pageLink = "api/product2.php?perpage=".$pagingAjax->perpage;
	$tempLink = "";
	$where = "";
	$params = array();
	$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen, type, photo, options from #_product_list where id = ? and type = ? limit 0,1",array($idList,'san-pham'));
	/* Math url */
	if($idList)
	{
		$tempLink .= "&idList=".$idList;
		$where .= " and id_list = ?";
		array_push($params, $idList);
	}
	$tempLink .= "&p=";
	$pageLink .= $tempLink;

	/* Get data */
	$sql = "select name$lang, slugvi, slugen, id, photo, regular_price, sale_price, discount, type from #_product where type='san-pham' $where and find_in_set('hienthi',status) order by numb,id desc";
	$sqlCache = $sql." limit $start, $pagingAjax->perpage";
    $items = $cache->get($sqlCache, $params, 'result', 7200);

	/* Count all data */
	$countItems = count($cache->get($sql, $params, 'result', 7200));

	/* Get page result */
	$pagingItems = $pagingAjax->getAllPageLinks($countItems, $pageLink, $eShow);
?>
<?php if($countItems) { ?>
<div class="position-relative owl-btn">
    <div class="owl-page owl-carousel owl-theme" data-xsm-items="2:15" data-sm-items="2:15" data-md-items="3:20"
        data-lg-items="4:24" data-xlg-items="4:24" data-rewind="1" data-autoplay="0" data-loop="0" data-lazyload="0"
        data-mousedrag="1" data-touchdrag="1" data-smartspeed="100" data-autoplayspeed="4000" data-dots="0" data-nav="1"
        data-navtext="
		<svg fill='rgba(51, 51, 51, 0.5)' width='20px' height='20px' viewBox='-78.5 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>left</title><path d='M257 64L291 98 128 262 291 426 257 460 61 262 257 64Z' /></svg>
		|
		<svg fill='rgba(51, 51, 51, 0.5)' width='20px' height='20px' viewBox='-77 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>right</title><path d='M98 460L64 426 227 262 64 98 98 64 294 262 98 460Z' /></svg>"
        data-navcontainer=".control-prodcthome">
        <?php foreach($items as $v) { $discountPercent = (!empty($v['sale_price']) && $v['regular_price'] > $v['sale_price']) ? round((($v['regular_price'] - $v['sale_price']) / $v['regular_price']) * 100) : 0; ?>
        <div>
            <a class="product-item" href="product/<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
                <div class="img" style="position:relative;">
                    <?php if($discountPercent > 0) { ?>
                    <span class="badge-discount">-<?=$discountPercent?>%</span>
                    <?php } ?>
                    <?= $func->getImage(['class' => 'lazy w-100', 'size-error' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
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
            <div class="quick-cart-btn" style="display:flex;gap:6px;padding:0 10px 12px;">
                <a href="javascript:void(0)" class="addcart" data-id="<?=$v['id']?>" data-action="buynow" title="Mua ngay" style="flex:1;text-align:center;padding:8px 4px;font-size:13px;font-weight:700;border-radius:4px;background:var(--bg_button);color:#fff;">Mua ngay</a>
                <a href="javascript:void(0)" class="addcart" data-id="<?=$v['id']?>" data-action="addnow" title="Thêm vào giỏ hàng" style="flex:1;text-align:center;padding:8px 4px;font-size:13px;font-weight:700;border-radius:4px;border:1px solid var(--color-primary);color:var(--color-primary);">Thêm giỏ</a>
            </div>
        </div>
        <?php } ?>
    </div>
    <div class="control-prodcthome control-owl transition"></div>
</div>
<div class="btn-view-all text-center">
    <a class="" href="danh-muc-san-pham/<?=$productList[$sluglang]?>/" title="">Xem tất cả<span class="link_arrow lnr lnr-arrow-right"></span></a>
</div>
<?php } ?>