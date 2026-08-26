<?php 
	include "config.php";
	$table = (!empty($_POST['item'])) ? htmlspecialchars($_POST['item']) : '';
	$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
	if(isset($_POST["id"])) {
	   $updateItems = $d->rawQueryOne("DELETE FROM `table_sortable` WHERE id = ?  ",array($id));     
	}
 ?>