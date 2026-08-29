<?php
	include "config.php";
	adminRequirePostCsrf();

	$result = 0;
	$table = (!empty($_POST['table'])) ? (string) $_POST['table'] : '';
	$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : 0;
	$attr = (!empty($_POST['attr'])) ? (string) $_POST['attr'] : '';
	$type = (!empty($_POST['type'])) ? (string) $_POST['type'] : '';
	$com = (!empty($_POST['com'])) ? (string) $_POST['com'] : '';

	if($id && adminAllowedMutationTable($table, 'status') && preg_match('/^[a-z0-9_-]{1,40}$/i', $attr))
	{
		adminRequireMutationPermission($table, 'status', $type, $com);
		$status_detail = $d->rawQueryOne("select status from #_{$table} where id = ? limit 0,1", array($id));
		$status_array = (!empty($status_detail['status'])) ? explode(',', $status_detail['status']) : array();

		if(array_search($attr, $status_array) !== false)
		{
			$key = array_search($attr, $status_array);
			unset($status_array[$key]);
		}
		else
		{
			array_push($status_array, $attr);
		}

		$data = array();
		$data['status'] = (!empty($status_array)) ? implode(',', $status_array) : "";
		$d->where('id', $id);
		if($d->update($table, $data))
		{
			$result = 1;
			$cache->delete();
		}
	}

	echo $result;
?>
