# Roadmap

## Theme V2 - ưu tiên trước quảng cáo

- [x] Đọc lịch sử thay đổi và đối chiếu source PHP với `solar-light-web`.
- [x] Tách lớp giao diện mới vào `assets/css/theme-v2.css` để dễ rollback.
- [x] Nâng header, logo wordmark và Home Hero.
- [x] Nâng Hero danh mục, bộ lọc, lưới và thẻ sản phẩm.
- [x] Nâng bố cục trang chi tiết, gallery, giá, CTA và thông số.
- [ ] Tinh chỉnh trực quan sau khi đối chiếu thủ công trên desktop/mobile.
- [ ] Thay dần các section trang chủ legacy còn lại bằng cấu trúc Theme V2.

## Trước khi chạy quảng cáo / production

- Nhập và duyệt nội dung thật cho 5 chính sách.
- Nhập video, thông số, mô tả và chứng nhận thật cho nhóm sản phẩm chạy ads.
- Rà 42 sản phẩm chưa có công suất chuẩn hóa.
- Xử lý Security Review 2026-08-27 rồi retest.
- Xác minh SMTP, PHP `calendar`, backup và rollback trên hosting.

## Đã hoàn thành trên local

- Giá gốc/khuyến mãi/% giảm, filter công suất/giá.
- Giỏ hàng, Mua ngay, checkout, trang cảm ơn và conversion một lần.
- Review/ảnh feedback/xác minh đã mua hàng.
- Admin đơn hàng, admin chính sách.
- Video YouTube, thông số có cấu trúc và chứng nhận sản phẩm.

## Sau khi chốt Theme V2

- Thêm smoke/E2E, CI/CD và staging.
- Data cleanup/legacy asset archival sau xác minh.
- Nâng cấp PHP/dependency/database theo test baseline.
