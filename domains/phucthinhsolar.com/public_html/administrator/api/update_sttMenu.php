<?php
include "config.php";
if(isset($_GET["order"])) {
    $order_dt  = explode(",",$_GET["order"]);
    for($i=0; $i < count($order_dt);$i++) {
        $updateItems = $d->rawQueryOne("update #_sortable set display_order = '" . $i . "'  where id = ?  ",array($order_dt[$i]));        
    }
}
?>