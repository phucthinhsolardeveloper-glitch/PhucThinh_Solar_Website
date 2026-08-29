<?php
include "config.php";
adminRequirePostCsrf();
adminRequireMutationPermission('sortable', 'numb');
if(isset($_POST["order"])) {
    $order_dt = array_values(array_filter(array_map('intval', explode(",", (string)$_POST["order"]))));
    for($i=0; $i < count($order_dt);$i++) {
		$d->rawQuery("update #_sortable set display_order = ? where id = ?",array($i, $order_dt[$i]));
    }
}
?>
