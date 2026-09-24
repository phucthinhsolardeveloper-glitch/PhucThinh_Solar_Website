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

	function contentSectionStatusVisible($status)
	{
		return in_array('hienthi', array_filter(array_map('trim', explode(',', (string) $status))), true);
	}

	function contentSectionStatusSetVisible($status, $visible)
	{
		$flags = array();
		foreach(array_filter(array_map('trim', explode(',', (string) $status))) as $flag)
		{
			if($flag === 'hienthi' || $flag === 'an') continue;
			if(!in_array($flag, $flags, true)) $flags[] = $flag;
		}
		array_unshift($flags, $visible ? 'hienthi' : 'an');
		return implode(',', $flags);
	}

	function contentSectionSourceLabel($scope, $mode)
	{
		if($scope !== 'product') return 'Nội dung gắn trực tiếp';
		$labels = array(
			'inherit' => 'Kế thừa thương hiệu / danh mục',
			'own'    => 'Nội dung riêng sản phẩm',
			'both'   => 'Riêng sản phẩm + kế thừa',
			'hide'   => 'Ẩn mô tả dài'
		);
		return isset($labels[$mode]) ? $labels[$mode] : 'Chưa xác định';
	}

	function contentSectionBackupRows($d)
	{
		try
		{
			return $d->rawQuery("select id, scope, scope_id, action, date_created from #_content_section_backup order by id desc");
		}
		catch(Throwable $e)
		{
			/* Cho phép mở Admin trong lúc migration backup chưa chạy; thao tác xoá
			   vẫn bị chặn ở contentSectionBackupCreate() để không mất dữ liệu. */
			return array();
		}
	}

	function contentSectionBackupCreate($d, $row, $action = 'delete')
	{
		global $loginAdmin;
		if(empty($row) || empty($row['scope']) || (int) $row['scope_id'] <= 0)
			throw new RuntimeException('Không có bản ghi nội dung để sao lưu.');

		$createdBy = !empty($_SESSION[$loginAdmin]['id']) ? (int) $_SESSION[$loginAdmin]['id'] : 0;
		$backupId = $d->insert('content_section_backup', array(
			'content_section_id' => !empty($row['id']) ? (int) $row['id'] : 0,
			'scope' => (string) $row['scope'],
			'scope_id' => (int) $row['scope_id'],
			'blocksvi' => isset($row['blocksvi']) ? $row['blocksvi'] : null,
			'blocksen' => isset($row['blocksen']) ? $row['blocksen'] : null,
			'mode' => !empty($row['mode']) ? $row['mode'] : 'own',
			'inherit_source' => !empty($row['inherit_source']) ? $row['inherit_source'] : 'auto',
			'status' => !empty($row['status']) ? $row['status'] : 'hienthi',
			'date_updated' => !empty($row['date_updated']) ? (int) $row['date_updated'] : 0,
			'action' => $action,
			'date_created' => time(),
			'created_by' => $createdBy
		));
		if(!$backupId) throw new RuntimeException('Không thể tạo bản sao nội dung.');
		return (int) $backupId;
	}

	function contentSectionList()
	{
		global $d, $type, $sectionExisting, $sectionBrands, $sectionCats, $sectionLists, $sectionSummary, $sectionBackups;
		$rows = $d->rawQuery("select id, scope, scope_id, mode, blocksvi, date_updated, status from #_content_section");
		$sectionExisting = array();
		$sectionBackups = array();
		foreach(contentSectionBackupRows($d) as $backup)
		{
			$key = $backup['scope'].':'.(int) $backup['scope_id'];
			if(!isset($sectionBackups[$key])) $sectionBackups[$key] = $backup;
		}
		$ownIds = array(); $hideIds = array(); $overrideIds = array();
		$brandWith = array(); $catWith = array(); $listWith = array();
		foreach($rows as $r)
		{
			$blocks = productSectionsDecode($r['blocksvi']);
			$key = $r['scope'].':'.(int)$r['scope_id'];
			$sectionExisting[$key] = array(
				'count' => count($blocks),
				'mode' => $r['mode'],
				'source_label' => contentSectionSourceLabel($r['scope'], $r['mode']),
				'visible' => contentSectionStatusVisible($r['status']),
				'date_updated' => (int)$r['date_updated'],
				'backup' => isset($sectionBackups[$key]) ? $sectionBackups[$key] : null
			);
			if($r['scope'] === 'product')
			{
				$overrideIds[] = (int)$r['scope_id'];
				if($r['mode'] === 'hide') $hideIds[] = (int)$r['scope_id'];
				elseif($blocks) $ownIds[] = (int)$r['scope_id'];
			}
			elseif($r['scope'] === 'brand' && $blocks && contentSectionStatusVisible($r['status'])) $brandWith[] = (int)$r['scope_id'];
			elseif($r['scope'] === 'cat' && $blocks && contentSectionStatusVisible($r['status'])) $catWith[] = (int)$r['scope_id'];
			elseif($r['scope'] === 'list' && $blocks && contentSectionStatusVisible($r['status'])) $listWith[] = (int)$r['scope_id'];
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

		$clean = contentSectionCleanBlocks($decoded, true, $type);

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

	function contentSectionToggle()
	{
		global $d, $type, $func;
		$scope = in_array(isset($_POST['scope']) ? $_POST['scope'] : '', array('brand', 'cat', 'list'), true) ? $_POST['scope'] : '';
		$scopeId = (int) (!empty($_POST['scope_id']) ? $_POST['scope_id'] : 0);
		$visible = isset($_POST['visible']) && (string) $_POST['visible'] === '1';
		$back = 'index.php?com=content_section&act=man&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
		if($scope === '' || $scopeId <= 0) $func->transfer('Thiếu tham số', $back, false);

		$row = $d->rawQueryOne("select id, status from #_content_section where scope = ? and scope_id = ? limit 0,1", array($scope, $scopeId));
		if(empty($row)) $func->transfer('Không tìm thấy bản nội dung', $back, false);
		$d->where('id', (int) $row['id']);
		$d->update('content_section', array('status' => contentSectionStatusSetVisible($row['status'], $visible), 'date_updated' => time()));
		$func->transfer($visible ? 'Đã bật nội dung trên website.' : 'Đã ẩn nội dung trên website.', $back);
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
		$row = $d->rawQueryOne("select * from #_content_section where scope = ? and scope_id = ? limit 0,1", array($scope, $scopeId));
		if(empty($row)) $func->transfer('Không tìm thấy bản khối nội dung', $back, false);
		try
		{
			contentSectionBackupCreate($d, $row, 'delete');
			$d->rawQuery("delete from #_content_section where scope = ? and scope_id = ?", array($scope, $scopeId));
		}
		catch(Throwable $e)
		{
			$func->transfer('Chưa xoá nội dung: không tạo được bản sao lưu. Hãy kiểm tra migration backup.', $back, false);
		}
		$func->transfer('Đã xoá bản khối nội dung', $back);
	}

	function contentSectionRestore()
	{
		global $d, $type, $func;
		$scope = in_array(isset($_POST['scope']) ? $_POST['scope'] : '', array('brand', 'cat', 'list', 'product'), true) ? $_POST['scope'] : '';
		$scopeId = (int) (!empty($_POST['scope_id']) ? $_POST['scope_id'] : 0);
		$backupId = (int) (!empty($_POST['backup_id']) ? $_POST['backup_id'] : 0);
		$back = 'index.php?com=content_section&act=man&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
		if($scope === '' || $scopeId <= 0 || $backupId <= 0) $func->transfer('Thiếu tham số phục hồi', $back, false);

		$backup = $d->rawQueryOne("select * from #_content_section_backup where id = ? and scope = ? and scope_id = ? limit 0,1", array($backupId, $scope, $scopeId));
		if(empty($backup)) $func->transfer('Không tìm thấy bản sao lưu', $back, false);
		$current = $d->rawQueryOne("select * from #_content_section where scope = ? and scope_id = ? limit 0,1", array($scope, $scopeId));
		try
		{
			if(!empty($current)) contentSectionBackupCreate($d, $current, 'restore-before');
			$payload = array(
				'blocksvi' => $backup['blocksvi'],
				'blocksen' => $backup['blocksen'],
				'mode' => !empty($backup['mode']) ? $backup['mode'] : 'own',
				'inherit_source' => !empty($backup['inherit_source']) ? $backup['inherit_source'] : 'auto',
				'status' => !empty($backup['status']) ? $backup['status'] : 'hienthi',
				'date_updated' => time()
			);
			if(!empty($current))
			{
				$d->where('id', (int) $current['id']);
				$d->update('content_section', $payload);
			}
			else
			{
				$payload['scope'] = $scope;
				$payload['scope_id'] = $scopeId;
				$d->insert('content_section', $payload);
			}
		}
		catch(Throwable $e)
		{
			$func->transfer('Không thể phục hồi bản sao lưu.', $back, false);
		}
		$func->transfer('Đã phục hồi bản nội dung gần nhất.', $back);
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

	/* Lọc + chuẩn hoá danh sách khối. Dùng chung cho lưu tay (có upload file) và
	   nhập JSON (không upload). Khối rỗng/sai loại bị bỏ. */
	function contentSectionCleanBlocks($decoded, $allowUpload = false, $type = '')
	{
		global $func, $config;
		if(!is_array($decoded)) return array();
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
				if($allowUpload)
				{
					$fkey = 'bf_'.$i;
					if($func->hasFile($fkey))
					{
						$fileName = $func->uploadName($_FILES[$fkey]['name']);
						$uploaded = $func->uploadImage($fkey, $config['product'][$type]['img_type'], UPLOAD_PRODUCT, $fileName);
						if($uploaded) $photo = $uploaded;
					}
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
			elseif($t === 'faq')
			{
				$out['question'] = trim(strip_tags((string) (isset($block['question']) ? $block['question'] : '')));
				$out['answer'] = trim(strip_tags((string) (isset($block['answer']) ? $block['answer'] : '')));
				if($out['question'] === '' || $out['answer'] === '') continue;
			}
			else continue;

			$clean[] = $out;
		}
		return $clean;
	}

	/* Xuất mọi bản khối (hãng/danh mục) của type hiện tại ra file JSON. */
	function contentSectionExport()
	{
		global $d, $type;
		$rows = $d->rawQuery("select scope, scope_id, mode, inherit_source, blocksvi, blocksen, status from #_content_section where scope in ('brand','cat','list') order by scope, scope_id");
		$items = array();
		foreach($rows as $r)
		{
			$blocks = contentSectionCleanBlocks(productSectionsDecode($r['blocksvi']), false, $type);
			if(!$blocks) continue;
			$name = contentSectionScopeName($d, $r['scope'], (int) $r['scope_id'], $type);
			if($name === '') continue;
			$entry = array(
				'scope'          => $r['scope'],
				'scope_name'     => $name,
				'scope_id'       => (int) $r['scope_id'],
				'mode'           => $r['mode'],
				'inherit_source' => $r['inherit_source'],
				'visible'        => contentSectionStatusVisible($r['status']),
				'blocks'         => $blocks,
			);
			$blocksEn = contentSectionCleanBlocks(productSectionsDecode($r['blocksen']), false, $type);
			if($blocksEn) $entry['blocks_en'] = $blocksEn;
			$items[] = $entry;
		}
		$payload = array(
			'meta'  => array('type' => $type, 'exported_at' => date('c'), 'count' => count($items)),
			'items' => $items,
		);
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		$fname = 'content-section-'.preg_replace('/[^a-z0-9\-]/i', '', (string) $type).'-'.date('Ymd-His').'.json';
		header('Content-Type: application/json; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$fname.'"');
		header('Content-Length: '.strlen($json));
		echo $json;
		exit;
	}

	/* Nhập JSON. $apply=false: chỉ dựng preview. $apply=true: ghi DB trong transaction.
	   Khớp đối tượng theo scope + tên (namevi), KHÔNG theo id -> an toàn local <-> production. */
	function contentSectionImport($apply)
	{
		global $d, $type, $func, $flash;
		global $importResults, $importSummary, $importPayloadRaw, $importError;
		$importResults = array();
		$importSummary = array('create' => 0, 'update' => 0, 'unchanged' => 0, 'skip' => 0, 'error' => 0, 'img_warn' => 0);
		$importPayloadRaw = '';
		$importError = '';

		$typeParam = htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8');
		$back        = 'index.php?com=content_section&act=man&type='.$typeParam;
		$selfPreview = 'index.php?com=content_section&act=import&type='.$typeParam;

		$raw = '';
		if($apply)
		{
			adminRequirePostCsrf();
			if(!isset($_POST['payload'])) $func->transfer('Thiếu dữ liệu nhập', $selfPreview, false);
			$raw = (string) $_POST['payload'];
		}
		elseif($func->hasFile('import_file'))
		{
			$tmp = isset($_FILES['import_file']['tmp_name']) ? $_FILES['import_file']['tmp_name'] : '';
			if($tmp && is_uploaded_file($tmp)) $raw = (string) file_get_contents($tmp);
			if(strlen($raw) > 3 * 1024 * 1024) { $importError = 'File quá lớn (giới hạn 3MB).'; return; }
		}
		else
		{
			return; /* hiện form upload rỗng */
		}

		$raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw); /* cắt BOM UTF-8 (editor Windows hay kèm) */
		$data = json_decode($raw, true);
		if(is_array($data) && isset($data['items']) && is_array($data['items'])) $rows = $data['items'];
		elseif(is_array($data)) $rows = $data;
		else { $importError = 'Nội dung không phải JSON hợp lệ.'; return; }

		$importPayloadRaw = $raw;
		$scopeLabels = array('brand' => 'Thương hiệu', 'cat' => 'Danh mục cấp 2', 'list' => 'Danh mục cấp 1');
		$plan = array();

		foreach($rows as $idx => $it)
		{
			$row = array('label' => '', 'name' => '', 'status' => 'error', 'msg' => '', 'scope' => '', 'scope_id' => 0, 'count' => 0, 'old' => null, 'id' => 0, 'img_missing' => 0, 'blocks' => array(), 'blocks_en' => null, 'visible' => true, 'current_status' => 'hienthi');

			if(!is_array($it)) { $row['msg'] = 'dòng '.($idx + 1).': không phải object'; $importSummary['error']++; $importResults[] = $row; continue; }

			$scope = isset($it['scope']) ? (string) $it['scope'] : '';
			$name  = isset($it['scope_name']) ? trim((string) $it['scope_name']) : '';
			$row['scope'] = $scope;
			$row['name']  = $name;
			$row['visible'] = !isset($it['visible']) || (bool) $it['visible'];
			$row['label'] = isset($scopeLabels[$scope]) ? $scopeLabels[$scope] : ($scope !== '' ? $scope : 'dòng '.($idx + 1));

			if(!isset($scopeLabels[$scope])) { $row['status'] = 'skip'; $row['msg'] = 'bỏ qua (chỉ nhận brand/cat/list)'; $importSummary['skip']++; $importResults[] = $row; continue; }
			if($name === '')                { $row['msg'] = 'thiếu scope_name'; $importSummary['error']++; $importResults[] = $row; continue; }

			$tbl  = $scope === 'brand' ? 'product_brand' : ($scope === 'cat' ? 'product_cat' : 'product_list');
			$hits = $d->rawQuery("select id from #_$tbl where namevi = ? and type = ?", array($name, $type));
			if(!$hits)              { $row['msg'] = 'không tìm thấy '.$row['label'].' tên "'.$name.'"'; $importSummary['error']++; $importResults[] = $row; continue; }
			if(count($hits) > 1)    { $row['msg'] = 'trùng tên "'.$name.'" ('.count($hits).' bản ghi)'; $importSummary['error']++; $importResults[] = $row; continue; }
			$sid = (int) $hits[0]['id'];
			$row['scope_id'] = $sid;

			$blocks   = contentSectionCleanBlocks(isset($it['blocks']) && is_array($it['blocks']) ? $it['blocks'] : array(), false, $type);
			$blocksEn = (isset($it['blocks_en']) && is_array($it['blocks_en'])) ? contentSectionCleanBlocks($it['blocks_en'], false, $type) : null;
			if(!$blocks) { $row['status'] = 'skip'; $row['msg'] = 'không có khối hợp lệ'; $importSummary['skip']++; $importResults[] = $row; continue; }
			$row['blocks']    = $blocks;
			$row['blocks_en'] = $blocksEn;
			$row['count']     = count($blocks);

			$missing = array();
			foreach($blocks as $b)
				if(!empty($b['photo']) && !is_file(UPLOAD_PRODUCT.$b['photo'])) $missing[] = $b['photo'];
			$row['img_missing'] = count($missing);
			if($missing)
			{
				$row['msg'] = 'thiếu ảnh: '.implode(', ', array_slice($missing, 0, 3)).(count($missing) > 3 ? '…' : '');
				$importSummary['img_warn']++;
			}

			$exist = $d->rawQueryOne("select id, blocksvi, status from #_content_section where scope = ? and scope_id = ? limit 0,1", array($scope, $sid));
			if($exist)
			{
				$row['id']  = (int) $exist['id'];
				$row['old'] = count(productSectionsDecode($exist['blocksvi']));
				$row['current_status'] = !empty($exist['status']) ? $exist['status'] : 'hienthi';
				$newJson = htmlspecialchars(json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
				if($blocksEn === null && productSectionsDecode($newJson) == productSectionsDecode((string) $exist['blocksvi']) && contentSectionStatusVisible($row['current_status']) === $row['visible'])
				{
					$row['status'] = 'unchanged';
					if($row['msg'] === '') $row['msg'] = 'không đổi';
					$importSummary['unchanged']++;
					$importResults[] = $row;
					continue;
				}
				$row['status'] = 'update';
				$importSummary['update']++;
			}
			else
			{
				$row['status'] = 'create';
				$importSummary['create']++;
			}
			$plan[] = $row;
			$importResults[] = $row;
		}

		if(!$apply) return;

		$done = 0;
		$d->startTransaction();
		try
		{
			foreach($plan as $r)
			{
				$json   = htmlspecialchars(json_encode($r['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
				$jsonEn = $r['blocks_en'] !== null
					? ($r['blocks_en'] ? htmlspecialchars(json_encode($r['blocks_en'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) : null)
					: false; /* false = giữ nguyên cột blocksen */

				if($r['id'])
				{
					$upd = array('blocksvi' => $json, 'status' => contentSectionStatusSetVisible($r['current_status'], $r['visible']), 'date_updated' => time());
					if($jsonEn !== false) $upd['blocksen'] = $jsonEn;
					$d->where('id', $r['id']);
					$d->update('content_section', $upd);
				}
				else
				{
					$ins = array('scope' => $r['scope'], 'scope_id' => $r['scope_id'], 'mode' => 'own', 'inherit_source' => 'auto', 'blocksvi' => $json, 'status' => $r['visible'] ? 'hienthi' : 'an', 'date_updated' => time());
					if($jsonEn !== false && $jsonEn !== null) $ins['blocksen'] = $jsonEn;
					$d->insert('content_section', $ins);
				}
				$done++;
			}
			$d->commit();
		}
		catch(Throwable $e)
		{
			$d->rollback();
			$func->transfer('Lỗi khi ghi dữ liệu, đã huỷ toàn bộ.', $selfPreview, false);
		}

		$toastText = 'Đã nhập '.$done.' mục ('.$importSummary['create'].' tạo mới, '.$importSummary['update'].' cập nhật, '.$importSummary['unchanged'].' không đổi). Bỏ qua '.$importSummary['skip'].', lỗi '.$importSummary['error'].'.';
		if($importSummary['img_warn'] > 0) $toastText .= ' ⚠ '.$importSummary['img_warn'].' mục có khối ảnh thiếu file — mở từng mục và upload ảnh.';
		$flash->set('toast', json_encode(array('status' => 'success', 'text' => $toastText), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$func->redirect($back);
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
	case 'toggle':
		contentSectionToggle();
		break;
	case 'import_legacy':
		contentSectionImportLegacy();
		break;
	case 'export':
		contentSectionExport();
		break;
	case 'import':
		contentSectionImport(false);
		$template = 'content_section/import';
		break;
	case 'import_run':
		contentSectionImport(true);
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
	case 'restore':
		contentSectionRestore();
		break;
	case 'man':
	default:
		contentSectionList();
		$template = 'content_section/list';
		break;
}
