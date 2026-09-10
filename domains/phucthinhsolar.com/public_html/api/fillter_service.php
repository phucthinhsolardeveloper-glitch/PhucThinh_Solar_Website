<?php 
	include "config.php";

	/* Paginations */
	include LIBRARIES."class/class.PaginationsAjax.php";
	$pagingAjax = new PaginationsAjax();
	/* Mặc định khớp sources/product.php ($perPage=16) để số trang không lệch
	   giữa lần tải trang đầu và các lần lọc AJAX sau đó (audit 2026-09-10). */
	$pagingAjax->perpage = isset($_GET['perpage']) ? max(1, min(48, (int) $_GET['perpage'])) : 16;
	$eShow = isset($_GET['eShow']) ? max(1, min(10, (int) $_GET['eShow'])) : 5;
	$idList = isset($_GET['id_list']) ? max(0, (int) $_GET['id_list']) : 0;
	$idCat = isset($_GET['id_cat']) ? max(0, (int) $_GET['id_cat']) : 0;
	$idBrand = (isset($_GET['is_brand']) && $_GET['is_brand'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_brand'])) ? $_GET['is_brand'] : '';
	$idXuatXu = (isset($_GET['is_origin']) && $_GET['is_origin'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_origin'])) ? $_GET['is_origin'] : '';
	$orderby = (isset($_GET['is_orderby']) && $_GET['is_orderby'] != '') ? htmlspecialchars($_GET['is_orderby']) : '';
    $gia = (publicProductPricesVisible() && isset($_GET['is_price']) && $_GET['is_price'] != '') ? htmlspecialchars($_GET['is_price']) : '';
    $idCongSuat = (isset($_GET['is_congsuat']) && $_GET['is_congsuat'] != '') ? htmlspecialchars($_GET['is_congsuat']) : '';
    $idCatFilter = (isset($_GET['is_cat']) && $_GET['is_cat'] != '') ? htmlspecialchars($_GET['is_cat']) : '';
	$keywordRaw = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : '';
	$keyword = ($keywordRaw !== '' && strlen($keywordRaw) <= 100) ? $func->changeTitle($keywordRaw) : '';
	$idType = 'dich-vu';
	$p = (isset($_GET['p']) && (int) $_GET['p'] > 0) ? (int) $_GET['p'] : 1;
	$start = ($p-1) * $pagingAjax->perpage;
	$pageLink = "api/fillter_service.php?perpage=".$pagingAjax->perpage;
	$tempLink = "";
	$where = "";
	$defaultProductOrder = publicProductDefaultOrder();
	$order = $defaultProductOrder;
	$params = array();

	/* Math url */
	if($idList > 0)
	{
		$tempLink .= "&id_list=".$idList;
		$where .= " and id_list = ?";
		$params[] = $idList;
	}
	if($idCat > 0)
	{
		$tempLink .= "&id_cat=".$idCat;
		$where .= " and id_cat = ?";
		$params[] = $idCat;
	}
	if($keyword !== '')
	{
		$tempLink .= "&keyword=".rawurlencode($keywordRaw);
		$where .= " and (name$lang like ? or code like ? or slugvi like ? or slugen like ?)";
		$keywordLike = "%".$keyword."%";
		$params = array_merge($params, array($keywordLike, $keywordLike, $keywordLike, $keywordLike));
	}
	if($idBrand !="")
	{
		$tempLink .= "&is_brand=".$idBrand;
		$brandIds = array_values(array_filter(array_map('intval', explode(',', $idBrand))));
		if(!empty($brandIds)) { $where .= " and id_brand in (".implode(',', array_fill(0, count($brandIds), '?')).")"; $params = array_merge($params, $brandIds); }
	}

	if($idXuatXu !="")
	{
		$tempLink .= "&is_origin=".$idXuatXu;
		$originIds = array_values(array_filter(array_map('intval', explode(',', $idXuatXu))));
		if(!empty($originIds)) { $where .= " and id_xuatxu in (".implode(',', array_fill(0, count($originIds), '?')).")"; $params = array_merge($params, $originIds); }
	}
	if($idCongSuat !="")
	{
		$tempLink .= "&is_congsuat=".$idCongSuat;
		list($powerWhere, $powerParams) = productPowerFilterSql($idCongSuat);
		$where .= $powerWhere;
		$params = array_merge($params, $powerParams);
	}
	if($idCatFilter !="")
	{
		$tempLink .= "&is_cat=".$idCatFilter;
		$catFilterArr = array_values(array_filter(array_map('intval', explode(',', $idCatFilter))));
		if(!empty($catFilterArr))
		{
			$where .= " and id_cat in (".implode(',', array_fill(0, count($catFilterArr), '?')).")";
			$params = array_merge($params, $catFilterArr);
		}
	}
	if($gia !="")
	{
		$tempLink .= "&is_price=".$gia;
		list($priceWhere, $priceParams) = productPriceFilterSql($gia);
		$where .= $priceWhere;
		$params = array_merge($params, $priceParams);
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
				if(!publicProductPricesVisible()) break;
				$order = " order by regular_price asc";
				break;

			case 'price-desc':
				if(!publicProductPricesVisible()) break;
				$order = " order by regular_price desc";
				break;

			case 'new':
				$order = " order by date_created desc";
				break;

			case 'new-desc':
				$order = " order by date_created asc";
				break;

			default:
				$order = $defaultProductOrder;
				break;
		}
	}

	$tempLink .= "&p=";
	$pageLink .= $tempLink;

	/* Get data */
	$sql = "select * from #_product where type='san-pham' $where and find_in_set('hienthi',status) $order";
	$sqlCache = $sql." limit ".(int) $start.",".(int) $pagingAjax->perpage;
    $items = $cache->get($sqlCache, $params, 'result', 7200);

	/* Count all data */
	$countItems = count($cache->get($sql, $params, 'result', 7200));

	/* Get page result */
	$pagingItems = $pagingAjax->getAllPageLinks($countItems, $pageLink, $eShow);
?>
<?php if(count($items)){ ?>
<div class="grid-product grid-product-3" id="search-body">
    <?php foreach($items as $k => $v) { include ROOT."templates/product/_product_card.php"; } ?>
</div>
<div class="pagination-ajax"><?=$pagingItems?></div>
<?php }else{ ?>
<div class="alert alert-warning w-100" role="alert">
    <strong><?=khongtimthayketqua?></strong>
</div>
<?php } ?>
