# Báo cáo tiếp tục redesign trang chủ — 2026-09-12

## Kết quả

Tiếp tục từ commit `305675e` trên nhánh `NewLayoutWeb`, đối chiếu trực tiếp ảnh
mockup `ChatGPT Image 14_30_49 12 thg 9, 2026.png`, prototype HTML và runtime
local. Lượt này chỉ thay source local; không sửa production, không ghi DB và
không thay URL/slug public.

Trang chủ hiện đi theo nhịp chính của mockup:

1. Hero.
2. Bốn hành trình khách hàng.
3. Giới thiệu ngắn.
4. Hệ sinh thái thiết bị.
5. Giải pháp.
6. Quy trình.
7. Dự án.
8. Thương hiệu và tín hiệu hỗ trợ.
9. CTA đại lý.
10. Kiến thức năng lượng.
11. CTA tư vấn cuối trang.

## Thay đổi triển khai

- Đổi khối đầu trang từ ba card theo nhu cầu sang bốn hành trình đúng mockup:
  hộ gia đình, doanh nghiệp, dự án và đối tác.
- Card gia đình/doanh nghiệp cuộn đến section Giải pháp và chọn đúng tab bằng
  tên hiển thị, không phụ thuộc thứ tự record trong Admin.
- Đưa Giới thiệu lên trước Hệ sinh thái; đưa Quy trình lên trước Dự án.
- Đổi Quy trình từ bốn card navy có mô tả dài sang timeline sáng, ngắn và nối
  bước; vẫn giữ dữ liệu/mã legacy để rollback.
- Ẩn sơ đồ Lợi ích bằng điều kiện render, không xóa query, code hay dữ liệu.
- Bỏ phần “Hoạt động dự án” trùng nguồn `du-an` với “Dự án đã thực hiện”, đồng
  thời bỏ query `$dahoatdong` dư thừa.
- Đổi phần Kiến thức từ danh sách hai cột dự án/tin tức sang lưới bài viết.
- Không dùng ảnh giấy chứng nhận làm logo thương hiệu. Local chưa có bộ logo
  phẳng đầy đủ nên chỉ hiện đúng tên năm record thật; không thêm Sungrow từ ảnh
  AI và không tự gắn nhãn “ủy quyền”.
- Bổ sung ba lối vào có URL canonical: sản phẩm, giao hàng/kiểm hàng và bảo
  hành/hỗ trợ kỹ thuật.
- Tách banner đại lý trong dữ liệu `adv` thành CTA riêng sau dải thương hiệu;
  giữ modal và backend form cũ. CTA tư vấn cuối trang lấy tên/mô tả từ record
  `adv` có link `#ptsContactModal` nếu record tồn tại.
- Sửa thẻ đóng container bị thiếu trong markup Quy trình.

## File thay đổi

- `domains/phucthinhsolar.com/public_html/templates/index/index_tpl.php`
- `domains/phucthinhsolar.com/public_html/assets/css/theme-v2.css`
- `domains/phucthinhsolar.com/public_html/assets/js/apps.js`
- `domains/phucthinhsolar.com/public_html/sources/index.php`

## Kiểm thử

- PHP 7.4 lint trong container: đạt cho hai file PHP thay đổi.
- `node --check assets/js/apps.js`: đạt.
- `git diff --check`: đạt.
- Playwright headless tại 1440x1000, 1280x900, 412x915 và 390x844:
  - Homepage HTTP 200.
  - Không có horizontal overflow.
  - Hero layout 3 có slide active, ảnh desktop 1920px và ảnh mobile 900px.
  - Có đúng bốn card hành trình.
  - Card Doanh nghiệp/Hộ gia đình chọn đúng tab tương ứng.
  - Modal đại lý mở thành công.
  - Khối Lợi ích không render; phần Hoạt động dự án trùng không còn render.
  - Thứ tự section đúng danh sách ở mục Kết quả.
- HTTP smoke các route `/`, `/san-pham/`, `/giai-phap/`,
  `/du-an-da-thuc-hien/`, `/dai-ly`, `/tin-tuc/`, `/ve-chung-toi/`,
  `/lien-he/` và hai URL chính sách canonical: đều kết thúc HTTP 200.
- Lỗi network duy nhất trong probe là GTranslate CDN bị chủ động chặn trong
  môi trường test; không ghi nhận request local >= 400.

## Rủi ro và việc cần chủ project chốt

1. Khách mới vẫn mặc định thấy Hero layout 2; layout 3 chỉ hiện khi chọn bằng
   nút đổi layout hoặc đã lưu trong `localStorage`. Cần chốt layout mặc định
   trước khi deploy.
2. Hai Hero legacy vẫn chứa số liệu/nhãn chưa có hồ sơ xác minh. Chưa sửa vì
   yêu cầu trước đó là giữ nguyên hai layout cũ để so sánh; không nên deploy
   chúng ở trạng thái public trước khi duyệt nội dung.
3. Dữ liệu bốn slide Hero layout 3 còn là mảng PHP tĩnh, chưa quản trị từ Admin.
4. Cần bộ logo PNG/SVG thật của HUAWEI, SHARP, JINDIAN, ZUOLIGHT và BLUE CARBON
   nếu muốn thay dải tên bằng logo phẳng như mockup.
5. Chưa thêm form dài trước Footer. Site đã có form Hero layout 2 và modal tư
   vấn dùng backend/CSRF/reCAPTCHA hiện hữu; thêm form thứ hai cần thiết kế cơ
   chế tái sử dụng không trùng ID/captcha và cần chủ project duyệt.
6. Commerce đang ở chế độ liên hệ/báo giá trên giao diện local nên lượt thay
   đổi homepage này không tạo đơn thử hoặc ghi dữ liệu order.

## Deploy và rollback

- Chưa deploy demo/production và chưa thay DB.
- Trước deploy cần chủ project chốt Hero mặc định, copy/số liệu và bộ logo.
- Sau khi có commit, rollback source bằng `git revert <commit>`; các block Lợi
  ích và banner carousel cũ vẫn còn trong template, chỉ đang bị tắt render nên
  cũng có thể khôi phục bằng thay đổi điều kiện có kiểm soát.
