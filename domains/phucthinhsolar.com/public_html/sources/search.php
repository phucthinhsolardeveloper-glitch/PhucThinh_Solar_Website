<?php  
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_sort.php';
	require_once LIBRARIES.'product_price_filter.php';

	/* Tìm kiếm sản phẩm */
	if(!empty($_GET['keyword']))
	{
		$rawKeyword = trim((string)$_GET['keyword']);
		$tukhoa = htmlspecialchars($rawKeyword, ENT_QUOTES, 'UTF-8');
		$tukhoa = $func->changeTitle($tukhoa);

		if($tukhoa)
		{
			/*
			 * Search both the normalized URL/code form and the text entered by the
			 * customer. The raw form is needed for alternate model/spec references
			 * stored in descriptions and technical data (for example DTSU666, IP65,
			 * or 200A), while the normalized form handles punctuation and accents in
			 * names/slugs without changing the stored product data.
			 */
			$rawPattern = '%'.$rawKeyword.'%';
			$normalizedPattern = "%$tukhoa%";
			$searchClauses = array();
			$params = array('san-pham');
			$rawSearchFields = array(
				'p.namevi', 'p.nameen', 'p.code', 'p.pdp_profile',
				'p.descvi', 'p.descen', 'p.highlightsvi', 'p.highlightsen',
				'p.applicationsvi', 'p.applicationsen', 'p.installationvi', 'p.installationen',
				'p.contentvi', 'p.contenten', 'p.additional_specs', 'p.options',
				'p.thongsokythuatvi', 'p.thongsokythuaten', 'p.warranty',
				'p.battery_capacity', 'p.panel_power', 'p.light_source', 'p.lamp_dimensions',
				'p.color_temperature', 'p.light_color', 'p.material', 'p.charging_time',
				'p.lighting_time', 'p.waterproof_rating', 'p.led_chip', 'p.lighting_area',
				'p.installation_height', 'p.panel_dimensions', 'p.package_contents',
				'p.carton_quantity', 'p.carton_dimensions', 'p.khoanggia'
			);
			foreach($rawSearchFields as $searchField)
			{
				$searchClauses[] = "$searchField LIKE ?";
				$params[] = $rawPattern;
			}
			$searchClauses[] = 'p.slugvi LIKE ?';
			$params[] = $normalizedPattern;
			$searchClauses[] = 'p.slugen LIKE ?';
			$params[] = $normalizedPattern;

			/* Cho phép tìm theo tên/slug thương hiệu ngay cả khi tên sản phẩm không lặp brand. */
			$brandClauses = array();
			foreach(array('pb.namevi', 'pb.nameen', 'pb.slugvi', 'pb.slugen') as $brandField)
			{
				$brandClauses[] = "$brandField LIKE ?";
				$params[] = (strpos($brandField, 'slug') !== false) ? $normalizedPattern : $rawPattern;
			}
			$searchClauses[] = "exists (select 1 from #_product_brand pb where pb.id = p.id_brand and (".implode(' or ', $brandClauses)."))";

			$compactKeyword = preg_replace('/[^a-z0-9]/', '', strtolower($tukhoa));
			if($compactKeyword !== '')
			{
				$compactPattern = "%$compactKeyword%";
				foreach(array(
					'p.code', 'p.slugvi', 'p.slugen', 'p.pdp_profile',
					'p.options', 'p.additional_specs', 'p.thongsokythuatvi', 'p.thongsokythuaten',
					'p.contentvi', 'p.contenten', 'p.descvi', 'p.descen'
				) as $compactField)
				{
					$compactExpression = "LOWER($compactField)";
					foreach(array('-', ' ', '_', '/', '.') as $separator)
					{
						$compactExpression = "REPLACE($compactExpression, '$separator', '')";
					}
					$searchClauses[] = "$compactExpression LIKE ?";
					$params[] = $compactPattern;
				}
			}
			$where = "p.type = ? and (".implode(' or ', $searchClauses).") and find_in_set('hienthi',p.status)";

			$curPage = $getPage;
			$perPage = 20;
			$startpoint = ($curPage * $perPage) - $perPage;
			$limit = " limit ".$startpoint.",".$perPage;
			/* Card dùng status + photo_khuyenmai để ghép lớp khung campaign giống trang danh mục. */
			$sql = "select p.photo, p.photo2, p.photo_khuyenmai, p.status, p.type, p.name$lang, p.slugvi, p.slugen, p.sale_price, p.regular_price, p.discount, p.id, p.id_list, p.id_cat, p.id_brand from #_product p where $where".publicProductDefaultOrder('p')." $limit";
			$product = $d->rawQuery($sql,$params);
			$sqlNum = "select count(*) as 'num' from #_product p where $where";
			$count = $d->rawQueryOne($sqlNum,$params);
			$total = (!empty($count)) ? $count['num'] : 0;
			$url = $func->getCurrentPageURL();
			$paging = $func->pagination($total,$perPage,$curPage,$url);
		}
	}

	/* SEO */
	$seo->set('title',$titleMain);

	/* breadCrumbs */
	$breadcr->set('',$titleMain);
	$breadcrumbs = $breadcr->get();
?>
