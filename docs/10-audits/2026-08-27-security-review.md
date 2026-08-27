# Security Review 2026-08-27

Trạng thái: **DEFERRED theo yêu cầu chủ project**. Review chỉ chạy trên Docker local, không sửa production.

## Findings cần xử lý lại trước production

- P0: SQL injection public ở `api/fillter_service.php`.
- P0: SQL injection public ở phân trang `libraries/class/class.Comments.php`.
- P1: Stored XSS trong nội dung review khi render ở admin/customer template.
- P1: Public caller có thể truyền `isAdmin=1` để đọc review chưa duyệt và PII.
- P1: Xóa đơn hàng bằng GET, thiếu CSRF.
- P1: Dynamic SQL trong filter quản trị đơn hàng.
- P2: Checkout/review thiếu CSRF, CAPTCHA/rate limit.
- P2: Credential admin local nằm trong tài liệu và Docker bind cổng ra mọi interface.

## Quyết định

Ngày 2026-08-27, chủ project yêu cầu tạm gác Security Review để ưu tiên nâng cấp tính năng. Không xem audit là đã xử lý; phải quay lại vá và retest trước khi deploy/chạy ads.
