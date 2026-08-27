<?php 
	include "config.php";

	/* Paginations */
	include LIBRARIES."class/class.PaginationsAjax.php";
	$pagingAjax = new PaginationsAjax();
	$pagingAjax->perpage = (!empty($_GET['perpage'])) ? htmlspecialchars($_GET['perpage']) : 18;
	$eShow = htmlspecialchars($_GET['eShow']);
	$idList = (isset($_GET['id_list']) && $_GET['id_list'] != '') ? htmlspecialchars($_GET['id_list']) : 0;
	$idCat = (isset($_GET['id_cat']) && $_GET['id_cat'] != '') ? htmlspecialchars($_GET['id_cat']) : 0;
	$idBrand = (isset($_GET['is_brand']) && $_GET['is_brand'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_brand'])) ? $_GET['is_brand'] : '';
	$idXuatXu = (isset($_GET['is_origin']) && $_GET['is_origin'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_origin'])) ? $_GET['is_origin'] : '';
	$orderby = (isset($_GET['is_orderby']) && $_GET['is_orderby'] != '') ? htmlspecialchars($_GET['is_orderby']) : '';
    $gia = (isset($_GET['is_price']) && $_GET['is_price'] != '') ? htmlspecialchars($_GET['is_price']) : '';
    $idCongSuat = (isset($_GET['is_congsuat']) && $_GET['is_congsuat'] != '') ? htmlspecialchars($_GET['is_congsuat']) : '';
	$idType = 'dich-vu';
	$p = (isset($_GET['p']) && $_GET['p'] > 0) ? htmlspecialchars($_GET['p']) : 1;
	$start = ($p-1) * $pagingAjax->perpage;
	$pageLink = "api/fillter_service.php?perpage=".$pagingAjax->perpage;
	$tempLink = "";
	$where = "";
	$order = " order by numb,id desc";
	$params = array();

	/* Math url */
	if($idList > 0)
	{
		$tempLink .= "&id_list=".$idList;
		$where .= " and id_list = ".$idList;
	}
	if($idCat > 0)
	{
		$tempLink .= "&id_cat=".$idCat;
		$where .= " and id_cat = ".$idCat;
	}
	if($idBrand !="")
	{
		$tempLink .= "&is_brand=".$idBrand;
		$where .= " and id_brand in (".$idBrand.")";
	}

	if($idXuatXu !="")
	{
		$tempLink .= "&is_origin=".$idXuatXu;
		$where .= " and id_xuatxu in (".$idXuatXu.")";
	}
	if($idCongSuat !="")
	{
		$tempLink .= "&is_congsuat=".$idCongSuat;
		$congSuatArr = array_values(array_filter(array_map('floatval', explode(',', $idCongSuat))));
		if(!empty($congSuatArr))
		{
			$where .= " and cong_suat in (".implode(',', array_fill(0, count($congSuatArr), '?')).")";
			$params = array_merge($params, $congSuatArr);
		}
	}
	if($gia !="")
	{
		$tempLink .= "&is_price=".$gia;
		$priceRanges = array(
			'1' => array(0, 500000),
			'2' => array(500000, 1000000),
			'3' => array(1000000, 2000000),
			'4' => array(2000000, null),
		);
		$priceConds = array();
		foreach(explode(',', $gia) as $pb)
		{
			if(!isset($priceRanges[$pb])) continue;
			list($min, $max) = $priceRanges[$pb];
			if($max === null)
			{
				$priceConds[] = "(if(sale_price>0,sale_price,regular_price) >= ?)";
				$params[] = $min;
			}
			else
			{
				$priceConds[] = "(if(sale_price>0,sale_price,regular_price) >= ? and if(sale_price>0,sale_price,regular_price) < ?)";
				$params[] = $min;
				$params[] = $max;
			}
		}
		if(!empty($priceConds))
		{
			$where .= " and (".implode(' or ', $priceConds).")";
		}
	}
	if($orderby !="")
	{
		$tempLink .= "&is_orderby=".$orderby;
		switch ($orderby) {
			case 'abc':
				$order = " order by namevi asc"; // hoặc theo ngày tạo: ngaytao desc
				break;

			case 'abc-desc':
				$order = " order by namevi desc";
				break;

			case 'price':
				$order = " order by regular_price asc";
				break;

			case 'price-desc':
				$order = " order by regular_price desc";
				break;

			case 'new':
				$order = " order by date_created desc";
				break;

			case 'new-desc':
				$order = " order by date_created asc";
				break;

			default:
				$order = "";
				break;
		}
	}

	$tempLink .= "&p=";
	$pageLink .= $tempLink;

	/* Get data */
	$sql = "select * from #_product where type='san-pham' $where and find_in_set('hienthi',status) $order";
	$sqlCache = $sql." limit $start, $pagingAjax->perpage";
    $items = $cache->get($sqlCache, $params, 'result', 7200);

	/* Count all data */
	$countItems = count($cache->get($sql, $params, 'result', 7200));

	/* Get page result */
	$pagingItems = $pagingAjax->getAllPageLinks($countItems, $pageLink, $eShow);
?>
<?php if(count($items)){ ?>
<div class="grid-product grid-product-3" id="search-body">
    <?php foreach($items as $k => $v) { $discountPercent = (!empty($v['sale_price']) && $v['regular_price'] > $v['sale_price']) ? round((($v['regular_price'] - $v['sale_price']) / $v['regular_price']) * 100) : 0; ?>
    <div class="product-card">
        <a class="product-item" href="product/<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
            <div class="product-card-img">
                <?php if($discountPercent > 0) { ?>
                <span class="badge-discount">-<?=$discountPercent?>%</span>
                <?php } ?>
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
        <div class="product-card-btns">
            <a href="javascript:void(0)" class="addcart btn-gold" data-id="<?=$v['id']?>" data-action="buynow" title="Mua ngay">Mua ngay</a>
            <a href="javascript:void(0)" class="addcart btn-navy-outline" data-id="<?=$v['id']?>" data-action="addnow" title="Thêm vào giỏ hàng">Thêm giỏ</a>
        </div>
    </div>
    <?php } ?>
</div>
<div class="pagination-ajax"><?=$pagingItems?></div>
<?php }else{ ?>
<div class="alert alert-warning w-100" role="alert">
    <strong><?=khongtimthayketqua?></strong>
</div>
<?php } ?>