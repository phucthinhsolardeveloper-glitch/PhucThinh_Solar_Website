# Current status

Cập nhật gần nhất: 2026-08-29. Mọi thay đổi bên dưới mới ở Git + Docker local, chưa triển khai production.

## WORKING ON LOCAL

- Trang sản phẩm có giá gốc/giá khuyến mãi/% giảm giá.
- Bộ lọc thương hiệu, công suất và mức giá.
- Giỏ hàng, Mua ngay, checkout COD/chuyển khoản và trang cảm ơn.
- Review sản phẩm, ảnh feedback và nhãn “Đã mua hàng”.
- Admin quản lý đơn hàng và admin chỉnh sửa 5 trang chính sách.
- Video YouTube, 19 trường thông số có cấu trúc, danh sách thông số riêng và nội dung chứng nhận theo từng sản phẩm.
- Admin và Excel CSV/XLS/XLSX nhập/xuất từng thông số; frontend dựng bảng từ dữ liệu cấu trúc và vẫn fallback về HTML kỹ thuật cũ.
- 4 dự án hiện có đều có ảnh và nội dung.

## PARTIAL / CHỜ DỮ LIỆU THẬT

- 5 trang chính sách đã có URL nhưng nội dung đang rỗng.
- Chứng nhận và video chỉ hiển thị khi admin nhập dữ liệu thật.
- Local còn 29/81 sản phẩm chưa có công suất chuẩn hóa; dữ liệu sau migration 005–006 vẫn cần được chủ dữ liệu duyệt trước khi áp dụng trên production.
- Nội dung mô tả chi tiết từng sản phẩm cần biên tập theo tài liệu kỹ thuật thật.
- SMTP production và extension `calendar` trên hosting chưa được xác minh.

## DEFERRED SECURITY

- Security Review ngày 2026-08-27 phát hiện các finding P0-P2; chủ project yêu cầu tạm hoãn xử lý.
- Không được coi nhánh hiện tại là sẵn sàng production cho tới khi audit được xử lý và retest.
- Xem `docs/10-audits/2026-08-27-security-review.md`.

## STILL MISSING

- Nội dung chính sách được doanh nghiệp phê duyệt.
- Nội dung/ảnh/video/chứng nhận thật cho sản phẩm.
- CI/CD, E2E, staging và quy trình deploy/rollback production đã diễn tập.
