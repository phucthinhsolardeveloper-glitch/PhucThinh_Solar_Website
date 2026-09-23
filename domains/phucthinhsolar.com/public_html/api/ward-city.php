<?php
	include "config.php";

	$id_city = (!empty($_POST['id_city']) && is_scalar($_POST['id_city'])) ? (int)$_POST['id_city'] : 0;
	$ward = array();
	if($id_city > 0)
	{
		$ward = $d->rawQuery(
			"select name, id from #_ward where id_city = ? and id_district = 0 and find_in_set('hienthi', status) order by numb asc, id asc",
			array($id_city)
		);
	}
?>
<option value=""><?=phuongxa?></option>
<?php foreach($ward as $item) { ?>
	<option value="<?=$item['id']?>"><?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8')?></option>
<?php } ?>
