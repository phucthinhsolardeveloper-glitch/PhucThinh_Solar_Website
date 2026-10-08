<?php  
	if(!defined('SOURCES')) die("Error");
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
	/* Dùng cùng thứ tự nhóm mà rail danh mục đèn đang hiển thị: Pha, Đường,
	   Sân vườn, UFO, Ốp trần, rồi mới tới nhóm còn lại. Khi khách chọn một
	   kiểu sắp xếp cụ thể bên giao diện, switch phía dưới sẽ ghi đè thứ tự này. */
	$defaultProductOrder = publicProductDefaultOrder('', publicProductBossCategoryOrder('', $type));
	$order_by = $defaultProductOrder;
	$productListHasItems = array();
	$productListPreview = array();
	$productListPreviewCount = array();
	if($template === 'product/product_list')
	{
		/* Render 4 card đầu của mỗi danh mục ngay trong HTML. Bản cũ trả về các
		   khối rỗng rồi gọi riêng product3.php sau DOM ready cho từng danh mục. */
		$productListPreviewRows = $cache->get("select name$lang, slugvi, slugen, id, id_list, id_cat, id_brand, photo, photo_khuyenmai, regular_price, sale_price, discount, type, status, cong_suat, waterproof_rating, lighting_time from #_product where type = ? and find_in_set('hienthi',status)".$defaultProductOrder, array($type), 'result', 7200);
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

		/* Nhu cầu sử dụng (nhóm đèn lọc theo taxonomy; list legacy dùng fallback) */
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
		require_once LIBRARIES.'product_images.php';
		$productResolvedImage = productImageResolve($rowDetail, $rowDetailPhoto, productImageUploadDirectory());
		$rowDetail['_display_photo'] = $productResolvedImage['photo'];

		/* Khối nội dung mô tả: kế thừa sản phẩm > hãng > danh mục */
		require_once LIBRARIES.'product_content_blocks.php';
		$productSections = productResolveSections($d, $rowDetail, $lang);
		$productDocuments = $d->rawQuery("select document_type,title$lang as title,file_path from #_product_document where id_product=? and find_in_set('hienthi',status) and file_path<>'' order by numb,id", array($rowDetail['id']));
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
		if(empty($imgJson) || ($imgJson['p'] != $rowDetail['_display_photo']))
		{
			$imgJson = $func->getImgSize($rowDetail['_display_photo'],UPLOAD_PRODUCT_L.$rowDetail['_display_photo']);
			$seo->updateSeoDB(json_encode($imgJson),'product',$rowDetail['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PRODUCT_L.$rowDetail['_display_photo']);
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
		/* Danh mục Sân Vườn đã chuyển thành product_cat của Đèn NLMT. Giữ
		   landing cũ làm URL legacy và chuyển thẳng sang canonical mới. */
		if(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-san-vuon')
		{
			$gardenParentList = $d->rawQueryOne("select id, slugvi, slugen from #_product_list where slugvi = ? and type = ? and find_in_set('hienthi',status) limit 1",array('den-nlmt',$type));
			$gardenCategory = !empty($gardenParentList['id']) ? $d->rawQueryOne("select pc.id, pc.id_list, pc.slugvi, pc.slugen, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id = pc.id_list and pl.type = pc.type where pc.id_list = ? and pc.slugvi = ? and pc.type = ? and find_in_set('hienthi',pc.status) limit 1",array($gardenParentList['id'],'den-san-vuon',$type)) : array();
			if(!empty($gardenCategory))
			{
				header('Location: '.$configBase.publicProductCategoryPath($gardenCategory, 'cat', $lang), true, 301);
				exit;
			}
		}
		$slug_param = $productList[$sluglang];
		$id_list = $productList['id'];
		$brand = productContextualBrands($d, $brand, "id_list = ? and type = ? and find_in_set('hienthi',status)", array($id_list, $type));
		$categorySectionBlocks = productCategorySectionBlocks($d, 'list', $id_list, $lang);
		$categorySectionSplit = productSplitFaqBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categorySectionSplit['rest'];
		$categoryFaqBlocks = $categorySectionSplit['faq'];
		$categoryCardsSplit = productSplitCardsBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categoryCardsSplit['rest'];
		$categoryCardsBlocks = $categoryCardsSplit['cards'];
		if(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-nlmt' && (empty($categoryFaqBlocks) || empty($categoryCardsBlocks)))
		{
			$lightingFallback = productLightingFallbackBlocks();
			if(empty($categoryFaqBlocks)) $categoryFaqBlocks = $lightingFallback['faq'];
			if(empty($categoryCardsBlocks)) $categoryCardsBlocks = $lightingFallback['cards'];
		}
		$categoryShowDescription = productCategoryShowDescription($d, 'list', $id_list);
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
		/* Các URL phân loại cũ của Đèn Sân Vườn được giữ để chuyển hướng sau
		   khi sản phẩm đã gom về danh mục con mới, tránh tạo trang rỗng hoặc
		   mất SEO. */
		if(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-san-vuon')
		{
			$gardenParentList = $d->rawQueryOne("select id, slugvi, slugen from #_product_list where slugvi = ? and type = ? and find_in_set('hienthi',status) limit 1",array('den-nlmt',$type));
			$gardenCategory = !empty($gardenParentList['id']) ? $d->rawQueryOne("select pc.id, pc.id_list, pc.slugvi, pc.slugen, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id = pc.id_list and pl.type = pc.type where pc.id_list = ? and pc.slugvi = ? and pc.type = ? and find_in_set('hienthi',pc.status) limit 1",array($gardenParentList['id'],'den-san-vuon',$type)) : array();
			if(!empty($gardenCategory))
			{
				header('Location: '.$configBase.publicProductCategoryPath($gardenCategory, 'cat', $lang), true, 301);
				exit;
			}
		}
		$slug_param = $productCat[$sluglang];
		$id_cat = $productCat['id'];
		$brand = productContextualBrands($d, $brand, "id_cat = ? and type = ? and find_in_set('hienthi',status)", array($id_cat, $type));
		$categorySectionBlocks = productCategorySectionBlocks($d, 'cat', $id_cat, $lang);
		$categorySectionSplit = productSplitFaqBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categorySectionSplit['rest'];
		$categoryFaqBlocks = $categorySectionSplit['faq'];
		$categoryCardsSplit = productSplitCardsBlocks($categorySectionBlocks);
		$categorySectionBlocks = $categoryCardsSplit['rest'];
		$categoryCardsBlocks = $categoryCardsSplit['cards'];
		$categoryShowDescription = productCategoryShowDescription($d, 'cat', $id_cat);
		/* Danh mục cấp 2 (vd "Đèn UFO", "Đèn Đường") nhiều cái đã có mô tả
		   riêng (heading/list, soạn từ trước) nhưng CHƯA có FAQ/Lưới thẻ
		   riêng - trước đây rơi về trắng 2 khối đó nên $ptsHasCuratedContent
		   = false, làm lộ lại Chứng nhận/Dự án/Lý do chọn dù danh mục cấp 1
		   cha đã soạn đầy đủ (bug phát hiện qua ảnh chụp thực tế của sếp
		   2026-10-02 - mọi danh mục con đều thiếu, không riêng 1 cái). Kế
		   thừa ĐÚNG PHẦN CÒN THIẾU (FAQ và/hoặc Lưới thẻ) từ cấp 1 cha, giữ
		   nguyên mô tả riêng và công tắc show_description của cấp 2 - không
		   trộn hay ghi đè phần cấp 2 đã tự có. */
		if((empty($categoryFaqBlocks) || empty($categoryCardsBlocks)) && !empty($productCat['id_list']))
		{
			$parentListId = (int) $productCat['id_list'];
			$parentBlocks = productCategorySectionBlocks($d, 'list', $parentListId, $lang);
			$parentFaq = productSplitFaqBlocks($parentBlocks);
			$parentCards = productSplitCardsBlocks($parentFaq['rest']);
			if(empty($categoryFaqBlocks)) $categoryFaqBlocks = $parentFaq['faq'];
			if(empty($categoryCardsBlocks)) $categoryCardsBlocks = $parentCards['cards'];
		}
		if(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-nlmt' && (empty($categoryFaqBlocks) || empty($categoryCardsBlocks)))
		{
			$lightingFallback = productLightingFallbackBlocks();
			if(empty($categoryFaqBlocks)) $categoryFaqBlocks = $lightingFallback['faq'];
			if(empty($categoryCardsBlocks)) $categoryCardsBlocks = $lightingFallback['cards'];
		}
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
	$productCategoryRailNav = array();
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
			/* Đèn sân vườn được trình bày bên trong rail tổng của Đèn, không
			   đứng ngang hàng với Quạt/Tấm pin/Thiết bị ở rail cấp gốc. */
			$productCategoryNavItem['hide_from_root_rail'] = !empty($productCategoryNavItem['slugvi']) && $productCategoryNavItem['slugvi'] === 'den-san-vuon';
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
			$productSubcategoryRows = $d->rawQuery("select pc.id, pc.name$lang, pc.desc$lang, pc.slugvi, pc.slugen, pc.photo, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id = pc.id_list and pl.type = pc.type where pc.id_list = ? and pc.type = ? and find_in_set('hienthi',pc.status) and exists (select 1 from #_product p where p.id_cat = pc.id and p.type = ? and find_in_set('hienthi',p.status)) order by pc.numb,pc.id desc", array((int)$productList['id'], $type, $type));
			foreach($productSubcategoryRows as $productSubcategoryRow)
			{
				$productSubcategoryRow['url'] = publicProductCategoryPath($productSubcategoryRow, 'cat', $lang);
				if(!empty($productSubcategoryRow['slugvi']) && $productSubcategoryRow['slugvi'] === 'den-san-vuon') $productSubcategoryRow['nav_name'] = 'Đèn Sân Vườn';
				$productSubcategoryRow['is_active'] = !empty($productCat['id']) && (int)$productCat['id'] === (int)$productSubcategoryRow['id'];
				$productSubcategoryNav[] = $productSubcategoryRow;
			}
		}

		/*
		 * Trang den-nlmt là danh mục tổng của nhóm đèn. Dữ liệu cũ lưu Đèn sân
		 * vườn ở một product_list riêng, nên không đưa bản ghi giả vào
		 * product_cat (sẽ làm sai bộ lọc và URL cấp con). Rail hiển thị dùng một
		 * bản sao tổng hợp; mục sân vườn vẫn trỏ về landing hiện có, nơi các
		 * phân loại cũ được giữ trong dữ liệu nhưng không còn phát hành thành
		 * nhóm lựa chọn trên giao diện.
		 */
		$productCategoryRailNav = $productSubcategoryNav;
		if(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-nlmt')
		{
			$productGardenList = array();
			foreach((array)$productCategoryNav as $productCategoryNavItem)
			{
				if(!empty($productCategoryNavItem['slugvi']) && $productCategoryNavItem['slugvi'] === 'den-san-vuon')
				{
					$productGardenList = $productCategoryNavItem;
					break;
				}
			}

			if(!empty($productGardenList))
			{
				$productGardenRailItem = $productGardenList;
				$productGardenRailItem['nav_name'] = 'Đèn Sân Vườn';
				$productGardenRailItem['is_virtual_list'] = true;
				$productGardenRailItem['is_active'] = false;
				$productCategoryRailNav[] = $productGardenRailItem;
			}

			$productLightingRailOrder = array(
				'den-pha-nang-luong-mat-troi' => 10,
				'den-pha' => 10,
				'den-duong-nang-luong-mat-troi' => 20,
				'den-duong' => 20,
				'den-san-vuon' => 30,
				'den-ufo' => 40,
				'den-op-tran' => 50
			);
			$productLightingRailAll = array();
			$productLightingRailItems = array();
			foreach($productCategoryRailNav as $productLightingRailIndex => $productLightingRailItem)
			{
				if(empty($productLightingRailItem['id']) && empty($productLightingRailItem['is_virtual_list']))
				{
					$productLightingRailAll[] = $productLightingRailItem;
					continue;
				}
				$productLightingRailItem['_pts_rail_order'] = isset($productLightingRailOrder[$productLightingRailItem['slugvi']]) ? $productLightingRailOrder[$productLightingRailItem['slugvi']] : 1000;
				$productLightingRailItem['_pts_rail_index'] = $productLightingRailIndex;
				$productLightingRailItems[] = $productLightingRailItem;
			}
			usort($productLightingRailItems, static function($left, $right) {
				if($left['_pts_rail_order'] === $right['_pts_rail_order']) return $left['_pts_rail_index'] <=> $right['_pts_rail_index'];
				return $left['_pts_rail_order'] <=> $right['_pts_rail_order'];
			});
			foreach($productLightingRailItems as &$productLightingRailItem) {
				unset($productLightingRailItem['_pts_rail_order'], $productLightingRailItem['_pts_rail_index']);
			}
			unset($productLightingRailItem);
			$productCategoryRailNav = array_merge($productLightingRailAll, $productLightingRailItems);
		}
		elseif(!empty($productList['slugvi']) && $productList['slugvi'] === 'den-san-vuon')
		{
			/* Theo bố cục mới, sân vườn là một nhóm sản phẩm duy nhất. Giữ các
			   product_cat cũ trong dữ liệu để bảo toàn URL/rollback, nhưng không
			   phát hành chúng thành các tab phân loại trên landing sân vườn. */
			$productCategoryRailNav = array();
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
			'san-pham' => array('catalog-products-hero-20261007-v2.png', 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar'),
			'danh-muc-san-pham/den-nlmt' => array('catalog-lighting-overview-20261008.png', 'Tổng quan giải pháp chiếu sáng năng lượng mặt trời'),
			'danh-muc-san-pham/den-nlmt/den-duong' => array('catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'),
			'danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
			'danh-muc-san-pham/den-nlmt/den-pha' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
			'danh-muc-san-pham/den-nlmt/den-san-vuon' => array('catalog-solar-garden-lighting-20261008-v2.png', 'Đèn sân vườn năng lượng mặt trời cho nhà ở và khuôn viên'),
			'danh-muc-san-pham/den-nlmt/den-ufo' => array('catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'),
			'danh-muc-san-pham/den-nlmt/den-op-tran' => array('catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'),
			'danh-muc-san-pham/tru-den/tru-den-duong' => array('catalog-solar-street-poles-20261008.png', 'Trụ đèn đường cho hệ thống chiếu sáng ngoài trời'),
			'danh-muc-san-pham/tru-den/tru-den-trang-tri' => array('catalog-decorative-lamp-posts-20261008.png', 'Trụ đèn trang trí cho sân vườn và khuôn viên'),
			'danh-muc-san-pham/tru-den/can-den-gia-do' => array('catalog-street-light-arms-20261008.png', 'Cần đèn và giá đỡ cho hệ thống chiếu sáng'),
			'danh-muc-san-pham/tru-den/mong-de-tru' => array('catalog-street-pole-foundations-20261008.png', 'Móng và đế trụ cho công trình chiếu sáng'),
			'danh-muc-san-pham/tam-pin-nang-luong-mat-troi/kep-tam-pin' => array('catalog-utility-solar-mounting-20261008.png', 'Kẹp tấm pin và phụ kiện cố định cho trang trại điện mặt trời'),
			'danh-muc-san-pham/bien-tan-inverter/hoa-luoi' => array('catalog-grid-tie-solar-20261008.png', 'Biến tần hòa lưới cho nhà máy điện mặt trời quy mô lớn'),
			'danh-muc-san-pham/bien-tan-inverter/hybrid-luu-tru' => array('catalog-hybrid-inverter-storage-20261008.png', 'Biến tần hybrid kết hợp điện mặt trời và hệ thống lưu trữ'),
			'danh-muc-san-pham/pin-luu-tru/pin-battery' => array('catalog-lithium-battery-modules-20261008.png', 'Module pin lithium cho hệ thống lưu trữ năng lượng'),
			'danh-muc-san-pham/pin-luu-tru/he-thong-ess' => array('catalog-ess-container-site-20261008.png', 'Hệ thống ESS container cho dự án lưu trữ năng lượng'),
			'danh-muc-san-pham/pin-luu-tru/bms-rack-phu-kien' => array('catalog-bms-rack-20261008.png', 'BMS, rack và phụ kiện quản lý hệ thống pin lưu trữ'),
			'danh-muc-san-pham/bien-tan-bom/bien-tan-bom-nlmt' => array('catalog-solar-pump-inverter-20261008.png', 'Biến tần bơm năng lượng mặt trời cho hệ thống tưới và cấp nước quy mô lớn'),
			'danh-muc-san-pham/bien-tan-bom/may-bom-nlmt' => array('catalog-solar-water-pump-20261008.png', 'Máy bơm năng lượng mặt trời cho nông nghiệp và công trình'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-mppt' => array('catalog-mppt-controller-20261008-v2.png', 'Bộ điều khiển sạc MPPT cho hệ thống điện mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-pwm' => array('catalog-pwm-controller-20261008.png', 'Bộ điều khiển sạc PWM cho hệ thống điện mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/cap-day-dien' => array('catalog-solar-cables-wiring-20261008.png', 'Cáp và dây điện chuyên dụng cho hệ thống năng lượng mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/giam-sat-do-luong' => array('catalog-solar-monitoring-20261008.png', 'Thiết bị giám sát và đo lường hệ thống điện mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/phu-kien-den-nlmt' => array('catalog-solar-lighting-accessories-20261008.png', 'Phụ kiện lắp đặt đèn năng lượng mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/tiep-dia-ong-bao-ve' => array('catalog-solar-protection-grounding-20261008.png', 'Tiếp địa và ống bảo vệ cho hệ thống điện mặt trời'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/vat-tu-phu-tro' => array('catalog-solar-electrical-accessories-v2.jpg', 'Vật tư phụ trợ cho thi công và lắp đặt điện mặt trời'),
			/* Giữ các đường dẫn con cũ nếu còn được chia sẻ trong link/SEO legacy. */
			'danh-muc-san-pham/den-duong' => array('catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'),
			'danh-muc-san-pham/den-pha-nang-luong-mat-troi' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
			'danh-muc-san-pham/den-pha' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
			'danh-muc-san-pham/den-san-vuon' => array('catalog-solar-garden-lighting-20261008-v2.png', 'Đèn sân vườn năng lượng mặt trời cho nhà ở và khuôn viên'),
			'danh-muc-san-pham/den-ufo' => array('catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'),
			'danh-muc-san-pham/den-op-tran' => array('catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'),
			'danh-muc-san-pham/bien-tan-inverter' => array('catalog-inverter-overview-20261008.png', 'Hệ thống biến tần điện mặt trời cho công trình và nhà máy'),
			'danh-muc-san-pham/tam-pin-nang-luong-mat-troi' => array('catalog-utility-solar-farm-20261008.png', 'Trang trại tấm pin năng lượng mặt trời quy mô lớn'),
			'danh-muc-san-pham/tru-den' => array('catalog-lamp-posts-v2.jpg', 'Trụ đèn cổ điển và phụ kiện lắp đặt ngoài trời'),
			'danh-muc-san-pham/den-san-vuon' => array('catalog-ai-garden-lighting.webp', 'Không gian sân vườn sử dụng đèn năng lượng mặt trời'),
			'danh-muc-san-pham/quat-nang-luong-mat-troi' => array('catalog-solar-fan-20261008-v2.png', 'Quạt đứng sử dụng năng lượng mặt trời cho không gian gia đình'),
			'danh-muc-san-pham/bien-tan-bom' => array('catalog-solar-pumping-overview-20261008.png', 'Hệ thống bơm nước năng lượng mặt trời cho nông nghiệp và công trình'),
			'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi' => array('catalog-solar-bos-accessories-20261008.png', 'Dây dẫn, đầu nối và thiết bị bảo vệ cho hệ thống điện mặt trời'),
			'danh-muc-san-pham/he-thong-khung-nhom' => array('catalog-utility-solar-mounting-20261008.png', 'Hệ khung và phụ kiện lắp đặt tấm pin cho dự án quy mô lớn'),
			'danh-muc-san-pham/pin-luu-tru' => array('catalog-utility-bess-20261008.png', 'Hệ thống pin lưu trữ năng lượng cho gia đình và doanh nghiệp')
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
		/* Dùng banner đang render làm ảnh chia sẻ. Trước đây photo danh mục
		   được dùng cho og:image; sau khi rail chuyển sang SVG, metadata bị rỗng
		   và nền tảng chia sẻ link tự rơi về favicon nhỏ. Với tunnel local, lấy
		   host hiện tại để crawler có thể tải được ảnh thay vì trỏ về localhost. */
		if(empty($seo->get('photo')) && !empty($productCategoryHero['photo']))
		{
			$shareHost = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';
			$shareBase = $configBase;
			if($shareHost !== '' && (strpos($shareHost, '.trycloudflare.com') !== false || $shareHost === $configUrl))
			{
				$shareForwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) : '';
				$shareScheme = ($shareForwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https://' : 'http://';
				$shareBase = $shareScheme.$shareHost.'/';
			}
			$shareUpload = trim((string)$productCategoryHero['upload'], '/\\');
			$sharePhoto = ltrim((string)$productCategoryHero['photo'], '/\\');
			$seo->set('photo', rtrim($shareBase, '/').'/'.($shareUpload !== '' ? $shareUpload.'/' : '').$sharePhoto);
		}
	}

	/* "Lý do chọn Phúc Thịnh Solar" dùng chung cho cả trang danh mục và trang
	   chi tiết sản phẩm. Dữ liệu lấy từ content Admin đã có sẵn. */
	$productWhyChooseUs = $cache->get("select name$lang, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('cam-ket'), 'result', 7200);
?>
