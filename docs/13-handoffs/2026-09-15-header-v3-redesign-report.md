# Header V3 — triển khai local 15/09/2026

## Kết quả

- Tạo nhánh `feature/kitawa-inspired-header` từ `refactor/about-page-v2` khi worktree sạch.
- Đã tạo và push commit mốc trước thay đổi: `34e8498 chore: checkpoint before header redesign`.
- Header mới lấy cảm hứng từ cấu trúc thương mại hai tầng của Kitawa, nhưng giữ logo, navy/gold, kiểu chữ và nội dung Phúc Thịnh Solar.
- Tầng chính gồm logo, ô tìm kiếm lớn, tối đa 5 danh mục tìm nhanh, hotline/tư vấn, ngôn ngữ và giỏ hàng khi cấu hình bán hàng công khai đang bật.
- Tầng điều hướng có nút Danh mục sản phẩm màu vàng, menu/dropdown hiện có và liên kết chính sách bảo hành.
- Mobile dùng hamburger, logo, hotline và tìm kiếm dạng mở rộng; menu desktop chuyển sang drawer từ 1120px để tránh ép chữ.
- Header fixed dùng đúng chiều cao thực để ẩn hoàn toàn khi cuộn xuống, thay vì hằng số 130px.

## Tích hợp Admin và dữ liệu hiện có

- Logo, email, giờ làm việc, hotline, Facebook, Zalo và trạng thái hiển thị giá/giỏ hàng tiếp tục lấy từ Cài đặt website.
- Danh mục tìm nhanh lấy 5 danh mục sản phẩm đầu theo dữ liệu/STT mà source hiện có cấp cho menu; không hardcode watt hay tạo taxonomy riêng.
- Thêm ô **Thông điệp ngắn trên đầu website** tại Admin > Cài đặt website (`options.header_promise`), tối đa 100 ký tự. Để trống dùng fallback “Giải pháp năng lượng tối ưu cho mọi công trình”. Output được escape; runtime cũng cắt tối đa 100 ký tự.
- Search giữ route `tim-kiem?keyword=...` và hàm dùng chung hiện có. Giỏ hàng giữ selector `.count-cart`; AJAX đồng thời cập nhật nhãn trợ năng.
- Khi website ở chế độ báo giá (local hiện tại), giỏ hàng chủ ý không render và hotline hiện “Tư vấn sản phẩm”. Khi bật giá công khai, cart render như luồng cũ.

## File thay đổi

- `domains/phucthinhsolar.com/public_html/templates/layout/header.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/menu.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/css.php`
- `domains/phucthinhsolar.com/public_html/assets/css/header-v3.css` (mới, tách riêng để rollback)
- `domains/phucthinhsolar.com/public_html/assets/js/apps.js`
- `domains/phucthinhsolar.com/public_html/assets/js/functions.js`
- `domains/phucthinhsolar.com/public_html/administrator/templates/setting/man/man_add_tpl.php`
- `scripts/test-admin-readiness.php`
- `tools/visual-tests/tests/header-v3.spec.js` (mới)
- `tools/visual-tests/tests/layout-interactions.spec.js`
- `tools/visual-tests/tests/visual-baseline.spec.js` và các snapshot desktop/mobile liên quan.

## Kiểm thử

- PHP 7.4 lint `header.php`, `menu.php`, `css.php`: PASS.
- Header V3 Playwright: 2/2 PASS, gồm desktop 1904px và Pixel 7; kiểm tra ảnh logo, thứ tự catalog, tìm kiếm thật, mobile search và tràn ngang.
- Hồi quy kết hợp header/layout/public/visual: 51 PASS, 7 SKIP có chủ đích, exit 0. Bao phủ menu nhiều cấp, drawer mobile, search, các trang public chính, sản phẩm/chi tiết, popup, breadcrumb/footer, News/Project rail và visual baseline.
- Visual baseline: 7 PASS, 1 SKIP có chủ đích cho MagicZoom mobile. Snapshot đã cập nhật theo header mới.
- `./scripts/predeploy-local.sh`: PASS toàn bộ sau khi cập nhật guard markup Header V3; gồm PHP 7.4 lint, Admin/CSRF/RBAC, commerce regression, public smoke, sitemap 509 URL (lấy mẫu 26 URL đều 200), log/cleanup và restore rehearsal 54 bảng + 925 file upload.

## Rủi ro và lưu ý

- Header chủ ý cao và đậm hơn bản cũ để tạo dấu ấn thương mại. Ở desktop 1904px tổng chiều cao khoảng 210px gồm utility bar; mobile khoảng 104px CSS.
- Logo hiện tại là wordmark navy/gold, nên đặt trên nền trắng trong header navy để đảm bảo tương phản; không chỉnh file logo.
- Tên danh mục quá dài sẽ bị giới hạn trong hàng tìm nhanh thay vì làm vỡ header; quản trị STT danh mục quyết định 5 mục xuất hiện.
- Chưa deploy production và chưa thay dữ liệu production.

## Deploy và rollback

Trước deploy cần backup code/DB, chạy predeploy bằng PHP 7.4 mục tiêu và smoke desktop/mobile. Deploy toàn bộ file trong danh sách cùng snapshot/test ngoài public webroot theo quy trình repository; không upload test vào document root.

Rollback ưu tiên hoàn tác commit Header V3. Vì CSS mới tách riêng, có thể khôi phục `header.php`, `menu.php`, `css.php`, hai file JS và form setting về release trước rồi bỏ `header-v3.css`. Trường `options.header_promise` nằm trong JSON options, không đổi schema; code cũ bỏ qua nên không cần restore đè DB.
