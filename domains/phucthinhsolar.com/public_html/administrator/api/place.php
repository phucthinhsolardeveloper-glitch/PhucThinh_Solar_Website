<?php
	include "config.php";

	if(!empty($_POST["id"]))
	{
		$level = (!empty($_POST["level"])) ? htmlspecialchars($_POST["level"]) : 0;
		$table = (!empty($_POST["table"])) ? (string) $_POST["table"] : '';
		$table = preg_replace('/^#_/', '', $table);
		$id = (!empty($_POST["id"])) ? (int) $_POST["id"] : 0;
		$row = null;
		if(!adminAllowedPlaceTable($table)) { http_response_code(400); exit('error ajax'); }

		switch($level)
		{
			case '0':
				$id_temp = "id_city";
				break;

			case '1':
				$id_temp = "id_district";
				break;

			default:
				echo 'error ajax';
				exit();
				break;
		}

		if($id)
		{
			$row = $d->rawQuery("select name, id from #_{$table} where {$id_temp} = ? order by id asc",array($id));
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
