# Roadmap

Checklist thực thi hiện hành: `docs/13-handoffs/2026-08-29-next-work-checklist.md`
(lưu ý: đã cũ so với `docs/13-handoffs/2026-09-09-category-restructure-and-mobile-audit.md`,
đọc file mới trước để biết việc gần nhất đã xong).

## Theme V2 - ưu tiên trước quảng cáo

- [x] Đọc lịch sử thay đổi và đối chiếu source PHP với `solar-light-web`.
- [x] Tách lớp giao diện mới vào `assets/css/theme-v2.css` để dễ rollback.
- [x] Nâng header, logo wordmark và Home Hero.
- [x] Nâng Hero danh mục, bộ lọc, lưới và thẻ sản phẩm.
- [x] Nâng bố cục trang chi tiết, gallery, giá, CTA và thông số.
- [x] Tinh chỉnh trực quan sau khi đối chiếu thủ công trên desktop/mobile
  (2026-09-09, xem `docs/13-handoffs/2026-09-09-category-restructure-and-mobile-audit.md`
  — vẫn còn 1 báo cáo chưa xác minh: lưới sản phẩm 2 cột/1 cột lệch giữa
  giả lập desktop và điện thoại thật, đang chờ ảnh chụp thật để đối chiếu).
- [ ] Thay dần các section trang chủ legacy còn lại bằng cấu trúc Theme V2.

## Trước khi chạy quảng cáo / production

- [ ] Khi có nguồn mới: preview và kiểm tra Excel trên local/staging; không chạy migration 006 apply nếu chưa duyệt dữ liệu.
- [ ] Khi doanh nghiệp cung cấp: nhập và duyệt nội dung thật cho 5 chính sách.
- [ ] Khi có nguồn: duyệt video, mô tả và chứng nhận thật cho nhóm sản phẩm chạy ads.
- [x] Xử lý finding code Security Review và chạy regression local.
- [ ] Chạy runtime/schema preflight, xác minh SMTP, reCAPTCHA, backup/restore và rollback trên hosting/staging.
- [ ] Chạy commerce smoke đầy đủ và chỉ deploy khi có phê duyệt.

## Đã hoàn thành trên local

- Giá gốc/khuyến mãi/% giảm, filter công suất/giá.
- Giỏ hàng, Mua ngay, checkout, trang cảm ơn và conversion một lần.
- Review/ảnh feedback/xác minh đã mua hàng.
- Admin đơn hàng, admin chính sách.
- Video YouTube, 19 trường thông số có cấu trúc, thông số riêng và chứng nhận sản phẩm.
- Import/export sản phẩm CSV/XLS/XLSX có preview, đối soát xung đột và rollback trong lượt xử lý.

## Sau khi chốt Theme V2

- Thêm smoke/E2E, CI/CD và staging.
- Data cleanup/legacy asset archival sau xác minh.
- Nâng cấp PHP/dependency/database theo test baseline.
