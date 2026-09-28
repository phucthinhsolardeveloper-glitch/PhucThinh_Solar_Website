<?php  
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_projects.php';
	require_once LIBRARIES.'product_power_filter.php';
	require_once LIBRARIES.'product_price_filter.php';
	require_once LIBRARIES.'product_need_filter.php';
	require_once LIBRARIES.'product_sort.php';
	require_once LIBRARIES.'product_content_blocks.php';

	@$id = htmlspecialchars($_GET['id']);
	@$idl = htmlspecialchars($_GET['idl']);
	@$idc = htmlspecialchars($_GET['idc']);
	@$idi = htmlspecialchars($_GET['idi']);
	@$ids = htmlspecialchars($_GET['ids']);
	@$idb = htmlspecialchars($_GET['idb']);
	if($idl !== '' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['is_cat']) && preg_match('/^[1-9][0-9]*$/', (string)$_GET['is_cat']))
	{
		$canonicalCat = $d->rawQueryOne("select pc.id from #_product_cat pc where pc.id = ? and pc.id_list = ? and pc.type = ? and find_in_set('hienthi',pc.status) limit 1", array((int)$_GET['is_cat'],(int)$idl,'san-pham'));
		if(!empty($canonicalCat))
		{
			$canonicalPath = publicProductEntityPath($d, 'product_cat', $canonicalCat['id'], $lang);
			$queryParams = $_GET;
			foreach(array('idl','id_list','idc','id_cat','is_cat') as $canonicalKey) unset($queryParams[$canonicalKey]);
			$queryString = !empty($queryParams) ? '?'.http_build_query($queryParams) : '';
			header('Location: '.$configBase.$canonicalPath.$queryString, true, 301);
			exit;
		}
	}
	$orderby = (isset($_GET['is_orderby']) && $_GET['is_orderby'] != '') ? htmlspecialchars($_GET['is_orderby']) : '';
	$defaultProductOrder = publicProductDefaultOrder();
	$order_by = $defaultProductOrder;
	$productListHasItems = array();
	$productListPreview = array();
	$productListPreviewCount = array();
	if($template === 'product/product_list')
	{
		/* Render 4 card đầu của mỗi danh mục ngay trong HTML. Bản cũ trả về các
		   khối rỗng rồi gọi riêng product3.php sau DOM ready cho từng danh mục. */
		$productListPreviewRows = $cache->get("select name$lang, slugvi, slugen, id, id_list, id_cat, id_brand, photo, photo_khuyenmai, regular_price, sale_price, discount, type, status, cong_suat, waterproof_rating, lighting_time from #_product where type = ? and find_in_set('hienthi',status)".publicProductDefaultOrder(), array($type), 'result', 7200);
		foreach($productListPreviewRows as $productListPreviewRow)
		{
			$productListId = (int)$productListPreviewRow['id_list'];
			$productListHasItems[$productListId] = true;
			$productListPreviewCount[$productListId] = isset($productListPreviewCount[$productListId]) ? $productListPreviewCount[$productListId] + 1 : 1;
			if(empty($productListPreview[$productListId]) || count($productListPreview[$productListId]) < 4) $productListPreview[$productListId][] = $productListPreviewRow;
		}

		/* Banner hình ảnh ngắt nhịp cho catalog dài. Giống home-campaign, truy
		   vấn cả record tắt để Admin có thể ẩn hoàn toàn mà fallback không tự
		   xuất hiện lại. Fallback chỉ dùng khi type chưa từng được cấu hình. */
		$catalogCampaignRows = $cache->get("select name$lang, desc$lang, photo, link, status from #_photo where type = ? order by numb,id desc", array('catalog-campaign'), 'result', 7200);
		$catalogCampaigns = array();
		foreach($catalogCampaignRows as $catalogCampaignRow)
		{
			if(empty($catalogCampaignRow['photo']) || strpos(','.(string)$catalogCampaignRow['status'].',', ',hienthi,') === false) continue;
			$catalogCampaigns[] = array(
				'name' => !empty($catalogCampaignRow['name'.$lang]) ? $catalogCampaignRow['name'.$lang] : 'Giải pháp thiết bị đồng bộ',
				'desc' => !empty($catalogCampaignRow['desc'.$lang]) ? trim(strip_tags(htmlspecialchars_decode($catalogCampaignRow['desc'.$lang], ENT_QUOTES))) : '',
				'photo' => $catalogCampaignRow['photo'],
				'upload' => UPLOAD_PHOTO_L,
				'link' => !empty($catalogCampaignRow['link']) ? $catalogCampaignRow['link'] : 'lien-he/'
			);
			if(count($catalogCampaigns) >= 3) break;
		}
		if(!$catalogCampaignRows)
		{
			$catalogCampaigns = array(
				array(
					'name' => 'Chiếu sáng đúng nhu cầu công trình',
					'desc' => 'Chọn công suất, thời gian sáng và kiểu lắp đặt phù hợp ngay từ đầu.',
					'photo' => 'catalog-street-lighting.jpg',
					'upload' => 'assets/images/site-visuals/',
					'link' => 'giai-phap/'
				),
				array(
					'name' => 'Thiết bị đồng bộ, thông số rõ ràng',
					'desc' => 'Từ tấm pin, bộ đèn đến phụ kiện lắp đặt trong cùng một hệ giải pháp.',
					'photo' => 'catalog-equipment-system.jpg',
					'upload' => 'assets/images/site-visuals/',
					'link' => 'lien-he/'
				)
			);
		}
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
				$order_by = $defaultProductOrder;
				break;
		}
	}

	/* Lọc công suất + mức giá (mục 2 checklist ads) - dùng chung cho các khối liệt kê sản phẩm */
	/* Thu hẹp danh sách thương hiệu (đọc sẵn ở allpage.php, dùng chung toàn
	   site) chỉ còn thương hiệu có sản phẩm hienthi trong đúng phạm vi danh
	   mục đang xem - tránh hiện cố định mọi thương hiệu bất kể trang nào. */
	function productContextualBrands($d, $brand, $scopeWhere, $scopeParams)
	{
		$rows = $d->rawQuery("select distinct id_brand from #_product where $scopeWhere and id_brand > 0", $scopeParams);
		$ids = array();
		foreach((array)$rows as $row) $ids[(int)$row['id_brand']] = true;
		if(!$ids) return array();
		$out = array();
		foreach((array)$brand as $b) if(isset($ids[(int)$b['id']])) $out[] = $b;
		return $out;
	}

	function productExtraFilters()
	{
		$extraWhere = '';
		$extraParams = array();

		/* Công suất (W) */
		$idCongSuat = (isset($_GET['is_congsuat']) && $_GET['is_congsuat'] != '') ? $_GET['is_congsuat'] : '';
		if($idCongSuat != '')
		{
			list($powerWhere, $powerParams) = productPowerFilterSql($idCongSuat);
			$extraWhere .= $powerWhere;
			$extraParams = array_merge($extraParams, $powerParams);
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
			list($priceWhere, $priceParams) = productPriceFilterSql($idPrice);
			$extraWhere .= $priceWhere;
			$extraParams = array_merge($extraParams, $priceParams);
		}

		/* Nhu cầu sử dụng (chỉ nhóm đèn - suy từ tên/mô tả sản phẩm) */
		$idNeed = (isset($_GET['is_need']) && $_GET['is_need'] != '') ? $_GET['is_need'] : '';
		if($idNeed != '')
		{
			list($needWhere, $needParams) = productNeedFilterSql($idNeed);
			$extraWhere .= $needWhere;
			$extraParams = array_merge($extraParams, $needParams);
		}

		return array($extraWhere, $extraParams);
	}

	if($id!='')
	{
		/* PDP tự render H1 tên sản phẩm; ngăn layout SEO thêm H1 ẩn thứ hai. */
		$pageRendersVisibleH1 = true;
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
		$productList = $d->rawQueryOne("select id, name$lang, slugvi, slugen, pdp_profile from #_product_list where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_list'],$type));

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
		$ck = $cache->get("select name$lang, address, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('cam-ket'), 'result', 7200);
		/* Lấy hình ảnh con */
		$rowDetailPhoto = $d->rawQuery("select photo, name$lang as caption from #_gallery where id_parent = ? and com='product' and type = ? and kind='man' and val = ? and find_in_set('hienthi',status) order by numb,id desc",array($rowDetail['id'],$type,$type));

		/* Khối nội dung mô tả: kế thừa sản phẩm > hãng > danh mục */
		require_once LIBRARIES.'product_content_blocks.php';
		$productSections = productResolveSections($d, $rowDetail, $lang);
		$productDocuments = $d->rawQuery("select document_type,title$lang as title,file_path from #_product_document where id_product=? and find_in_set('hienthi',status) and file_path<>'' order by numb,id", array($rowDetail['id']));
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

		/* breadCrumbs: dùng URL phân cấp đầy đủ (khớp canonical), không phải slug
		   rời - xem libraries/product_category_urls.php::publicProductEntityPath() */
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		if(!empty($productList)) $breadcr->set(publicProductEntityPath($d,'product_list',$productList['id'],$lang),$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set(publicProductEntityPath($d,'product_cat',$productCat['id'],$lang),$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set(publicProductEntityPath($d,'product_item',$productItem['id'],$lang),$productItem['name'.$lang]);
		if(!empty($productSub)) $breadcr->set(publicProductEntityPath($d,'product_sub',$productSub['id'],$lang),$productSub['name'.$lang]);
		$breadcr->set(publicProductEntityPath($d,'product',$rowDetail['id'],$lang),$rowDetail['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idl!='')
	{
		/* Lấy cấp 1 detail */
		$productList = $d->rawQueryOne("select id, name$lang, desc$lang, slugvi, slugen, type, photo, options from #_product_list where id = ? and type = ? limit 0,1",array($idl,$type));
		$slug_param = $productList[$sluglang];
		$id_list = $productList['id'];
		$brand = productContextualBrands($d, $brand, "id_list = ? and type = ? and find_in_set('hienthi',status)", array($id_list, $type));
		$categorySectionBlocks = productCategorySectionBlocks($d, 'list', $id_list, $lang);
		$categorySectionSplit = productSplitFaqBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categorySectionSplit['rest'];
		$categoryFaqBlocks = $categorySectionSplit['faq'];
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
		/* 12 -> 16 -> 20 (4 cột x 5 dòng ở desktop) - số cũ luôn dừng ở đúng 3
		   dòng bất kể danh mục có bao nhiêu sản phẩm, khiến lưới ngắn hơn hẳn
		   sidebar filter. Áp dụng đồng bộ cho mọi nhánh danh mục/hãng/tất cả
		   sản phẩm trong file này + api/fillter_service.php (audit 2026-09-10). */
		$perPage = 20;
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
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		if(!empty($productList)) $breadcr->set(publicProductCategoryPath($productList,'list',$lang),$productList['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idc!='')
	{
		/* Lấy cấp 2 detail */
		$productCat = $d->rawQueryOne("select id, id_list, name$lang, desc$lang, slugvi, slugen, type, photo, options from #_product_cat where id = ? and type = ? limit 0,1",array($idc,$type));

		/* Lấy cấp 1 */
		$productList = $d->rawQueryOne("select id, name$lang, desc$lang, slugvi, slugen, photo from #_product_list where id = ? and type = ? limit 0,1",array($productCat['id_list'],$type));
		$slug_param = $productCat[$sluglang];
		$id_cat = $productCat['id'];
		$brand = productContextualBrands($d, $brand, "id_cat = ? and type = ? and find_in_set('hienthi',status)", array($id_cat, $type));
		$categorySectionBlocks = productCategorySectionBlocks($d, 'cat', $id_cat, $lang);
		$categorySectionSplit = productSplitFaqBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categorySectionSplit['rest'];
		$categoryFaqBlocks = $categorySectionSplit['faq'];
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
		$perPage = 20;
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
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		if(!empty($productList)) $breadcr->set(publicProductEntityPath($d,'product_list',$productList['id'],$lang),$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set(publicProductEntityPath($d,'product_cat',$productCat['id'],$lang),$productCat['name'.$lang]);
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
		$brand = productContextualBrands($d, $brand, $where, $params);

		$curPage = $getPage;
		$perPage = 20;
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
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		if(!empty($productList)) $breadcr->set(publicProductEntityPath($d,'product_list',$productList['id'],$lang),$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set(publicProductEntityPath($d,'product_cat',$productCat['id'],$lang),$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set(publicProductEntityPath($d,'product_item',$productItem['id'],$lang),$productItem['name'.$lang]);
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
		$brand = productContextualBrands($d, $brand, $where, $params);

		$curPage = $getPage;
		$perPage = 20;
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
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		if(!empty($productList)) $breadcr->set(publicProductEntityPath($d,'product_list',$productList['id'],$lang),$productList['name'.$lang]);
		if(!empty($productCat)) $breadcr->set(publicProductEntityPath($d,'product_cat',$productCat['id'],$lang),$productCat['name'.$lang]);
		if(!empty($productItem)) $breadcr->set(publicProductEntityPath($d,'product_item',$productItem['id'],$lang),$productItem['name'.$lang]);
		if(!empty($productSub)) $breadcr->set(publicProductEntityPath($d,'product_sub',$productSub['id'],$lang),$productSub['name'.$lang]);
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
		$perPage = 20;
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
		$perPage = 20;
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
		if(!empty($titleMain)) $breadcr->set('san-pham/',$titleMain);
		$breadcrumbs = $breadcr->get();
	}

	/* Dữ liệu điều hướng của landing danh mục. Trang danh mục vẫn giữ toàn bộ
	   URL cấp list/cat hiện có, nhưng trình bày theo một rail "Tất cả + nhóm
	   con" giống catalog mẫu. Mọi nhãn, slug và số lượng đều lấy từ taxonomy
	   đang bật trong Admin; không dựng danh mục giả ở frontend. */
	$productCategoryNav = array();
	$productSubcategoryNav = array();
	$productCategoryDescription = '';
	$productCatalogSupport = array();
	if($id == '')
	{
		/* Landing catalog tự render H1 nhìn thấy trong hero. */
		$pageRendersVisibleH1 = true;
		if(!empty($productCat['desc'.$lang])) $productCategoryDescription = trim(strip_tags(htmlspecialchars_decode($productCat['desc'.$lang], ENT_QUOTES)));
		if($productCategoryDescription === '' && !empty($productList['desc'.$lang])) $productCategoryDescription = trim(strip_tags(htmlspecialchars_decode($productList['desc'.$lang], ENT_QUOTES)));
		if($productCategoryDescription === '' && !empty($seoDB['description'.$seolang])) $productCategoryDescription = trim(strip_tags(htmlspecialchars_decode($seoDB['description'.$seolang], ENT_QUOTES)));
		if($productCategoryDescription === '' && !empty($seopage['description'.$seolang])) $productCategoryDescription = trim(strip_tags(htmlspecialchars_decode($seopage['description'.$seolang], ENT_QUOTES)));
		if(mb_strlen($productCategoryDescription, 'UTF-8') > 210) $productCategoryDescription = rtrim(mb_substr($productCategoryDescription, 0, 207, 'UTF-8')).'…';

		$productCategoryNav = $d->rawQuery("select pl.id, pl.name$lang, pl.desc$lang, pl.slugvi, pl.slugen, pl.photo from #_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array($type, $type));
		foreach($productCategoryNav as &$productCategoryNavItem)
		{
			$productCategoryNavItem['url'] = publicProductCategoryPath($productCategoryNavItem, 'list', $lang);
			$productCategoryNavItem['is_active'] = !empty($productList['id']) && (int)$productList['id'] === (int)$productCategoryNavItem['id'];
		}
		unset($productCategoryNavItem);
		array_unshift($productCategoryNav, array(
			'name'.$lang => 'Tất cả',
			'url' => 'san-pham/',
			'is_active' => empty($productList['id'])
		));

		if(!empty($productList['id']))
		{
			$productSubcategoryNav[] = array(
				'name'.$lang => $productList['name'.$lang],
				'nav_name' => 'Tất cả',
				'url' => publicProductCategoryPath($productList, 'list', $lang),
				'is_active' => empty($idc) && empty($idi) && empty($ids)
			);
			$productSubcategoryRows = $d->rawQuery("select pc.id, pc.name$lang, pc.desc$lang, pc.slugvi, pc.slugen, pc.photo, coalesce(nullif(pc.photo,''),(select pnav.photo from #_product pnav where pnav.id_cat = pc.id and pnav.type = pc.type and find_in_set('hienthi',pnav.status) and pnav.photo <> '' order by pnav.numb,pnav.id desc limit 1)) nav_photo, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id = pc.id_list and pl.type = pc.type where pc.id_list = ? and pc.type = ? and find_in_set('hienthi',pc.status) and exists (select 1 from #_product p where p.id_cat = pc.id and p.type = ? and find_in_set('hienthi',p.status)) order by pc.numb,pc.id desc", array((int)$productList['id'], $type, $type));
			foreach($productSubcategoryRows as $productSubcategoryRow)
			{
				$productSubcategoryRow['url'] = publicProductCategoryPath($productSubcategoryRow, 'cat', $lang);
				$productSubcategoryRow['is_active'] = !empty($productCat['id']) && (int)$productCat['id'] === (int)$productSubcategoryRow['id'];
				$productSubcategoryNav[] = $productSubcategoryRow;
			}
		}

		/* Dải hỗ trợ cuối catalog dùng slot Admin hiện có. Nếu Admin từng cấu
		   hình nhưng tắt toàn bộ thì tôn trọng công tắc và không dựng fallback. */
		$productCatalogSupportRows = $cache->get("select name$lang, desc$lang, photo, link, status from #_photo where type = ? order by numb,id desc", array('catalog-campaign'), 'result', 7200);
		foreach($productCatalogSupportRows as $productCatalogSupportRow)
		{
			if(empty($productCatalogSupportRow['photo']) || strpos(','.(string)$productCatalogSupportRow['status'].',', ',hienthi,') === false) continue;
			$productCatalogSupport = array(
				'name' => !empty($productCatalogSupportRow['name'.$lang]) ? $productCatalogSupportRow['name'.$lang] : 'Cần hỗ trợ chọn sản phẩm phù hợp?',
				'desc' => !empty($productCatalogSupportRow['desc'.$lang]) ? trim(strip_tags(htmlspecialchars_decode($productCatalogSupportRow['desc'.$lang], ENT_QUOTES))) : 'Chia sẻ nhu cầu để được tư vấn cấu hình và báo giá phù hợp.',
				'photo' => $productCatalogSupportRow['photo'],
				'upload' => UPLOAD_PHOTO_L,
				'link' => !empty($productCatalogSupportRow['link']) ? $productCatalogSupportRow['link'] : 'lien-he/'
			);
			break;
		}
		if(!$productCatalogSupportRows)
		{
			$productCatalogSupport = array(
				'name' => 'Cần hỗ trợ chọn sản phẩm phù hợp?',
				'desc' => 'Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp.',
				'photo' => 'catalog-equipment-system.jpg',
				'upload' => 'assets/images/site-visuals/',
				'link' => 'lien-he/'
			);
		}
	}

	/* Banner danh mục: Admin có thể gán một ảnh 16:5 bằng trường Link chứa
	   canonical path của danh mục. Mỗi nhóm có fallback đúng ngữ cảnh, nhưng
	   một record Admin đã tắt vẫn được coi là đã cấu hình để công tắc Hiển thị
	   không bị fallback tự bật lại. */
	$productCategoryHero = array();
	if($id == '')
	{
		$productCategoryPath = trim(parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');
		$productCategoryHeroRows = $cache->get("select name$lang, desc$lang, link, photo, status from #_photo where type = ? order by numb,id desc", array('product-category-hero'), 'result', 7200);
		$productCategoryHeroConfigured = false;
		foreach($productCategoryHeroRows as $productCategoryHeroRow)
		{
			$productCategoryHeroLink = trim(parse_url((string)$productCategoryHeroRow['link'], PHP_URL_PATH), '/');
			if($productCategoryHeroLink === '' || $productCategoryHeroLink !== $productCategoryPath) continue;
			$productCategoryHeroConfigured = true;
			if(empty($productCategoryHeroRow['photo']) || strpos(','.(string)$productCategoryHeroRow['status'].',', ',hienthi,') === false) break;
			$productCategoryHero = array(
				'photo' => $productCategoryHeroRow['photo'],
				'upload' => UPLOAD_PHOTO_L,
				'alt' => !empty($productCategoryHeroRow['name'.$lang]) ? $productCategoryHeroRow['name'.$lang] : $titleCate
			);
			break;
		}
		$productCategoryHeroFallbacks = array(
			'san-pham' => array('catalog-equipment-system.jpg', 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar'),
			'danh-muc-san-pham/den-nlmt' => array('catalog-street-lighting.jpg', 'Chiếu sáng năng lượng mặt trời cho đường và công trình'),
			'danh-muc-san-pham/bien-tan-inverter' => array('catalog-inverter-system-v2.jpg', 'Hệ thống biến tần điện mặt trời được lắp đặt gọn gàng'),
			'danh-muc-san-pham/tam-pin-nang-luong-mat-troi' => array('catalog-solar-panel-mounting-v2.jpg', 'Tấm pin và hệ khung điện mặt trời trên mái công trình'),
			'danh-muc-san-pham/tru-den' => array('catalog-lamp-posts-v2.jpg', 'Trụ đèn cổ điển và phụ kiện lắp đặt ngoài trời'),
			'danh-muc-san-pham/den-san-vuon' => array('catalog-garden-lighting-v2.jpg', 'Không gian sân vườn sử dụng đèn năng lượng mặt trời'),
			'danh-muc-san-pham/quat-nang-luong-mat-troi' => array('catalog-solar-fan-v3.jpg', 'Quạt đứng sử dụng năng lượng mặt trời cho không gian gia đình'),
			'danh-muc-san-pham/bien-tan-bom' => array('catalog-solar-pump-v2.jpg', 'Hệ thống bơm nước sử dụng năng lượng mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi' => array('catalog-solar-electrical-accessories-v2.jpg', 'Dây dẫn, đầu nối và thiết bị bảo vệ cho hệ thống điện mặt trời'),
			'danh-muc-san-pham/he-thong-khung-nhom' => array('catalog-solar-panel-mounting-v2.jpg', 'Hệ khung và phụ kiện lắp đặt tấm pin năng lượng mặt trời'),
			'danh-muc-san-pham/pin-luu-tru' => array('catalog-battery-storage-v2.jpg', 'Hệ thống pin lưu trữ năng lượng cho gia đình và doanh nghiệp')
		);
		if(empty($productCategoryHero) && !$productCategoryHeroConfigured && isset($productCategoryHeroFallbacks[$productCategoryPath]))
		{
			$productCategoryHero = array(
				'photo' => $productCategoryHeroFallbacks[$productCategoryPath][0],
				'upload' => 'assets/images/site-visuals/',
				'alt' => $productCategoryHeroFallbacks[$productCategoryPath][1]
			);
		}
		/* Nếu chưa có banner riêng, ưu tiên ảnh danh mục do Admin quản lý. Chỉ
		   dùng ảnh site-visuals fallback khi danh mục chưa có ảnh dữ liệu. */
		if(empty($productCategoryHero) && !empty($productCat['photo']))
		{
			$productCategoryHero = array('photo' => $productCat['photo'], 'upload' => UPLOAD_PRODUCT_L, 'alt' => $titleCate);
		}
		if(empty($productCategoryHero) && !empty($productList['photo']))
		{
			$productCategoryHero = array('photo' => $productList['photo'], 'upload' => UPLOAD_PRODUCT_L, 'alt' => $titleCate);
		}
	}

	/* "Lý do chọn Phúc Thịnh Solar" và "Dự án tiêu biểu" - dùng chung cho cả
	   trang danh mục và trang chi tiết sản phẩm (yêu cầu sếp 2026-09-28), đặt
	   ngoài 2 nhánh if($id=='')/nếu không để áp dụng được cho cả hai loại
	   trang. Dữ liệu lấy từ content Admin đã có sẵn (cam-ket, du-an) - không
	   tạo content type mới, không suy đoán. */
	$productWhyChooseUs = $cache->get("select name$lang, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('cam-ket'), 'result', 7200);
	/* "Dự án tiêu biểu" chung (không gắn theo sản phẩm cụ thể) - trang chi
	   tiết sản phẩm ưu tiên $productRelatedProjects (dự án gắn đúng sản phẩm
	   này qua #_product_project) nếu Admin đã liên kết; chưa liên kết cái nào
	   thì rơi về danh sách chung này, không ẩn hẳn section. */
	/* Đúng 3 để luôn lấp kín 1 hàng lưới 3 cột - 4 dự án từng bị lẻ 1 card ở
	   hàng dưới trông vỡ bố cục (sếp báo 2026-09-28). */
	$productFeaturedProjects = $cache->get("select id, name$lang, slugvi, slugen, photo, address from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,3", array('du-an'), 'result', 7200);
?>
