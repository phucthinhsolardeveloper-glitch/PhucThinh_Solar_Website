<?php 
	include "config.php";
	adminRequirePostCsrf();
	adminRequireMutationPermission('sortable', 'delete');
	$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
	if($id > 0) {
	   $d->rawQuery("DELETE FROM `table_sortable` WHERE id = ?",array($id));
	}
 ?>
