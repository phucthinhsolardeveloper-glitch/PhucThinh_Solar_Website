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
- Không dùng ảnh giấy chứng nhận làm logo thương hiệu. Khối này nay lấy đúng
  toàn bộ 10 record `doitac` đang bật trong Admin và hiện đủ 10 file logo thật:
  Oriana, Jindian, Blue Carbon, Huawei, Sungrow, GoodWe, Growatt, Deye, Sharp và
  LONGi. Không giới hạn số lượng trong template.
- Dải logo giữ một hàng, tự overflow ngang khi có nhiều thương hiệu. Scrollbar
  thị giác được ẩn; desktop có nút trái/phải và hỗ trợ kéo chuột, mobile vuốt
  trực tiếp. Nút tự ẩn nếu danh sách không còn overflow.
- Sửa selector CSS của Hệ sinh thái để rule Grid thắng rule Flex dùng chung.
  Runtime nay thực sự xếp đúng 4 card HOT lớn ở hàng đầu + 6 card nhỏ ở hàng
  thứ hai, thay vì 4–4–2 như trước.
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
- `tools/visual-tests/tests/layout-interactions.spec.js`

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
- Probe riêng dải thương hiệu tại 1280px và 412px:
  - Có đúng 10 item và 10 ảnh logo khác nhau; tất cả ảnh có kích thước tự nhiên
    lớn hơn 0, không có request local lỗi.
  - Desktop: track 992/2100px, nút điều hướng cuộn được; mobile: track
    307/1760px, vuốt/cuộn được.
  - `scrollbar-width: none` và WebKit scrollbar đều ẩn; body không overflow.
  - Lưới Hệ sinh thái được đo runtime đúng một hàng 4 major + một hàng 6 minor.
- Regression `layout-interactions.spec.js`: 24 passed, 6 skipped đúng theo
  project desktop/mobile; gồm Hero ba layout, AJAX đổi danh mục sản phẩm,
  header/footer, modal báo giá/đại lý và các luồng listing/detail liên quan.
- Cập nhật hai assertion đã lỗi thời: test Hero nay đi đủ chu kỳ 2→3→1 và test
  Hệ sinh thái kiểm tra CSS Grid 4+6 thay cho rail cuộn ngang legacy.
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
4. Một số record `doitac` chưa có tên/link trong Admin dù file logo có thật;
   giao diện ảnh vẫn đủ nhưng `alt` phải dùng mô tả chung. Nên bổ sung metadata
   trong Admin, không hard-code tên theo filename vào template.
5. Chưa thêm form dài trước Footer. Site đã có form Hero layout 2 và modal tư
   vấn dùng backend/CSRF/reCAPTCHA hiện hữu; thêm form thứ hai cần thiết kế cơ
   chế tái sử dụng không trùng ID/captcha và cần chủ project duyệt.
6. Commerce đang ở chế độ liên hệ/báo giá trên giao diện local nên lượt thay
   đổi homepage này không tạo đơn thử hoặc ghi dữ liệu order.

## Checklist còn lại để chốt homepage

### Bắt buộc trước khi xem là bản public cuối

1. Chốt một Hero mặc định. Hiện khách mới vẫn thấy layout 2 và còn nút đổi giữa
   ba layout; đề xuất chọn layout 3 làm bản public, bỏ nút thử nghiệm khỏi giao
   diện và chỉ giữ code cũ để rollback có kiểm soát.
2. Duyệt toàn bộ copy/số liệu của các Hero còn được phép public. Hai Hero legacy
   vẫn có claim chưa kèm hồ sơ xác minh.
3. Điền SEO homepage trong Admin. Runtime local hiện trả `<title>`, keywords và
   description rỗng; cần xác nhận đây là thiếu dữ liệu local hay thiếu thật ở
   môi trường sẽ deploy.
4. Chạy vòng duyệt nội dung cuối trên desktop/mobile sau khi chốt Hero, rồi mới
   đóng baseline ảnh và deploy demo.

### Cần chủ project chọn mức bám mockup

1. Hệ sinh thái hiện đúng 4+6 danh mục nhưng vẫn tải thêm bốn card sản phẩm bên
   dưới. Mockup chỉ có dải danh mục; cần chọn giữ preview sản phẩm hay rút gọn
   để homepage ngắn hơn.
2. Giải pháp đã có tab pill nhưng phần nội dung vẫn là split card navy của giao
   diện cũ; mockup dùng ảnh + copy trên nền sáng, nhẹ hơn.
3. Dự án vẫn là carousel một card trung tâm rất lớn; mockup là hai card ngang
   gọn cùng lúc.
4. Kiến thức đang hiện sáu bài theo lưới 3x2; mockup là ba bài compact một hàng.
5. Khối cuối trang hiện là ba CTA mở kênh liên hệ/modal, chưa phải form tư vấn
   nhúng. Nếu chọn form nhúng phải tái sử dụng validation, CSRF, rate limit và
   reCAPTCHA hiện có, đồng thời tránh hai form dài khi Hero 2 còn public.
6. Cụm nút Facebook/Zalo/điện thoại cố định đang che mép phải một số section ở
   viewport hẹp. Cần chọn thu gọn thành một nút mở rộng hoặc chừa safe-area.

### Nâng cấp quản trị sau khi duyệt giao diện

1. Chuyển bốn slide Hero layout 3 từ mảng PHP tĩnh sang dữ liệu Admin có schema
   rõ ràng và fallback an toàn.
2. Bổ sung tên/link đầy đủ cho 10 record đối tác để cải thiện accessibility và
   cho phép dẫn tới trang thương hiệu khi có URL đã được duyệt.

## Deploy và rollback

- Chưa deploy demo/production và chưa thay DB.
- Trước deploy cần chủ project chốt Hero mặc định, copy/số liệu và mức bám
  mockup cho các section trong checklist trên.
- Sau khi có commit, rollback source bằng `git revert <commit>`; các block Lợi
  ích và banner carousel cũ vẫn còn trong template, chỉ đang bị tắt render nên
  cũng có thể khôi phục bằng thay đổi điều kiện có kiểm soát.
