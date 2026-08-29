<?php
	include "config.php";
	adminRequirePostCsrf();

	if(!empty($_POST['id']))
	{
		$table = (!empty($_POST['table'])) ? (string) $_POST['table'] : '';
		$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : 0;
		$value = isset($_POST['value']) ? max(0, (int) $_POST['value']) : 0;
		if(!$id || !adminAllowedMutationTable($table, 'numb')) { http_response_code(400); exit(); }

		$data['numb'] = $value;
		
		$d->where('id',$id);
		$d->update($table,$data);
		$cache->delete();
	}
?>
