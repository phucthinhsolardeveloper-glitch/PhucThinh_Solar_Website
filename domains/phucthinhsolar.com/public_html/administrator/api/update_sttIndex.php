<?php
include "config.php";
adminRequirePostCsrf(true);
adminRequireMutationPermission('sortable', 'numb', '', '', true);
header('Content-Type: application/json; charset=UTF-8');
try {
    $updated = adminUpdateSortOrder($d, 'sortable_index', isset($_POST['order']) ? $_POST['order'] : '');
    echo json_encode(adminApiEnvelope(true, 'Đã cập nhật thứ tự.', 'SORT_ORDER_UPDATED', array('updated'=>$updated), array('success'=>true, 'updated'=>$updated)));
} catch(InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(adminApiEnvelope(false, 'Danh sách thứ tự không hợp lệ.', 'INVALID_SORT_ORDER', array(), array('success'=>false)));
} catch(Throwable $e) {
    error_log('Admin sortable index update failed: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(adminApiEnvelope(false, 'Không thể cập nhật thứ tự.', 'SORT_ORDER_FAILED', array(), array('success'=>false)));
}
?>
