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
	$pageLink = "api/product3.php?perpage=".$pagingAjax->perpage;
	$tempLink = "";
	$where = "";
	$params = array();
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
<div class="grid-product">
    <?php foreach($items as $v) { ?>
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
<div class="pagination-ajax"><?=$pagingItems?></div>
<?php } ?>