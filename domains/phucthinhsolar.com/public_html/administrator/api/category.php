<?php
	include "config.php";

	function categoryApiError($message, $code, $status = 400)
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(adminApiEnvelope(false, $message, $code, array(), array('success'=>false)));
		exit();
	}

	if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') categoryApiError('Phương thức request không hợp lệ.', 'METHOD_NOT_ALLOWED', 405);
	header('Content-Type: text/html; charset=UTF-8');

	if(!empty($_POST["id"]))
	{
		$level = isset($_POST["level"]) ? (string)$_POST["level"] : '';
		$table = (!empty($_POST["table"])) ? (string) $_POST["table"] : '';
		$table = preg_replace('/^#_/', '', $table);
		$id = (!empty($_POST["id"])) ? (int) $_POST["id"] : 0;
		$type = (!empty($_POST["type"])) ? trim((string)$_POST["type"]) : '';
		$row = null;
		if(!adminAllowedCategoryTable($table)) categoryApiError('Bảng danh mục không hợp lệ.', 'INVALID_CATEGORY_TABLE');
		if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $type)) categoryApiError('Loại danh mục không hợp lệ.', 'INVALID_CATEGORY_TYPE');

		switch($level)
		{
			case '0':
				$id_temp = "id_list";
				break;

			case '1':
				$id_temp = "id_cat";
				break;

			case '2':
				$id_temp = "id_item";
				break;

			default:
				categoryApiError('Cấp danh mục không hợp lệ.', 'INVALID_CATEGORY_LEVEL');
				break;
		}

		if($id)
		{
			$row = $d->rawQuery("select namevi, id from #_{$table} where {$id_temp} = ? and type = ? order by numb,id desc",array($id,$type));
		}

		$str = '<option value="0">Chọn danh mục</option>';
		if(!empty($row))
		{
			foreach($row as $v)
			{
				$str .= '<option value="'.(int) $v["id"].'">'.htmlspecialchars($v["namevi"], ENT_QUOTES, 'UTF-8').'</option>';
			}
		}
	}
	else
	{
		$str = '<option value="0">Chọn danh mục</option>';
	}

	echo $str;
?>
