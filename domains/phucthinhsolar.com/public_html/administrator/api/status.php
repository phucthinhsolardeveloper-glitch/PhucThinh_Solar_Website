<?php
	include "config.php";
	adminRequirePostCsrf();

	$result = array('success' => false, 'updated' => 0);
	$table = (!empty($_POST['table'])) ? (string) $_POST['table'] : '';
	$idsInput = isset($_POST['ids']) ? (string) $_POST['ids'] : (isset($_POST['id']) ? (string) $_POST['id'] : '');
	$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsInput)))));
	$attr = (!empty($_POST['attr'])) ? (string) $_POST['attr'] : '';
	$hasExplicitValue = isset($_POST['value']) && in_array((string) $_POST['value'], array('0', '1'), true);
	$explicitValue = $hasExplicitValue ? (string) $_POST['value'] === '1' : null;
	$type = (!empty($_POST['type'])) ? (string) $_POST['type'] : '';
	$com = (!empty($_POST['com'])) ? (string) $_POST['com'] : '';

	if($ids && count($ids) <= 500 && adminAllowedMutationTable($table, 'status') && preg_match('/^[a-z0-9_-]{1,40}$/i', $attr))
	{
		adminRequireMutationPermission($table, 'status', $type, $com);
		if($table !== 'product' || (!empty($config['product'][$type]['check']) && array_key_exists($attr, $config['product'][$type]['check'])))
		{
			foreach($ids as $id)
			{
				$status_detail = $d->rawQueryOne("select status from #_{$table} where id = ? limit 0,1", array($id));
				if(!$status_detail) continue;
				$status_array = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)$status_detail['status'])))));
				$enabled = in_array($attr, $status_array, true);
				$shouldEnable = $hasExplicitValue ? $explicitValue : !$enabled;
				if($shouldEnable && !$enabled) $status_array[] = $attr;
				if(!$shouldEnable && $enabled) $status_array = array_values(array_diff($status_array, array($attr)));
				if($shouldEnable === $enabled) { $result['updated']++; continue; }
				$d->where('id', $id);
				if($d->update($table, array('status' => implode(',', $status_array)))) $result['updated']++;
			}
			$result['success'] = $result['updated'] === count($ids);
			if($result['updated']) $cache->delete();
		}
	}

	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
