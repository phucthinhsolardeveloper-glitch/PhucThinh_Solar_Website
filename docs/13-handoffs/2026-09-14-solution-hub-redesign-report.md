# Bàn giao nâng cấp trang Giải pháp — 2026-09-14

## Kết quả

- `/giai-phap/` dùng hero chia ảnh/nội dung, hai nhóm giải pháp hiện hữu, bốn lối tắt thiết bị và CTA tư vấn.
- Card đầu trang lấy từ `news_list` loại `giai-phap` và dẫn qua đúng trang nhóm Doanh nghiệp/Hộ gia đình; bài `news` trong nhóm cung cấp ảnh/nội dung đại diện.
- `/doanh-nghiep` và `/danh-muc-giai-phap/ho-gia-dinh/` dùng cùng ngôn ngữ giao diện, bỏ ngày/tác giả và giữ URL/SEO cũ.
- Không tạo bài giải pháp giả. Local hiện có hai nhóm, mỗi nhóm một bài.

## Admin

- Nhóm **Trang Giải pháp** gom Mở đầu, Giải pháp, CTA và lối tắt chọn danh mục sản phẩm.
- `solution-intro`: nhãn nhỏ, tiêu đề, mô tả, ảnh tùy chọn.
- Giải pháp cấp 1: tên, mô tả, ảnh, thứ tự, cờ Trang Giải pháp.
- Bài giải pháp: cờ Đại diện trên Trang Giải pháp để chọn ảnh/nội dung đại diện trong từng nhóm.
- `solution-cta`: tiêu đề, mô tả, chữ nút, ảnh nền tùy chọn; nút dùng modal tư vấn sẵn có và hotline trong Cấu hình website.
- Danh mục sản phẩm: cờ Tìm nhanh trên trang Giải pháp và mô tả ngắn.

Nếu có bản ghi Mở đầu/CTA nhưng tất cả bị tắt Hiển thị, module tương ứng bị ẩn. Nếu chưa tạo bản ghi, giao diện dùng nội dung mặc định an toàn.

## Dữ liệu local

Script `scripts/setup-solution-hub-local.php` chỉ thêm cờ `solutionhub` vào bốn danh mục thiết bị đã xác minh. Trạng thái cũ nằm trong container local tại `/tmp/solution-hub-selection-20260914-142219-764f9c.json`. Không chạy script này trên production.

## Kiểm thử

- PHP 7.4 lint toàn bộ file PHP thay đổi: đạt.
- `scripts/test-solution-hub.php`: đạt các trạng thái 0/1/2/4 nhóm, ẩn module, locale fallback, escape nội dung và kiểm tra ảnh.
- `scripts/test-admin-readiness.php`: đạt toàn bộ, gồm tạo/lưu/ẩn Mở đầu và CTA cùng các field/cờ quản trị.
- Playwright About + public pages + Solution hub: 23 đạt, 1 test desktop chủ động bỏ qua; desktop và mobile đều đạt.

## Deploy và rollback

- Deploy code trước, chạy `migrations/037_grant_redesign_page_permissions.php --apply`, sau đó cấu hình dữ liệu thật trong Admin. Migration chỉ cộng quyền cho nhóm đã có đủ quyền Giải pháp gốc và có thể chạy lặp.
- Production cần tự chọn các danh mục thiết bị nếu muốn giới hạn bốn lối tắt. Khi chưa chọn cờ nào, hub dùng danh mục đang Hiển thị theo fallback.
- Rollback code bằng revert commit triển khai. Nếu rollback toàn bộ redesign CMS, bỏ tám quyền `solution-intro`/`solution-cta` khỏi các nhóm trong Admin; việc bỏ các cờ `solutionhub` là tùy chọn, không xóa field, bảng hoặc nội dung.
