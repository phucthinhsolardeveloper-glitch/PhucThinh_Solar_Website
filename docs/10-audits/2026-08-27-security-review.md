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
- Checkout và gửi review yêu cầu CSRF; checkout, review, contact và hai form newsletter dùng giới hạn DB riêng theo IP, 5 lượt/10 phút, không thể né chỉ bằng session mới.
- Login Admin dùng rate limit DB theo IP/hash tài khoản, POST + CSRF, thông báo lỗi chung, trusted-proxy allowlist và regenerate session ID.
- API Admin `status`, `numb`, `copy`, `category`, `place` dùng allowlist bảng/identifier; ID và số thứ tự được ép kiểu, query ID được parameterized.
- Upload ảnh kiểm extension chính xác, kích thước, nội dung ảnh/MIME thực; upload video dùng allowlist MIME riêng. Upload gallery lỗi không để lại record rỗng.
- Bootstrap bảo đảm thư mục `upload/` tắt thực thi CGI/PHP và chặn truy cập các đuôi script phía Apache.
- Toàn bộ action xóa qua controller Admin legacy đã chuyển sang POST + CSRF; link xóa cache và các nút xóa danh sách dùng form POST tương thích source cũ.
- API gallery/filer yêu cầu CSRF cho sửa thứ tự, sửa chú thích và xóa; ID phải thuộc đúng sản phẩm/bài viết đang mở, thư mục upload được ánh xạ phía server và filename có path bị từ chối.
- API xóa sortable phụ đã yêu cầu CSRF và ép ID về số nguyên.
- API mutation `status`, `numb`, `copy`, upload và filer đã ánh xạ sang quyền action hiện có; khi RBAC bật, quyền được nạp lại từ DB và endpoint trả 403 nếu sai module/type hoặc chưa có mapping. RBAC vẫn đang tắt để tránh thay đổi vận hành trước khi test tài khoản nhân viên.
- 42 form POST Admin có CSRF field phía server; controller legacy chặn GET/thiếu token cho `save/delete/upload/sync/create`, gửi newsletter và preview watermark.
- API comment, ba API kéo-thả sortable và bảo trì database yêu cầu POST + CSRF; database action giới hạn `ANALYZE/OPTIMIZE/CHECK/REPAIR`, không còn đưa input tùy ý vào câu SQL.
- File manager legacy và API save sortable không còn call site đã bị chặn trực tiếp bằng 404 nhưng chưa xóa code để giữ khả năng đối chiếu/rollback.

Vẫn còn trước production:

- Kiểm thử ma trận tài khoản/nhóm quyền trên staging rồi mới bật RBAC toàn hệ thống; authorization cho API mutation chính đã sẵn sàng nhưng cấu hình hiện vẫn tắt.
- Retest thao tác lưu thực tế theo từng module trên staging với dữ liệu kiểm soát; CSRF đã phủ các form save/upload và API mutation đang dùng.
- Đã retest local ảnh/PDF thật, MIME giả, PHP giả PDF/tên kép và vượt dung lượng; còn retest video MP4/MKV dung lượng thực qua UI staging.
- Xác minh cookie `Secure`, forwarded proxy chain và các ngưỡng rate limit trên HTTPS staging thực tế.

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
