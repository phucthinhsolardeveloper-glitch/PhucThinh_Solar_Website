<?php
session_start();
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'admin_security.php';
require LIBRARIES.'form_security.php';
require LIBRARIES.'login_security.php';
require LIBRARIES.'order_security.php';
require LIBRARIES.'public_pricing.php';

function securityAssert($condition, $message)
{
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "PASS: {$message}\n";
}

$adminToken = adminCsrfToken();
securityAssert(strlen($adminToken) === 64 && adminCsrfValid($adminToken), 'CSRF Admin sinh token ngẫu nhiên và xác minh đúng');
$apiEnvelope = adminApiEnvelope(true, 'Đã xử lý', 'TEST_OK', array('id'=>1), array('success'=>'legacy'));
securityAssert($apiEnvelope['ok'] === true && $apiEnvelope['code'] === 'TEST_OK' && $apiEnvelope['success'] === 'legacy' && $apiEnvelope['data']['id'] === 1, 'Envelope API chuẩn giữ tương thích field legacy');
securityAssert(adminApiEnvelope(false, 'Không có quyền', 'FORBIDDEN')['ok'] === false, 'Envelope API biểu diễn lỗi bằng boolean ổn định');
securityAssert(!adminCsrfValid('sai-token'), 'CSRF Admin từ chối token sai');
$searchParams = array();
$searchKeyword = "O'Reilly % Đèn";
$searchClause = adminKeywordLikeClause($searchKeyword, array('p.namevi', 'p.nameen'), $searchParams);
securityAssert(
    $searchClause === '(p.namevi LIKE ? or p.nameen LIKE ?)'
        && $searchParams === array('%'.$searchKeyword.'%', '%'.$searchKeyword.'%')
        && strpos($searchClause, $searchKeyword) === false,
    'Tìm kiếm Admin bind dấu nháy, wildcard và Unicode thay vì ghép vào SQL'
);
$invalidSearchColumnRejected = false;
try { $unusedParams = array(); adminKeywordLikeClause('test', array('namevi) or 1=1 --'), $unusedParams); }
catch(InvalidArgumentException $exception) { $invalidSearchColumnRejected = true; }
securityAssert($invalidSearchColumnRejected, 'Tìm kiếm Admin từ chối identifier cột ngoài allowlist cú pháp');
$exportParams = array();
$exportWhere = adminOrderExportFilter(array(
    'listid' => '12,34',
    'order_status' => '2',
    'id_city' => '7',
    'order_date' => '01/09/2026 - 05/09/2026',
    'range_price' => '100000;200000',
    'keyword' => $searchKeyword
), $exportParams);
securityAssert(
    substr_count($exportWhere, '?') === count($exportParams)
        && strpos($exportWhere, $searchKeyword) === false
        && in_array('%'.$searchKeyword.'%', $exportParams, true),
    'Bộ lọc export đơn hàng parameterize đồng bộ ID, ngày, giá và keyword'
);
$invalidExportParams = array();
$invalidExportWhere = adminOrderExportFilter(array('listid' => '12,34 or 1=1'), $invalidExportParams);
securityAssert(strpos($invalidExportWhere, '1=0') !== false && empty($invalidExportParams), 'Export đơn hàng từ chối toàn bộ list ID sai định dạng');
$adminSearchSourceFiles = array('product.php', 'news.php', 'places.php', 'tags.php', 'user.php', 'lang.php', 'pushOnesignal.php', 'wordAll.php', 'excelAll.php');
$keywordHelperCalls = 0;
$orderExportFilterCalls = 0;
$legacyKeywordSql = array();
foreach($adminSearchSourceFiles as $sourceFile)
{
    $source = file_get_contents('/var/www/html/administrator/sources/'.$sourceFile);
    $keywordHelperCalls += substr_count($source, 'adminKeywordLikeClause(');
    $orderExportFilterCalls += substr_count($source, 'adminOrderExportFilter(');
    if(strpos($source, "LIKE '%\$keyword%'") !== false) $legacyKeywordSql[] = $sourceFile;
}
securityAssert(
    $keywordHelperCalls === 19 && $orderExportFilterCalls === 2 && empty($legacyKeywordSql),
    'Đủ 21 truy vấn tìm kiếm Admin dùng placeholder, không còn nội suy keyword legacy'
);
securityAssert(adminAllowedMutationTable('product', 'status') && !adminAllowedMutationTable('order_detail', 'status'), 'Allowlist status không nhận bảng ngoài phạm vi');
securityAssert(adminAllowedMutationTable('news', 'copy') && !adminAllowedMutationTable('user', 'copy'), 'Allowlist copy chỉ nhận product/news');
securityAssert(adminAllowedCategoryTable('product_cat') && !adminAllowedCategoryTable('user'), 'Allowlist category chặn identifier tùy ý');
securityAssert(adminAllowedPlaceTable('district') && !adminAllowedPlaceTable('product'), 'Allowlist place chặn identifier tùy ý');
securityAssert(adminGalleryUploadFolder('product') === 'product' && adminGalleryUploadFolder('../../') === null, 'Gallery chỉ ánh xạ thư mục upload phía server');
securityAssert(adminSafeUploadBasename('safe-image.jpg') && !adminSafeUploadBasename('../config.php'), 'Gallery từ chối filename có path traversal');
securityAssert(adminMutationPermissionKey('product_cat', 'status', 'san-pham') === 'product_edit_cat_san-pham', 'RBAC ánh xạ status danh mục sản phẩm sang quyền edit tương ứng');
securityAssert(adminMutationPermissionKey('gallery', 'delete', 'san-pham', 'product') === 'product_delete_photo_san-pham', 'RBAC ánh xạ xóa gallery theo module và type');
securityAssert(adminMutationPermissionKey('news', 'copy', 'tin-tuc') === 'news_add_tin-tuc', 'RBAC yêu cầu quyền add khi sao chép nội dung');
securityAssert(adminMutationPermissionKey('user', 'status', '') === null, 'RBAC từ chối mutation chưa có mapping quyền');
securityAssert(adminPermissionGranted(false, 1, false, null, array()), 'RBAC tắt giữ nguyên hành vi hiện tại');
securityAssert(adminPermissionGranted(true, 3, false, null, array()), 'Tài khoản developer role 3 giữ quyền quản trị');
securityAssert(adminPermissionGranted(true, 1, false, 'product_edit_san-pham', array('product_edit_san-pham')), 'RBAC cho phép đúng quyền được cấp');
securityAssert(!adminPermissionGranted(true, 1, false, 'product_edit_san-pham', array('news_edit_tin-tuc')), 'RBAC từ chối quyền khác module');
$is_permission = true;
$_SESSION[$loginAdmin]['permissions'] = array('product_man_san-pham');
securityAssert(adminUiCan('product', 'man', '', 'san-pham') && !adminUiCan('product', 'add', '', 'san-pham'), 'UI RBAC hiện thao tác xem và ẩn thao tác thêm ngoài quyền');
securityAssert(adminHideUnless('product', 'edit', '', 'san-pham', true) === ' d-none' && adminHideUnless('product', 'delete', '', 'san-pham', true) === ' d-none', 'UI RBAC ẩn nút sửa và xóa ngoài quyền');
$is_permission = false;
securityAssert(adminUiCan('product', 'add', '', 'san-pham'), 'UI không tự ẩn nút khi cổng phân quyền không áp dụng');
unset($is_permission, $_SESSION[$loginAdmin]['permissions']);
securityAssert(adminMutationPermissionKey('setting', 'database') === 'setting_update', 'Bảo trì database yêu cầu quyền cấu hình hệ thống');
securityAssert(adminLegacyMutationNeedsCsrf('product', 'save', 'POST', array()), 'Mutation save legacy yêu cầu CSRF');
securityAssert(adminLegacyMutationNeedsCsrf('pushOnesignal', 'sync', 'GET', array()), 'Mutation OneSignal GET cũ bị từ chối để chuyển sang POST');
securityAssert(adminLegacyMutationNeedsCsrf('newsletter', 'man', 'POST', array('listemail' => '1')), 'Gửi email từ màn danh sách yêu cầu CSRF');
securityAssert(!adminLegacyMutationNeedsCsrf('product', 'man', 'GET', array()), 'Request đọc danh sách không bị yêu cầu CSRF');
securityAssert(adminAllowedDatabaseAction('optimize') && !adminAllowedDatabaseAction('DROP TABLE table_user'), 'Bảo trì database chỉ nhận action allowlist');
securityAssert(adminLoginClientIp(array('REMOTE_ADDR' => '198.51.100.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.1'), array()) === '198.51.100.1', 'Login không tin forwarded IP khi proxy chưa được cấu hình');
securityAssert(count(adminLoginLimitDescriptors('198.51.100.1', 'Admin', 5, 20)) === 2, 'Rate limit đăng nhập kết hợp IP và tài khoản băm');
$sessionRow = array('username' => 'admin', 'password' => 'password-hash', 'lastlogin' => 1000, 'user_token' => 'stable-token');
$sessionState = array('login_session' => md5(sha1('password-hashadmin')), 'login_token' => 'stable-token');
securityAssert(!adminSessionInvalid($sessionRow, $sessionState, true, 1059, 3600), 'Phiên Admin hợp lệ giữ nguyên trong timeout');
securityAssert(adminSessionInvalid($sessionRow, $sessionState, true, 4601, 3600), 'Phiên Admin hết hạn theo lastlogin');
securityAssert(adminSessionInvalid($sessionRow, $sessionState, false, 1059, 3600), 'Phiên Admin thiếu session token bị từ chối');
securityAssert(!adminSessionHasConcurrentLogin($sessionRow, $sessionState), 'Token cùng phiên không cảnh báo đăng nhập đồng thời');
$otherLoginState = $sessionState;
$otherLoginState['login_token'] = 'older-token';
securityAssert(adminSessionHasConcurrentLogin($sessionRow, $otherLoginState), 'Token bị login khác thay thế vẫn phát cảnh báo');
securityAssert(!adminSessionActivityDue(1000, 1059, 60) && adminSessionActivityDue(1000, 1060, 60), 'Activity write chỉ đến hạn sau khoảng throttle');
$activityDb = new class {
    public $queries = array();
    public function rawQuery($sql, $params) { $this->queries[] = array($sql, $params); return true; }
};
securityAssert(!adminTouchSessionActivity($activityDb, 7, 1000, 1059, 60) && count($activityDb->queries) === 0, 'Request trong throttle không ghi table_user');
securityAssert(adminTouchSessionActivity($activityDb, 7, 1000, 1060, 60) && count($activityDb->queries) === 1, 'Request đến hạn chỉ cập nhật lastlogin');
securityAssert(strpos($activityDb->queries[0][0], 'user_token') === false && $activityDb->queries[0][1] === array(1060, 7, 1000), 'Activity update không xoay token và có điều kiện chống race');
securityAssert(formClientIp(array('REMOTE_ADDR' => '198.51.100.2', 'HTTP_X_FORWARDED_FOR' => '203.0.113.2'), array()) === '198.51.100.2', 'Form public không tin forwarded IP ngoài trusted proxy');
$orderUpdate = prepareAdminOrderUpdate($d, array('order_status' => 1, 'payment_status' => 0, 'notes' => 'test', 'total_price' => 1, 'code' => 'overwrite'), array(0 => 'Chưa thanh toán', 1 => 'Đã thanh toán'));
securityAssert(empty($orderUpdate['errors']) && !isset($orderUpdate['data']['total_price']) && !isset($orderUpdate['data']['code']), 'Cập nhật đơn chỉ nhận field nghiệp vụ allowlist');
$invalidOrderUpdate = prepareAdminOrderUpdate($d, array('order_status' => 999999, 'payment_status' => 200), array(0 => 'Chưa thanh toán', 1 => 'Đã thanh toán'));
securityAssert(count($invalidOrderUpdate['errors']) === 2, 'Cập nhật đơn từ chối trạng thái không tồn tại và placeholder thanh toán');
$optsetting = array();
securityAssert(!publicProductPricesVisible() && publicProductRequiresQuote(array('regular_price' => 999999, 'sale_price' => 888888)), 'Mặc định an toàn giữ chế độ báo giá khi chưa có thiết lập');
$optsetting['public_product_prices'] = '1';
securityAssert(publicProductPricesVisible() && !publicProductRequiresQuote(array('regular_price' => 999999, 'sale_price' => 888888)), 'Thiết lập Admin bật giá cho sản phẩm có giá nội bộ');
securityAssert(publicProductRequiresQuote(array('regular_price' => 999999, 'sale_price' => 888888, 'status' => 'hienthi,baogia')), 'Sản phẩm có checkbox báo giá vẫn ẩn giá khi toàn website đang bật giá');
securityAssert(publicProductRequiresQuote(array('regular_price' => 0, 'sale_price' => 0)), 'Sản phẩm không có giá vẫn yêu cầu báo giá khi bật giá công khai');
$optsetting['public_product_prices'] = '0';
securityAssert(!publicProductPricesVisible() && publicProductRequiresQuote(array('regular_price' => 999999, 'sale_price' => 888888)), 'Thiết lập Admin tắt giá trên toàn website');

