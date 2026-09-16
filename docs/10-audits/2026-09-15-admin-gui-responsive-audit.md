# Audit Admin, GUI và responsive — 2026-09-15 đến 2026-09-16

## Phạm vi và kết quả

- Audit thực hiện trên Docker/local, không ghi hoặc deploy production.
- Cổng pre-deploy đạt: PHP lint, security, RBAC, CRUD Admin, form public,
  commerce, route và migration preflight.
- Quét đọc 113/113 màn hình Admin: không lỗi HTTP, không cảnh báo PHP và không
  phát hiện màn hình rỗng bất thường.
- Danh mục quyền có 256 quyền; ba nhóm quyền hợp lệ, nhóm cao nhất phủ đủ quyền.
- Resolver public đối chiếu đủ 503 slug.
- Form liên hệ/đại lý đạt 19/19; tư vấn sản phẩm/review 19/19; tài khoản 20/20;
  permission gate 12/12; file manager 18/18; password migration 9/9.

## Thay đổi giao diện public

### Responsive nhiều cỡ màn hình

- Container không còn bị khóa 1560 px trên mọi desktop. Mốc hiện tại:
  1560 px ở desktop thường, 1680 px từ viewport 1800 px và 1920 px từ viewport
  2300 px; gutter cũng tăng có kiểm soát.
- Header, campaign, hệ sinh thái, card sản phẩm và trang Đại lý được tăng tỷ lệ
  ở Full HD/2K, trong khi tablet/mobile vẫn chuyển layout theo breakpoint.
- Ma trận tự động kiểm tra 360, 390, 768, 1024, 1366, 1440, 1920 và 2560 px trên
  Trang chủ, Sản phẩm, Giải pháp, Tin tức và Đại lý. Mỗi cỡ đều kiểm tra HTTP,
  JavaScript, tràn ngang, header desktop/mobile, logo, lưới sản phẩm và form đại lý.
- Wrapper logo Header đã bỏ nền trắng, border, shadow và nét trang trí ở cả
  desktop/mobile; file logo nền trong suốt hiển thị trực tiếp trên nền navy.

### Trang Đại lý

- Loại khoảng trắng bất thường giữa breadcrumb và banner; nguyên nhân là selector
  padding section tổng quát có độ ưu tiên cao hơn hero.
- Thu gọn banner, cân lại H1 và ảnh; tách phần giới thiệu/form thành hai cột trên
  desktop, một cột trên mobile.
- H1 vẫn tồn tại khi Admin tắt ảnh hero. Form có label, autocomplete, giới hạn độ
  dài, trường bắt buộc, CSRF và anchor menu trỏ đúng vào form thật.
- Đổi nhãn Admin khó hiểu, thêm ghi chú quản trị và đường tắt xem trang public.

### Liên hệ và Giới thiệu

- Trang Liên hệ được gom lại thành dải tư vấn hai cột có ảnh 8:5, thông tin liên
  hệ tương phản trên nền navy và form nằm liền ngay sau đó; bỏ khoảng trắng lớn
  dưới breadcrumb, giữ đúng một H1 và label đầy đủ cho form.
- Nhãn public của trang công ty đổi thành “Giới thiệu”; URL mới là
  `/gioi-thieu/`. URL cũ `/ve-chung-toi/` trả redirect 301 để không làm hỏng link
  đã chia sẻ và tín hiệu SEO.
- Trang Giới thiệu được tổ chức lại theo câu chuyện, lịch sử, con người, lý do lựa
  chọn, tầm nhìn/sứ mệnh, chứng nhận và CTA. Ảnh đội ngũ chỉ hiện khi file Admin
  thật sự tồn tại; không dùng ảnh AI làm bằng chứng con người hoặc năng lực.
- Ảnh mở đầu đã thay bằng ảnh showroom Phúc Thịnh Solar do chủ project cung cấp.
  Template dùng `<picture>` với WebP 960/1920 px và JPEG fallback; bản gốc ngoài
  project được giữ nguyên, không chỉnh sửa hoặc dùng như ảnh AI/stock.
- Sửa cascade CSS mobile khiến khối “Vì sao khách hàng lựa chọn chúng tôi?” vẫn
  giữ lưới desktop: tiêu đề và bốn card hiện xếp một cột, chữ không còn bị ép ra
  ngoài viewport. Regression đo trực tiếp vị trí và chiều rộng nội dung.

### Bổ sung 2026-09-16 (tiếp nối sau khi Codex hết hạn mức)

- Xác minh lại toàn bộ trạng thái Codex để lại: PHP lint toàn bộ file đã sửa,
  `node --check` apps.js và mọi spec, full Playwright suite đạt 176 pass/18 skip,
  redirect `/ve-chung-toi/` → `/gioi-thieu/` trả đúng 301, bảy trang public chính
  trả 200. Migration 043 đã ở trạng thái đã áp trên local (DB trỏ ảnh showroom
  mới, đủ ba file), không chạy lại.
