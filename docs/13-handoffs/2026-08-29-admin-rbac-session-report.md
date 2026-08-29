# Báo cáo RBAC, CSRF và phiên Admin — 2026-08-29

## Kết quả

- Ma trận RBAC local đạt với bốn tài khoản/nhóm tạm: sản phẩm, đơn hàng, nội dung và chỉ xem. Đã xác minh quyền đúng module, từ chối quyền chéo module và nhóm chỉ xem không có quyền sửa.
- 42/42 form POST trong template Admin có trường CSRF. Luồng HTTP thật xác nhận login thiếu CSRF trả 403, form sản phẩm sau đăng nhập phát hành token mới và API mutation thiếu token trả 403.
- Login dùng rate limit DB theo hai khóa: IP và hash tên tài khoản. Forwarded IP chỉ được tin từ proxy cấu hình rõ ràng.
- Session ID được đổi ngay sau login. Cookie theo phiên có strict mode, `HttpOnly`, `SameSite=Lax`; `Secure` tự bật trên HTTPS.
- Tài khoản, nhóm, permission, log và khóa rate-limit thử được dọn trong `finally`; test assertion dùng exception để cleanup vẫn chạy khi test thất bại.

## Kiểm thử đã chạy

- Build lại image Docker PHP 7.4 và lint các file auth/session.
- `scripts/test-security-hardening.php`: toàn bộ security regression đạt.
- `scripts/test-admin-readiness.php`: toàn bộ RBAC, rate-limit, cookie, login và CSRF đạt.
- `scripts/test-product-bulk.php`: toàn bộ regression Excel/transaction đạt; số sản phẩm trước và sau không đổi.
- `administrator/assets/js/apps.js`: Node syntax check đạt.

## Phạm vi chưa bật

- `permission.active` vẫn là `false`. Không bật thẳng trên production; phải tạo nhóm/tài khoản thật và lặp lại ma trận trên staging.
- Local chạy HTTP nên chưa thể chứng minh header cookie `Secure`; phải xác minh trên staging HTTPS.
- Việc quét 42 form xác minh coverage CSRF ở code; mới chạy HTTP đại diện cho login, product form và API status. Các thao tác nghiệp vụ của mọi form vẫn cần smoke bằng trình duyệt trên staging.
- Password hashing MD5 có secret/salt là legacy, cần migration tương thích sau khi có baseline ổn định.

## Deploy và rollback

- Deploy: đưa toàn bộ file trong commit của batch này lên staging cùng nhau, không copy cấu hình/credential local; build sạch và chạy lại hai script security/readiness. Code có mặc định an toàn `5` lần/IP, `20` lần/tài khoản, khóa `15` phút và timeout `3600` giây; chỉ thêm `trusted-proxies` vào cấu hình riêng khi đã biết chính xác IP reverse proxy.
- Không có migration schema và không import dữ liệu nghiệp vụ trong batch này.
- Rollback: revert commit batch, build/deploy lại release trước. Các dòng rate-limit mới có thể giữ nguyên; không xóa toàn bảng `user_limit`.