$formToken = formCsrfToken();
securityAssert(strlen($formToken) === 64 && formCsrfValid($formToken), 'CSRF frontend sinh và xác minh token đúng');
$checkoutToken = checkoutIdempotencyToken();
securityAssert(strlen($checkoutToken) === 64 && checkoutIdempotencyTokenValid($checkoutToken), 'Checkout phát và xác minh token idempotency 256-bit');
securityAssert(!checkoutIdempotencyTokenValid(str_repeat('0', 64)), 'Checkout từ chối token idempotency không thuộc phiên hiện tại');
securityAssert(checkoutIdempotencyTokenFormatValid(str_repeat('a', 64)) && !checkoutIdempotencyTokenFormatValid('../token'), 'Checkout chỉ tra cứu replay với token hex đúng 256-bit');
checkoutRotateIdempotencyToken();
securityAssert(checkoutIdempotencyToken() !== $checkoutToken, 'Checkout xoay token idempotency sau khi hoàn tất đơn');
securityAssert(formRateLimit('test-security', 2, 60) && formRateLimit('test-security', 2, 60) && !formRateLimit('test-security', 2, 60), 'Rate limit chặn lượt vượt ngưỡng');
$persistentIp = '198.51.100.'.random_int(100, 199);
$persistentKey = 'form:'.sha1('security-test|'.$persistentIp);
try
{
    securityAssert(formPersistentRateLimit($d, 'security-test', $persistentIp, 2, 60, 1000), 'Rate limit DB nhận lượt đầu');
    securityAssert(formPersistentRateLimit($d, 'security-test', $persistentIp, 2, 60, 1001), 'Rate limit DB nhận lượt trong ngưỡng');
    securityAssert(!formPersistentRateLimit($d, 'security-test', $persistentIp, 2, 60, 1002), 'Rate limit DB chặn vượt ngưỡng qua session mới');
    securityAssert(formPersistentRateLimit($d, 'security-test', $persistentIp, 2, 60, 1062), 'Rate limit DB mở lại sau cửa sổ');
}
finally
{
    $d->rawQuery("delete from #_user_limit where login_ip = ?", array($persistentKey));
}

