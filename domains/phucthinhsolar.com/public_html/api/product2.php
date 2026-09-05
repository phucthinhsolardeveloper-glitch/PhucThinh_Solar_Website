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
	$sql = "select name$lang, slugvi, slugen, id, id_list, id_cat, id_brand, photo, photo_khuyenmai, regular_price, sale_price, discount, type, status from #_product where type='san-pham' $where and find_in_set('hienthi',status)".publicProductDefaultOrder();
	$sqlCache = $sql." limit $start, $pagingAjax->perpage";
    $items = $cache->get($sqlCache, $params, 'result', 7200);

	/* Count all data */
	$countItems = count($cache->get($sql, $params, 'result', 7200));

	/* Get page result */
	$pagingItems = $pagingAjax->getAllPageLinks($countItems, $pageLink, $eShow);
?>
<?php if($countItems) { ?>
<div class="btn-view-all text-center pts-viewall-top">
    <a class="" href="danh-muc-san-pham/<?=$productList[$sluglang]?>/" title="">Xem tất cả<span class="link_arrow lnr lnr-arrow-right"></span></a>
</div>
<div class="position-relative owl-btn">
    <div class="owl-page owl-carousel owl-theme" data-xsm-items="2:15" data-sm-items="2:15" data-md-items="3:20"
        data-lg-items="4:24" data-xlg-items="4:24" data-rewind="1" data-autoplay="0" data-loop="0" data-lazyload="0"
        data-mousedrag="1" data-touchdrag="1" data-smartspeed="100" data-autoplayspeed="4000" data-dots="0" data-nav="1"
        data-navtext="
		<svg fill='rgba(51, 51, 51, 0.5)' width='20px' height='20px' viewBox='-78.5 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>left</title><path d='M257 64L291 98 128 262 291 426 257 460 61 262 257 64Z' /></svg>
		|
		<svg fill='rgba(51, 51, 51, 0.5)' width='20px' height='20px' viewBox='-77 0 512 512' xmlns='http://www.w3.org/2000/svg' ><title>right</title><path d='M98 460L64 426 227 262 64 98 98 64 294 262 98 460Z' /></svg>"
        data-navcontainer=".control-prodcthome">
        <?php $pcNoLazy = true; /* card nằm trong Owl carousel - xem chú thích ở partial */ ?>
        <?php foreach($items as $v) { include ROOT."templates/product/_product_card.php"; } ?>
    </div>
    <div class="control-prodcthome control-owl transition"></div>
</div>
<?php } ?>
