<?php
	include "config.php";
	adminRequirePostCsrf();

	$result = array('success' => false, 'updated' => 0, 'message' => 'Yêu cầu cập nhật không hợp lệ.');
	$table = (!empty($_POST['table'])) ? (string) $_POST['table'] : '';
	$idsInput = isset($_POST['ids']) ? (string) $_POST['ids'] : (isset($_POST['id']) ? (string) $_POST['id'] : '');
	$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsInput)))));
	$attr = (!empty($_POST['attr'])) ? (string) $_POST['attr'] : '';
	$hasExplicitValue = isset($_POST['value']) && in_array((string) $_POST['value'], array('0', '1'), true);
	$explicitValue = $hasExplicitValue ? (string) $_POST['value'] === '1' : null;
	$type = (!empty($_POST['type'])) ? (string) $_POST['type'] : '';
	$com = (!empty($_POST['com'])) ? (string) $_POST['com'] : '';
	$scope = isset($_POST['scope']) ? (string)$_POST['scope'] : 'selected';

	if($scope === 'filtered' && $table === 'product' && $hasExplicitValue && adminAllowedMutationTable($table, 'status') && preg_match('/^[a-z0-9_-]{1,40}$/i', $attr))
	{
		adminRequireMutationPermission($table, 'status', $type, $com);
		if(!empty($config['product'][$type]['check']) && array_key_exists($attr, $config['product'][$type]['check']))
		{
			$where = " where p.type = ?";
			$params = array($type);
			foreach(array('id_list','id_cat','id_item','id_sub','id_brand') as $filterColumn)
			{
				$filterValue = isset($_POST[$filterColumn]) ? (int)$_POST[$filterColumn] : 0;
				if($filterValue > 0) { $where .= " and p.$filterColumn = ?"; $params[] = $filterValue; }
			}
			if(isset($_POST['comment_status']) && $_POST['comment_status'] === 'new')
			{
				$where .= " and exists (select 1 from #_comment c where c.id_variant = p.id and c.type = ? and find_in_set('new-admin', c.status))";
				$params[] = $type;
			}
			if(isset($_POST['data_status']) && $_POST['data_status'] === 'missing_wattage') $where .= " and (p.cong_suat is null or p.cong_suat <= 0)";
			if(isset($_POST['keyword']) && trim((string)$_POST['keyword']) !== '')
			{
				$keyword = '%'.trim((string)$_POST['keyword']).'%';
				$where .= " and (p.namevi like ? or p.nameen like ? or p.code like ?)";
				$params[] = $keyword; $params[] = $keyword; $params[] = $keyword;
			}
			$countRow = $d->rawQueryOne("select count(*) as total from #_product p $where", $params);
			$targetCount = !empty($countRow['total']) ? (int)$countRow['total'] : 0;
			if($targetCount > 0 && $targetCount <= 5000)
			{
				if($explicitValue)
					$sql = "update #_product p set p.status = trim(both ',' from concat(p.status, if(p.status = '', '', ','), ?)) $where and not find_in_set(?,p.status)";
				else
					$sql = "update #_product p set p.status = trim(both ',' from replace(concat(',',p.status,','), concat(',',?,','), ',')) $where and find_in_set(?,p.status)";
				$updateParams = array_merge(array($attr), $params, array($attr));
				$d->rawQuery($sql, $updateParams);
				$cache->delete();
				$result = array('success' => true, 'updated' => $targetCount, 'message' => 'Đã áp dụng cho toàn bộ kết quả đang lọc.');
			}
			elseif($targetCount === 0) $result['message'] = 'Không có sản phẩm phù hợp bộ lọc hiện tại.';
			else $result['message'] = 'Phạm vi vượt quá 5.000 sản phẩm; hãy thu hẹp bộ lọc.';
		}
	}

	if($scope !== 'filtered' && $ids && count($ids) <= 500 && adminAllowedMutationTable($table, 'status') && preg_match('/^[a-z0-9_-]{1,40}$/i', $attr))
	{
		adminRequireMutationPermission($table, 'status', $type, $com);
		if($table !== 'product' || (!empty($config['product'][$type]['check']) && array_key_exists($attr, $config['product'][$type]['check'])))
		{
			foreach($ids as $id)
			{
				$status_detail = $d->rawQueryOne("select status from #_{$table} where id = ? limit 0,1", array($id));
				if(!$status_detail) continue;
				$status_array = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)$status_detail['status'])))));
				$enabled = in_array($attr, $status_array, true);
				$shouldEnable = $hasExplicitValue ? $explicitValue : !$enabled;
				if($shouldEnable && !$enabled) $status_array[] = $attr;
				if(!$shouldEnable && $enabled) $status_array = array_values(array_diff($status_array, array($attr)));
				if($shouldEnable === $enabled) { $result['updated']++; continue; }
				$d->where('id', $id);
				if($d->update($table, array('status' => implode(',', $status_array)))) $result['updated']++;
			}
			$result['success'] = $result['updated'] === count($ids);
			$result['message'] = $result['success'] ? 'Đã cập nhật các sản phẩm được chọn.' : 'Một số sản phẩm chưa được cập nhật.';
			if($result['updated']) $cache->delete();
		}
	}

	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
