# Authentication and RBAC

## Admin

`administrator/api/login.php` lookup user bằng prepared query, so password qua `Functions::encryptPassword()` = MD5 với secret/salt tĩnh, tạo token ngẫu nhiên, regenerate session ID, log IP/user-agent và rate limit bền vững bằng DB theo cả IP lẫn hash tài khoản. Login bắt buộc POST + CSRF và trả thông báo chung để hạn chế dò tài khoản. `libraries/requick.php` kiểm tra token/session, timeout cấu hình 3600 giây và concurrent login token.

Session Admin dùng cookie theo phiên với strict mode, `HttpOnly`, `SameSite=Lax`; thuộc tính `Secure` tự bật khi request được xác định là HTTPS. `X-Forwarded-For` chỉ được dùng khi địa chỉ proxy nằm trong allowlist `login.trusted-proxies`; mặc định allowlist rỗng.

## Authorization

Permission strings nằm trong `table_permission`, nhóm trong `table_permission_group`; request admin có code so `com_act[_variant][_type]`. Loader quyền xác minh cả tài khoản và nhóm còn active, đồng thời loại permission trùng. Ma trận local đã test bốn nhóm sản phẩm, đơn hàng, nội dung và chỉ xem, gồm cả từ chối truy cập chéo module. Tuy nhiên `config-type.php` vẫn đặt `permission.active = false`: chưa bật enforcement trước khi cấu hình và retest tài khoản thật trên staging. Role 3/debug developer vẫn bypass check theo thiết kế legacy.

## Member

`sources/user.php` có đăng ký, login, activation, forgot password, profile, order history/return/cancel; session member được kiểm tra trong `Functions`. Config admin đặt `user.active = false`; usage production NEEDS VERIFICATION.

Rủi ro còn lại: password hashing/secret legacy và RBAC vẫn tắt. Toàn bộ 42 form POST Admin đã có CSRF; API mutation chính kiểm CSRF và quyền action khi RBAC bật. Cần lặp lại ma trận bằng tài khoản staging, kiểm tra cookie `Secure` qua HTTPS thật và không xem việc ẩn menu là authorization.
