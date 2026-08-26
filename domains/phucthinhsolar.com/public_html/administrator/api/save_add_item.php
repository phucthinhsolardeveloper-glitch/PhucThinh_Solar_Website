<?php 
	include "config.php";
	$table = (!empty($_POST['table'])) ? htmlspecialchars($_POST['table']) : '';
	echo $id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
	$item = (!empty($_POST['item'])) ? htmlspecialchars($_POST['item']) : 0;

	// if(!empty($_POST)){
	// 	for($i=0;$i<count($_POST['id_item']);$i++){
	// 		 if($_POST['ten_item'][$i]!='')
	// 		 {
	// 			 if($_POST['id_item'][$i]>0){
	// 					$data2['display_order'] = $_POST['stt_item'][$i];
	// 					$data2['name'] = $_POST['ten_item'][$i];
	// 					$data2['type'] = $_POST['type_item'][$i];
	// 					$data2['created'] = time();
	// 					$data2['status'] = 1;

	// 					$d->where('id', $_POST['stt_size'][$i]);
	// 					$d->update($table,$data2);
	// 					$cache->delete(); 
	// 			 }
	// 			 else{
	// 					$data2['display_order'] = $_POST['stt_item'][$i];
	// 					$data2['name'] = $_POST['ten_item'][$i];
	// 					$data2['type'] = $_POST['type_item'][$i];

	// 					$data2['modified'] = time();
	// 					$data2['status'] = 1;
	// 					$d->insert($table,$data2);
	// 			 }
	// 		 }
	// 	 }
	// }

 ?>