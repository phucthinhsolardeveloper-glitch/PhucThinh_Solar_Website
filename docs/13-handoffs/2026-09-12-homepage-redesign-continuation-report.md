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
- Theo vòng duyệt cuối của chủ project, Hero layout 3 nay là mặc định public.
  Nút đổi layout được ẩn ở layout 3; layout 1-2 và nút của chúng vẫn giữ trong
  source/localStorage để nối vào tính năng quản lý layout sau này. Key lưu lựa
  chọn được đổi phiên bản để preference thử nghiệm 1/2 cũ không ghi đè mặc định
  layout 3 trên trình duyệt đã từng dùng nút.
- Giữ nguyên số liệu hai Hero legacy theo xác nhận của chủ project; không còn
  coi đây là nội dung chưa xác minh.
- Giữ bốn sản phẩm preview và sáu bài Kiến thức như hiện tại.
- Đổi Giải pháp sang bố cục sáng: ảnh bên trái/nội dung bên phải trên desktop,
  ảnh trên/nội dung dưới trên mobile; giữ tab, dữ liệu và link chi tiết cũ.
- Dự án giữ Slick carousel và tab dữ liệu cũ nhưng đổi thành hai card gọn trên
  desktop, một card trên mobile.
- CTA tư vấn cuối trang giữ nguyên gọi điện, Zalo và modal cũ; đổi sang bố cục
  ảnh–nội dung mở trực tiếp trên nền sáng, không dùng khung card/viền/shadow,
  với ảnh văn phòng thật từ record `adv` hiện có.
- Thu gọn ba nút Facebook/Zalo/điện thoại thành một nút liên hệ 48px mở rộng
  theo yêu cầu, hỗ trợ focus, phím Escape và tự tránh nút cuộn lên đầu trang.

## File thay đổi

- `domains/phucthinhsolar.com/public_html/templates/index/index_tpl.php`
- `domains/phucthinhsolar.com/public_html/assets/css/theme-v2.css`
- `domains/phucthinhsolar.com/public_html/assets/js/apps.js`
- `domains/phucthinhsolar.com/public_html/assets/wp-content/themes/local/js/home080f.js`
- `domains/phucthinhsolar.com/public_html/libraries/home_hero_carousel.php`
- `domains/phucthinhsolar.com/public_html/sources/index.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/footer.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/slide.php`
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
- Regression `layout-interactions.spec.js`: 28 passed, 6 skipped đúng theo
  project desktop/mobile; gồm Hero ba layout, dock liên hệ, AJAX đổi danh mục sản phẩm,
  header/footer, modal báo giá/đại lý và các luồng listing/detail liên quan.
- Cập nhật assertion Hero: khách mới thấy layout 3 và không có nút đổi; dữ liệu
  đã lưu vẫn mở được layout 1-2 cùng nút cũ. Thêm kiểm tra dock liên hệ mở/đóng,
  link điện thoại/Zalo, bố cục Giải pháp responsive, hai/một card Dự án, sáu bài
  Kiến thức và ảnh CTA tải thành công.
- Audit ảnh toàn trang và từng section ở 1280px/412px: ảnh Giải pháp đúng thứ
  tự, Dự án gọn 2/1 card, CTA dùng ảnh văn phòng thật, dải logo không lộ thanh
  cuộn và dock chỉ chiếm một nút 48px khi đóng.
- Sau khi `predeploy-local.sh` rebuild container, smoke tập trung homepage đạt
  5 passed, 1 skipped theo thiết bị. File upload CTA `4-8075.png` vẫn tồn tại
  trong volume local và tải có `naturalWidth > 0`.
- Predeploy tổng thể chạy qua lint PHP, security hardening, Admin readiness,
  bulk import, RBAC, upload/file manager và các nhóm trước đó, nhưng dừng ở test
  nhận xét sản phẩm: lượt chạy riêng đạt 16 pass, 2 fail vì API vẫn nhận nhận
  xét khi mã đơn không tồn tại. Không file nào của luồng comment/order nằm trong
  diff homepage này; lỗi vẫn phải xử lý trước khi coi toàn bộ predeploy là xanh.
- HTTP smoke các route `/`, `/san-pham/`, `/giai-phap/`,
  `/du-an-da-thuc-hien/`, `/dai-ly`, `/tin-tuc/`, `/ve-chung-toi/`,
  `/lien-he/` và hai URL chính sách canonical: đều kết thúc HTTP 200.
- Lỗi network duy nhất trong probe là GTranslate CDN bị chủ động chặn trong
  môi trường test; không ghi nhận request local >= 400.

## Rủi ro còn lại

1. Dữ liệu bốn slide Hero layout 3 còn là mảng PHP tĩnh, chưa quản trị từ Admin.
   Chủ project đã chọn để tính năng quản lý layout/slide sang giai đoạn sau;
   lượt này không thêm schema hay ghi DB.
2. Một số record `doitac` chưa có tên/link trong Admin dù file logo có thật;
   giao diện ảnh vẫn đủ nhưng `alt` phải dùng mô tả chung. Nên bổ sung metadata
   trong Admin, không hard-code tên theo filename vào template.
3. SEO homepage trong runtime local vẫn đang rỗng; cần điền/kiểm tra dữ liệu
   Admin ở môi trường sẽ deploy, không hard-code trong template.
4. Ảnh văn phòng CTA là upload do Admin quản lý (`4-8075.png`), không phải asset
   Git; cần xác nhận file upload này tồn tại ở môi trường nhận source.
5. Commerce đang ở chế độ liên hệ/báo giá trên giao diện local nên lượt thay
   đổi homepage này không tạo đơn thử hoặc ghi dữ liệu order.
6. Full predeploy chưa xanh do hai assertion xác minh mua hàng của API nhận xét
   sản phẩm nêu ở phần Kiểm thử; đây là blocker deploy chung dù không phải
   regression của các file homepage vừa sửa.

## Checklist còn lại để chốt homepage

1. Điền/kiểm tra SEO homepage trong Admin ở môi trường sẽ deploy.
2. Bổ sung tên/link còn thiếu cho record đối tác và xác nhận ảnh upload CTA.
3. Thay ảnh slide Hero “Đối tác” bằng ảnh showroom/văn phòng phù hợp hơn khi có
   asset được duyệt; hiện vẫn dùng ảnh công trình thật làm fallback.
4. Chốt baseline ảnh sau khi chủ project duyệt trực quan bản local/demo.

### Nâng cấp quản trị sau khi duyệt giao diện

1. Chuyển bốn slide Hero layout 3 từ mảng PHP tĩnh sang dữ liệu Admin có schema
   rõ ràng và fallback an toàn.
2. Bổ sung tên/link đầy đủ cho 10 record đối tác để cải thiện accessibility và
   cho phép dẫn tới trang thương hiệu khi có URL đã được duyệt.

## Deploy và rollback

- Chưa deploy demo/production và chưa thay DB.
- Trước deploy cần kiểm tra SEO, file upload CTA và chạy predeploy/smoke theo
  môi trường đích; Hero mặc định và số liệu đã được chủ project chốt.
- Sau khi có commit, rollback source bằng `git revert <commit>`; các block Lợi
  ích và banner carousel cũ vẫn còn trong template, chỉ đang bị tắt render nên
  cũng có thể khôi phục bằng thay đổi điều kiện có kiểm soát.
