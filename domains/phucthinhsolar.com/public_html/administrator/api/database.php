<?php
	include "config.php";
	adminRequirePostCsrf(true);
	adminRequireMutationPermission('setting', 'database', '', '', true);

	$action = (!empty($_POST['action'])) ? strtoupper((string)$_POST['action']) : '';
	$result = array();
	$ok = false;
	$message = 'Thao tác bảo trì database không hợp lệ.';
	$code = 'INVALID_DATABASE_ACTION';
	$tables = array();

	if(adminAllowedDatabaseAction($action))
	{
		$tables = $d->rawQuery("SHOW TABLES");

		if(!empty($tables))
		{
			$result = $func->databaseMaintenance($action, $tables);
			$ok = is_array($result);
			$message = $ok ? 'Đã hoàn tất bảo trì database.' : 'Không thể hoàn tất bảo trì database.';
			$code = $ok ? 'DATABASE_MAINTENANCE_OK' : 'DATABASE_MAINTENANCE_FAILED';
		}
	}
	else http_response_code(400);

	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(adminApiEnvelope($ok, $message, $code, array('results'=>$result), array('success'=>$ok, 'results'=>$result)));
?>
