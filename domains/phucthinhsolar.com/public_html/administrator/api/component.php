<?php
/* Endpoint file manager legacy đã bị loại khỏi runtime. Giữ stub để client cũ
   nhận lỗi ổn định, không giữ implementation hoặc credential trong source. */
http_response_code(404);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(array(
    'ok' => false,
    'message' => 'API không tồn tại.',
    'code' => 'LEGACY_API_DISABLED',
    'data' => array(),
    'success' => false
));
exit();
