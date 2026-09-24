# Landing danh mục sản phẩm v3 — 2026-09-23

## Phạm vi

Trang `/san-pham/` và danh mục cấp 1/cấp 2 (`/danh-muc-san-pham/.../`) giữ
nguyên URL, taxonomy, lọc, sắp xếp, phân trang và card sản phẩm hiện có. Bố cục
mới học theo mẫu catalog đã duyệt: hero danh mục, rail ảnh `Tất cả + danh mục
đang có dữ liệu` dạng ngang, rail loại sản phẩm cấp 2 theo ngữ cảnh, dải cam
kết dịch vụ, tiêu đề kết quả và bộ lọc/lưới sản phẩm.

## Quy tắc dữ liệu

- Tên, slug, ảnh và nhóm con lấy từ `product_list`/`product_cat` đang bật
  `hienthi`; không tạo danh mục giả ở frontend.
- Mô tả hero ưu tiên `desc` của danh mục, sau đó SEO description; khi chưa có
  dữ liệu dùng câu fallback an toàn, không ghi ngược vào DB.
- Banner vẫn ưu tiên slot Admin `product-category-hero`; nếu slot chưa cấu hình
  thì dùng ảnh danh mục và fallback site visual đã có.
- Rail chính “Tất cả” trỏ về `/san-pham/` và lấy đủ danh mục cấp 1 trong Admin;
  rail phụ của từng danh mục dùng URL cấp 2 phân cấp chuẩn để không làm mất
  SEO/link cũ.
- `/san-pham/` là lưới tất cả sản phẩm dùng chung bộ lọc thay cho bố cục nhiều
  block danh mục rời; các landing danh mục cũ vẫn hoạt động và được liên kết.
- Sidebar không lặp lại điều hướng danh mục đã có ở rail phía trên; chỉ giữ
  tiêu chí lọc theo ngữ cảnh là thương hiệu, công suất và mức giá.
- Chữ bộ lọc tăng lên 15px (gợi ý phụ 12px), thêm lối `Xóa bộ lọc`; tổng số
  kết quả được cập nhật sau mỗi lần lọc AJAX thay vì giữ số ban đầu.
- Vòng rà soát viewport cuối tăng chữ tab/sắp xếp/card, ép H1 hero màu trắng
  trên banner để đủ tương phản và chuyển dải cam kết mobile sang lưới 2×2 để
  giảm chiều cao mà vẫn giữ đủ bốn cam kết.
- Card trong lưới catalog dùng CTA chữ rõ nghĩa `Yêu cầu báo giá`/`Thêm giỏ
  hàng`; các vị trí card nhỏ khác vẫn giữ nút icon gọn.
- Nhóm checkbox `Loại đèn - dùng cho` đã được bỏ vì dùng đúng tập
  `product_cat` của rail cấp 2 và lựa chọn đơn còn chuyển về cùng URL canonical;
  sidebar hiện chỉ giữ Thương hiệu, Công suất và Mức giá. Backend `is_cat` vẫn
  được giữ để URL lọc cũ tiếp tục hoạt động.
- Nội dung mô tả và FAQ do `content_section` quản lý nằm dưới lưới; không dựng
  FAQ giả nếu Admin chưa nhập.
- CTA tư vấn cuối trang dùng record **Hiển thị** đầu tiên của slot Admin
  `catalog-campaign`. Nếu slot chưa từng có record mới dùng ảnh fallback đóng
  gói; khi Admin tắt toàn bộ record thì CTA được ẩn đúng công tắc.
- Hero render H1 nhìn thấy; layout không còn tạo thêm H1 SEO ẩn.

## File triển khai

- `sources/product.php`: chuẩn bị dữ liệu mô tả, banner và rail taxonomy.
- `libraries/router.php`: đưa `/san-pham/` vào template catalog thống nhất.
- `templates/product/product_tpl.php`: render hero, rail, service strip và
  heading kết quả dùng chung cho list/cat.
- `assets/css/product-category-v3.css`: lớp trình bày catalog riêng, responsive
  mobile full-bleed hero và filter drawer không ảnh hưởng PDP.
- `templates/layout/css.php`: chỉ nạp stylesheet mới cho `product/product`.
- `api/fillter_service.php`, `assets/js/functions.js`: trả metadata và cập nhật
  số lượng kết quả sau lọc.
- `templates/product/_product_card.php`, `assets/css/theme-v2.css`: nhãn CTA
  theo ngữ cảnh catalog.
- `libraries/type/config-type-photo.php`: mô tả đúng cách vận hành slot CTA.

## Kiểm tra

- PHP 7.4 lint: `sources/product.php`, `templates/product/product_tpl.php`,
  `templates/layout/css.php` đạt.
- Playwright responsive matrix: 8/8 kích thước từ 360px đến 2560px PASS.
- Test hồi quy tương phản hero, chữ sidebar và dải cam kết mobile: PASS.
- Playwright tập trung catalog/PDP/filter: 6 PASS, 2 SKIP đúng breakpoint.
- Playwright icon + card/PDP mobile: 4 PASS, 2 SKIP đúng breakpoint.
- `git diff --check`: PASS.

Bundle JS có cache 30 ngày; deploy phải xóa/tái tạo `assets/caches/*.js` theo
quy trình predeploy để logic cập nhật tổng kết quả được phục vụ ngay.

Chưa deploy production; rollback là bỏ stylesheet mới và hoàn nguyên ba file
template/source/layout sau khi đã chụp backup code hiện hành.
