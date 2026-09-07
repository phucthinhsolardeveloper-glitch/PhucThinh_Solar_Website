# Dependency và asset map

Ngày xác minh gần nhất: 2026-08-31. Project không có root Composer manifest. Thư viện PHP/JS legacy chủ yếu được copy trực tiếp vào source; ngoại lệ kiểm thử trình duyệt có npm lock riêng tại `tools/visual-tests`.

## Dependency theo chức năng

| Nhóm | Dependency/source | Consumer chính | Trạng thái |
|---|---|---|---|
| Routing/data | AltoRouter, PDODb/PDO | `index.php`, router, public/Admin source | Bắt buộc giữ |
| Email/import | PHPMailer, PHPExcel, PHPWord | SMTP, bulk CSV/XLS/XLSX, export Admin | Bắt buộc giữ đến khi staging retest |
| Media | WebpConvert, GD/fileinfo, CKEditor/CKFinder/elFinder | thumbnail/upload/editor/file manager trong form CKEditor | Giữ; connector dùng bootstrap Admin + RBAC backend và upload allowlist |
| Public UI | jQuery, Bootstrap, UIkit, Slick, Owl, Jssor, Fancybox, Photobox, MagicZoom, mmenu, Font Awesome | layout public và markup/AJAX fragment | Đang nạp global để giữ tương thích |
| External | Google Fonts, UIkit CDN, GTranslate; reCAPTCHA/OneSignal theo config | font/UI/dịch/form/notification | Cần retest network/CSP/staging |
| Browser test | Playwright 1.62.1 + Chromium | `tools/visual-tests` | 16/16 smoke desktop/mobile đạt |

Không tìm thấy app-level Redis, queue, ORM, payment/shipping SDK hoặc API versioned. Manifest khác trong source thuộc subtree vendor như WebpConvert/elFinder/CKFinder, không phải dependency manager của toàn ứng dụng.

## CSS/JS public

- `templates/layout/css.php` và `templates/layout/js.php` là điểm nạp chính. Static scan có 105 dòng include trực tiếp hoặc include đã comment; browser baseline hiện tải 76–77 CSS/JS local tùy route, khoảng 3,31 MB trước gzip.
- Ba thử nghiệm tách Jssor/Fancybox/Owl theo route từng giảm request nhưng đã hoàn nguyên vì visual/tính năng khác baseline. Không tái áp dụng chỉ dựa trên tên plugin hoặc số request.
- Các fragment AJAX và script inline cũng gọi plugin, nên tìm kiếm template server-side đơn thuần chưa đủ chứng minh asset không dùng. Trước khi tách một dependency phải kiểm tra DOM sau JavaScript, request fragment, console, menu/popup/gallery/carousel và Playwright desktop/mobile.
- `assets/themes/local` và `assets/wp-content/themes/local` có nhiều file/font trùng nguồn legacy. Chưa được gộp hoặc xóa vì CSS có thể tham chiếu path tương đối khác nhau.

## Media và storage

| Vùng | Dung lượng local đo được | Vai trò | Quy tắc release/cleanup |
|---|---:|---|---|
| `assets/` | 243 MB; Git theo dõi khoảng 250,6 MB/1.438 entry | CSS/JS/font/icon và fallback WordPress | Đóng gói theo commit; 85 URL fallback legacy đang có consumer public |
| `upload/` | 157 MB | Dữ liệu persistent do CMS quản lý | Không lấy từ Git release, không xóa; backup/restore riêng |
| `thumbs/` | 75 MB | Thumbnail/cache ảnh dẫn xuất | Không coi là tái tạo an toàn trước khi staging xác minh đầy đủ |
| `wp-content/` root | 1,2 GB | Cây WordPress legacy local/hosting | Không thuộc bootstrap PHP hiện tại nhưng phải lập inventory hosting trước archive |
| `caches/` | 364 KB | Cache runtime | Chỉ clear có mục tiêu trong quy trình deploy |

Các file lớn đã thấy gồm MP4 khoảng 8–16 MB, PNG nội dung khoảng 9–15 MB và font SVG Montserrat bị lặp giữa hai cây theme. Đây mới là ứng viên tối ưu, chưa phải bằng chứng cho phép xóa. Package release vẫn phải giữ `assets/wp-content/uploads` vì public đang dùng fallback từ URL WordPress cũ.

## Luồng video cần giữ

- Sản phẩm: `product.video_url` nhận YouTube và render trong chi tiết sản phẩm.
- Bài viết/Giải pháp/Dự án: gallery `kind=man`, `val=video`, mở qua Fancybox/Photobox tùy template.
- Thư viện Video legacy: `sources/video.php`, `templates/video/video_tpl.php`, `api/video.php` và addon fotorama đọc `photo.link_video`; menu/config hiện có phần bị tắt nhưng endpoint/source còn tồn tại.
- MP4 upload: `photo.video` và video feedback/comment dùng file trong upload; cần retest MIME, size, quyền ghi và playback trên staging trước khi archive hoặc bật lại UI.
- Slider/core-value có cột `video`; nguồn trang chủ/allpage vẫn select field này, vì vậy không được kết luận không dùng chỉ vì một template không có thẻ `<video>`.

## Cổng xác minh trước khi loại hoặc tách

1. Chụp inventory URL từ HTML/DOM và network trên toàn bộ route/interaction liên quan.
2. Xác minh call site PHP, AJAX fragment, CSS `url()`, dữ liệu DB và file trên upload/hosting.
3. Tạo screenshot baseline có kiểm duyệt và regression cho plugin/asset định thay đổi.
4. Thử trên branch/commit nhỏ; chạy full pre-deploy và Playwright, so markup/interaction/log.
5. Chỉ archive sau backup/checksum và kế hoạch rollback; không xóa upload/media legacy trong cùng batch tối ưu code.
