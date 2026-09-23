<?php
	include "config.php";

	function placeApiError($message, $code, $status = 400)
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(adminApiEnvelope(false, $message, $code, array(), array('success'=>false)));
		exit();
	}

	if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') placeApiError('Phương thức request không hợp lệ.', 'METHOD_NOT_ALLOWED', 405);
	header('Content-Type: text/html; charset=UTF-8');

	if(!empty($_POST["id"]))
	{
		$level = isset($_POST["level"]) ? (string)$_POST["level"] : '';
		$table = (!empty($_POST["table"])) ? (string) $_POST["table"] : '';
		$table = preg_replace('/^#_/', '', $table);
		$id = (!empty($_POST["id"])) ? (int) $_POST["id"] : 0;
		$row = null;
		if(!adminAllowedPlaceTable($table)) placeApiError('Bảng địa giới không hợp lệ.', 'INVALID_PLACE_TABLE');

		switch($level)
		{
			case '0':
				$id_temp = "id_city";
				break;

			case '1':
				$id_temp = "id_district";
				break;

			default:
				placeApiError('Cấp địa giới không hợp lệ.', 'INVALID_PLACE_LEVEL');
				break;
		}

		if($id)
		{
			$where = "{$id_temp} = ? and find_in_set('hienthi', status)";
			$params = array($id);
			if($table === 'ward') $where .= ' and id_district = 0';
			$row = $d->rawQuery("select name, id from #_{$table} where {$where} order by id asc", $params);
		}

		$str = '<option value="0">Chọn danh mục</option>';
		if(!empty($row))
		{
			foreach($row as $v)
			{
				$str .= '<option value="'.(int) $v["id"].'">'.htmlspecialchars($v["name"], ENT_QUOTES, 'UTF-8').'</option>';
			}
		}
	}
	else
	{
		$str = '<option value="0">Chọn danh mục</option>';
	}

	echo $str;
?>
