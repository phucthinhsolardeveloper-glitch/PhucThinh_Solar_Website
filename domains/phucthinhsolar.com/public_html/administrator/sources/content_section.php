<?php
/* ============================================================
   Khối nội dung mô tả dùng chung / override.
   com=content_section
     act=man   -> danh sách hãng + danh mục
     act=edit  -> composer cho một (scope, scope_id)
     act=save  -> lưu blocks JSON (+ upload ảnh từng khối)
   Kế thừa khi hiển thị: sản phẩm > hãng > danh mục (xử lý ở frontend).
   ============================================================ */

require_once LIBRARIES.'product_content_blocks.php';

$sectionScopeLabels = array('brand' => 'Thương hiệu', 'cat' => 'Danh mục cấp 2', 'list' => 'Danh mục cấp 1', 'product' => 'Sản phẩm');

if(!function_exists('contentSectionScopeName'))
{
	function contentSectionScopeName($d, $scope, $scopeId, $type)
	{
		if($scope === 'brand')  $r = $d->rawQueryOne("select namevi from #_product_brand where id = ? and type = ? limit 0,1", array($scopeId, $type));
		elseif($scope === 'list') $r = $d->rawQueryOne("select namevi from #_product_list where id = ? and type = ? limit 0,1", array($scopeId, $type));
		elseif($scope === 'cat') $r = $d->rawQueryOne("select namevi from #_product_cat where id = ? and type = ? limit 0,1", array($scopeId, $type));
		else $r = $d->rawQueryOne("select namevi from #_product where id = ? and type = ? limit 0,1", array($scopeId, $type));
		return !empty($r['namevi']) ? $r['namevi'] : '';
	}

	function contentSectionList()
	{
		global $d, $type, $sectionExisting, $sectionBrands, $sectionCats, $sectionLists, $sectionSummary;
		$rows = $d->rawQuery("select scope, scope_id, mode, blocksvi, date_updated from #_content_section");
		$sectionExisting = array();
		$ownIds = array(); $hideIds = array(); $overrideIds = array();
		$brandWith = array(); $catWith = array(); $listWith = array();
		foreach($rows as $r)
		{
			$blocks = productSectionsDecode($r['blocksvi']);
			$sectionExisting[$r['scope'].':'.(int)$r['scope_id']] = array('count' => count($blocks), 'mode' => $r['mode'], 'date_updated' => (int)$r['date_updated']);
			if($r['scope'] === 'product')
			{
				$overrideIds[] = (int)$r['scope_id'];
				if($r['mode'] === 'hide') $hideIds[] = (int)$r['scope_id'];
				elseif($blocks) $ownIds[] = (int)$r['scope_id'];
			}
			elseif($r['scope'] === 'brand' && $blocks) $brandWith[] = (int)$r['scope_id'];
			elseif($r['scope'] === 'cat' && $blocks) $catWith[] = (int)$r['scope_id'];
			elseif($r['scope'] === 'list' && $blocks) $listWith[] = (int)$r['scope_id'];
		}

		$total = (int) $d->rawQueryOne("select count(*) c from #_product where type = ? and find_in_set('hienthi',status)", array($type))['c'];
		$inheritable = 0;
		if($brandWith || $catWith || $listWith)
		{
			$cond = array(); $params = array($type);
			if($brandWith) { $cond[] = 'id_brand in ('.implode(',', array_fill(0, count($brandWith), '?')).')'; $params = array_merge($params, $brandWith); }
			if($catWith)   { $cond[] = 'id_cat in ('.implode(',', array_fill(0, count($catWith), '?')).')';    $params = array_merge($params, $catWith); }
			if($listWith)  { $cond[] = 'id_list in ('.implode(',', array_fill(0, count($listWith), '?')).')';  $params = array_merge($params, $listWith); }
			$notIn = $overrideIds ? ' and id not in ('.implode(',', array_map('intval', $overrideIds)).')' : '';
			$inheritable = (int) $d->rawQueryOne("select count(*) c from #_product where type = ? and find_in_set('hienthi',status)".$notIn." and (".implode(' or ', $cond).")", $params)['c'];
		}
		$own = count(array_unique($ownIds)); $hide = count(array_unique($hideIds));
		$sectionSummary = array(
			'total' => $total,
			'own' => $own,
			'hide' => $hide,
			'inherit' => $inheritable,
			'auto_only' => max(0, $total - $own - $hide - $inheritable),
		);

		$sectionBrands = $d->rawQuery("select id, namevi from #_product_brand where type = ? and find_in_set('hienthi',status) order by namevi", array($type));
		$sectionCats   = $d->rawQuery("select id, namevi from #_product_cat where type = ? and find_in_set('hienthi',status) order by numb, id", array($type));
		$sectionLists  = $d->rawQuery("select id, namevi from #_product_list where type = ? and find_in_set('hienthi',status) order by numb, id", array($type));
	}

	function contentSectionEdit()
	{
		global $d, $type, $func, $sectionScope, $sectionScopeId, $sectionScopeName, $sectionItem, $sectionBlocks;
		$sectionScope = in_array(isset($_GET['scope']) ? $_GET['scope'] : '', array('brand', 'cat', 'list', 'product'), true) ? $_GET['scope'] : '';
		$sectionScopeId = (int) (!empty($_GET['scope_id']) ? $_GET['scope_id'] : 0);
		if($sectionScope === '' || $sectionScopeId <= 0)
			$func->transfer('Thiếu tham số scope/scope_id', 'index.php?com=content_section&act=man', false);

		$sectionScopeName = contentSectionScopeName($d, $sectionScope, $sectionScopeId, $type);
		if($sectionScopeName === '')
			$func->transfer('Không tìm thấy đối tượng', 'index.php?com=content_section&act=man', false);

		$sectionItem = $d->rawQueryOne("select * from #_content_section where scope = ? and scope_id = ? limit 0,1", array($sectionScope, $sectionScopeId));
		$sectionBlocks = $sectionItem ? productSectionsDecode($sectionItem['blocksvi']) : array();
	}

	function contentSectionSave()
	{
		global $d, $type, $func, $config;

		if(empty($_POST)) $func->transfer('Không nhận được dữ liệu', 'index.php?com=content_section&act=man', false);

		$scope = in_array(isset($_POST['scope']) ? $_POST['scope'] : '', array('brand', 'cat', 'list', 'product'), true) ? $_POST['scope'] : '';
		$scopeId = (int) (!empty($_POST['scope_id']) ? $_POST['scope_id'] : 0);
		if($scope === '' || $scopeId <= 0) $func->transfer('Thiếu tham số', 'index.php?com=content_section&act=man', false);
		$back = 'index.php?com=content_section&act=edit&scope='.$scope.'&scope_id='.$scopeId;

		if(contentSectionScopeName($d, $scope, $scopeId, $type) === '')
			$func->transfer('Không tìm thấy đối tượng', 'index.php?com=content_section&act=man', false);

		$decoded = json_decode(isset($_POST['blocks_json']) ? (string) $_POST['blocks_json'] : '[]', true);
		if(!is_array($decoded)) $decoded = array();

		$clean = array();
		foreach($decoded as $i => $block)
		{
			if(!is_array($block) || empty($block['type'])) continue;
			$t = $block['type'];
			$out = array('type' => $t);

			if($t === 'heading')
			{
				$out['text'] = trim(strip_tags((string) (isset($block['text']) ? $block['text'] : '')));
				if($out['text'] === '') continue;
			}
			elseif($t === 'text')
			{
				$out['text'] = trim((string) (isset($block['text']) ? $block['text'] : ''));
				if($out['text'] === '') continue;
			}
			elseif($t === 'list')
			{
				$items = array();
				if(!empty($block['items']) && is_array($block['items']))
					foreach($block['items'] as $it) { $it = trim(strip_tags((string) $it)); if($it !== '') $items[] = $it; }
				if(!$items) continue;
				$out['ordered'] = !empty($block['ordered']);
				$out['items'] = $items;
			}
			elseif($t === 'image' || $t === 'media')
			{
				$photo = trim((string) (isset($block['photo']) ? $block['photo'] : ''));
				$fkey = 'bf_'.$i;
				if($func->hasFile($fkey))
				{
					$fileName = $func->uploadName($_FILES[$fkey]['name']);
					$uploaded = $func->uploadImage($fkey, $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $fileName);
					if($uploaded) $photo = $uploaded;
				}
				$photo = preg_replace('#[^A-Za-z0-9._/-]#', '', $photo);
				$photo = ltrim(str_replace('..', '', $photo), '/');

				if($t === 'image')
				{
					if($photo === '') continue;
					$out['photo'] = $photo;
					$out['caption'] = trim(strip_tags((string) (isset($block['caption']) ? $block['caption'] : '')));
				}
				else
				{
					$out['photo'] = $photo;
					$out['title'] = trim(strip_tags((string) (isset($block['title']) ? $block['title'] : '')));
					$out['text']  = trim((string) (isset($block['text']) ? $block['text'] : ''));
					$out['side']  = in_array(isset($block['side']) ? $block['side'] : '', array('left', 'right', 'center'), true) ? $block['side'] : 'left';
					if($out['photo'] === '' && $out['title'] === '' && $out['text'] === '') continue;
				}
			}
			else continue;

			$clean[] = $out;
		}

		$blocksJson = $clean ? htmlspecialchars(json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) : null;

		/* Soạn khối cho sản phẩm = chế độ "own". Có khối rồi mà đang inherit thì
		   chuyển sang own; xoá hết khối ở sản phẩm -> quay lại inherit. */
		$exists = $d->rawQueryOne("select id, mode from #_content_section where scope = ? and scope_id = ? limit 0,1", array($scope, $scopeId));
		if($exists)
		{
			$upd = array('blocksvi' => $blocksJson, 'date_updated' => time());
			if($scope === 'product')
				$upd['mode'] = $clean ? ($exists['mode'] === 'both' ? 'both' : 'own') : ($exists['mode'] === 'hide' ? 'hide' : 'inherit');
			$d->where('id', $exists['id']);
			$d->update('content_section', $upd);
		}
		elseif($clean)
		{
			$d->insert('content_section', array('scope' => $scope, 'scope_id' => $scopeId, 'mode' => ($scope === 'product' ? 'own' : 'own'), 'blocksvi' => $blocksJson, 'status' => 'hienthi', 'date_updated' => time()));
		}

		$func->transfer('Đã lưu khối nội dung ('.count($clean).' khối)', $back);
	}

	function contentSectionImportLegacy()
	{
		global $d, $type;
		header('Content-Type: application/json; charset=UTF-8');
		$scopeId = (int) (!empty($_GET['scope_id']) ? $_GET['scope_id'] : 0);
		if($scopeId <= 0) { echo json_encode(array('ok' => false, 'msg' => 'Thiếu scope_id')); exit; }
		$row = $d->rawQueryOne("select contentvi from #_product where id = ? and type = ? limit 0,1", array($scopeId, $type));
		if(empty($row)) { echo json_encode(array('ok' => false, 'msg' => 'Không tìm thấy sản phẩm')); exit; }
		$parsed = productLegacyHtmlToBlocks(htmlspecialchars_decode((string) $row['contentvi'], ENT_QUOTES));
		echo json_encode(array('ok' => true, 'blocks' => $parsed['blocks'], 'skipped_images' => $parsed['skipped_images']), JSON_UNESCAPED_UNICODE);
		exit;
	}

	function contentSectionDelete()
	{
		global $d, $type, $func;
		$scope = in_array(isset($_POST['scope']) ? $_POST['scope'] : '', array('brand', 'cat', 'list', 'product'), true) ? $_POST['scope'] : '';
		$scopeId = (int) (!empty($_POST['scope_id']) ? $_POST['scope_id'] : 0);
		$back = 'index.php?com=content_section&act=man&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
		if($scope === '' || $scopeId <= 0) $func->transfer('Thiếu tham số', $back, false);
		$d->rawQuery("delete from #_content_section where scope = ? and scope_id = ?", array($scope, $scopeId));
		$func->transfer('Đã xoá bản khối nội dung', $back);
	}

	function contentSectionUploadImage()
	{
		global $type, $func, $config;
		header('Content-Type: application/json; charset=UTF-8');
		if(!$func->hasFile('image')) { echo json_encode(array('ok' => false, 'msg' => 'Không nhận được file')); exit; }
		$allow = isset($config['product'][$type]['img_type']) ? $config['product'][$type]['img_type'] : '.jpg|.png|.jpeg|.webp';
		$fileName = $func->uploadName($_FILES['image']['name']);
		$uploaded = $func->uploadImage('image', $allow, UPLOAD_PRODUCT, $fileName);
		if(!$uploaded) { echo json_encode(array('ok' => false, 'msg' => 'Ảnh không hợp lệ (chỉ .jpg .png .jpeg .webp)')); exit; }
		echo json_encode(array('ok' => true, 'photo' => $uploaded, 'url' => '../upload/product/'.$uploaded), JSON_UNESCAPED_UNICODE);
		exit;
	}

	function contentSectionInheritedBlocks()
	{
		global $d, $type;
		header('Content-Type: application/json; charset=UTF-8');
		$scopeId = (int) (!empty($_GET['scope_id']) ? $_GET['scope_id'] : 0);
		if($scopeId <= 0) { echo json_encode(array('ok' => false, 'msg' => 'Thiếu scope_id')); exit; }
		/* id_cat thiếu ở đây trước 2026-09-10 khiến nút này không bao giờ chép được
		   khối của danh mục cấp 2 - chỉ hãng/cấp 1. */
		$row = $d->rawQueryOne("select id, id_brand, id_cat, id_list from #_product where id = ? and type = ? limit 0,1", array($scopeId, $type));
		if(empty($row)) { echo json_encode(array('ok' => false, 'msg' => 'Không tìm thấy sản phẩm')); exit; }
		$res = productInheritedSectionBlocks($d, $row, 'vi');
		echo json_encode(array('ok' => true, 'blocks' => $res['blocks'], 'source' => $res['source']), JSON_UNESCAPED_UNICODE);
		exit;
	}
}

switch($act)
{
	case 'edit':
		contentSectionEdit();
		$template = 'content_section/edit';
		break;
	case 'save':
		contentSectionSave();
		break;
	case 'import_legacy':
		contentSectionImportLegacy();
		break;
	case 'inherited_blocks':
		contentSectionInheritedBlocks();
		break;
	case 'upload_image':
		contentSectionUploadImage();
		break;
	case 'delete':
		contentSectionDelete();
		break;
	case 'man':
	default:
		contentSectionList();
		$template = 'content_section/list';
		break;
}