- Bỏ nền `#eef3f8` hai bên khối nội dung bài Giải pháp (`.pts-sd-body`) theo yêu
  cầu chủ project; toàn site giờ nền trắng đồng nhất, đã xác minh bundle CSS
  không còn mã màu này.
- Landing `/giai-phap/` trước đây rơi vào nhánh `.pts-sd-toc` nền trong suốt nên
  mục lục gần như vô hình, chủ project tưởng trang landing không có mục lục
  (thực tế DOM luôn sinh đủ 5 mục). Đã cho landing dùng chung kiểu mục lục navy
  sticky với bài chi tiết, gồm cả mobile.
- Kèm theo đó, assertion cũ "mục lục landing phải ẩn trên mobile" không còn phản
  ánh hành vi mong muốn. Thay bằng kiểm tra đúng ràng buộc mới thay vì nới lỏng:
  mục lục hiện, có mục, không rộng hơn viewport, danh sách bị giới hạn ≤240px và
  `overflow-y: auto` để không đẩy nội dung xuống sâu.

### Giải pháp và Tin tức

- Landing Giải pháp đi thẳng tới năm bài, dùng nhãn ngắn và breadcrumb ba nấc.
- Bố cục bài dài, mục lục và năm card dùng chung responsive. Ba card từng thiếu
  ảnh đã có minh họa ứng dụng đúng ngữ cảnh (hộ gia đình, BESS, bơm nước); không
  còn placeholder. Thumbnail card dùng 600×375: đủ nét và không tải dư.
- Nội dung đã chuẩn hóa về giọng chính thức của Phúc Thịnh Solar, bỏ câu hỏi ngược,
  chủ thể không rõ và thuật ngữ khó hiểu không cần thiết.
- Bài Tin tức không còn hiển thị lại cover khi CKEditor đã chứa cùng hình; sáu bài
  trùng hình đã được kiểm tra, bài có hai hình khác nhau vẫn giữ đủ.
- Lưới bài Kiến thức ở màn hình 2048 px được chặn co giãn sai bằng cột
  `minmax(0, ...)`; ảnh, phần chữ và mũi tên của card phụ không còn chồng lấn.

### Danh mục và card sản phẩm

- Card sản phẩm dùng chung một chiều cao, ảnh vuông `object-fit: contain`, phần
  giá và nút hành động nằm cùng hàng đáy thay vì nút “+” rơi tít xuống dưới.
- Chênh lệch kích thước chủ thể bên trong ảnh gốc không bị xử lý bằng crop vì sẽ
  làm mất sản phẩm; cần chuẩn hóa canvas ảnh nguồn qua Admin ở một đợt dữ liệu
  riêng nếu muốn chủ thể có tỷ lệ tuyệt đối giống nhau.
- Danh mục Đèn sân vườn có ảnh nền 16:5, lớp phủ giữ chữ rõ và fallback an toàn.
  Ảnh marketing này không được dùng như bằng chứng dự án hay khách hàng.
- Cả 10 danh mục cấp 1 đã được đối chiếu ngữ cảnh và dùng đúng nhóm ảnh: đèn đường,
  inverter, tấm pin/khung, trụ đèn, đèn sân vườn, quạt mặt trời, bơm, phụ kiện điện
  và pin lưu trữ. Regression khóa mapping để tránh tái dùng nhầm ảnh đèn cho quạt
  hoặc phụ kiện điện.
- Dải nhảy nhanh trên landing Sản phẩm tăng tương phản vàng/navy, bỏ bo pill quá
  lớn và chuyển sang cuộn ngang một hàng trên mobile.
- Dải chứng nhận trang chủ dùng ảnh nền showroom có lớp phủ để chữ luôn rõ; ảnh nền
  quản trị được và có công tắc hiển thị. Ba bản quét được tăng kích thước, giữ
  `contain`, không crop hoặc phóng méo chứng nhận.

### Pipeline WebP

- Docker PHP 7.4 đã có GD + libwebp và công cụ `cwebp`; runtime tự phát hiện codec,
  không phụ thuộc `libraries/config.php` vốn được giữ riêng trên hosting. Máy chưa
  có codec tự dùng JPG/PNG gốc, không phát URL WebP hỏng.
- Upload JPG/PNG mới tự tạo biến thể WebP khi file nhỏ hơn; ảnh gốc luôn được giữ.
  Thumbnail động trả đúng MIME `image/webp`, cache một năm và chỉ nhận ba định dạng
  raster hỗ trợ, không nối `.webp` nhầm vào SVG.
- `scripts/optimize-webp.php` hỗ trợ file/thư mục, dry-run, quality, ngưỡng dung
  lượng và chạy lặp an toàn. Lượt tối ưu 18 ảnh marketing đang dùng giảm 4.972.587
  byte xuống 2.888.420 byte (giảm 41,9%) mà không xóa ảnh nguồn. Bản minh họa quạt
  trần sai ngữ cảnh đã được bỏ; danh mục dùng bản quạt đứng + tấm pin đúng sản phẩm.
