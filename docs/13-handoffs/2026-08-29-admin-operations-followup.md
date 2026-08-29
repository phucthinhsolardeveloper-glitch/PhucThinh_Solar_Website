# Bàn giao nâng cấp vận hành Admin — 2026-08-29

Phạm vi ban đầu: Docker local, chưa triển khai production. Follow-up thông số sản phẩm ngày 2026-08-29 có bổ sung migration schema 004–006 nhưng chưa triển khai production.

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
- Tiêu đề cột Excel/CSV xuất ra dùng tiếng Việt và được tự ánh xạ về field nội bộ khi nhập lại. Import vẫn nhận tên cột kỹ thuật cũ để tương thích ngược.
- File nghiệp vụ không còn bắt buộc cột Thao tác/Mốc cập nhật. Hệ thống tự nhận diện: ID hoặc mã đã tồn tại là cập nhật; ID trống và mã mới là tạo mới. Tạo mới bắt buộc mã và tên; ID và mã chỉ đến hai sản phẩm khác nhau sẽ bị từ chối.
- Workbook Excel có sheet `SanPham`, `HuongDan`, `DanhMuc` và sheet `_HeThong` ẩn hoàn toàn. Snapshot trong `_HeThong` cho phép so sánh dữ liệu lúc export, dữ liệu website hiện tại và dữ liệu Excel.
- Khi website và Excel cùng sửa một ô, preview mặc định giữ website và cho quản trị viên chọn riêng từng ô cần dùng dữ liệu Excel. Danh mục/thương hiệu có danh sách chọn; ID và tên ảnh hiện tại được khóa để đối chiếu.

## Kiểm thử 2026-08-29

- PHP 7.4 lint: đạt trên 14 file PHP thuộc thay đổi.
- `scripts/test-product-bulk.php`: đạt toàn bộ kiểm tra đọc CSV, preview, create, update, archive, chống trùng, bắt buộc ID và sanitize HTML.
- Workbook thực tế 81 sản phẩm: export/import round-trip đạt, đọc đủ snapshot 81/81, không sinh lỗi hoặc xung đột giả; dropdown và sheet hệ thống ẩn được xác minh.
- Test tự nhận diện tạo/cập nhật theo ID/mã, từ chối ID–mã lệch nhau, phát hiện three-way conflict, mặc định giữ website và commit lựa chọn ghi đè từng ô: đạt.
- Dữ liệu sản phẩm thử đã được xóa; tổng số sản phẩm trước/sau test không đổi.
- `git diff --check`: đạt.
- Audit Admin trước đó đã render 65/65 route và kiểm tra filter/query state ở các module chính.

## Rủi ro còn lại

- Database MyISAM không hỗ trợ transaction; bulk dùng compensating rollback trong code, không có tính atomic tuyệt đối nếu process/server dừng đột ngột.
- Bulk chưa quản lý ảnh; cột `photo` chỉ đọc. Không dùng bulk để thay upload/media.
- Sheet `_HeThong` được ẩn và khóa để tránh sửa nhầm nhưng không phải chữ ký mật mã. File chỉ nên trao đổi trong nhóm quản lý sản phẩm đáng tin cậy.
- Chưa chạy SMTP/reCAPTCHA thật; cần site key và mailbox staging riêng để tránh gửi dữ liệu ngoài ý muốn.
- Mutation Admin legacy khác vẫn thiếu CSRF/authorization theo action; thay đổi này không giải quyết toàn bộ Security Review.
- Cần smoke lại bằng browser sau build sạch trước khi deploy.

## Follow-up: thông số sản phẩm có cấu trúc

- Mở rộng từ 8 lên 19 trường thông số chuẩn và thêm danh sách `additional_specs` cho các thông số riêng.
- Admin và bulk CSV/XLS/XLSX dùng chung parser `Tên | Giá trị`; frontend dựng bảng từ dữ liệu cấu trúc và fallback về HTML cũ khi cần.
- Migration 004 thêm schema; migration 005 tách bảng HTML cũ; migration 006 chuẩn hóa nhãn đồng nghĩa và giữ dòng trùng bằng hậu tố. Hai script dữ liệu mặc định dry-run và không xóa HTML cũ.
- Test tích hợp bổ sung XLS legacy, round-trip workbook, `__CLEAR__`, lỗi giữa batch/rollback, JSON lỗi và nhãn trùng; toàn bộ đạt trên PHP 7.4 local.
- Migration 006 mới được xác minh dry-run, chưa được phép áp dụng lên production.

## Deploy và rollback

- Chưa được phép deploy production. Trước deploy phải backup code/DB, build staging sạch và smoke product/order/contact/newsletter.
- Phần nâng cấp Admin ban đầu không có migration; follow-up thông số phải chạy migration 004–006 đúng thứ tự sau backup và duyệt dry-run.
- Rollback bằng revert commit chứa các file trong bàn giao này. Nếu bulk đã được dùng sau deploy, phải đối chiếu export/backup DB trước khi rollback code; rollback code không tự đảo dữ liệu sản phẩm đã nhập.
