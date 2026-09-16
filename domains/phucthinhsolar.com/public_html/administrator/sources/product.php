<?php
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_projects.php';
	require_once LIBRARIES.'product_content_blocks.php';
	require_once LIBRARIES.'product_promo.php';
	require_once LIBRARIES.'product_merchant_feed.php';
	require_once LIBRARIES.'public_pricing.php';

	/* Kiểm tra active product */
	if(isset($config['product']))
	{
		$arrCheck = array();
		foreach($config['product'] as $k => $v) $arrCheck[] = $k;
		if(!count($arrCheck) || !in_array($type,$arrCheck)) $func->transfer("Trang không tồn tại", "index.php", false);
	}
	else
	{
		$func->transfer("Trang không tồn tại", "index.php", false);	
	}

	/* Công cụ nhập/xuất sản phẩm an toàn theo ID, mã và slug */
	require_once SOURCES.'product_bulk.php';
	require_once LIBRARIES.'admin_product_completeness.php';

	function productAdminSeoDefaults($func, $data, $dataSeo)
	{
		$dataSeo = is_array($dataSeo) ? $dataSeo : array();
		foreach(array('vi','en') as $langKey)
		{
			$name = !empty($data['name'.$langKey]) ? html_entity_decode($data['name'.$langKey], ENT_QUOTES, 'UTF-8') : '';
			if($name === '') continue;
			$descriptionSource = !empty($data['desc'.$langKey]) ? $data['desc'.$langKey] : $name.' chính hãng, tư vấn đúng nhu cầu, bảo hành rõ ràng và giao hàng toàn quốc.';
			$description = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($descriptionSource, ENT_QUOTES, 'UTF-8'))));
			if(empty($dataSeo['title'.$langKey])) $dataSeo['title'.$langKey] = htmlspecialchars(mb_substr($name.' | Phúc Thịnh Solar', 0, 70), ENT_QUOTES, 'UTF-8');
			if(empty($dataSeo['keywords'.$langKey])) $dataSeo['keywords'.$langKey] = htmlspecialchars(mb_substr($name, 0, 70), ENT_QUOTES, 'UTF-8');
			if(empty($dataSeo['description'.$langKey])) $dataSeo['description'.$langKey] = htmlspecialchars(mb_substr($description, 0, 160), ENT_QUOTES, 'UTF-8');
		}
		return $dataSeo;
	}
	/* adminReturnUrl()/adminReturnSuffix() dùng chung, định nghĩa ở requick.php */
	function productAdminRememberRedirect($d, $oldPath, $entityTable, $entityId)
	{
		$oldPath = trim((string)$oldPath, '/');
		if($oldPath === '' || !in_array($entityTable, array('product','product_list','product_cat','product_item','product_sub'), true) || !(int)$entityId) return;
		$d->rawQuery("insert into #_url_redirect (old_path,entity_table,entity_id,date_created) values (?,?,?,?) on duplicate key update entity_table=values(entity_table),entity_id=values(entity_id)", array($oldPath,$entityTable,(int)$entityId,time()));
	}
	function productAdminCategoryDependency($d, $level, $id, $type)
	{
		$id = (int)$id;
		if($level === 'list') {
			$product = $d->rawQueryOne("select count(*) num from #_product where id_list=? and type=?", array($id,$type));
			$child = $d->rawQueryOne("select count(*) num from #_product_cat where id_list=? and type=?", array($id,$type));
		} elseif($level === 'cat') {
			$product = $d->rawQueryOne("select count(*) num from #_product where id_cat=? and type=?", array($id,$type));
			$child = $d->rawQueryOne("select count(*) num from #_product_item where id_cat=? and type=?", array($id,$type));
		} elseif($level === 'item') {
			$product = $d->rawQueryOne("select count(*) num from #_product where id_item=? and type=?", array($id,$type));
			$child = $d->rawQueryOne("select count(*) num from #_product_sub where id_item=? and type=?", array($id,$type));
		} elseif($level === 'brand') {
			$product = $d->rawQueryOne("select count(*) num from #_product where id_brand=? and type=?", array($id,$type));
			$child = array('num'=>0);
		} else {
			$product = $d->rawQueryOne("select count(*) num from #_product where id_sub=? and type=?", array($id,$type));
			$child = array('num'=>0);
		}
		return (int)$product['num'] + (int)$child['num'];
	}

	/* Xuất feed Google Merchant Center (Shopping).
	   Nâng cấp 2026-09-16: bản trước chỉ có 6 cột (code/title/link/image_link/
	   price/sale_price) nên chưa phù hợp để gửi thẳng lên Merchant Center: thiếu
	   các cột bắt buộc
	   (id, description, availability, condition, brand) và price không có đơn vị
	   tiền tệ. Nay xuất đúng tên cột theo đặc tả Google, price dạng
	   "1356000 VND", và chỉ xuất dòng đủ điều kiện để feed không bị lỗi hàng loạt.

	   Ảnh: chỉ dùng photo_feed đã biên tập nền sạch, không logo/chữ/khung. */
	function productExportFeedCsv()
	{
		global $d, $type, $configBase, $func;

		/* Merchant yêu cầu giá feed khớp giá nhìn thấy trên landing page. */
		if(!publicProductPricesVisible())
		{
			$func->transfer('Chưa thể xuất feed: website đang ở chế độ liên hệ báo giá. Hãy bật hiển thị giá công khai trước để giá trên feed khớp trang sản phẩm.', 'index.php?com=product&act=man&type='.$type, false);
		}

		$rows = $d->rawQuery("select p.code, p.namevi, p.slugvi, p.regular_price, p.sale_price, p.photo_feed, p.descvi, p.cong_suat, p.status,
			b.namevi as brand_name, pl.namevi as list_name, pc.namevi as cat_name
			from #_product p
			left join #_product_brand b on b.id = p.id_brand and b.type = p.type
			left join #_product_list pl on pl.id = p.id_list and pl.type = p.type
			left join #_product_cat pc on pc.id = p.id_cat and pc.type = p.type
			where p.type = ? and find_in_set('hienthi',p.status) order by p.id", array($type));

		$feedRows = array();
		foreach($rows as $row)
		{
			$feedRow = productMerchantFeedRow($row, $configBase, ROOT.UPLOAD_PRODUCT_L);
			if($feedRow !== null) $feedRows[] = $feedRow;
		}
		if(!$feedRows)
		{
			$func->transfer('Chưa có sản phẩm đủ điều kiện xuất feed. Cần mã sản phẩm, giá hợp lệ và ảnh Shopping sạch tối thiểu 500×500 px.', 'index.php?com=product&act=man&type='.$type.'&data_status=missing_photo_feed', false);
		}

		$filename = 'shopping-feed-'.preg_replace('/[^a-z0-9\-]/i', '', (string) $type).'-'.date('Ymd-His').'.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");
		/* Tên cột đúng đặc tả Google Merchant Center. */
		fputcsv($out, productMerchantFeedHeaders());

		foreach($feedRows as $feedRow)
		{
			fputcsv($out, $feedRow);
		}

		fclose($out);
		exit;
	}

	/* Cấu hình đường dẫn trả về */
	$strUrl = "";
	$arrUrl = array('id_list','id_cat','id_item','id_sub','id_brand');
	if(isset($_POST['data']))
	{
		$dataUrl = isset($_POST['data']) ? $_POST['data'] : null;
		if($dataUrl)
		{
			foreach($arrUrl as $k => $v)
			{
				if(isset($dataUrl[$arrUrl[$k]])) $strUrl .= "&".$arrUrl[$k]."=".htmlspecialchars($dataUrl[$arrUrl[$k]]);
			}
		}
	}
	else
	{
		foreach($arrUrl as $k => $v)
		{
			if(isset($_REQUEST[$arrUrl[$k]])) $strUrl .= "&".$arrUrl[$k]."=".htmlspecialchars($_REQUEST[$arrUrl[$k]]);
		}

		if(!empty($_REQUEST['comment_status'])) $strUrl .= "&comment_status=".htmlspecialchars($_REQUEST['comment_status']);
		if(!empty($_REQUEST['data_status'])) $strUrl .= "&data_status=".urlencode($_REQUEST['data_status']);
		if(isset($_REQUEST['keyword']) && is_scalar($_REQUEST['keyword'])) $strUrl .= "&keyword=".urlencode((string)$_REQUEST['keyword']);
	}
	$adminPerPageOptions = array(10, 20, 50, 100);
	$adminPerPage = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 20;
	if(!in_array($adminPerPage, $adminPerPageOptions, true)) $adminPerPage = 20;
	$strUrl .= "&per_page=".$adminPerPage;

	switch($act)
	{
		/* Bulk import/export */
		case "bulk":
			$template = "product/man/bulk";
			break;
		case "bulk_preview":
			productBulkPreview();
			$template = "product/man/bulk";
			break;
		case "bulk_commit":
			productBulkCommit();
			$template = "product/man/bulk";
			break;
		case "bulk_image_preview":
			productBulkImagePreview();
			$template = "product/man/bulk";
			break;
		case "bulk_image_commit":
			productBulkImageCommit();
			$template = "product/man/bulk";
			break;
		case "bulk_template":
			productBulkExport(true);
			break;
		case "bulk_export":
			productBulkExport(false);
			break;
		case "export_feed_csv":
			productExportFeedCsv();
			break;

		/* Man */
		case "man":
			viewMans();
			$template = "product/man/mans";
			break;
		case "add":
			$template = "product/man/man_add";
			break;
		case "edit":
		case "copy":
			if((!isset($config['product'][$type]['copy']) || $config['product'][$type]['copy'] == false) && $act=='copy')
			{
				$template = "404";
				return false;
			}
			editMan();
			$template = "product/man/man_add";
			break;
		case "save":
		case "save_copy":
			saveMan();
			break;
		case "delete":
			deleteMan();
			break;

		/* Size */
		case "man_size":
			viewSizes();
			$template = "product/size/sizes";
			break;
		case "add_size":
			$template = "product/size/size_add";
			break;
		case "edit_size":
			editSize();
			$template = "product/size/size_add";
			break;
		case "save_size":
			saveSize();
			break;
		case "delete_size":
			deleteSize();
			break;

		/* Color */
		case "man_color":
			viewColors();
			$template = "product/color/colors";
			break;
		case "add_color":
			$template = "product/color/color_add";
			break;
		case "edit_color":
			editColor();
			$template = "product/color/color_add";
			break;
		case "save_color":
			saveColor();
			break;
		case "delete_color":
			deleteColor();
			break;

		/* Brand */
		case "man_brand":
			viewBrands();
			$template = "product/brand/brand";
			break;
		case "add_brand":
			$template = "product/brand/brand_add";
			break;
		case "edit_brand":
			editBrand();
			$template = "product/brand/brand_add";
			break;
		case "save_brand":
			saveBrand();
			break;
		case "delete_brand":
			deleteBrand();
			break;

		/* List */
		case "man_list":
			viewLists();
			$template = "product/list/lists";
			break;
		case "add_list":
			$template = "product/list/list_add";
			break;
		case "edit_list":
			editList();
			$template = "product/list/list_add";
			break;
		case "save_list":
			saveList();
			break;
		case "delete_list":
			deleteList();
			break;

		/* Cat */
		case "man_cat":
			viewCats();
			$template = "product/cat/cats";
			break;
		case "add_cat":
			$template = "product/cat/cat_add";
			break;
		case "edit_cat":
			editCat();
			$template = "product/cat/cat_add";
			break;
		case "save_cat":
			saveCat();
			break;
		case "delete_cat":
			deleteCat();
			break;

		/* Item */
		case "man_item":
			viewItems();
			$template = "product/item/items";
			break;
		case "add_item":
			$template = "product/item/item_add";
			break;
		case "edit_item":
			editItem();
			$template = "product/item/item_add";
			break;
		case "save_item":
			saveItem();
			break;
		case "delete_item":
			deleteItem();
			break;

		/* Sub */
		case "man_sub":
			viewSubs();
			$template = "product/sub/subs";
			break;
		case "add_sub":
			$template = "product/sub/sub_add";
			break;
		case "edit_sub":
			editSub();
			$template = "product/sub/sub_add";
			break;
		case "save_sub":
			saveSub();
			break;
		case "delete_sub":
			deleteSub();
			break;
		
		/* Gallery */
		case "man_photo":
		case "add_photo":
		case "edit_photo":
		case "save_photo":
		case "delete_photo":
			include "gallery.php";
			break;

		default:
			$template = "404";
	}

	/* View man */
	function viewMans()
	{
		global $d, $func, $comment, $commentTotals, $commentNewTotals, $strUrl, $curPage, $items, $paging, $type, $productStatusFilter, $productStatusFilterTotal, $adminPerPage;

		$where = "";
		$params = array($type);
		$filters = array(
			'id_list' => isset($_REQUEST['id_list']) ? (int) $_REQUEST['id_list'] : 0,
			'id_cat' => isset($_REQUEST['id_cat']) ? (int) $_REQUEST['id_cat'] : 0,
			'id_item' => isset($_REQUEST['id_item']) ? (int) $_REQUEST['id_item'] : 0,
			'id_sub' => isset($_REQUEST['id_sub']) ? (int) $_REQUEST['id_sub'] : 0,
			'id_brand' => isset($_REQUEST['id_brand']) ? (int) $_REQUEST['id_brand'] : 0
		);

		foreach($filters as $column => $value)
		{
			if($value > 0)
			{
				$where .= " and p.".$column." = ?";
				$params[] = $value;
			}
		}

		$comment_status = !empty($_REQUEST['comment_status']) ? $_REQUEST['comment_status'] : '';
		if($comment_status === 'new')
		{
			$where .= " and exists (select 1 from #_comment c where c.id_variant = p.id and c.type = ? and find_in_set('new-admin', c.status))";
			$params[] = $type;
		}

		$dataStatus = !empty($_REQUEST['data_status']) ? (string)$_REQUEST['data_status'] : '';
		$dataStatusDefinition = adminProductDataStatus($dataStatus);
		if($dataStatusDefinition) $where .= ' and '.$dataStatusDefinition['sql'];
		else $dataStatus = '';

		if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
		{
			$keyword = '%'.trim($_REQUEST['keyword']).'%';
			$where .= " and (p.namevi LIKE ? or p.nameen LIKE ? or p.code LIKE ?)";
			$params[] = $keyword;
			$params[] = $keyword;
			$params[] = $keyword;
		}

		$perPage = $adminPerPage;
		$startpoint = max(0, (((int) $curPage) * $perPage) - $perPage);
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select p.*, case when p.id_cat = 0 then 1 when pc.id is null then 2 else 0 end as _category_issue from #_product p left join #_product_cat pc on pc.id = p.id_cat where p.type = ? $where order by p.numb,p.id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product p where p.type = ? $where";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$productStatusFilterTotal = (int)$total;
		$productStatusFilter = array(
			'id_list' => $filters['id_list'], 'id_cat' => $filters['id_cat'], 'id_item' => $filters['id_item'],
			'id_sub' => $filters['id_sub'], 'id_brand' => $filters['id_brand'],
			'comment_status' => $comment_status, 'data_status' => $dataStatus,
			'keyword' => isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : ''
		);
		$commentTotals = array();
		$commentNewTotals = array();
		if(!empty($items))
		{
			$productIds = array_map(function($item) { return (int)$item['id']; }, $items);
			$placeholders = implode(',', array_fill(0, count($productIds), '?'));
			$commentParams = array_merge(array($type), $productIds);
			$commentRows = $d->rawQuery("select id_variant, sum(case when id_parent = 0 then 1 else 0 end) as total_comments, sum(case when find_in_set('new-admin',status) then 1 else 0 end) as new_comments from #_comment where type = ? and id_variant in ($placeholders) group by id_variant", $commentParams);
			foreach($commentRows as $commentRow)
			{
				$productId = (int)$commentRow['id_variant'];
				$commentTotals[$productId] = (int)$commentRow['total_comments'];
				$commentNewTotals[$productId] = (int)$commentRow['new_comments'];
			}
		}
		$url = "index.php?com=product&act=man".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* Comment */
		$comment = new Comments($d, $func);
	}

	/* Edit man */
	function editMan()
	{
		global $d, $func, $strUrl, $curPage, $item, $gallery, $linkedProjects, $type, $com, $act;

		if(!empty($_GET['id'])) $id = htmlspecialchars($_GET['id']);
		else if(!empty($_GET['id_copy'])) $id = htmlspecialchars($_GET['id_copy']);
		else $id = 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
			}
			else
			{
				$linkedProjects = $d->rawQuery("select n.id, n.namevi, n.status from #_product_project pp inner join #_news n on n.id = pp.id_project where pp.id_product = ? and n.type = ? order by pp.numb,pp.id", array($id, 'du-an'));
				if($act != 'copy')
				{
					/* Get gallery */
					$gallery = $d->rawQuery("select * from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc",array($id,$com,$type,'man',$type));
				}
			}
		}		
	}

	/* Save man */
	function saveMan()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $act, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$savehere = (isset($_POST['save-here'])) ? true : false;
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$oldSlugRow = $id ? $d->rawQueryOne("select slugvi from #_product where id=? and type=? limit 1", array($id,$type)) : array();
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		$dataTags = (!empty($_POST['dataTags'])) ? $_POST['dataTags'] : null;
		$dataColor = (!empty($_POST['dataColor'])) ? $_POST['dataColor'] : null;
		$dataSize = (!empty($_POST['dataSize'])) ? $_POST['dataSize'] : null;
		foreach($config['website']['lang'] as $blockLang => $blockLabel)
		{
			$blockDefinitions = array(
				'highlights' => array(true, 6, 'Điểm nổi bật'),
				'applications' => array(false, 8, 'Ứng dụng'),
				'installation' => array(false, 10, 'Hướng dẫn')
			);
			foreach($blockDefinitions as $blockField => $blockDefinition)
			{
				$postKey = $blockField.'_text_'.$blockLang;
				if(!isset($_POST[$postKey])) continue;
				$parsedBlock = productContentBlockParse($_POST[$postKey], $blockDefinition[0], $blockDefinition[1]);
				foreach($parsedBlock['errors'] as $blockError) $response['messages'][] = $blockDefinition[2].' ('.$blockLang.') '.$blockError;
				$data[$blockField.$blockLang] = $parsedBlock['items'] ? json_encode($parsedBlock['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
				$flash->set($postKey, (string)$_POST[$postKey]);
			}
		}
		$additionalSpecs = array();
		if(isset($_POST['additional_specs_text']))
		{
			$additionalSpecsText = (string) $_POST['additional_specs_text'];
			$parsedAdditionalSpecs = productBulkParseSpecLines($additionalSpecsText);
			foreach($parsedAdditionalSpecs['errors'] as $specError) $response['messages'][] = 'Thông số riêng '.$specError;
			foreach($parsedAdditionalSpecs['specs'] as $spec)
			{
				$additionalSpecs[] = array(
					'label' => trim(strip_tags($func->sanitize($spec['label']))),
					'value' => trim(strip_tags($func->sanitize($spec['value'])))
				);
			}
			$data['additional_specs'] = $additionalSpecs ? json_encode($additionalSpecs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
		}

		if($data)
		{
			foreach($data as $column => $value)
			{
                if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false 	|| strpos($column, 'thongsokythuat') !== false || strpos($column, 'chinhsachmuahang') !== false || strpos($column, 'certificate') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

            if(!empty($data['video_url']))
            {
                $videoUrl = trim(htmlspecialchars_decode($data['video_url'], ENT_QUOTES));
                $videoParts = parse_url($videoUrl);
                $videoHost = (!empty($videoParts['host'])) ? strtolower($videoParts['host']) : '';
                $allowedVideoHosts = array(
                    'youtube.com',
                    'www.youtube.com',
                    'm.youtube.com',
                    'youtu.be',
                    'www.youtu.be',
                    'youtube-nocookie.com',
                    'www.youtube-nocookie.com'
                );

                if(!filter_var($videoUrl, FILTER_VALIDATE_URL) || !in_array($videoHost, $allowedVideoHosts, true))
                    $response['messages'][] = 'Video sản phẩm chỉ chấp nhận URL YouTube hợp lệ';
                else
                    $data['video_url'] = htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8');
            }
		    if(!empty($config['product'][$type]['slug']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

			$pricing = adminNormalizeProductPricing($data);
			$data['regular_price'] = $pricing['values']['regular_price'];
			$data['sale_price'] = $pricing['values']['sale_price'];
			$data['discount'] = $pricing['values']['discount'];
			foreach($pricing['errors'] as $pricingError) $response['messages'][] = $pricingError;
			if(isset($data['cong_suat']))
			{
				$wattage = str_replace(",",".",$data['cong_suat']);
				if($wattage !== '' && (!is_numeric($wattage) || (float)$wattage < 0)) $response['messages'][] = 'Công suất không hợp lệ';
				else $data['cong_suat'] = ($wattage !== '') ? (float)$wattage : null;
			}
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo']) && $config['product'][$type]['seo'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
			$dataSeo = productAdminSeoDefaults($func, $data, isset($dataSeo) ? $dataSeo : array());
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = ($act == 'save_copy') ? true : false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		/* Chặn ảnh Shopping quá nhỏ ngay trên form thay vì âm thầm loại sản
		   phẩm khỏi CSV sau khi Admin đã lưu. Kiểm tra trên file tạm; bước
		   uploadImage() tiếp tục chịu trách nhiệm MIME, extension và dung lượng. */
		if($func->hasFile('file_feed') && !productMerchantImageMeetsMinimum($_FILES['file_feed']['tmp_name']))
		{
			$response['messages'][] = 'Ảnh Shopping phải có kích thước tối thiểu 500×500 px';
		}

		if(!empty($response))
		{
			if(isset($additionalSpecsText)) $flash->set('additional_specs_text', $additionalSpecsText);
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id || $act == 'save_copy')
			{
				if($act == 'save_copy')
				{
					$func->redirect("index.php?com=product&act=copy&type=".$type."&p=".$curPage.$strUrl."&id_copy=".$id.adminReturnSuffix());
				}
				else
				{
					$func->redirect("index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix());
				}
			}
			else
			{
				$func->redirect("index.php?com=product&act=add&type=".$type."&p=".$curPage.$strUrl.adminReturnSuffix());
			}
		}

		/* Save data */
		if($id && $act!='save_copy')
		{
			$data['date_updated'] = time();

			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product',$data))
			{
				require_once LIBRARIES.'product_content_blocks.php';
				if(isset($_POST['section_mode'])) productContentSectionSetMode($d, $id, $_POST['section_mode'], isset($_POST['section_inherit_source']) ? $_POST['section_inherit_source'] : null);
				if(!empty($oldSlugRow['slugvi']) && $oldSlugRow['slugvi'] !== $data['slugvi']) productAdminRememberRedirect($d, 'san-pham/'.$oldSlugRow['slugvi'], 'product', $id);
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file2"))
				{
					$photoUpdate = array();
					$file_name2 = $func->uploadName($_FILES["file2"]["name"]);

					if($photo2 = $func->uploadImage("file2", $config['product'][$type]['img_type2'], UPLOAD_PRODUCT, $file_name2))
					{
						$row = $d->rawQueryOne("select id, photo2 from #_product where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo2']);
						}

						$photoUpdate['photo2'] = $photo2;
						$d->where('id', $id);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* Khung khuyến mãi/sự kiện PNG — phủ lên ảnh sản phẩm khi bật cờ "khuyenmai" */
				if($func->hasFile("file_khuyenmai"))
				{
					$photoUpdate = array();
					$file_name_khuyenmai = $func->uploadName($_FILES["file_khuyenmai"]["name"]);

					if($photoKhuyenMai = $func->uploadImage("file_khuyenmai", '.png', UPLOAD_PRODUCT, $file_name_khuyenmai))
					{
						$row = $d->rawQueryOne("select id, photo_khuyenmai from #_product where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row['photo_khuyenmai']))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo_khuyenmai']);
						}

						$photoUpdate['photo_khuyenmai'] = $photoKhuyenMai;
						$d->where('id', $id);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if(isset($_POST['remove_photo_khuyenmai']))
				{
					$row = $d->rawQueryOne("select id, photo_khuyenmai from #_product where id = ? and type = ? limit 0,1",array($id,$type));
					if(!empty($row['photo_khuyenmai']))
					{
						$func->deleteFile(UPLOAD_PRODUCT.$row['photo_khuyenmai']);
						$d->where('id', $id);
						$d->update('product', array('photo_khuyenmai' => ''));
					}
				}

				/* Anh Shopping/quang cao: nen sach, khong logo/chu. */
				if($func->hasFile("file_feed"))
				{
					$photoUpdate = array();
					$file_name_feed = $func->uploadName($_FILES["file_feed"]["name"]);

					if($photoFeed = $func->uploadImage("file_feed", $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $file_name_feed))
					{
						$row = $d->rawQueryOne("select id, photo_feed from #_product where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row['photo_feed']))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo_feed']);
						}

						$photoUpdate['photo_feed'] = $photoFeed;
						$d->where('id', $id);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if(isset($_POST['remove_photo_feed']))
				{
					$row = $d->rawQueryOne("select id, photo_feed from #_product where id = ? and type = ? limit 0,1",array($id,$type));
					if(!empty($row['photo_feed']))
					{
						$func->deleteFile(UPLOAD_PRODUCT.$row['photo_feed']);
						$d->where('id', $id);
						$d->update('product', array('photo_feed' => ''));
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo']) && $config['product'][$type]['seo'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Tags */
				if(isset($config['product'][$type]['tags']) && $config['product'][$type]['tags'] == true)
				{
					if($dataTags)
					{
						$d->rawQuery("delete from #_product_tags where id_parent = ?",array($id));
						foreach($dataTags as $v)
						{
							$dataTag = array();
							$dataTag['id_parent'] = $id;
							$dataTag['id_tags'] = $v;
							$d->insert('product_tags',$dataTag);
						}
					}
					else
					{
						$d->rawQuery("delete from #_product_tags where id_parent = ?",array($id));
					}
				}

				/* Sale */
				if(!empty($config['product'][$type]['color']) || !empty($config['product'][$type]['size']))
				{
					if(!empty($dataColor) && !empty($dataSize))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_color';
						$dataSale1['data'] = $dataColor;

						$dataSale2 = array();
						$dataSale2['id'] = 'id_size';
						$dataSale2['data'] = $dataSize;
					}
					else if(!empty($dataColor))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_color';
						$dataSale1['data'] = $dataColor;
					}
					else if(!empty($dataSize))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_size';
						$dataSale1['data'] = $dataSize;
					}

					if(!empty($dataSale1['data']) || !empty($dataSale2['data']))
					{
						$d->rawQuery("delete from #_product_sale where id_parent = ?",array($id));

						foreach($dataSale1['data'] as $v_sale1)
						{
							$dataSale = array();
							$dataSale['id_parent'] = $id;
							$dataSale[$dataSale1['id']] = $v_sale1;

							if(!empty($dataSale2['data']))
							{
								foreach($dataSale2['data'] as $v_sale2)
								{
									$dataSale[$dataSale2['id']] = $v_sale2;
									$d->insert('product_sale',$dataSale);
								}
							}
							else
							{
								$d->insert('product_sale',$dataSale);
							}
						}
					}
					else
					{
						$d->rawQuery("delete from #_product_sale where id_parent = ?",array($id));
					}
				}

				if($savehere)
				{
					$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix());
				}
				else
				{
					$func->transfer("Cập nhật dữ liệu thành công", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl));
				}
			}
			else
			{
				if($savehere)
				{
					$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id.adminReturnSuffix(), false);
				}
				else
				{
					$func->transfer("Cập nhật dữ liệu bị lỗi", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
				}
			}
		}
		else
		{	
			$data['date_created'] = time();

			if($d->insert('product',$data))
			{
				$id_insert = $d->getLastInsertId();
				require_once LIBRARIES.'product_content_blocks.php';
				if(isset($_POST['section_mode'])) productContentSectionSetMode($d, $id_insert, $_POST['section_mode'], isset($_POST['section_inherit_source']) ? $_POST['section_inherit_source'] : null);

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file2"))
				{
					$photoUpdate = array();
					$file_name2 = $func->uploadName($_FILES['file2']["name"]);

					if($photo2 = $func->uploadImage("file2", $config['product'][$type]['img_type2'], UPLOAD_PRODUCT, $file_name2))
					{
						$photoUpdate['photo2'] = $photo2;
						$d->where('id', $id_insert);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file_khuyenmai"))
				{
					$photoUpdate = array();
					$file_name_khuyenmai = $func->uploadName($_FILES['file_khuyenmai']["name"]);

					if($photoKhuyenMai = $func->uploadImage("file_khuyenmai", '.png', UPLOAD_PRODUCT, $file_name_khuyenmai))
					{
						$photoUpdate['photo_khuyenmai'] = $photoKhuyenMai;
						$d->where('id', $id_insert);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				if($func->hasFile("file_feed"))
				{
					$photoUpdate = array();
					$file_name_feed = $func->uploadName($_FILES['file_feed']["name"]);

					if($photoFeed = $func->uploadImage("file_feed", $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $file_name_feed))
					{
						$photoUpdate['photo_feed'] = $photoFeed;
						$d->where('id', $id_insert);
						$d->update('product', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo']) && $config['product'][$type]['seo'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Tags */
				if(isset($config['product'][$type]['tags']) && $config['product'][$type]['tags'] == true)
				{
					if($dataTags)
					{
						foreach($dataTags as $v)
						{
							$dataTag = array();
							$dataTag['id_parent'] = $id_insert;
							$dataTag['id_tags'] = $v;
							$d->insert('product_tags',$dataTag);
						}
					}
				}

				/* Sale */
				if(!empty($config['product'][$type]['color']) || !empty($config['product'][$type]['size']))
				{
					if(!empty($dataColor) && !empty($dataSize))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_color';
						$dataSale1['data'] = $dataColor;

						$dataSale2 = array();
						$dataSale2['id'] = 'id_size';
						$dataSale2['data'] = $dataSize;
					}
					else if(!empty($dataColor))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_color';
						$dataSale1['data'] = $dataColor;
					}
					else if(!empty($dataSize))
					{
						$dataSale1 = array();
						$dataSale1['id'] = 'id_size';
						$dataSale1['data'] = $dataSize;
					}

					if(!empty($dataSale1['data']) || !empty($dataSale2['data']))
					{
						foreach($dataSale1['data'] as $v_sale1)
						{
							$dataSale = array();
							$dataSale['id_parent'] = $id_insert;
							$dataSale[$dataSale1['id']] = $v_sale1;

							if(!empty($dataSale2['data']))
							{
								foreach($dataSale2['data'] as $v_sale2)
								{
									$dataSale[$dataSale2['id']] = $v_sale2;
									$d->insert('product_sale',$dataSale);
								}
							}
							else
							{
								$d->insert('product_sale',$dataSale);
							}
						}
					}
				}

				/* Cập nhật hash khi upload multi */
				$hash = (isset($_POST['hash']) && $_POST['hash'] != '') ? addslashes($_POST['hash']) : null;
				if($hash)
				{
					$d->rawQuery("update #_gallery set hash = ?, id_parent = ? where hash = ?",array('',$id_insert,$hash));
				}

				if($act=='save_copy')
				{
					if($savehere)
					{
						$func->transfer("Sao chép dữ liệu thành công", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix());
					}
					else
					{
						$func->transfer("Sao chép dữ liệu thành công", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl));
					}
				}
				else
				{
					if($savehere)
					{
						$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix());
					}
					else
					{
						$func->transfer("Lưu dữ liệu thành công", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl));
					}
				}
			}
			else
			{
				if($act=='save_copy')
				{
					if($savehere)
					{
						$func->transfer("Sao chép dữ liệu bị lỗi", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix(), false);
					}
					else
					{
						$func->transfer("Sao chép dữ liệu bị lỗi", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
					}
				}
				else
				{
					if($savehere)
					{
						$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=edit&type=".$type."&p=".$curPage.$strUrl."&id=".$id_insert.adminReturnSuffix(), false);
					}
					else
					{
						$func->transfer("Lưu dữ liệu bị lỗi", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
					}
				}
			}
		}
	}

	/* Delete man */
	function deleteMan()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product where id = ?",array($id));
				$d->rawQuery("delete from #_product_project where id_product = ?", array($id));
				$d->rawQuery("delete from #_content_section where scope = 'product' and scope_id = ?", array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

				/* Xóa Tags */
				$d->rawQuery("delete from #_product_tags where id_parent = ?",array($id));

				/* Xóa Sale */
				$d->rawQuery("delete from #_product_sale where id_parent = ?",array($id));

				/* Xóa gallery */
				$rowGallery = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));

				if(count($rowGallery))
				{
					foreach($rowGallery as $v)
					{
						$func->deleteFile(UPLOAD_PRODUCT.$v['photo']);
						$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
					}

					$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));
				}

				/* Xóa comment */
				$rowComment = $d->rawQuery("select id, id_parent from #_comment where id_variant = ? and type = ?",array($id,$type));

				if(!empty($rowComment))
				{
					foreach($rowComment as $v)
					{
						if($v['id_parent'] == 0)
						{
							/* Xóa comment photo */
							$rowCommentPhoto = $d->rawQuery("select photo from #_comment_photo where id_parent = ?",array($v['id']));

							if(!empty($rowCommentPhoto))
							{
								/* Xóa image */
								foreach($rowCommentPhoto as $v_photo)
								{
									$func->deleteFile(UPLOAD_PHOTO.$v_photo['photo']);
								}

								/* Xóa photo */
								$d->rawQuery("delete from #_comment_photo where id_parent = ?",array($v['id']));
							}

							/* Xóa comment video */
							$rowCommentVideo = $d->rawQueryOne("select photo, video from #_comment_video where id_parent = ? limit 0,1",array($v['id']));

							if(!empty($rowCommentVideo))
							{
								$func->deleteFile(UPLOAD_PHOTO.$rowCommentVideo['photo']);
								$func->deleteFile(UPLOAD_VIDEO.$rowCommentVideo['video']);
								$d->rawQuery("delete from #_comment_video where id_parent = ?",array($v['id']));
							}

							/* Xóa child */
							$d->rawQuery("delete from #_comment where id_parent = ? and type = ?",array($v['id'],$type));
						}

						/* Xóa comment main */
						$d->rawQuery("delete from #_comment where id = ? and type = ?",array($v['id'],$type));
					}
				}

				$func->transfer("Xóa dữ liệu thành công", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl));
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product where id = ?",array($id));
					$d->rawQuery("delete from #_product_project where id_product = ?", array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man',$type));

					/* Xóa Tags */
					$d->rawQuery("delete from #_product_tags where id_parent = ?",array($id));

					/* Xóa Sale */
					$d->rawQuery("delete from #_product_sale where id_parent = ?",array($id));

					/* Xóa gallery */
					$rowGallery = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));

					if(count($rowGallery))
					{
						foreach($rowGallery as $v)
						{
							$func->deleteFile(UPLOAD_PRODUCT.$v['photo']);
							$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
						}

						$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man',$com));
					}

					/* Xóa comment */
					$rowComment = $d->rawQuery("select id, id_parent from #_comment where id_variant = ? and type = ?",array($id,$type));

					if(!empty($rowComment))
					{
						foreach($rowComment as $v)
						{
							if($v['id_parent'] == 0)
							{
								/* Xóa comment photo */
								$rowCommentPhoto = $d->rawQuery("select photo from #_comment_photo where id_parent = ?",array($v['id']));

								if(!empty($rowCommentPhoto))
								{
									/* Xóa image */
									foreach($rowCommentPhoto as $v_photo)
									{
										$func->deleteFile(UPLOAD_PHOTO.$v_photo['photo']);
									}

									/* Xóa photo */
									$d->rawQuery("delete from #_comment_photo where id_parent = ?",array($v['id']));
								}

								/* Xóa comment video */
								$rowCommentVideo = $d->rawQueryOne("select photo, video from #_comment_video where id_parent = ? limit 0,1",array($v['id']));

								if(!empty($rowCommentVideo))
								{
									$func->deleteFile(UPLOAD_PHOTO.$rowCommentVideo['photo']);
									$func->deleteFile(UPLOAD_VIDEO.$rowCommentVideo['video']);
									$d->rawQuery("delete from #_comment_video where id_parent = ?",array($v['id']));
								}

								/* Xóa child */
								$d->rawQuery("delete from #_comment where id_parent = ? and type = ?",array($v['id'],$type));
							}

							/* Xóa comment main */
							$d->rawQuery("delete from #_comment where id = ? and type = ?",array($v['id'],$type));
						}
					}
				}
			}

			$func->transfer("Xóa dữ liệu thành công", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl));
		} 
		else
		{
			$func->transfer("Không nhận được dữ liệu", adminReturnUrl("index.php?com=product&act=man&type=".$type."&p=".$curPage.$strUrl), false);
		}
	}

	/* View size */
	function viewSizes()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;

		$where = "";
		$params = array($type);

		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_size where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_size where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_size&type=".$type.$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit size */
	function editSize()
	{
		global $d, $func, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_size where id = ? limit 0,1",array($id));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
			}
		}
	}

	/* Save size */
	function saveSize()
	{
		global $d, $func, $flash, $curPage, $config, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				$data[$column] = htmlspecialchars($func->sanitize($value));
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

			$data['type'] = $type;
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_size&type=".$type."&p=".$curPage."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_size&type=".$type."&p=".$curPage);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();

			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('size',$data))
			{
				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
			}
		}
		else
		{
			$data['date_created'] = time();

			if($d->insert('size',$data))
			{
				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
			}
		}
	}

	/* Delete size */
	function deleteSize()
	{
		global $d, $func, $curPage, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			$row = $d->rawQueryOne("select id from #_size where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				$d->rawQuery("delete from #_size where id = ? and type = ?",array($id,$type));

				/* Xóa size in Sale */
				$d->rawQuery("delete from #_product_sale where id_size = ?",array($id));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);
				$row = $d->rawQueryOne("select id from #_size where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					$d->rawQuery("delete from #_size where id = ? and type = ?",array($id,$type));

					/* Xóa size in Sale */
					$d->rawQuery("delete from #_product_sale where id_size = ?",array($id));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage);
		} 
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_size&type=".$type."&p=".$curPage, false);
		}
	}

	/* View color */
	function viewColors()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;

		$where = "";
		$params = array($type);

		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_color where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_color where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_color&type=".$type.$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit color */
	function editColor()
	{
		global $d, $func, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_color where id = ? limit 0,1",array($id));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
			}
		}
	}

	/* Save color */
	function saveColor()
	{
		global $d, $func, $flash, $curPage, $config, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				$data[$column] = htmlspecialchars($func->sanitize($value));
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

			$data['type'] = $type;
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_color&type=".$type."&p=".$curPage."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_color&type=".$type."&p=".$curPage);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();

			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('color',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_color'], UPLOAD_COLOR, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_color where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_COLOR.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('color', $photoUpdate);
						unset($photoUpdate);
					}
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
			}
		}
		else
		{
			$data['date_created'] = time();

			if($d->insert('color',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_color'], UPLOAD_COLOR, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('color', $photoUpdate);
						unset($photoUpdate);
					}
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
			}
		}
	}

	/* Delete color */
	function deleteColor()
	{
		global $d, $curPage, $func, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			$row = $d->rawQueryOne("select * from #_color where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				$func->deleteFile(UPLOAD_COLOR.$row['photo']);
				$d->rawQuery("delete from #_color where id = ?",array($id));

				/* Xóa color in Sale */
				$d->rawQuery("delete from #_product_sale where id_color = ?",array($id));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);
				$row = $d->rawQueryOne("select * from #_color where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					$func->deleteFile(UPLOAD_COLOR.$row['photo']);
					$d->rawQuery("delete from #_color where id = ?",array($id));

					/* Xóa color in Sale */
					$d->rawQuery("delete from #_product_sale where id_color = ?",array($id));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage);
		} 
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_color&type=".$type."&p=".$curPage, false);
		}
	}

	/* View list */
	function viewLists()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type, $adminPerPage;
		
		$where = "";
		$params = array($type);

		if(isset($_REQUEST['keyword']) && trim((string)$_REQUEST['keyword']) !== '')
		{
			$keyword = '%'.trim((string)$_REQUEST['keyword']).'%';
			$where .= " and (pl.namevi LIKE ? or pl.nameen LIKE ?)";
			$params[] = $keyword;
			$params[] = $keyword;
		}

		$perPage = $adminPerPage;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select pl.*, (select count(*) from #_product p where p.id_list = pl.id and p.type = pl.type) as product_count from #_product_list pl where pl.type = ? $where order by pl.numb,pl.id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product_list pl where pl.type = ? $where";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_list&type=".$type.$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit list */
	function editList()
	{
		global $d, $func, $strUrl, $curPage, $item, $gallery, $type, $com;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product_list where id = ? and type = ? limit 0,1",array($id,$type));
		
			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
			else
			{
				/* Get gallery */
				$gallery = $d->rawQuery("select * from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc",array($id,$com,$type,'man_list',$type));
			}
		}
	}

	/* Save list */
	function saveList()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$oldSlugRow = $id ? $d->rawQueryOne("select slugvi from #_product_list where id=? and type=? limit 1", array($id,$type)) : array();
		$oldChildRows = $id ? $d->rawQuery("select id, slugvi from #_product_cat where id_list=? and type=?", array($id,$type)) : array();
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
		    {
		        $status = '';
		        foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
		        $data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
		    }
		    else
		    {
		        $data['status'] = "";
		    }

		    if(!empty($config['product'][$type]['slug_list']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }

			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo_list']) && $config['product'][$type]['seo_list'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
			$dataSeo = productAdminSeoDefaults($func, $data, isset($dataSeo) ? $dataSeo : array());
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug_list']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_list&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_list&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product_list',$data))
			{
				if(!empty($oldSlugRow['slugvi']) && $oldSlugRow['slugvi'] !== $data['slugvi']) productAdminRememberRedirect($d, 'danh-muc-san-pham/'.$oldSlugRow['slugvi'], 'product_list', $id);
				if(!empty($oldSlugRow['slugvi']) && $oldSlugRow['slugvi'] !== $data['slugvi']) foreach($oldChildRows as $oldChildRow) productAdminRememberRedirect($d, 'danh-muc-san-pham/'.$oldSlugRow['slugvi'].'/'.$oldChildRow['slugvi'], 'product_cat', $oldChildRow['id']);
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_list'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product_list where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product_list', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_list']) && $config['product'][$type]['seo_list'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_list';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{		
			$data['date_created'] = time();
			
			if($d->insert('product_list',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_list'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product_list', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_list']) && $config['product'][$type]['seo_list'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_list';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				/* Cập nhật hash khi upload multi */
				$hash = (isset($_POST['hash']) && $_POST['hash'] != '') ? addslashes($_POST['hash']) : null;
				if($hash)
				{
					$d->rawQuery("update #_gallery set hash = ?, id_parent = ? where hash = ?",array('',$id_insert,$hash));
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete list */
	function deleteList()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			if(productAdminCategoryDependency($d,'list',$id,$type)) $func->transfer("Không thể xóa: danh mục còn sản phẩm hoặc danh mục con. Hãy chuyển dữ liệu trước.", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product_list where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product_list where id = ?",array($id));
				$d->rawQuery("delete from #_content_section where scope = 'list' and scope_id = ?", array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

				/* Xóa gallery */
				$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));

				if(count($row))
				{
					foreach($row as $v)
					{
						$func->deleteFile(UPLOAD_PRODUCT.$v['photo']);
						$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
					}

					$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));
				}

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);
			foreach($listid as $checkId) if(productAdminCategoryDependency($d,'list',(int)$checkId,$type)) $func->transfer("Không thể xóa hàng loạt: có danh mục còn sản phẩm hoặc danh mục con.", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product_list where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product_list where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_list',$type));

					/* Xóa gallery */
					$row = $d->rawQuery("select id, photo, file_attach from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));

					if(count($row))
					{
						foreach($row as $v)
						{
							$func->deleteFile(UPLOAD_PRODUCT.$v['photo']);
							$func->deleteFile(UPLOAD_FILE.$v['file_attach']);
						}

						$d->rawQuery("delete from #_gallery where id_parent = ? and kind = ? and com = ?",array($id,'man_list',$com));
					}
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_list&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* Get cat */
	function viewCats()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type, $adminPerPage;
		
		$where = "";
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$params = array($type);

		if($idlist) { $where .= " and pc.id_list = ?"; $params[] = $idlist; }
		if(isset($_REQUEST['keyword']) && trim((string)$_REQUEST['keyword']) !== '')
		{
			$keyword = '%'.trim((string)$_REQUEST['keyword']).'%';
			$where .= " and (pc.namevi LIKE ? or pc.nameen LIKE ?)";
			$params[] = $keyword;
			$params[] = $keyword;
		}

		$perPage = $adminPerPage;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select pc.*, (select count(*) from #_product p where p.id_cat = pc.id and p.type = pc.type) as product_count from #_product_cat pc where pc.type = ? $where order by pc.numb,pc.id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product_cat pc where pc.type = ? $where";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_cat".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit cat */
	function editCat()
	{
		global $d, $func, $strUrl, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product_cat where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save cat */
	function saveCat()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$oldSlugRow = $id ? $d->rawQueryOne("select pc.slugvi, pc.id_list, pl.slugvi list_slugvi from #_product_cat pc join #_product_list pl on pl.id=pc.id_list where pc.id=? and pc.type=? limit 1", array($id,$type)) : array();
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

		    if(!empty($config['product'][$type]['slug_cat']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }
			
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo_cat']) && $config['product'][$type]['seo_cat'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
			$dataSeo = productAdminSeoDefaults($func, $data, isset($dataSeo) ? $dataSeo : array());
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug_cat']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_cat&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_cat&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product_cat',$data))
			{
				if(!empty($oldSlugRow['slugvi']) && ($oldSlugRow['slugvi'] !== $data['slugvi'] || (int)$data['id_list'] !== (int)$oldSlugRow['id_list'])) productAdminRememberRedirect($d, 'danh-muc-san-pham/'.$oldSlugRow['list_slugvi'].'/'.$oldSlugRow['slugvi'], 'product_cat', $id);
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_cat'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product_cat where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product_cat', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_cat']) && $config['product'][$type]['seo_cat'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_cat';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('product_cat',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_cat'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product_cat', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_cat']) && $config['product'][$type]['seo_cat'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_cat';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete cat */
	function deleteCat()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			if(productAdminCategoryDependency($d,'cat',$id,$type)) $func->transfer("Không thể xóa: danh mục còn sản phẩm hoặc danh mục con. Hãy chuyển dữ liệu trước.", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product_cat where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product_cat where id = ?",array($id));
				$d->rawQuery("delete from #_content_section where scope = 'cat' and scope_id = ?", array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);
			foreach($listid as $checkId) if(productAdminCategoryDependency($d,'cat',(int)$checkId,$type)) $func->transfer("Không thể xóa hàng loạt: có danh mục còn sản phẩm hoặc danh mục con.", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product_cat where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product_cat where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_cat',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_cat&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* View item */
	function viewItems()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;
		
		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$idcat = isset($_REQUEST['id_cat']) ? (int)$_REQUEST['id_cat'] : 0;

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if($idcat > 0) { $where .= " and id_cat = ?"; $params[] = $idcat; }
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product_item where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product_item where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_item".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit item */
	function editItem()
	{
		global $d, $func, $strUrl, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product_item where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save item */
	function saveItem()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$oldSlugRow = $id ? $d->rawQueryOne("select pi.slugvi, pi.id_list, pi.id_cat, pc.slugvi cat_slugvi, pl.slugvi list_slugvi from #_product_item pi join #_product_cat pc on pc.id=pi.id_cat join #_product_list pl on pl.id=pi.id_list where pi.id=? and pi.type=? limit 1", array($id,$type)) : array();
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

		    if(!empty($config['product'][$type]['slug_item']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }
			
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo_item']) && $config['product'][$type]['seo_item'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
			$dataSeo = productAdminSeoDefaults($func, $data, isset($dataSeo) ? $dataSeo : array());
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug_item']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_item&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_item&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product_item',$data))
			{
				if(!empty($oldSlugRow['slugvi']) && ($oldSlugRow['slugvi'] !== $data['slugvi'] || (int)$oldSlugRow['id_list'] !== (int)$data['id_list'] || (int)$oldSlugRow['id_cat'] !== (int)$data['id_cat'])) productAdminRememberRedirect($d, 'danh-muc-san-pham/'.$oldSlugRow['list_slugvi'].'/'.$oldSlugRow['cat_slugvi'].'/'.$oldSlugRow['slugvi'], 'product_item', $id);
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_item'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product_item where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product_item', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_item']) && $config['product'][$type]['seo_item'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_item';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('product_item',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_item'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product_item', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_item']) && $config['product'][$type]['seo_item'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_item';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete item */
	function deleteItem()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			if(productAdminCategoryDependency($d,'item',$id,$type)) $func->transfer("Không thể xóa: danh mục còn sản phẩm hoặc danh mục con. Hãy chuyển dữ liệu trước.", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product_item where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product_item where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);
			foreach($listid as $checkId) if(productAdminCategoryDependency($d,'item',(int)$checkId,$type)) $func->transfer("Không thể xóa hàng loạt: có danh mục còn sản phẩm hoặc danh mục con.", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product_item where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product_item where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_item',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_item&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* View sub */
	function viewSubs()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;

		$where = "";
		$params = array($type);
		$idlist = isset($_REQUEST['id_list']) ? (int)$_REQUEST['id_list'] : 0;
		$idcat = isset($_REQUEST['id_cat']) ? (int)$_REQUEST['id_cat'] : 0;
		$iditem = isset($_REQUEST['id_item']) ? (int)$_REQUEST['id_item'] : 0;

		if($idlist > 0) { $where .= " and id_list = ?"; $params[] = $idlist; }
		if($idcat > 0) { $where .= " and id_cat = ?"; $params[] = $idcat; }
		if($iditem > 0) { $where .= " and id_item = ?"; $params[] = $iditem; }
		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product_sub where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product_sub where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_sub".$strUrl."&type=".$type;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit sub */
	function editSub()
	{
		global $d, $func, $strUrl, $curPage, $item, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product_sub where id = ? and type = ? limit 0,1",array($id,$type));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save sub */
	function saveSub()
	{
		global $d, $strUrl, $func, $flash, $curPage, $config, $com, $type;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
		
		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
		$oldSlugRow = $id ? $d->rawQueryOne("select ps.slugvi, ps.id_list, ps.id_cat, ps.id_item, pi.slugvi item_slugvi, pc.slugvi cat_slugvi, pl.slugvi list_slugvi from #_product_sub ps join #_product_item pi on pi.id=ps.id_item join #_product_cat pc on pc.id=ps.id_cat join #_product_list pl on pl.id=ps.id_list where ps.id=? and ps.type=? limit 1", array($id,$type)) : array();
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

		    if(!empty($config['product'][$type]['slug_sub']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }
			
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo_sub']) && $config['product'][$type]['seo_sub'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
			$dataSeo = productAdminSeoDefaults($func, $data, isset($dataSeo) ? $dataSeo : array());
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug_sub']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_sub&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_sub&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product_sub',$data))
			{
				if(!empty($oldSlugRow['slugvi']) && ($oldSlugRow['slugvi'] !== $data['slugvi'] || (int)$oldSlugRow['id_list'] !== (int)$data['id_list'] || (int)$oldSlugRow['id_cat'] !== (int)$data['id_cat'] || (int)$oldSlugRow['id_item'] !== (int)$data['id_item'])) productAdminRememberRedirect($d, 'danh-muc-san-pham/'.$oldSlugRow['list_slugvi'].'/'.$oldSlugRow['cat_slugvi'].'/'.$oldSlugRow['item_slugvi'].'/'.$oldSlugRow['slugvi'], 'product_sub', $id);
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_sub'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product_sub where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product_sub', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_sub']) && $config['product'][$type]['seo_sub'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_sub';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		else
		{
			$data['date_created'] = time();
			
			if($d->insert('product_sub',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_sub'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product_sub', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_sub']) && $config['product'][$type]['seo_sub'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_sub';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Delete sub */
	function deleteSub()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			if(productAdminCategoryDependency($d,'sub',$id,$type)) $func->transfer("Không thể xóa: danh mục còn sản phẩm. Hãy chuyển dữ liệu trước.", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product_sub where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product_sub where id = ?",array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);
			foreach($listid as $checkId) if(productAdminCategoryDependency($d,'sub',(int)$checkId,$type)) $func->transfer("Không thể xóa hàng loạt: có danh mục còn sản phẩm.", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product_sub where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product_sub where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_sub',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_sub&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}

	/* View brand */
	function viewBrands()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $type;

		$where = "";
		$params = array($type);

		if(isset($_REQUEST['keyword']))
		{
			$keywordClause = adminKeywordLikeClause($_REQUEST['keyword'], array('namevi', 'nameen'), $params);
			if($keywordClause !== '') $where .= " and ".$keywordClause;
		}

		$perPage = 10;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select * from #_product_brand where type = ? $where order by numb,id desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_product_brand where type = ? $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=product&act=man_brand&type=".$type.$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);
	}

	/* Edit brand */
	function editBrand()
	{
		global $d, $func, $strUrl, $curPage, $item, $gallery, $type, $com;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_product_brand where id = ? and type = ? limit 0,1",array($id,$type));
		
			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
	}

	/* Save brand */
	function saveBrand()
	{
		global $d, $curPage, $func, $flash, $config, $com, $type;

		/* Check post*/
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage, false);
		}

		/* Post dữ liệu */
		$message = '';
		$response = array();
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$data = (!empty($_POST['data'])) ? $_POST['data'] : null;
		if($data)
		{
			foreach($data as $column => $value)
			{
				if(strpos($column, 'content') !== false || strpos($column, 'desc') !== false)
				{
					$data[$column] = htmlspecialchars($func->sanitize($value, 'iframe'));
				}
				else
				{
					$data[$column] = htmlspecialchars($func->sanitize($value));
				}
			}

			if(isset($_POST['status']))
			{
				$status = '';
				foreach($_POST['status'] as $attr_column => $attr_value) if($attr_value != "") $status .= $attr_value.',';
				$data['status'] = (!empty($status)) ? rtrim($status, ",") : "";
			}
			else
			{
				$data['status'] = "";
			}

		    if(!empty($config['product'][$type]['slug_brand']))
		    {
		    	if(!empty($_POST['slugvi'])) $data['slugvi'] = $func->changeTitle(htmlspecialchars($_POST['slugvi']));
		    	else $data['slugvi'] = (!empty($data['namevi'])) ? $func->changeTitle($data['namevi']) : '';
		    	if(!empty($_POST['slugen'])) $data['slugen'] = $func->changeTitle(htmlspecialchars($_POST['slugen']));
		    	else $data['slugen'] = (!empty($data['nameen'])) ? $func->changeTitle($data['nameen']) : '';
		    }
			
			$data['type'] = $type;
		}

		/* Post seo */
		if(isset($config['product'][$type]['seo_brand']) && $config['product'][$type]['seo_brand'] == true)
		{
			$dataSeo = (isset($_POST['dataSeo'])) ? $_POST['dataSeo'] : null;
			if($dataSeo)
			{
				foreach($dataSeo as $column => $value)
				{
					$dataSeo[$column] = htmlspecialchars($func->sanitize($value));
				}
			}
		}

		/* Valid data */
		$checkTitle = $func->checkTitle($data);

		if(!empty($checkTitle))
		{
			foreach($checkTitle as $k => $v)
			{
				$response['messages'][] = $v;
			}
		}

		if(!empty($config['product'][$type]['slug_brand']))
		{
			foreach($config['website']['slug'] as $k => $v)
			{
				$dataSlug = array();
				$dataSlug['slug'] = $data['slug'.$k];
				$dataSlug['id'] = $id;
				$dataSlug['copy'] = false;
				$checkSlug = $func->checkSlug($dataSlug);

				if($checkSlug == 'exist')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') đã tồn tại';
				}
				else if($checkSlug == 'empty')
				{
					$response['messages'][] = 'Đường dẫn ('.$v.') không được trống';
				}
			}
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($data))
			{
				foreach($data as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			if(!empty($dataSeo))
			{
				foreach($dataSeo as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);

			if($id)
			{
				$func->redirect("index.php?com=product&act=edit_brand&type=".$type."&p=".$curPage.$strUrl."&id=".$id);
			}
			else
			{
				$func->redirect("index.php?com=product&act=add_brand&type=".$type."&p=".$curPage.$strUrl);
			}
		}

		/* Save data */
		if($id)
		{
			$data['date_updated'] = time();
			
			$d->where('id', $id);
			$d->where('type', $type);
			if($d->update('product_brand',$data))
			{
				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES["file"]["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_brand'], UPLOAD_PRODUCT, $file_name))
					{
						$row = $d->rawQueryOne("select id, photo from #_product_brand where id = ? and type = ? limit 0,1",array($id,$type));

						if(!empty($row))
						{
							$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
						}

						$photoUpdate['photo'] = $photo;
						$d->where('id', $id);
						$d->update('product_brand', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_brand']) && $config['product'][$type]['seo_brand'] == true)
				{
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_brand',$type));

					$dataSeo['id_parent'] = $id;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_brand';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage, false);
			}
		}
		else
		{		
			$data['date_created'] = time();
			
			if($d->insert('product_brand',$data))
			{
				$id_insert = $d->getLastInsertId();

				/* Photo */
				if($func->hasFile("file"))
				{
					$photoUpdate = array();
					$file_name = $func->uploadName($_FILES['file']["name"]);

					if($photo = $func->uploadImage("file", $config['product'][$type]['img_type_brand'], UPLOAD_PRODUCT, $file_name))
					{
						$photoUpdate['photo'] = $photo;
						$d->where('id', $id_insert);
						$d->update('product_brand', $photoUpdate);
						unset($photoUpdate);
					}
				}

				/* SEO */
				if(isset($config['product'][$type]['seo_brand']) && $config['product'][$type]['seo_brand'] == true)
				{
					$dataSeo['id_parent'] = $id_insert;
					$dataSeo['com'] = $com;
					$dataSeo['act'] = 'man_brand';
					$dataSeo['type'] = $type;
					$d->insert('seo',$dataSeo);
				}

				$func->transfer("Lưu dữ liệu thành công", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage);
			}
			else
			{
				$func->transfer("Lưu dữ liệu bị lỗi", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage, false);
			}
		}
	}

	/* Delete brand */
	function deleteBrand()
	{
		global $d, $strUrl, $func, $curPage, $com, $type;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			/* Xóa thương hiệu còn sản phẩm sẽ để lại id_brand mồ côi: sản phẩm mất
			   nhãn thương hiệu và biến mất khỏi bộ lọc thương hiệu ngoài public. */
			if(productAdminCategoryDependency($d,'brand',$id,$type)) $func->transfer("Không thể xóa: thương hiệu còn sản phẩm. Hãy chuyển sản phẩm sang thương hiệu khác trước.", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl, false);

			/* Lấy dữ liệu */
			$row = $d->rawQueryOne("select id, photo from #_product_brand where id = ? and type = ? limit 0,1",array($id,$type));

			if(!empty($row))
			{
				/* Xóa chính */
				$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
				$d->rawQuery("delete from #_product_brand where id = ?",array($id));
				$d->rawQuery("delete from #_content_section where scope = 'brand' and scope_id = ?", array($id));

				/* Xóa SEO */
				$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_brand',$type));

				$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl, false);
			}
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);

				/* Lấy dữ liệu */
				$row = $d->rawQueryOne("select id, photo from #_product_brand where id = ? and type = ? limit 0,1",array($id,$type));

				if(!empty($row))
				{
					/* Xóa chính */
					$func->deleteFile(UPLOAD_PRODUCT.$row['photo']);
					$d->rawQuery("delete from #_product_brand where id = ?",array($id));

					/* Xóa SEO */
					$d->rawQuery("delete from #_seo where id_parent = ? and com = ? and act = ? and type = ?",array($id,$com,'man_brand',$type));
				}
			}

			$func->transfer("Xóa dữ liệu thành công", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=product&act=man_brand&type=".$type."&p=".$curPage.$strUrl, false);
		}
	}
?>
