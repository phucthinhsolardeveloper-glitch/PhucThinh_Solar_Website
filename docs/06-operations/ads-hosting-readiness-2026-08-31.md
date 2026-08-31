# Báo cáo readiness chạy quảng cáo và cập nhật hosting — 2026-08-31

## Kết luận hiện tại

- **Code và Docker local:** đạt baseline kỹ thuật.
- **Nội dung quảng cáo:** chưa đạt.
- **Staging/hosting:** chưa được xác minh.
- **Production:** **NO-GO** ở thời điểm lập báo cáo.

Không deploy hoặc ghi dữ liệu production trong đợt kiểm tra này.

## Đã hoàn thành trên local

### Giao diện

- Chuẩn hóa wordmark “Phúc Thịnh Solar” sang vàng kim gradient, có màu fallback.
- Tăng typography và khoảng đọc cho trang nội dung/bài viết.
- Mở rộng và cân đối form liên hệ, input, textarea và CTA.
- Tin tức, Giải pháp và Dự án dùng grid độc lập 3/2/1 cột; đã loại bỏ rule `.col-12` khiến card desktop bị kéo toàn hàng.
- HTTP smoke desktop/mobile đạt cho trang chủ, sản phẩm, giải pháp, dự án, tin tức, giới thiệu và liên hệ.

### Tính năng và bảo mật

- Chế độ báo giá ẩn giá public, Offer schema và thao tác mua hàng; giá nội bộ không bị xóa.
- Security, Admin readiness, RBAC local, CSRF 42 form, rate limit, upload và Excel regression đều đạt.
- Canonical, noindex trang nội bộ, Twitter metadata, robots và sitemap có regression.
- Chặn file SQL/log/backup và gói archive bàn giao; response có header phòng vệ và không lộ `X-Powered-By`.
- PHP động dùng `no-store` để tránh cache session, giỏ hàng và token form.

### Dữ liệu và backup

- 81 sản phẩm hiển thị có tên, slug, ảnh và danh mục hợp lệ.
- Không có mã/slug trùng, giá sai thứ tự, ảnh đại diện mất hoặc dữ liệu CODEX.
- Backup local gồm database, upload, release metadata và checksum được tạo ngoài webroot.
- Restore rehearsal đạt 49 bảng và 775 file upload; database/thư mục tạm được dọn sau test.

## Điều kiện còn chặn chạy quảng cáo

- 5/5 trang chính sách chưa có nội dung.
- 78 sản phẩm thiếu mô tả ngắn, 6 thiếu nội dung chi tiết và 16 thiếu thông số.
- Chưa có danh sách landing page/sản phẩm được doanh nghiệp duyệt để chạy quảng cáo.
- Chưa đối chiếu trực quan bằng trình duyệt thật trên desktop/mobile staging.
- Chưa xác minh SMTP, reCAPTCHA, HTTPS, cookie Secure, proxy chain và video upload trên staging.
- Chưa test tài khoản nhân viên thật trước khi bật RBAC.
- Chưa chứng minh backup hosting, off-site copy, retention và restore staging.

## Trình tự để chuyển sang GO

1. Nhận Excel và nội dung thật; backup rồi chỉ preview trên local/staging.
2. Duyệt nhóm sản phẩm/landing page quảng cáo và hoàn thiện chính sách.
3. Dựng staging đúng cấu hình hosting; test HTTPS, SMTP, reCAPTCHA, upload và tài khoản.
4. Đối chiếu giao diện bằng trình duyệt thật ở desktop/mobile và sửa lỗi còn lại.
5. Tạo backup hosting, diễn tập restore trên staging và chốt rollback.
6. Chạy lại pre-deploy, smoke staging và chỉ deploy khi chủ project phê duyệt release.

Checklist chi tiết: [Checklist sẵn sàng chạy quảng cáo và cập nhật hosting](../13-handoffs/2026-08-31-ads-hosting-readiness-checklist.md).
