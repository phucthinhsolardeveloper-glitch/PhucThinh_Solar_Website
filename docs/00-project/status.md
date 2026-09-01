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
- Toàn bộ giá public đang ẩn theo chế độ báo giá; Admin và Excel vẫn giữ giá nội bộ. Có công tắc Admin → Thiết lập thông tin → “Hiển thị giá sản phẩm” (khóa `public_product_prices`), mặc định “Liên hệ báo giá”, lưu xong tự xóa cache.
- Canonical, Twitter metadata, robots, sitemap, HTTP security headers và chặn file nhạy cảm đã có regression local.
- Wordmark “Phúc Thịnh Solar” trong template public dùng vàng kim gradient.
- Đợt 2026-08-31: phóng to giao diện public (thang chữ/header/container/section title), cân lại form liên hệ + chi tiết dự án, rà soát responsive mobile (nút liên hệ nổi, gutter, canh chữ, footer đầy đủ), sửa lỗi khung ảnh chi tiết sản phẩm phình trên mobile, khôi phục bộ lọc Thương hiệu/Công suất/Loại SP ở listing. Có `docs/07-features/ecommerce-upgrade-proposal.md` (phân tích Kitawa, chờ chốt phạm vi).
- Có script backup database/upload local kèm checksum; restore rehearsal đạt 49 bảng và 775 file upload.
- Media fallback đã phục hồi 85/85 URL ảnh WordPress legacy; package release vẫn giữ các asset đang được tham chiếu.
- Migration 007 thêm index route; lookup local dùng index thay cho full scan nhỏ.
- Đã loại N+1 ở menu, block trang chủ, danh mục Sản phẩm và thống kê review trong danh sách Sản phẩm Admin; full pre-deploy đạt.
- Profiling authenticated bốn danh sách Admin không còn N+1 theo từng dòng; dropdown địa giới Đơn hàng bỏ query quận/phường khi chưa chọn parent.
- Ba thử nghiệm conditional loading CSS/JS đã được hoàn nguyên để giữ tương thích visual và tính năng.
- Đã cài Playwright Chromium trong `tools/visual-tests`; full suite đạt 44 test, 2 skip có chủ ý. Có 7 screenshot baseline đã review, gồm lưới danh mục trang chủ desktop/mobile; regression phủ thêm mega menu, danh mục rỗng, modal tư vấn/báo giá, AJAX chuyển nhóm sản phẩm và drawer bộ lọc mobile.
- CTA “Gửi yêu cầu báo giá” ở chi tiết sản phẩm đã nối vào modal tư vấn hiện có; form có CSRF và backend newsletter, không còn là UI không thể mở.
- Import Excel sản phẩm xử lý được thương hiệu chưa có: preview gom tên mới và bắt quản trị viên chọn tạo mới, ghép hãng hiện hữu hoặc bỏ trống; brand mới và sản phẩm được áp dụng cùng lượt, có rollback thủ công cho MyISAM.
- A3 e-commerce hoàn tất: trang chủ hiển thị đủ 9 danh mục nổi bật thành lưới 5/3/2 cột; ưu tiên ảnh Admin và dùng icon vàng khi danh mục chưa có ảnh, không thêm query DB.
- A4 e-commerce hoàn tất: trang danh mục có nút “Bộ lọc” trên mobile/tablet, drawer trượt có backdrop, khóa cuộn, Escape/focus/ARIA và tự đóng sau khi lọc AJAX; desktop giữ sidebar hiện hữu.
- Đã sửa cú pháp `.htaccess` upload từng làm ảnh/logo trả HTTP 500; pre-deploy kiểm tra trực tiếp media để chống tái phát.
- Đã lập dependency/usage map CSS, JS, media và bốn luồng video legacy; chưa xóa/tách asset khi chưa đủ browser/staging evidence.

## PARTIAL / CHỜ DỮ LIỆU THẬT

- 5 trang chính sách đã có URL nhưng nội dung đang rỗng.
- 78 sản phẩm thiếu mô tả ngắn, 6 thiếu nội dung chi tiết và 16 thiếu thông số kỹ thuật.
- Đã import file Excel thật `artifacts/san-pham-20260831-da-bo-sung-oriana.xlsx` vào DB local (preview 0 lỗi, backup `backup/local/20260831-132554`): +70 sản phẩm ORIANA (id 467–536), cập nhật #321/#311. Tổng 81 → 151. 70 sản phẩm mới còn thiếu ảnh + mô tả ngắn + nội dung; đã đặt `numb=2` để xếp cuối danh mục, chờ bổ sung ảnh/nội dung rồi mới đưa lên đầu.
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