- Ảnh showroom 3060×1625, 6.251.965 byte được xuất thành JPEG fallback 1920×1020
  (421.306 byte), WebP desktop (268.092 byte) và WebP mobile 960×510 (105.252 byte).
  Migration 043 đóng gói đủ ba file vì `upload/` không nằm trong release source.

## Điều chỉnh regression

- Kiểm thử sản phẩm không còn đóng đinh site ở chế độ “chỉ báo giá”; tự xác minh
  đúng một trong hai cấu hình Admin: giá/giỏ hàng hoặc liên hệ báo giá.
- Chờ lazy image có giới hạn 3 giây để một request bên thứ ba không treo toàn suite.
- Test Tin tức chặn riêng widget dịch GTranslate nhằm giữ DOM tiếng Việt ổn định;
  vẫn ghi nhận mọi `pageerror` từ mã nội bộ. Chạy lặp mobile ba lần đạt 6/6.
- Baseline Trang chủ/Sản phẩm được cập nhật có chọn lọc sau khi so ảnh cũ: baseline
  cũ còn menu `Tin tức / Đại lý / Liên hệ` top-level và campaign cũ, trái quyết định
  menu năm nhóm hiện hành.
- Suite media riêng đạt 28/28 trên desktop/mobile; có kiểm tra ảnh showroom thật,
  lựa chọn `srcset`, MIME WebP, mapping 10 banner danh mục, card sản phẩm và ảnh
  Giải pháp. Full suite đạt 175 pass, 18 skip theo breakpoint; một phép tìm kiếm
  Tin tức từng cần retry đã được khóa chờ URL và chạy lặp lại đạt 18/18.
- Cổng pre-deploy đạt toàn bộ lint, security, CRUD, form, commerce, route, 113 màn
  Admin, sitemap và log/cleanup. Diễn tập restore đạt 54 bảng và 949 file upload.
- Guard pre-deploy CTA card được sửa để kiểm tra HTML landing `/san-pham/` thay vì
  trang chi tiết mới nhất vốn không bắt buộc có related products.

## Quyền Admin bổ sung

- Module “Banner danh mục sản phẩm” dùng bốn quyền photo CRUD mới. Migration 042
  được mở rộng theo hướng idempotent; preview chỉ chọn nhóm vốn đủ quyền Banner
  quảng cáo, sau đó local đã thêm đúng 4 quyền cho nhóm cấp cao.
- Sau migration: nhóm cấp cao 256/256 quyền; nhóm cơ bản 26 và nhóm content 84
  quyền hợp lệ; không nhóm nào còn quyền lỗi thời.

## Dữ liệu/rủi ro cần chủ project xử lý

- Gallery Đại lý hiện có một ảnh mang nội dung `QUẠT BLUE FAN` lẫn trong nhóm ảnh
  đèn năng lượng. Không tự xóa vì đây là dữ liệu Admin; cần chủ project xác nhận.
- Hero Đại lý hiện dùng được về mặt kỹ thuật, nhưng ảnh chụp thật của kho/showroom
  Phúc Thịnh Solar sẽ đáng tin cậy hơn ảnh marketing dựng.
- Local đang bật `website.debug-developer`, vì vậy có bypass RBAC phục vụ phát triển.
  Production bắt buộc đặt `debug-developer=false` và kiểm thử lại bằng tài khoản
  nghiệp vụ.
- Chưa xác minh SMTP, proxy/HTTPS và tỷ lệ hiển thị vật lý trên iMac thật. Ma trận
  trình duyệt đã phủ tới 2560 px, nhưng vẫn cần chủ project nhìn trực tiếp ở mức
  zoom hệ điều hành/trình duyệt đang sử dụng.
- Chưa chạy chuyển đổi hàng loạt toàn bộ kho upload cũ. Pipeline mới đã sẵn sàng,
  nhưng batch lớn vẫn cần backup upload, dry-run theo dung lượng và kiểm tra quota;
  không cần đổi tên ảnh/DB vì biến thể dùng hậu tố `.webp` cạnh file gốc.

## Deploy và rollback

- Chưa deploy. Trước production phải backup DB/upload, diễn tập restore, chạy
  preflight production và smoke test lại các hành trình chính.
- Deploy ảnh showroom phải chạy `migrations/043_install_about_showroom_image.php`
  ở chế độ xem trước rồi mới `--apply`. Rollback dùng `--rollback`; chỉ đổi DB về
  ảnh cũ và giữ file mới để tránh thao tác xóa khó phục hồi.
- Rollback code bằng revert commit của đợt này. Nội dung Giải pháp local có backup
  `backup/local/20260915-144101`; chỉ restore khi chủ project yêu cầu vì thao tác đó
  ghi đè DB local. Production phải dùng backup tạo ngay trước lần deploy tương ứng.
