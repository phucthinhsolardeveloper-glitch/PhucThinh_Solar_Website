# Security Review 2026-08-27

Trạng thái: **ĐANG XỬ LÝ trên Docker local**, chưa sửa production.

## Retest 2026-08-29

Đã xử lý và retest:

- P0 SQL injection ở `api/fillter_service.php`: ID/list/category/brand/origin và LIMIT được ép kiểu, giới hạn và parameterized; payload injection trả HTTP 200 bình thường, không có SQL error.
- P0 SQL injection ở phân trang comment: offset/limit/ID ép số và giới hạn; type dùng allowlist ký tự.
- P1 bypass `isAdmin=1`: chỉ bật chế độ Admin khi `Functions::checkLoginAdmin()` xác nhận session/token hợp lệ. Public payload không đọc được review chưa duyệt hoặc PII.
- P1 stored XSS review: fullname/title/content/reply được escape ở template customer và Admin. Regression bằng record local chứa script/img handler không render HTML thực thi; record test đã xóa.
- Dynamic SQL trong filter quản trị đơn hàng đã được parameterized trong commit nâng cấp vận hành Admin.
- Xóa đơn hàng chuyển sang POST và yêu cầu CSRF theo session; lưu trạng thái đơn cũng kiểm CSRF.
- Checkout và gửi review yêu cầu CSRF; thêm giới hạn 5 lượt/10 phút theo session để giảm spam cơ bản.
- API Admin `status`, `numb`, `copy`, `category`, `place` dùng allowlist bảng/identifier; ID và số thứ tự được ép kiểu, query ID được parameterized.
- Upload ảnh kiểm extension chính xác, kích thước, nội dung ảnh/MIME thực; upload video dùng allowlist MIME riêng. Upload gallery lỗi không để lại record rỗng.
- Bootstrap bảo đảm thư mục `upload/` tắt thực thi CGI/PHP và chặn truy cập các đuôi script phía Apache.

Vẫn còn trước production:

- CAPTCHA/rate limit bền vững theo IP/tài khoản cho checkout/review; rate limit hiện tại chỉ theo session nên có thể bị né bằng session mới.
- Authorization theo từng action cho API mutation Admin; hiện đã có login, CSRF và allowlist nhưng RBAC toàn hệ thống vẫn tắt.
- Rà các mutation/delete legacy ngoài đơn hàng để chuyển đồng bộ sang POST + CSRF.
- Retest upload thực tế qua UI Admin/review với các kích thước và định dạng video thật trước production.

## Findings ban đầu (lưu để đối chiếu lịch sử)

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
