# Codex -> Claude: tiếp tục Theme V2 - 2026-08-27

## Mục tiêu đang làm

Port phong cách giao diện của `/mnt/d/Test01/solar-light-web` sang website PHP hiện tại, giữ nguyên URL, dữ liệu, route, session và backend thương mại. Chỉ làm trên Docker local; chưa đụng production.

## Commit mới của Codex

- `cbff424` - Theme V2 foundation.
- `8c22a79` - Theme V2 cho các section trang chủ.

## Đã làm

### Foundation

- Tạo `assets/css/theme-v2.css`, nạp sau toàn bộ CSS legacy để dễ rollback.
- Header sticky, top utility bar, logo + wordmark, menu/dropdown, footer navy/gold.
- Home Hero mới, CTA và các con số theo ngôn ngữ thiết kế React.
- Bỏ khối giới thiệu cũ ở đầu trang chủ để tránh trùng Hero.

### Danh mục và product card

- Category Hero động theo tên danh mục.
- Trust strip, sidebar/filter, sort pills, grid 4 cột desktop.
- Card sản phẩm navy/gold, badge giảm giá và một nút tròn dấu cộng.
- Nút card vẫn gọi handler `addnow`/API giỏ hàng cũ.

### Chi tiết sản phẩm

- Bố cục gallery + buy box, giá/khuyến mãi, số lượng, CTA, trust items.
- Restyle bảng thông số, tab nội dung, video và sản phẩm liên quan.
- Không thay backend đặt hàng.

### Trang chủ đợt 2

- Restyle banner quảng cáo, giải pháp, banner đại lý, lợi ích, số liệu, đối tác.
- Restyle feedback, dự án, quy trình và khối hoạt động/kiến thức.
- Responsive 991/768 px đã bổ sung ở lớp Theme V2.

## Kiểm thử đã qua

- PHP 7.4 lint: toàn bộ template/API đã thay đổi.
- HTTP 200: trang chủ, danh mục đèn NLMT, chi tiết JD-T300, giỏ hàng.
- Commerce smoke: POST `cmd=add-cart&id=311&quantity=1` trả `{"max":1}`; cùng cookie mở giỏ có `DB02`.
- CSS Theme V2 và trang chủ trả HTTP 200 sau khi sync vào container.

## Việc Claude nên làm tiếp

1. Refresh hard cache và chụp desktop/mobile cho trang chủ, danh mục, chi tiết; sửa overflow/spacing/typography bằng CSS, không đổi handler.
2. Đồng bộ các trang nội dung với React: `news_tpl.php`, `project_tpl.php`, `service_tpl.php` và các trang detail tương ứng.
3. Rà `contact_tpl.php`, `about_tpl.php`, `daily_tpl.php`; giữ nguyên form action, field name và CSRF/quyền hiện có.
4. Kiểm tra menu mobile, filter drawer, dropdown desktop và nút dấu cộng trên card bằng Playwright.
5. Chạy lại commerce smoke category -> product -> add cart -> cart -> checkout -> thanks.
6. Rebuild Docker từ source trước khi bàn giao cuối; không chỉ `docker cp`.

## File chính

- `domains/phucthinhsolar.com/public_html/assets/css/theme-v2.css`
- `templates/layout/css.php`
- `templates/layout/header.php`
- `templates/layout/slide.php`
- `templates/index/index_tpl.php`
- `templates/product/product_tpl.php`
- `templates/product/product_detail_tpl.php`
- `api/fillter_service.php`
- `api/product2.php`

## Docker build context

Build sạch gửi khoảng 567 MB. Nguyên nhân chính: `assets` 243 MB (`assets/wp-content` 187 MB), `upload` 157 MB và `thumbs` 75 MB. DB backup chỉ 5,3 MB; ZIP 505 MB đã bị `.dockerignore` loại.

Đã thử exclude ba thư mục media + bind mount: context giảm còn 135 MB nhưng lúc đó Docker Desktop chưa bật WSL integration và báo thiếu `ubuntu.sock`; thử nghiệm đã hoàn nguyên. Người dùng hiện đã bật Ubuntu integration. Có thể thử lại sau, nhưng ưu tiên hoàn thiện giao diện trước và không xóa media.

## Lỗi điều khiển browser của Codex

Website và Docker chạy bình thường. Browser automation của Codex không khởi tạo được vì tiến trình browser sandbox lỗi `windows sandbox failed: helper_unknown_error: setup refresh had errors`; `open_in_codex` chỉ trả trạng thái `queued`, không điều hướng tab ngay. Đây là lỗi runtime Codex/Windows, không phải code PHP hay WSL website.

Claude có thể tiếp tục dùng Playwright riêng như các commit trước. Không cần sửa source để xử lý lỗi browser của Codex.

## Rollback

- Hoàn nguyên `8c22a79` để bỏ restyle các section trang chủ.
- Hoàn nguyên `cbff424` để bỏ toàn bộ Theme V2 foundation.
- Không có migration DB trong hai commit trên.
