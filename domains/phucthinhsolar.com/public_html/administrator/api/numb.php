<?php
	include "config.php";
	adminRequirePostCsrf(true);
	$result = adminApiEnvelope(false, 'Yêu cầu cập nhật thứ tự không hợp lệ.', 'INVALID_NUMB_REQUEST', array(), array('success'=>false));

	if(!empty($_POST['id']))
	{
		$table = (!empty($_POST['table'])) ? (string) $_POST['table'] : '';
		$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : 0;
		$value = isset($_POST['value']) ? max(0, (int) $_POST['value']) : 0;
		$type = (!empty($_POST['type'])) ? (string) $_POST['type'] : '';
		$com = (!empty($_POST['com'])) ? (string) $_POST['com'] : '';
		if(!$id || !adminAllowedMutationTable($table, 'numb')) { http_response_code(400); echo json_encode($result); exit(); }
		adminRequireMutationPermission($table, 'numb', $type, $com, true);

		$data['numb'] = $value;
		
		$d->where('id',$id);
		$updated = (bool)$d->update($table,$data);
		if($updated) $cache->delete();
		$result = adminApiEnvelope($updated, $updated ? 'Đã cập nhật thứ tự.' : 'Không thể cập nhật thứ tự.', $updated ? 'NUMB_UPDATED' : 'NUMB_UPDATE_FAILED', array('id'=>$id, 'value'=>$value), array('success'=>$updated));
	}
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