$validPath = tempnam(sys_get_temp_dir(), 'pts-valid-image-');
$invalidPath = tempnam(sys_get_temp_dir(), 'pts-invalid-image-');
file_put_contents($validPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
file_put_contents($invalidPath, '<?php echo "not an image";');
$targetName = 'security-valid-'.bin2hex(random_bytes(4));
$_FILES['security_image'] = array('name' => 'safe.png', 'type' => 'image/png', 'tmp_name' => $validPath, 'error' => UPLOAD_ERR_OK, 'size' => filesize($validPath));
$saved = $func->uploadImage('security_image', '.jpg|.png|.jpeg', sys_get_temp_dir().'/', $targetName);
securityAssert($saved === $targetName.'.png' && is_file(sys_get_temp_dir().'/'.$saved), 'Upload ảnh hợp lệ dựa trên nội dung thực');
@unlink(sys_get_temp_dir().'/'.$saved);
$_FILES['security_image'] = array('name' => 'fake.jpg', 'type' => 'image/jpeg', 'tmp_name' => $invalidPath, 'error' => UPLOAD_ERR_OK, 'size' => filesize($invalidPath));
ob_start();
$rejected = $func->uploadImage('security_image', '.jpg|.png|.jpeg', sys_get_temp_dir().'/', 'security-invalid');
ob_end_clean();
securityAssert($rejected === false && !is_file(sys_get_temp_dir().'/security-invalid.jpg'), 'Upload từ chối file giả ảnh dù đuôi JPG');
@unlink($validPath);
@unlink($invalidPath);

$validPdf = tempnam(sys_get_temp_dir(), 'pts-valid-pdf-');
$fakePdf = tempnam(sys_get_temp_dir(), 'pts-fake-pdf-');
file_put_contents($validPdf, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
file_put_contents($fakePdf, '<?php echo "unsafe";');
$_FILES['security_file'] = array('name' => 'tai-lieu.pdf', 'type' => 'application/pdf', 'tmp_name' => $validPdf, 'error' => UPLOAD_ERR_OK, 'size' => filesize($validPdf));
$savedPdf = $func->uploadFile('security_file', '.pdf|.docx', sys_get_temp_dir().'/', 'security-document');
securityAssert($savedPdf === 'security-document.pdf' && is_file(sys_get_temp_dir().'/'.$savedPdf), 'Upload tài liệu chấp nhận PDF có MIME thực');
@unlink(sys_get_temp_dir().'/'.$savedPdf);
$_FILES['security_file'] = array('name' => 'shell.php.pdf', 'type' => 'application/pdf', 'tmp_name' => $fakePdf, 'error' => UPLOAD_ERR_OK, 'size' => filesize($fakePdf));
ob_start();
$fakePdfResult = $func->uploadFile('security_file', '.pdf', sys_get_temp_dir().'/', $func->uploadName($_FILES['security_file']['name']));
ob_end_clean();
securityAssert($fakePdfResult === false, 'Upload tài liệu từ chối file PHP giả PDF và tên kép');
$_FILES['security_image'] = array('name' => 'large.png', 'type' => 'image/png', 'tmp_name' => $validPdf, 'error' => UPLOAD_ERR_OK, 'size' => PHP_INT_MAX);
ob_start();
$oversized = $func->uploadImage('security_image', '.png', sys_get_temp_dir().'/', 'security-large');
ob_end_clean();
securityAssert($oversized === false, 'Upload ảnh từ chối dung lượng vượt giới hạn trước khi lưu');
@unlink($validPdf);
@unlink($fakePdf);

echo "Security hardening tests completed.\n";
