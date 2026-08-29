<?php
	include "config.php";
	adminRequirePostCsrf(true);
	adminRequireMutationPermission('setting', 'database', '', '', true);

	$action = (!empty($_POST['action'])) ? strtoupper((string)$_POST['action']) : '';
	$result = array();
	$tables = array();

	if(adminAllowedDatabaseAction($action))
	{
		$tables = $d->rawQuery("SHOW TABLES");

		if(!empty($tables))
		{
			$result = $func->databaseMaintenance($action, $tables);
		}
	}
	else http_response_code(400);

	echo json_encode($result);
?>
