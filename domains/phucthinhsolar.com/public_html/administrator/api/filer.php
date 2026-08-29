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

	if($cmd === 'refresh' && ($id_parent > 0 || $hash !== ''))
	{
		$gallery = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind);
		if($gallery) filerRenderGallery($gallery, $func, $com, $colfiler);
	}
	else if($cmd === 'info' && $id > 0 && ($id_parent > 0 || $hash !== ''))
	{
		$allowedRows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id');
		$allowedIds = array_map('intval', array_column($allowedRows, 'id'));
		if(!in_array($id, $allowedIds, true)) exit();
		$data = array();
		if($info === 'numb') $data['numb'] = max(0, (int)$value);
		if($info === 'name') $data['namevi'] = function_exists('mb_substr') ? mb_substr($value, 0, 255, 'UTF-8') : substr($value, 0, 255);
		if(!$data) exit();
		$d->where('id',$id);
		if($d->update('gallery',$data))
		{
			$gallery = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind);
			if($gallery) filerRenderGallery($gallery, $func, $com, $colfiler);
			$cache->delete();
		}
	}
	else if($cmd === 'updateNumb' && ($id_parent > 0 || $hash !== ''))
	{
		$ids = filerNormalizeIds($listid);
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, numb');
		$allowed = array();
		foreach($rows as $row) $allowed[(int)$row['id']] = (int)$row['numb'];
		if(!$ids || count($ids) !== count($rows)) exit();
		foreach($ids as $galleryId) if(!isset($allowed[$galleryId])) exit();
		$arrNumb = array_values($allowed);
		foreach($ids as $index => $galleryId) { $d->where('id',$galleryId); $d->update('gallery',array('numb' => $arrNumb[$index])); }
		$cache->delete();
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('id' => $ids, 'numb' => $arrNumb));
	}
	else if($cmd === 'delete' && $id > 0 && ($id_parent > 0 || $hash !== ''))
	{
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, photo, com');
		foreach($rows as $row) if((int)$row['id'] === $id)
		{
			filerDeletePhoto($func, $row['com'], $row['photo']);
			$d->rawQuery("delete from #_gallery where id = ?",array($id));
			$cache->delete();
			break;
		}
	}
	else if($cmd === 'delete-all' && ($id_parent > 0 || $hash !== ''))
	{
		$ids = filerNormalizeIds($listid);
		$rows = filerGalleryRows($d, $id_parent, $hash, $com, $type, $kind, 'id, photo, com');
		$selected = array_fill_keys($ids, true);
		foreach($rows as $row)
		{
			$galleryId = (int)$row['id'];
			if(!isset($selected[$galleryId])) continue;
			filerDeletePhoto($func, $row['com'], $row['photo']);
			$d->rawQuery("delete from #_gallery where id = ?",array($galleryId));
		}
		if($ids) $cache->delete();
	}
?>
