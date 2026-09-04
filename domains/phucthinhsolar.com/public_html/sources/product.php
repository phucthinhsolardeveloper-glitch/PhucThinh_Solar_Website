<?php  
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_projects.php';

	@$id = htmlspecialchars($_GET['id']);
	@$idl = htmlspecialchars($_GET['idl']);
	@$idc = htmlspecialchars($_GET['idc']);
	@$idi = htmlspecialchars($_GET['idi']);
	@$ids = htmlspecialchars($_GET['ids']);
	@$idb = htmlspecialchars($_GET['idb']);
	$orderby = (isset($_GET['is_orderby']) && $_GET['is_orderby'] != '') ? htmlspecialchars($_GET['is_orderby']) : '';
	$order_by = ' order by numb,id desc';
	$productListHasItems = array();
	if($template === 'product/product_list')
	{
		$productListRows = $cache->get("select distinct id_list from #_product where type = ? and find_in_set('hienthi',status)", array($type), 'result', 7200);
		foreach($productListRows as $productListRow) $productListHasItems[(int)$productListRow['id_list']] = true;
	}
	if($orderby !="")
	{
		switch ($orderby) {
			case 'abc':
				$order_by = " order by namevi asc"; // hoặc theo ngày tạo: ngaytao desc
				break;

			case 'abc-desc':
				$order_by = " order by namevi desc";
				break;

			case 'price':
				if(!publicProductPricesVisible()) break;
				$order_by = " order by regular_price asc";
				break;

			case 'price-desc':
				if(!publicProductPricesVisible()) break;
				$order_by = " order by regular_price desc";
				break;

			case 'new':
				$order_by = " order by date_created desc";
				break;

			case 'new-desc':
				$order_by = " order by date_created asc";
				break;

			default:
				$order_by = "";
				break;
		}
	}

	/* Lọc công suất + mức giá (mục 2 checklist ads) - dùng chung cho các khối liệt kê sản phẩm */
	function productExtraFilters()
	{
		$extraWhere = '';
		$extraParams = array();

		/* Công suất (W) */
		$idCongSuat = (isset($_GET['is_congsuat']) && $_GET['is_congsuat'] != '') ? $_GET['is_congsuat'] : '';
		if($idCongSuat != '')
		{
			$congSuatArr = array_values(array_filter(array_map('floatval', explode(',', $idCongSuat))));
			if(!empty($congSuatArr))
			{
				$extraWhere .= " and cong_suat in (".implode(',', array_fill(0, count($congSuatArr), '?')).")";
				$extraParams = array_merge($extraParams, $congSuatArr);
			}
		}

		/* Loại sản phẩm (danh mục con) */
		$idCatFilter = (isset($_GET['is_cat']) && $_GET['is_cat'] != '') ? $_GET['is_cat'] : '';
		if($idCatFilter != '')
		{
			$catArr = array_values(array_filter(array_map('intval', explode(',', $idCatFilter))));
			if(!empty($catArr))
			{
				$extraWhere .= " and id_cat in (".implode(',', array_fill(0, count($catArr), '?')).")";
				$extraParams = array_merge($extraParams, $catArr);
			}
		}

		/* Mức giá (áp theo sale_price nếu có, ngược lại regular_price) */
		$idPrice = publicProductPricesVisible() && isset($_GET['is_price']) && $_GET['is_price'] != '' ? $_GET['is_price'] : '';
		if($idPrice != '')
		{
			$priceRanges = array(
				'1' => array(0, 500000),
				'2' => array(500000, 1000000),
				'3' => array(1000000, 2000000),
				'4' => array(2000000, null),
			);
			$priceConds = array();
			foreach(explode(',', $idPrice) as $pb)
			{
				if(!isset($priceRanges[$pb])) continue;
				list($min, $max) = $priceRanges[$pb];
				if($max === null)
				{
					$priceConds[] = "(if(sale_price>0,sale_price,regular_price) >= ?)";
					$extraParams[] = $min;
				}
				else
				{
					$priceConds[] = "(if(sale_price>0,sale_price,regular_price) >= ? and if(sale_price>0,sale_price,regular_price) < ?)";
					$extraParams[] = $min;
					$extraParams[] = $max;
				}
			}
			if(!empty($priceConds))
			{
				$extraWhere .= " and not find_in_set('baogia',status) and (".implode(' or ', $priceConds).")";
			}
		}

		return array($extraWhere, $extraParams);
	}

	if($id!='')
	{
		/* Lấy sản phẩm detail */
		$rowDetail = $d->rawQueryOne("select * from #_product where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($id,$type));

		/* Cập nhật lượt xem */
		$views = array();
		$views['view'] = $rowDetail['view'] + 1;
		$d->where('id',$rowDetail['id']);
		$d->update('product',$views);

        /* Lấy tags */
		$productTags = $d->rawQuery("select id_tags from #_product_tags where id_parent = ?", array($rowDetail['id']));
		$productTags = (!empty($productTags)) ? $func->joinCols($productTags, 'id_tags') : array();

		if(!empty($productTags))
		{
			$rowTags = $d->rawQuery("select id, name$lang, slugvi, slugen from #_tags where type='".$type."' and id in ($productTags) and find_in_set('hienthi',status) order by numb,id desc");
		}

		/* Lấy màu */
		$productColor = $d->rawQuery("select id_color from #_product_sale where id_parent = ?", array($rowDetail['id']));
		$productColor = (!empty($productColor)) ? $func->joinCols($productColor, 'id_color') : array();

		if(!empty($productColor))
		{
			$rowColor = $d->rawQuery("select type_show, photo, color, id from #_color where type='".$type."' and id in ($productColor) and find_in_set('hienthi',status) order by numb,id desc");
		}

		/* Lấy size */
		$productSize = $d->rawQuery("select id_size from #_product_sale where id_parent = ?", array($rowDetail['id']));
		$productSize = (!empty($productSize)) ? $func->joinCols($productSize, 'id_size') : array();

		if(!empty($productSize))
		{
			$rowSize = $d->rawQuery("select id, name$lang from #_size where type='".$type."' and id in ($productSize) and find_in_set('hienthi',status) order by numb,id desc");
		}

		/* Lấy cấp 1 */
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_list where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_list'],$type));

		/* Lấy cấp 2 */
		$productCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_cat where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_cat'],$type));

		/* Lấy cấp 3 */
		$productItem = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_item where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_item'],$type));

		/* Lấy cấp 4 */
		$productSub = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_sub where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_sub'],$type));
		
		/* Lấy thương hiệu */
		$productBrand = $d->rawQueryOne("select name$lang, slugvi, slugen, id from #_product_brand where id = ? and type = ? and find_in_set('hienthi',status)",array($rowDetail['id_brand'],$type));

		/* Lấy thương hiệu */
		// $productXuatXu = $d->rawQueryOne("select name$lang, slugvi, slugen, id from #_product_xuatxu where id = ? and type = ? and find_in_set('hienthi',status)",array($rowDetail['id_xuatxu'],$type));
		$tc = $cache->get("select name$lang, address, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('tieu-chi'), 'result', 7200);
		$ck = $cache->get("select name$lang, address, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('cam-ket'), 'result', 7200);
		/* Lấy hình ảnh con */
		$rowDetailPhoto = $d->rawQuery("select photo from #_gallery where id_parent = ? and com='product' and type = ? and kind='man' and val = ? and find_in_set('hienthi',status) order by numb,id desc",array($rowDetail['id'],$type,$type));
		$productRelatedProjects = $d->rawQuery("select n.id, n.name$lang, n.slugvi, n.slugen, n.photo, n.address from #_product_project pp inner join #_news n on n.id = pp.id_project where pp.id_product = ? and find_in_set('hienthi',pp.status) and n.type = ? and find_in_set('hienthi',n.status) order by pp.numb,pp.id", array($rowDetail['id'], 'du-an'));
		$productProjectGallery = array();
		if($productRelatedProjects)
		{
			$projectIds = array_map(function($project) { return (int)$project['id']; }, $productRelatedProjects);
			$projectPlaceholders = implode(',', array_fill(0, count($projectIds), '?'));
			$galleryRows = $d->rawQuery("select id_parent, photo from #_gallery where id_parent in ($projectPlaceholders) and com = ? and type = ? and kind = ? and val = ? and find_in_set('hienthi',status) order by numb,id desc", array_merge($projectIds, array('news', 'du-an', 'man', 'du-an')));
			foreach($galleryRows as $galleryRow) $productProjectGallery[(int)$galleryRow['id_parent']][] = $galleryRow['photo'];
		}

		/* Lấy sản phẩm cùng loại */
		$where = "";
		$where = "id <> ? and id_list = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($id,$rowDetail['id_list'],$type);

		$curPage = $getPage;
		$perPage = 15;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* Comment */
		$comment = new Comments($d, $func, $rowDetail['id'], $rowDetail['type']);

		/* SEO */
		$seoDB = $seo->getOnDB($rowDetail['id'],'product','man',$rowDetail['type']);
		$seo->set('h1',$rowDetail['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$rowDetail['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($rowDetail['options'])) ? json_decode($rowDetail['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $rowDetail['photo']))
		{
			$imgJson = $func->getImgSize($rowDetail['photo'],UPLOAD_PRODUCT_L.$rowDetail['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product',$rowDetail['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$rowDetail['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($productList)) $breadcr->set($productList[$sluglang],$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set($productCat[$sluglang],$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set($productItem[$sluglang],$productItem['name'.$lang]);
		if(!empty($productSub)) $breadcr->set($productSub[$sluglang],$productSub['name'.$lang]);
		$breadcr->set($rowDetail[$sluglang],$rowDetail['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idl!='')
	{
		/* Lấy cấp 1 detail */
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen, type, photo, options from #_product_list where id = ? and type = ? limit 0,1",array($idl,$type));
		$slug_param = $productList[$sluglang];
		$id_list = $productList['id'];
		/* SEO */
		$titleCate = $productList['name'.$lang];
		$seoDB = $seo->getOnDB($productList['id'],'product','man_list',$productList['type']);
		$seo->set('h1',$productList['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$productList['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($productList['options'])) ? json_decode($productList['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $productList['photo']))
		{
			$imgJson = $func->getImgSize($productList['photo'],UPLOAD_PRODUCT_L.$productList['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product_list',$productList['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$productList['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* Lấy sản phẩm */
		$where = "";
		$where = "id_list = ? and type = ? and find_in_set('hienthi',status)";
		$idBrand = (isset($_GET['is_brand']) && $_GET['is_brand'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_brand'])) ? $_GET['is_brand'] : '';
		$idXuatXu = (isset($_GET['is_origin']) && $_GET['is_origin'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_origin'])) ? $_GET['is_origin'] : '';
		if($idBrand !="")
		{
			$where .= " and id_brand in (".$idBrand.")";
		}
		if($idXuatXu !="")
		{
			$where .= " and id_xuatxu in (".$idXuatXu.")";
		}
		list($extraWhere, $extraParams) = productExtraFilters();
		$where .= $extraWhere;

		$params = array($idl,$type);
		$params = array_merge($params, $extraParams);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($productList)) $breadcr->set($productList[$sluglang],$productList['name'.$lang]);
		$breadcrumbs = $breadcr->get();	
	}
	else if($idc!='')
	{
		/* Lấy cấp 2 detail */
		$productCat = $d->rawQueryOne("select id, id_list, name$lang, slugvi, slugen, type, photo, options from #_product_cat where id = ? and type = ? limit 0,1",array($idc,$type));

		/* Lấy cấp 1 */
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_list where id = ? and type = ? limit 0,1",array($productCat['id_list'],$type));
		$slug_param = $productCat[$sluglang];
		$id_cat = $productCat['id'];
		/* Lấy sản phẩm */
		$where = "";
		$where = "id_cat = ? and type = ? and find_in_set('hienthi',status)";
		$idBrand = (isset($_GET['is_brand']) && $_GET['is_brand'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_brand'])) ? $_GET['is_brand'] : '';
		$idXuatXu = (isset($_GET['is_origin']) && $_GET['is_origin'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_origin'])) ? $_GET['is_origin'] : '';
		if($idBrand !="")
		{
			$where .= " and id_brand in (".$idBrand.")";
		}
		if($idXuatXu !="")
		{
			$where .= " and id_xuatxu in (".$idXuatXu.")";
		}
		list($extraWhere, $extraParams) = productExtraFilters();
		$where .= $extraWhere;

		$params = array($idc,$type);
		$params = array_merge($params, $extraParams);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $productCat['name'.$lang];
		$seoDB = $seo->getOnDB($productCat['id'],'product','man_cat',$productCat['type']);
		$seo->set('h1',$productCat['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$productCat['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($productCat['options'])) ? json_decode($productCat['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $productCat['photo']))
		{
			$imgJson = $func->getImgSize($productCat['photo'],UPLOAD_PRODUCT_L.$productCat['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product_cat',$productCat['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$productCat['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($productList)) $breadcr->set($productList[$sluglang],$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set($productCat[$sluglang],$productCat['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idi!='')
	{
		/* Lấy cấp 3 detail */
		$productItem = $d->rawQueryOne("select id, id_list, id_cat, name$lang, slugvi, slugen, type, photo, options from #_product_item where id = ? and type = ? limit 0,1",array($idi,$type));

		/* Lấy cấp 1 */
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_list where id = ? and type = ? limit 0,1",array($productItem['id_list'],$type));

		/* Lấy cấp 2 */
		$productCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_cat where id_list = ? and id = ? and type = ? limit 0,1",array($productItem['id_list'],$productItem['id_cat'],$type));

		/* Lấy sản phẩm */
		$where = "";
		$where = "id_item = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($idi,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $productItem['name'.$lang];
		$seoDB = $seo->getOnDB($productItem['id'],'product','man_item',$productItem['type']);
		$seo->set('h1',$productItem['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$productItem['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($productItem['options'])) ? json_decode($productItem['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $productItem['photo']))
		{
			$imgJson = $func->getImgSize($productItem['photo'],UPLOAD_PRODUCT_L.$productItem['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product_item',$productItem['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$productItem['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($productList)) $breadcr->set($productList[$sluglang],$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set($productCat[$sluglang],$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set($productItem[$sluglang],$productItem['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($ids!='')
	{
		/* Lấy cấp 4 */
		$productSub = $d->rawQueryOne("select id, id_list, id_cat, id_item, name$lang, slugvi, slugen, type, photo, options from #_product_sub where id = ? and type = ? limit 0,1",array($ids,$type));

		/* Lấy cấp 1 */
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_list where id = ? and type = ? limit 0,1",array($productSub['id_list'],$type));

		/* Lấy cấp 2 */
		$productCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_cat where id_list = ? and id = ? and type = ? limit 0,1",array($productSub['id_list'],$productSub['id_cat'],$type));

		/* Lấy cấp 3 */
		$productItem = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_product_item where id_list = ? and id_cat = ? and id = ? and type = ? limit 0,1",array($productSub['id_list'],$productSub['id_cat'],$productSub['id_item'],$type));

		/* Lấy sản phẩm */
		$where = "";
		$where = "id_sub = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($ids,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $productSub['name'.$lang];
		$seoDB = $seo->getOnDB($productSub['id'],'product','man_sub',$productSub['type']);
		$seo->set('h1',$productSub['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$productSub['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($productSub['options'])) ? json_decode($productSub['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $productSub['photo']))
		{
			$imgJson = $func->getImgSize($productSub['photo'],UPLOAD_PRODUCT_L.$productSub['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product_sub',$productSub['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$productSub['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($productList)) $breadcr->set($productList[$sluglang],$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set($productCat[$sluglang],$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set($productItem[$sluglang],$productItem['name'.$lang]);
		if(!empty($productSub)) $breadcr->set($productSub[$sluglang],$productSub['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idb!='')
	{
		/* Lấy brand detail */
		$productBrand = $d->rawQueryOne("select name$lang, slugvi, slugen, id, type, photo, options from #_product_brand where id = ? and type = ? limit 0,1",array($idb,$type));

		/* SEO */
		$titleCate = $productBrand['name'.$lang];
		$seoDB = $seo->getOnDB($productBrand['id'],'product','man_brand',$productBrand['type']);
		$seo->set('h1',$productBrand['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$productBrand['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($productBrand['options'])) ? json_decode($productBrand['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $productBrand['photo']))
		{
			$imgJson = $func->getImgSize($productBrand['photo'],UPLOAD_PRODUCT_L.$productBrand['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product_brand',$productBrand['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$productBrand['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* Lấy sản phẩm */
		$where = "";
		$where = "id_brand = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($productBrand['id'],$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* breadCrumbs */
		$breadcr->set($productBrand[$sluglang],$titleCate);
		$breadcrumbs = $breadcr->get();
	}
	else
	{
		/* SEO */
		$seopage = $d->rawQueryOne("select * from #_seopage where type = ? limit 0,1",array($type));
		$seo->set('h1',$titleMain);
		if(!empty($seopage['title'.$seolang])) $seo->set('title',$seopage['title'.$seolang]);
		else $seo->set('title',$titleMain);
		if(!empty($seopage['keywords'.$seolang])) $seo->set('keywords',$seopage['keywords'.$seolang]);
		if(!empty($seopage['description'.$seolang])) $seo->set('description',$seopage['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($seopage['options'])) ? json_decode($seopage['options'],true) : null;
		if(!empty($seopage['photo']))
		{
			if(empty($imgJson) || ($imgJson['p'] != $seopage['photo']))
			{
				$imgJson = $func->getImgSize($seopage['photo'],UPLOAD_SEOPAGE_L.$seopage['photo']);
				$seo->updateSeoDB(json_encode($imgJson),'seopage',$seopage['id']);
			}
			if(!empty($imgJson))
			{
				$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_SEOPAGE_L.$seopage['photo']);
				$seo->set('photo:width',$imgJson['w']);
				$seo->set('photo:height',$imgJson['h']);
				$seo->set('photo:type',$imgJson['m']);
			}
		}

		/* Lấy tất cả sản phẩm */
		$where = "";
		$where = "type = ? and find_in_set('hienthi',status)";

		$idBrand = (isset($_GET['is_brand']) && $_GET['is_brand'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_brand'])) ? $_GET['is_brand'] : '';
		$idXuatXu = (isset($_GET['is_origin']) && $_GET['is_origin'] != '' && preg_match('/^[0-9,]+$/',$_GET['is_origin'])) ? $_GET['is_origin'] : '';
		if($idBrand !="")
		{
			$where .= " and id_brand in (".$idBrand.")";
		}
		if($idXuatXu !="")
		{
			$where .= " and id_xuatxu in (".$idXuatXu.")";
		}
		list($extraWhere, $extraParams) = productExtraFilters();
		$where .= $extraWhere;

		$params = array($type);
		$params = array_merge($params, $extraParams);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product where $where $order_by $limit";
		$product = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product where $where $order_by";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		$breadcrumbs = $breadcr->get();
	}
?>
