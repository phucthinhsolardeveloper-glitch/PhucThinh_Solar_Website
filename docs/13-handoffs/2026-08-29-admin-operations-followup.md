# Bàn giao nâng cấp vận hành Admin — 2026-08-29

Phạm vi: Docker local, chưa triển khai production và không thay đổi schema.

## Mục tiêu

- Làm danh sách sản phẩm, đơn hàng, liên hệ và lead dễ tìm/lọc hơn.
- Loại bỏ SQL ghép trực tiếp từ tham số tìm kiếm trong các màn hình này.
- Bổ sung công cụ nhập/xuất sản phẩm hàng loạt có bước xem trước và khả năng hoàn tác trong lượt xử lý.
- Nối đúng form liên hệ Theme V2 với controller hiện có.

## Đã triển khai trong worktree

- Sản phẩm: query filter parameterized; tìm theo tên/mã; lọc thiếu công suất và đánh giá mới; bật lối vào quản trị đánh giá.
- Đơn hàng: parameterized toàn bộ trạng thái, thanh toán, ngày, giá, địa phương và từ khóa; giữ đúng trạng thái thanh toán `0`; lấy sẵn tên trạng thái/phương thức bằng join.
- Liên hệ: tìm theo tên, điện thoại, email, chủ đề; lọc chưa/đã xác nhận; thêm menu Admin; form Theme V2 gửi đúng `submit-contact` và token reCAPTCHA, địa chỉ không còn bắt buộc vì form mới không có trường này.
- Newsletter/tư vấn/đại lý: tìm theo tên, email, điện thoại, công ty; lọc tình trạng xử lý và giữ filter khi phân trang.
- Bulk sản phẩm: CSV/XLS/XLSX tối đa 5 MB và 500 dòng; kiểm tra extension/MIME/header; create/update/upsert/archive/skip; kiểm tra ID, mã, slug, danh mục, giá và trạng thái; chống CSV formula injection; sanitize HTML; CSRF; preview hết hạn sau 30 phút; xác nhận `CAP_NHAT`; kiểm tra optimistic timestamp; tạo SEO khi tạo sản phẩm; phục hồi các dòng đã xử lý nếu một dòng sau thất bại.

## Kiểm thử 2026-08-29

- PHP 7.4 lint: đạt trên 14 file PHP thuộc thay đổi.
- `scripts/test-product-bulk.php`: đạt toàn bộ kiểm tra đọc CSV, preview, create, update, archive, chống trùng, bắt buộc ID và sanitize HTML.
- Dữ liệu sản phẩm thử đã được xóa; tổng số sản phẩm trước/sau test không đổi.
- `git diff --check`: đạt.
- Audit Admin trước đó đã render 65/65 route và kiểm tra filter/query state ở các module chính.

## Rủi ro còn lại

- Database MyISAM không hỗ trợ transaction; bulk dùng compensating rollback trong code, không có tính atomic tuyệt đối nếu process/server dừng đột ngột.
- Bulk chưa quản lý ảnh; cột `photo` chỉ đọc. Không dùng bulk để thay upload/media.
- Chưa chạy SMTP/reCAPTCHA thật; cần site key và mailbox staging riêng để tránh gửi dữ liệu ngoài ý muốn.
- Mutation Admin legacy khác vẫn thiếu CSRF/authorization theo action; thay đổi này không giải quyết toàn bộ Security Review.
- Cần smoke lại bằng browser sau build sạch trước khi deploy.

## Deploy và rollback

- Chưa được phép deploy production. Trước deploy phải backup code/DB, build staging sạch và smoke product/order/contact/newsletter.
- Thay đổi không có migration database.
- Rollback bằng revert commit chứa các file trong bàn giao này. Nếu bulk đã được dùng sau deploy, phải đối chiếu export/backup DB trước khi rollback code; rollback code không tự đảo dữ liệu sản phẩm đã nhập.
