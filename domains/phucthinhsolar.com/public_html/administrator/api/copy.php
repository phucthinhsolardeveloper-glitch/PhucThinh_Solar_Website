<?php
	include "config.php";
	adminRequirePostCsrf(true);
	include LIBRARIES."type/config-type-product.php";
	include LIBRARIES."type/config-type-news.php";
	$id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
	$table = !empty($_POST['table']) ? (string)$_POST['table'] : '';
	$copyimg = !empty($_POST['copyimg']);
	$type = !empty($_POST['type']) ? (string)$_POST['type'] : '';
	$result = adminApiEnvelope(false, 'Yêu cầu sao chép không hợp lệ.', 'INVALID_COPY_REQUEST', array(), array('success'=>false));
	if(!$id || !adminAllowedMutationTable($table, 'copy')) {
		http_response_code(400); header('Content-Type: application/json; charset=UTF-8'); echo json_encode($result); exit();
	}
	adminRequireMutationPermission($table, 'copy', $type, '', true);
	$item = $d->rawQueryOne("select * from #_$table where id = ? and type = ? limit 0,1", array($id,$type));
	if(!empty($item['id']) && isset($config[$table][$item['type']])) {
		$titleBase = (string)$item['namevi']; $titleCopy = $titleBase; $titleSlug = (string)$item['slugvi']; $attempt = 0;
		do {
			$exists = $d->rawQueryOne("select id from #_$table where slugvi = ? or slugen = ? limit 0,1", array($titleSlug,$titleSlug));
			if(empty($exists['id'])) break;
			$attempt++; $titleCopy = $titleBase.' ('.$attempt.')'; $titleSlug = $func->changeTitle($titleCopy);
		} while($attempt < 1000);
		if($attempt < 1000) {
		try {
			$dataCopy = array();
			foreach($config['website']['lang'] as $key => $value) { $dataCopy['desc'.$key]=$item['desc'.$key]; $dataCopy['content'.$key]=$item['content'.$key]; }
			if($copyimg) { $dataCopy['photo']=$func->copyImg($item['photo'],$table); $dataCopy['photo2']=$func->copyImg($item['photo2'],$table); }
			$dataCopy['namevi']=$titleCopy; $dataCopy['slugvi']=!empty($config[$table][$item['type']]['slug']) ? $titleSlug : '';
			foreach(array('id_list','id_cat','id_item','id_sub') as $field) $dataCopy[$field]=(int)$item[$field];
			if($table === 'product') {
				$dataCopy['id_brand']=(int)$item['id_brand']; $dataCopy['code']=$item['code'];
				foreach(array('regular_price','discount','sale_price','cong_suat') as $field) $dataCopy[$field]=is_numeric($item[$field]) ? $item[$field] : 0;
			}
			$dataCopy['numb']=0; $dataCopy['status']=''; $dataCopy['type']=$item['type']; $dataCopy['date_created']=time();
			$inserted=(bool)$d->insert($table,$dataCopy);
			$newId=$inserted ? (int)$d->getLastInsertId() : 0;
			if($newId>0) { $cache->delete(); $result=adminApiEnvelope(true,'Đã sao chép dữ liệu.','COPY_CREATED',array('id'=>$newId),array('success'=>true,'id'=>$newId)); }
			else $result=adminApiEnvelope(false,'Không thể lưu bản sao.','COPY_FAILED',array(),array('success'=>false));
		} catch(Exception $e) {
			error_log('Admin copy failed: '.($e instanceof PDODbException ? $e->getDriverMessage() : $e->getMessage()));
			$result=adminApiEnvelope(false,'Không thể lưu bản sao.','COPY_FAILED',array(),array('success'=>false));
		}
		}
	}
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
