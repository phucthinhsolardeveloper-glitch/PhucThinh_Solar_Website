# Current status

Cập nhật gần nhất: 2026-08-31. Mọi thay đổi bên dưới mới ở Git + Docker local, chưa triển khai production.

## WORKING ON LOCAL

- Trang sản phẩm có giá gốc/giá khuyến mãi/% giảm giá.
- Bộ lọc thương hiệu, công suất và mức giá.
- Giỏ hàng, Mua ngay, checkout COD/chuyển khoản và trang cảm ơn.
- Review sản phẩm, ảnh feedback và nhãn “Đã mua hàng”.
- Admin quản lý đơn hàng và admin chỉnh sửa 5 trang chính sách.
- Video YouTube, 19 trường thông số có cấu trúc, danh sách thông số riêng và nội dung chứng nhận theo từng sản phẩm.
- Admin và Excel CSV/XLS/XLSX nhập/xuất từng thông số; frontend dựng bảng từ dữ liệu cấu trúc và vẫn fallback về HTML kỹ thuật cũ.
- 4 dự án hiện có đều có ảnh và nội dung.
- Toàn bộ giá public đang ẩn theo chế độ báo giá; Admin và Excel vẫn giữ giá nội bộ.
- Canonical, Twitter metadata, robots, sitemap, HTTP security headers và chặn file nhạy cảm đã có regression local.
- Wordmark “Phúc Thịnh Solar” trong template public dùng vàng kim gradient.
- Có script backup database/upload local kèm checksum; restore rehearsal đạt 49 bảng và 775 file upload.
- Media fallback đã phục hồi 85/85 URL ảnh WordPress legacy; package release vẫn giữ các asset đang được tham chiếu.
- Migration 007 thêm index route; lookup local dùng index thay cho full scan nhỏ.
- Đã loại N+1 ở menu, block trang chủ, danh mục Sản phẩm và thống kê review trong danh sách Sản phẩm Admin; full pre-deploy đạt.
- Profiling authenticated bốn danh sách Admin không còn N+1 theo từng dòng; dropdown địa giới Đơn hàng bỏ query quận/phường khi chưa chọn parent.
- Ba thử nghiệm conditional loading CSS/JS đã được hoàn nguyên để giữ tương thích visual và tính năng.
- Đã cài Playwright Chromium trong `tools/visual-tests`; 16/16 browser smoke desktop/mobile đạt cho bảy trang public và icon chính.
- Đã sửa cú pháp `.htaccess` upload từng làm ảnh/logo trả HTTP 500; pre-deploy kiểm tra trực tiếp media để chống tái phát.

## PARTIAL / CHỜ DỮ LIỆU THẬT

- 5 trang chính sách đã có URL nhưng nội dung đang rỗng.
- 78 sản phẩm thiếu mô tả ngắn, 6 thiếu nội dung chi tiết và 16 thiếu thông số kỹ thuật.
- Chứng nhận và video chỉ hiển thị khi admin nhập dữ liệu thật.
- Dữ liệu kỹ thuật thật sẽ được cập nhật từ file Excel bên ngoài; không chạy migration 006 apply nếu chưa có phê duyệt riêng.
- Nội dung mô tả chi tiết từng sản phẩm cần biên tập theo tài liệu kỹ thuật thật.
- SMTP production và extension `calendar` trên hosting chưa được xác minh.

## SECURITY

- Các finding code chính của Security Review ngày 2026-08-27 đã được xử lý và regression đạt trên local.
- RBAC vẫn tắt; cần test tài khoản nghiệp vụ, HTTPS, cookie Secure, proxy, SMTP, reCAPTCHA và upload video trên staging.
- Không coi nhánh hiện tại là sẵn sàng production cho tới khi cổng staging và backup hosting đạt.

## STILL MISSING

- Nội dung chính sách được doanh nghiệp phê duyệt.
- Nội dung/ảnh/video/chứng nhận thật cho sản phẩm.
- CI/CD, E2E nghiệp vụ đầy đủ, staging và quy trình deploy/rollback production đã diễn tập.
