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

function securityAssert($condition, $message)
{
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "PASS: {$message}\n";
}

$adminToken = adminCsrfToken();
securityAssert(strlen($adminToken) === 64 && adminCsrfValid($adminToken), 'CSRF Admin sinh token ngẫu nhiên và xác minh đúng');
securityAssert(!adminCsrfValid('sai-token'), 'CSRF Admin từ chối token sai');
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
securityAssert(adminMutationPermissionKey('setting', 'database') === 'setting_update', 'Bảo trì database yêu cầu quyền cấu hình hệ thống');
securityAssert(adminLegacyMutationNeedsCsrf('product', 'save', 'POST', array()), 'Mutation save legacy yêu cầu CSRF');
securityAssert(adminLegacyMutationNeedsCsrf('pushOnesignal', 'sync', 'GET', array()), 'Mutation OneSignal GET cũ bị từ chối để chuyển sang POST');
securityAssert(adminLegacyMutationNeedsCsrf('newsletter', 'man', 'POST', array('listemail' => '1')), 'Gửi email từ màn danh sách yêu cầu CSRF');
securityAssert(!adminLegacyMutationNeedsCsrf('product', 'man', 'GET', array()), 'Request đọc danh sách không bị yêu cầu CSRF');
securityAssert(adminAllowedDatabaseAction('optimize') && !adminAllowedDatabaseAction('DROP TABLE table_user'), 'Bảo trì database chỉ nhận action allowlist');

$formToken = formCsrfToken();
securityAssert(strlen($formToken) === 64 && formCsrfValid($formToken), 'CSRF frontend sinh và xác minh token đúng');
securityAssert(formRateLimit('test-security', 2, 60) && formRateLimit('test-security', 2, 60) && !formRateLimit('test-security', 2, 60), 'Rate limit chặn lượt vượt ngưỡng');

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

echo "Security hardening tests completed.\n";
