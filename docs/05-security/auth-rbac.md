# Authentication and RBAC

## Admin

`administrator/api/login.php` lookup user bằng prepared query và ưu tiên xác
minh hash do `password_hash()` tạo. Hash MD5 với secret/salt cũ chỉ được giữ
như nhánh tương thích: đăng nhập đúng một lần sẽ cập nhật có điều kiện sang
hash mới, không reset mật khẩu. Tạo/đổi mật khẩu Admin mới đều dùng
`password_hash()`. Login tạo token ngẫu nhiên, regenerate session ID, log
IP/user-agent và rate limit bền vững bằng DB theo cả IP lẫn hash tài khoản.
Login bắt buộc POST + CSRF và trả thông báo chung để hạn chế dò tài khoản.
`libraries/requick.php` kiểm tra token/session, timeout cấu hình 3600 giây và
concurrent login token.

Session Admin dùng cookie theo phiên với strict mode, `HttpOnly`, `SameSite=Lax`; thuộc tính `Secure` tự bật khi request được xác định là HTTPS. `X-Forwarded-For` chỉ được dùng khi địa chỉ proxy nằm trong allowlist `login.trusted-proxies`; mặc định allowlist rỗng.

Frontend, public API và `Flash` cũng dùng chính `libraries/session_security.php`,
không còn gọi `session_start()` theo cơ chế riêng. Cookie public giữ
`HttpOnly`, `SameSite=Lax`, strict mode và tự bật `Secure` trên HTTPS để không
phá luồng cart/contact/checkout hiện tại.

## Authorization

Permission strings nằm trong `table_permission`, nhóm trong
`table_permission_group`; request admin có code so `com_act[_variant][_type]`.
Loader quyền xác minh cả tài khoản và nhóm còn active, đồng thời loại permission
trùng. `config-type.php` đã bật `permission.active`; ma trận local đã test nhóm
sản phẩm, đơn hàng, nội dung và chỉ xem, gồm cả từ chối truy cập chéo module.
Role 3 vẫn bypass theo thiết kế quản trị. `debug-developer` cũng bypass nên bắt
buộc phải là `false` trên production.

CKFinder và elFinder dùng `administrator/filemanager_bootstrap.php`: session
Admin active, token file manager và permission add/edit của đúng ngữ cảnh
`com/act/type` đều được kiểm tra lại ở backend. Quyền chỉ xem bị từ chối 403;
request chưa đăng nhập trả 401. Upload chỉ nhận image/media/document allowlist,
chặn script/HTML và tạo file/thư mục với mode `0644`/`0755`. Integration test
`scripts/test-admin-filemanager-security.php` bao phủ cả hai connector.

## Member

`sources/user.php` có đăng ký, login, activation, forgot password, profile, order history/return/cancel; session member được kiểm tra trong `Functions`. Config admin đặt `user.active = false`; usage production NEEDS VERIFICATION.

Rủi ro còn lại: hash MD5 chỉ biến mất dần khi từng tài khoản đăng nhập và chưa
retest tài khoản nghiệp vụ trên hosting. Toàn bộ form POST Admin đã có CSRF; mutation chính kiểm
CSRF và quyền action. Cần chạy runtime/readiness trên production, lặp lại ma
trận bằng tài khoản hạn chế, kiểm tra cookie `Secure` qua HTTPS thật và không
xem việc ẩn menu là authorization.
