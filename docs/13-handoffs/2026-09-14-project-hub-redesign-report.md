# Bàn giao nâng cấp trang Dự án — 2026-09-14

## Kết quả

- `/du-an-da-thuc-hien/` và các route `danh-muc-du-an/*` dùng chung project hub mới: tiêu đề, bộ lọc nhóm, lưới dự án thật, trạng thái rỗng và CTA tư vấn.
- Giữ type `du-an`, slug public, SEO, gallery, liên kết sản phẩm và trang chi tiết hiện hữu; bỏ URL sai `store-manager/*` ở trang danh sách và card dự án trang chủ.
- Không tạo dự án hoặc nhóm giả. Dữ liệu local hiện có bốn dự án Cá nhân và nhóm Doanh nghiệp chưa có dự án nên hiển thị empty state.
- Card dùng tên, ảnh, địa điểm và nhóm dự án; nội dung đầu ra được escape, ảnh thiếu dùng placeholder thay vì request ảnh hỏng.

## Admin và dữ liệu

- Nhóm **Trang Dự án** gom `project-intro`, bài/nhóm `du-an`, `project-cta` và lối tắt xem trang public.
- `project-intro`: nhãn nhỏ, tiêu đề và mô tả; `project-cta`: nhãn, tiêu đề, mô tả, chữ nút và ảnh nền tùy chọn.
- Nhóm dự án dùng thêm cột mô tả hiện hữu và ảnh làm icon filter. Bài dự án vẫn giữ địa điểm, khách hàng, ngày hợp đồng, nội dung, gallery và liên kết sản phẩm.
- Không có migration schema hoặc ghi dữ liệu nội dung. Có migration quyền `037_grant_redesign_page_permissions.php`: chỉ cấp quyền hai block mới cho nhóm vốn đã có đủ bốn quyền `du-an`.

## File chính

- `domains/phucthinhsolar.com/public_html/sources/project_hub.php`
- `domains/phucthinhsolar.com/public_html/templates/news/project_tpl.php`
- `domains/phucthinhsolar.com/public_html/templates/news/project_cta_tpl.php`
- `domains/phucthinhsolar.com/public_html/assets/css/project-hub.css`
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-news.php`
- `domains/phucthinhsolar.com/public_html/libraries/config-type.php`
- `migrations/037_grant_redesign_page_permissions.php`
- `scripts/test-project-hub.php`, `scripts/test-admin-readiness.php`, `scripts/test-public-lead-and-review.php`
- `scripts/predeploy-local.sh`
- `tools/visual-tests/tests/project-hub.spec.js`

## Kiểm thử

- PHP 7.4 lint toàn bộ PHP thay đổi: đạt.
- `scripts/test-project-hub.php`: đạt render 0/4 dự án, filter Admin, canonical URL, escape metadata, ảnh thiếu, H1 và tắt intro/CTA.
- Admin readiness: đạt toàn bộ, gồm tạo/lưu/ẩn intro/CTA và kiểm tra các field dự án/nhóm hiện hữu.
- Smoke route local: trang tổng `4` card, Cá nhân `4`, Doanh nghiệp `0`; cả ba HTTP 200, không warning/fatal.
- Playwright project hub: `4/4` đạt trên desktop Chromium và Pixel 7; kiểm tra link chi tiết, ảnh, overflow, popup tư vấn và empty state.
- Permission catalogue sau migration: nhóm Cấp cao `208/208`, không quyền lỗi thời; migration chạy lặp không thêm dòng.
- `scripts/test-public-lead-and-review.php`: `19/19` đạt hai lần liên tiếp sau khi cô lập và khôi phục đúng bucket rate-limit fixture.
- Pre-deploy local đầy đủ: đạt; regression, smoke, sitemap mẫu `26/509` URL, log, cleanup và diễn tập restore `54` bảng/`925` upload đều sạch.

## Deploy và rollback

1. Backup code và DB theo quy trình hiện hữu; deploy code.
2. Chạy preview `php migrations/037_grant_redesign_page_permissions.php`, kiểm tra nhóm dự kiến được cấp.
3. Chạy `php migrations/037_grant_redesign_page_permissions.php --apply`, rồi yêu cầu người dùng Admin đăng xuất/đăng nhập lại.
4. Cấu hình nội dung thật trong Admin nếu muốn thay fallback; không chạy script seed local trên production.

Rollback code bằng revert commit triển khai. Nếu rollback toàn bộ ba redesign CMS, bỏ các quyền type mới trong màn Nhóm quyền để audit không còn quyền lỗi thời; không xóa bài dự án, upload, gallery hay quan hệ sản phẩm.

## Rủi ro còn lại

- Nhóm Doanh nghiệp hiện chưa có dự án nên empty state là đúng dữ liệu, không phải lỗi giao diện.
- Nội dung intro/CTA dùng fallback VI/EN cho tới khi Admin tạo record. Nếu đã có record nhưng tắt toàn bộ, block tương ứng bị ẩn.
- Chưa deploy hoặc ghi dữ liệu production.
