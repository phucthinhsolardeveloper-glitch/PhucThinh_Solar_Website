<?php
	include "config.php";

	$slug = !empty($_POST['slug']) ? trim((string)$_POST['slug']) : '';
	$id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
	$copy = !empty($_POST['copy']) ? 1 : 0;
	if($slug === '' || strlen($slug) > 255 || !preg_match('/^[a-z0-9-]+$/i', $slug))
	{
		http_response_code(400);
		$result = adminApiEnvelope(false, 'Đường dẫn không hợp lệ.', 'INVALID_SLUG', array('available'=>false), array('available'=>false));
	}
	else
	{
		$available = $func->checkSlug(array('slug'=>$slug, 'id'=>$id, 'copy'=>$copy)) !== 'exist';
		$result = adminApiEnvelope(true, $available ? 'Đường dẫn có thể sử dụng.' : 'Đường dẫn đã tồn tại.', $available ? 'SLUG_AVAILABLE' : 'SLUG_EXISTS', array('available'=>$available), array('available'=>$available));
	}
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
