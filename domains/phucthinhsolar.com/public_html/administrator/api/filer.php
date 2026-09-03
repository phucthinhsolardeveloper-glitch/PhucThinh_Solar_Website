<?php
	include "config.php";

	$id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
	$id_parent = !empty($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;
	$info = !empty($_POST['info']) ? (string)$_POST['info'] : '';
	$value = isset($_POST['value']) ? trim((string)$_POST['value']) : '';
	$listid = isset($_POST['listid']) ? $_POST['listid'] : array();
	$com = !empty($_POST['com']) ? (string)$_POST['com'] : '';
	$kind = !empty($_POST['kind']) ? (string)$_POST['kind'] : '';
	$type = !empty($_POST['type']) ? (string)$_POST['type'] : '';
	$colfiler = !empty($_POST['colfiler']) ? (string)$_POST['colfiler'] : '';
	$cmd = !empty($_POST['cmd']) ? (string)$_POST['cmd'] : '';
	$hash = !empty($_POST['hash']) ? (string)$_POST['hash'] : '';

	if(in_array($cmd, array('info', 'updateNumb', 'delete', 'delete-all'), true)) adminRequirePostCsrf();
	if(!in_array($com, array('product', 'news'), true)) exit();
	if(!preg_match('/^[a-zA-Z0-9_-]{0,100}$/', $kind)) exit();
	if(!preg_match('/^[a-zA-Z0-9_-]{0,100}$/', $type)) exit();
	if(!preg_match('/^[a-zA-Z0-9 _-]{0,50}$/', $colfiler)) $colfiler = '';
	if($hash !== '' && !preg_match('/^[a-zA-Z0-9]{1,64}$/', $hash)) exit();
	if(in_array($cmd, array('info', 'updateNumb', 'delete', 'delete-all'), true)) adminRequireMutationPermission('gallery', $cmd, $type, $com);

	function filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, $columns = 'numb, id, photo, namevi')
	{
		if($id_parent > 0) return $d->rawQuery("select ".$columns." from #_gallery where id_parent = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc", array($id_parent,$com,$type,$kind,$type));
		if($hash !== '') return $d->rawQuery("select ".$columns." from #_gallery where hash = ? and com = ? and type = ? and kind = ? and val = ? order by numb,id desc", array($hash,$com,$type,$kind,$type));
		return array();
	}

	function filerRenderGallery($gallery, $func, $com, $colfiler)
	{
		foreach($gallery as $item) echo $func->galleryFiler($item['numb'],$item['id'],$item['photo'],$item['namevi'],$com,$colfiler);
	}

	function filerGalleryHtml($gallery, $func, $com, $colfiler)
	{
		$html = '';
		$bufferLevel = ob_get_level();
		foreach($gallery as $item) $html .= $func->galleryFiler($item['numb'],$item['id'],$item['photo'],$item['namevi'],$com,$colfiler);
		while(ob_get_level() > $bufferLevel) ob_end_clean();
		return $html;
	}

	function filerNormalizeIds($value)
	{
		if(!is_array($value)) $value = explode(',', (string)$value);
		$result = array();
		foreach($value as $id) { $id = (int)$id; if($id > 0) $result[$id] = $id; }
		return array_values($result);
	}

	function filerDeletePhoto($func, $com, $photo)
	{
		$folder = adminGalleryUploadFolder($com);
		if($folder === null || !adminSafeUploadBasename($photo)) return;
		$func->deleteFile('../../upload/'.$folder.'/'.$photo);
	}

	function filerJsonResponse($ok, $message, $code, $data = array(), $legacy = array(), $status = 200)
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(adminApiEnvelope($ok, $message, $code, $data, $legacy));
		exit();
	}

	if($cmd === 'refresh' && ($id_parent > 0 || $hash !== ''))
	{
		$gallery = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind);
		if($gallery) filerRenderGallery($gallery, $func, $com, $colfiler);
	}
	else if($cmd === 'info' && $id > 0 && ($id_parent > 0 || $hash !== ''))
	{
		$allowedRows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id');
		$allowedIds = array_map('intval', array_column($allowedRows, 'id'));
		if(!in_array($id, $allowedIds, true)) filerJsonResponse(false, 'Ảnh không tồn tại trong album này.', 'GALLERY_ITEM_NOT_FOUND', array(), array('success'=>false), 404);
		$data = array();
		if($info === 'numb' && preg_match('/^\d{1,9}$/', $value)) $data['numb'] = (int)$value;
		if($info === 'name') $data['namevi'] = function_exists('mb_substr') ? mb_substr($value, 0, 255, 'UTF-8') : substr($value, 0, 255);
		if(!$data) filerJsonResponse(false, 'Thông tin ảnh không hợp lệ.', 'INVALID_GALLERY_INFO', array(), array('success'=>false), 400);
		$d->where('id',$id);
		$d->update('gallery',$data);
		$saved = $d->rawQueryOne('select numb, namevi from #_gallery where id = ?', array($id));
		$field = $info === 'numb' ? 'numb' : 'namevi';
		$expected = $data[$field];
		if(!$saved) filerJsonResponse(false, 'Không thể cập nhật thông tin ảnh.', 'GALLERY_INFO_UPDATE_FAILED', array(), array('success'=>false), 500);
		$actual = $field === 'numb' ? (int)$saved[$field] : (string)$saved[$field];
		if($actual !== $expected) filerJsonResponse(false, 'Không thể cập nhật thông tin ảnh.', 'GALLERY_INFO_UPDATE_FAILED', array(), array('success'=>false), 500);
		$gallery = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind);
		$html = filerGalleryHtml($gallery, $func, $com, $colfiler);
		$cache->delete();
		filerJsonResponse(true, 'Đã cập nhật thông tin ảnh.', 'GALLERY_INFO_UPDATED', array('id'=>$id, 'field'=>$field, 'value'=>$actual, 'html'=>$html), array('success'=>true, 'html'=>$html));
	}
	else if($cmd === 'updateNumb' && ($id_parent > 0 || $hash !== ''))
	{
		$ids = filerNormalizeIds($listid);
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, numb');
		$allowed = array();
		foreach($rows as $row) $allowed[(int)$row['id']] = (int)$row['numb'];
		if(!$ids || count($ids) > 200 || count($ids) !== count($rows)) filerJsonResponse(false, 'Danh sách sắp xếp không hợp lệ.', 'INVALID_GALLERY_ORDER', array(), array('success'=>false), 400);
		foreach($ids as $galleryId)
			if(!isset($allowed[$galleryId])) filerJsonResponse(false, 'Danh sách sắp xếp không thuộc album hiện tại.', 'INVALID_GALLERY_ORDER', array(), array('success'=>false), 400);

		$arrNumb = array_values($allowed);
		$caseSql = array();
		$params = array();
		foreach($ids as $index => $galleryId)
		{
			$caseSql[] = 'when ? then ?';
			$params[] = $galleryId;
			$params[] = $arrNumb[$index];
		}
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$params = array_merge($params, $ids);
		$d->rawQuery('update #_gallery set numb = case id '.implode(' ', $caseSql).' end where id in ('.$placeholders.')', $params);

		$updatedRows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, numb');
		$updated = array();
		foreach($updatedRows as $row) $updated[(int)$row['id']] = (int)$row['numb'];
		foreach($ids as $index => $galleryId)
			if(!isset($updated[$galleryId]) || $updated[$galleryId] !== $arrNumb[$index]) filerJsonResponse(false, 'Không thể cập nhật thứ tự album.', 'GALLERY_ORDER_FAILED', array(), array('success'=>false), 500);

		$cache->delete();
		filerJsonResponse(true, 'Đã cập nhật thứ tự album.', 'GALLERY_ORDER_UPDATED', array('ids'=>$ids, 'numb'=>$arrNumb, 'updated'=>count($ids)), array('success'=>true, 'id'=>$ids, 'numb'=>$arrNumb));
	}
	else if($cmd === 'delete' && $id > 0 && ($id_parent > 0 || $hash !== ''))
	{
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, photo, com');
		$target = null;
		foreach($rows as $row) if((int)$row['id'] === $id)
		{
			$target = $row;
			break;
		}
		if(!$target) filerJsonResponse(false, 'Ảnh không tồn tại trong album này.', 'GALLERY_ITEM_NOT_FOUND', array(), array('success'=>false), 404);
		$d->rawQuery("delete from #_gallery where id = ?", array($id));
		if($d->rawQueryOne("select id from #_gallery where id = ?", array($id))) filerJsonResponse(false, 'Không thể xóa ảnh.', 'GALLERY_DELETE_FAILED', array(), array('success'=>false), 500);
		filerDeletePhoto($func, $target['com'], $target['photo']);
		$cache->delete();
		filerJsonResponse(true, 'Đã xóa ảnh.', 'GALLERY_ITEM_DELETED', array('ids'=>array($id), 'deleted'=>1), array('success'=>true, 'id'=>$id));
	}
	else if($cmd === 'delete-all' && ($id_parent > 0 || $hash !== ''))
	{
		$ids = filerNormalizeIds($listid);
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, photo, com');
		$allowed = array();
		foreach($rows as $row) $allowed[(int)$row['id']] = $row;
		if(!$ids) filerJsonResponse(false, 'Chưa chọn ảnh cần xóa.', 'EMPTY_GALLERY_SELECTION', array(), array('success'=>false), 400);
		foreach($ids as $galleryId)
			if(!isset($allowed[$galleryId])) filerJsonResponse(false, 'Danh sách ảnh không thuộc album hiện tại.', 'INVALID_GALLERY_SELECTION', array(), array('success'=>false), 400);

		$selected = array_fill_keys($ids, true);
		$deleted = array();
		foreach($rows as $row)
		{
			$galleryId = (int)$row['id'];
			if(!isset($selected[$galleryId])) continue;
			$d->rawQuery("delete from #_gallery where id = ?", array($galleryId));
			if($d->rawQueryOne("select id from #_gallery where id = ?", array($galleryId))) filerJsonResponse(false, 'Không thể xóa toàn bộ ảnh đã chọn.', 'GALLERY_BULK_DELETE_FAILED', array('ids'=>$deleted, 'deleted'=>count($deleted)), array('success'=>false), 500);
			filerDeletePhoto($func, $row['com'], $row['photo']);
			$deleted[] = $galleryId;
		}
		$cache->delete();
		filerJsonResponse(true, 'Đã xóa các ảnh được chọn.', 'GALLERY_ITEMS_DELETED', array('ids'=>$deleted, 'deleted'=>count($deleted)), array('success'=>true, 'id'=>$deleted));
	}
?>
