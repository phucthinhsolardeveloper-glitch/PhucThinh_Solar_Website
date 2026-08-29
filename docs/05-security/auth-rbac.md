# Authentication and RBAC

## Admin

`administrator/api/login.php` lookup user bằng prepared query, so password qua `Functions::encryptPassword()` = MD5 với secret/salt tĩnh, tạo session/token DB, log IP/user-agent và lockout theo IP. `libraries/requick.php` kiểm tra token/session, timeout 3600 giây và concurrent login token.

## Authorization

Permission strings nằm trong `table_permission`, nhóm trong `table_permission_group`; request admin có code so `com_act[_variant][_type]`. Tuy nhiên `config-type.php` đặt `permission.active = false`, nên enforcement granular đang tắt. Role 3/debug developer bypass check.

## Member

`sources/user.php` có đăng ký, login, activation, forgot password, profile, order history/return/cancel; session member được kiểm tra trong `Functions`. Config admin đặt `user.active = false`; usage production NEEDS VERIFICATION.

Rủi ro: password hashing legacy, secret trong code và RBAC vẫn tắt. API mutation chính đã kiểm quyền action khi RBAC được bật; cần test ma trận tài khoản trên staging trước khi đổi cấu hình. CSRF chưa phủ hết mutation save/update legacy. Không xem việc ẩn menu là authorization.
