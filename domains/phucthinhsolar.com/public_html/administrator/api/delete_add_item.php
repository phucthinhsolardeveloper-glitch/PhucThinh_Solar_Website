<?php 
	include "config.php";
	adminRequirePostCsrf(true);
	adminRequireMutationPermission('sortable', 'delete', '', '', true);
	$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
	$item = isset($_POST['item']) ? (string)$_POST['item'] : '';
	$tables = array('menu'=>'sortable', 'index'=>'sortable_index', 'detail'=>'sortable_detail');
	header('Content-Type: application/json; charset=UTF-8');
	if($id < 1 || !isset($tables[$item]))
	{
		http_response_code(400);
		echo json_encode(adminApiEnvelope(false, 'Mục cần xóa không hợp lệ.', 'INVALID_SORTABLE_ITEM', array(), array('success'=>false)));
		exit();
	}

	$table = $tables[$item];
	try
	{
		$row = $d->rawQueryOne("select id from #_{$table} where id = ? limit 0,1", array($id));
		if(empty($row['id']))
		{
			http_response_code(404);
			echo json_encode(adminApiEnvelope(false, 'Mục cần xóa không tồn tại.', 'SORTABLE_ITEM_NOT_FOUND', array(), array('success'=>false)));
			exit();
		}
		$d->rawQuery("delete from #_{$table} where id = ?", array($id));
		$remaining = $d->rawQueryOne("select id from #_{$table} where id = ? limit 0,1", array($id));
		if(!empty($remaining['id'])) throw new RuntimeException('Sortable item was not deleted');
		echo json_encode(adminApiEnvelope(true, 'Đã xóa mục.', 'SORTABLE_ITEM_DELETED', array('id'=>$id, 'item'=>$item), array('success'=>true)));
	}
	catch(Throwable $e)
	{
		error_log('Admin sortable delete failed: '.$e->getMessage());
		http_response_code(500);
		echo json_encode(adminApiEnvelope(false, 'Không thể xóa mục.', 'SORTABLE_DELETE_FAILED', array(), array('success'=>false)));
	}
 ?>
