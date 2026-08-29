# Security Review 2026-08-27

Trạng thái: **ĐANG XỬ LÝ trên Docker local**, chưa sửa production.

## Retest 2026-08-29

Đã xử lý và retest:

- P0 SQL injection ở `api/fillter_service.php`: ID/list/category/brand/origin và LIMIT được ép kiểu, giới hạn và parameterized; payload injection trả HTTP 200 bình thường, không có SQL error.
- P0 SQL injection ở phân trang comment: offset/limit/ID ép số và giới hạn; type dùng allowlist ký tự.
- P1 bypass `isAdmin=1`: chỉ bật chế độ Admin khi `Functions::checkLoginAdmin()` xác nhận session/token hợp lệ. Public payload không đọc được review chưa duyệt hoặc PII.
- P1 stored XSS review: fullname/title/content/reply được escape ở template customer và Admin. Regression bằng record local chứa script/img handler không render HTML thực thi; record test đã xóa.
- Dynamic SQL trong filter quản trị đơn hàng đã được parameterized trong commit nâng cấp vận hành Admin.

Vẫn còn trước production:

- Xóa đơn hàng bằng GET và thiếu CSRF.
- Checkout/review chưa có CSRF, CAPTCHA/rate limit đầy đủ.
- Allowlist/authorization theo action cho các API mutation Admin legacy.
- Hardening upload review và upload Admin theo MIME/size/non-executable storage.

## Findings cần xử lý lại trước production

- P0: SQL injection public ở `api/fillter_service.php`.
- P0: SQL injection public ở phân trang `libraries/class/class.Comments.php`.
- P1: Stored XSS trong nội dung review khi render ở admin/customer template.
- P1: Public caller có thể truyền `isAdmin=1` để đọc review chưa duyệt và PII.
- P1: Xóa đơn hàng bằng GET, thiếu CSRF.
- P1: Dynamic SQL trong filter quản trị đơn hàng.
- P2: Checkout/review thiếu CSRF, CAPTCHA/rate limit.
- P2: Credential admin local nằm trong tài liệu và Docker bind cổng ra mọi interface.

## Quyết định ban đầu

Ngày 2026-08-27, chủ project yêu cầu tạm gác Security Review để ưu tiên nâng cấp tính năng. Không xem audit là đã xử lý; phải quay lại vá và retest trước khi deploy/chạy ads.
