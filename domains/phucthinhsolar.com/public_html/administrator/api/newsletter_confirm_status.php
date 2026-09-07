<?php
	/* Cập nhật nhanh "Tình trạng xử lý" (confirm_status) ngay trên danh sách
	   Yêu cầu tư vấn / Đăng ký đại lý, không bắt buộc phải mở màn hình Sửa chỉ
	   để đổi một giá trị. Theo đúng khuôn mẫu bảo mật của numb.php: CSRF, cổng
	   phân quyền, chỉ ghi đúng một cột đã định trước — không nhận tên cột từ
	   client như api/status.php (api/status.php dành cho cột "status" nhiều cờ
	   phẩy, khác hẳn confirm_status là một giá trị đơn). */
	include "config.php";
	adminRequirePostCsrf(true);
	$result = adminApiEnvelope(false, 'Yêu cầu cập nhật tình trạng không hợp lệ.', 'INVALID_CONFIRM_STATUS_REQUEST', array(), array('success'=>false));

	$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : 0;
	$type = (!empty($_POST['type'])) ? (string) $_POST['type'] : '';
	$value = isset($_POST['value']) ? (string) $_POST['value'] : '';

	if($id && $type !== '' && adminAllowedMutationTable('newsletter', 'status'))
	{
		adminRequireMutationPermission('newsletter', 'status', $type, 'newsletter', true);

		$allowedValues = (!empty($config['newsletter'][$type]['confirm_status']) && is_array($config['newsletter'][$type]['confirm_status']))
			? array_map('strval', array_keys($config['newsletter'][$type]['confirm_status']))
			: array();

		/* Chuỗi rỗng = quay về "Mới / chưa xử lý" (mặc định của getStatusNewsletter). */
		if($value === '' || in_array($value, $allowedValues, true))
		{
			$d->where('id', $id);
			$d->where('type', $type);
			$updated = (bool) $d->update('newsletter', array('confirm_status' => $value, 'date_updated' => time()));
			$result = adminApiEnvelope($updated, $updated ? 'Đã cập nhật tình trạng.' : 'Không thể cập nhật tình trạng.',
				$updated ? 'CONFIRM_STATUS_UPDATED' : 'CONFIRM_STATUS_UPDATE_FAILED', array('id' => $id, 'value' => $value), array('success' => $updated));
		}
	}

	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($result);
?>
