# Bàn giao: kế hoạch điều chỉnh Hero và dẫn hướng trang chủ — 2026-09-11

## Mục tiêu

Tài liệu này chuyển phản hồi UX/marketing về phần đầu trang chủ thành kế hoạch
triển khai có thể giao cho Claude. Phạm vi của lần rà soát này chỉ là đọc source,
giao diện local và dữ liệu local; **chưa sửa code, chưa thay DB và chưa đụng
production**.

Nguyên tắc triển khai:

- Trang chủ giới thiệu toàn bộ Phúc Thịnh Solar, đồng thời làm rõ đèn năng lượng
  mặt trời là nhóm sản phẩm chủ lực.
- Ưu tiên giúp khách nhận biết đúng nhu cầu và tạo liên hệ, không chỉ làm đẹp.
- Không công bố số liệu, quan hệ phân phối hoặc chứng nhận khi chưa có bằng chứng.
- Không xóa ảnh/asset cũ; chỉ tắt hiển thị và giữ phương án rollback.
- Không thay URL/slug public hiện có.

## Phản hồi từ phía kinh doanh/marketing

### Những điểm đang làm tốt

- Tông xanh đậm, vàng và trắng phù hợp nhận diện Phúc Thịnh Solar.
- Tiêu đề lớn, chữ trắng/vàng có độ nổi bật.
- Header đã có hotline, sản phẩm, giải pháp và dự án.
- Chứng nhận và số liệu tạo được tín hiệu tin cậy nếu dữ liệu chính xác.

### Những điểm được đề nghị điều chỉnh

1. Ảnh nền ghép nhiều ngành hàng làm mất trọng tâm; chiếc quạt hút mắt quá mạnh.
2. Lớp phủ xanh làm toàn bộ ảnh tối và thiếu sức sống.
3. Nhãn “Đèn dự án · Đèn công trình” chưa thống nhất với tiêu đề/hình nền giới
   thiệu nhiều ngành hàng.
4. Hai CTA đều dẫn đi xem thêm, chưa có CTA liên hệ trực tiếp.
5. Khối chứng nhận chiếm diện tích nhưng chữ trong giấy quá nhỏ để đọc.
6. Các số `10.000+`, `50+`, `5+` phải được kiểm chứng trước khi công bố.
7. Giờ làm việc cần thể hiện `T2–T7: 8:00–12:00, 13:00–17:00` sau khi chủ
   doanh nghiệp xác nhận.
8. “Trang chủ” và “Sản phẩm” cùng nổi vàng gây hiểu nhầm về trạng thái menu.
9. Ngay dưới Hero cần dẫn khách theo ba hành trình: đại lý, dự án chiếu sáng,
   và giải pháp điện mặt trời/lưu trữ/bơm nước.

## Hiện trạng đã xác minh trên local

### Hero

- `home_hero_mode` hiện là `text`.
- Hero lấy ảnh nền từ `table_photo`, `type=home-hero-bg`, trạng thái `hienthi`.
- Có 3 ảnh đang bật và tự crossfade mỗi 6 giây:
  - `chatgpt-image-082303-9-thg-9-2026-1-88842.png`: ảnh ghép nhiều nhóm sản
    phẩm, có nhãn chữ và quạt kích thước lớn; đây là ảnh khớp trực tiếp với phản
    hồi của marketing.
  - `chatgpt-image-082305-9-thg-9-2026-3-16691.png`: phối cảnh mặt tiền Phúc
    Thịnh Solar, có logo/thông tin được dựng trong ảnh.
  - `chatgpt-image-082304-9-thg-9-2026-2-24610.png`: cánh đồng pin mặt trời,
    hình ảnh tổng quát, không thể hiện rõ dự án thực tế của công ty.
- Lớp phủ hiện là gradient navy với alpha xấp xỉ `0.88` bên trái, `0.72` ở giữa
  và `0.55` bên phải. Nhận xét ảnh đang tối là có cơ sở.
- Hero dùng hai cột; cột chứng nhận bên phải rộng tối đa khoảng 392px.
- Eyebrow, H1, mô tả, CTA và số liệu đang viết trực tiếp trong template.

### Chứng nhận

- Local đang có 5 record `chungnhan` bật hiển thị: HUAWEI, ZUOLIGHT, JINDIAN,
  BLUE CARBON và SHARP.
- Hero lấy tối đa 3 ảnh để xếp chồng, nhưng liệt kê tên của cả 5 record dưới
  nhãn chung “Nhà phân phối uỷ quyền”. Cách trình bày này có thể khiến người xem
  hiểu rằng cả 5 thương hiệu đều đã có hồ sơ ủy quyền hợp lệ.
- Trang “Về chúng tôi” đã có section hiển thị đầy đủ danh sách chứng nhận, nhưng
  ảnh chưa phải liên kết/lightbox để xem bản lớn.

### Số liệu, giờ làm việc và menu

- `10.000+ khách hàng`, `50+ nhân sự`, `5+ năm kinh nghiệm` đang hard-code ở cả
  hai biến thể Hero `slider` và `text`; chưa thấy nguồn chứng minh trong cấu hình.
- `thoigianlamviec` trong DB local đang là `8:00 - 17:00`.
- Giá trị này đang được dùng ở top bar và trang liên hệ; footer chưa thể hiện giờ
  làm việc.
- Template chỉ gắn class `active` cho “Trang chủ” ở route trang chủ. “Sản phẩm”
  luôn có nền vàng vì CSS biến nó thành nút catalog; đây không phải lỗi xác định
  route nhưng tạo ngữ nghĩa thị giác giống trạng thái active.

### Dẫn hướng ngay dưới Hero

Khối “BẠN ĐANG CẦN GÌ?” đã tồn tại và đang có 4 card:

1. Đèn năng lượng mặt trời.
2. Điện mặt trời.
3. Thiết bị.
4. Tôi chưa biết chọn.

Không tạo thêm section trùng chức năng. Nên sửa chính khối này từ cách phân loại
theo sản phẩm sang cách phân loại theo hành trình khách hàng.

### Ảnh dự án hiện có

Local có 4 dự án public, ảnh đại diện rộng khoảng 956–1280px. Hai ảnh dự án đèn
còn có watermark lớn. Các ảnh này có thể dùng cho mockup nội bộ nhưng chưa đạt
chất lượng lý tưởng cho Hero `1920x900`; không tự chọn làm ảnh chính thức khi
chưa được chủ doanh nghiệp duyệt.

Ngoài ra, ba ảnh Hero hiện có tồn tại trong container local đang chạy nhưng
không có trong bản `upload/photo` của workspace tại thời điểm rà soát. Khi triển
khai phải đồng bộ asset đúng quy trình release, không chỉ sửa trực tiếp container.

## Quyết định nội dung đề xuất

Định vị khuyến nghị: **trang chủ toàn công ty; đèn năng lượng mặt trời là nhóm
sản phẩm chủ lực**.

Bản copy để chủ doanh nghiệp duyệt, chưa phải nội dung cuối:

- Eyebrow: `Đèn năng lượng mặt trời là nhóm sản phẩm chủ lực`
- H1: `THIẾT BỊ VÀ GIẢI PHÁP NĂNG LƯỢNG MẶT TRỜI`
- Mô tả: `Cung cấp đèn năng lượng mặt trời chính hãng, thiết bị điện mặt trời
  và giải pháp thi công cho gia đình, đại lý và công trình.`
- CTA chính: `Xem sản phẩm`
- CTA thứ hai: `Nhận tư vấn`
- Link phụ: `Bạn muốn kinh doanh? Đăng ký đại lý`

CTA “Nhận tư vấn” dùng modal `#ptsContactModal` hiện có. Link “Đăng ký đại lý”
ưu tiên dùng modal `#ptsDealerModal` hiện có, trừ khi chủ project muốn đưa khách
đến landing page đại lý.

## Điều kiện phải có trước khi triển khai

Chưa làm production cho đến khi chủ project xác nhận/cung cấp đủ:

1. **Ảnh Hero thật**
   - Ảnh công trình thực tế của Phúc Thịnh Solar.
   - Ảnh ngang, khuyến nghị tối thiểu 1920x900; tốt hơn nếu có bản gốc lớn hơn.
   - Không ghép nhiều ảnh, không có text dựng sẵn trên ảnh.
   - Có quyền sử dụng.
   - Chủ thể chính ưu tiên ở nửa phải để chừa vùng chữ bên trái.

2. **Số liệu doanh nghiệp**
   - Nguồn cho 10.000+ khách hàng.
   - Nguồn cho 50+ nhân sự.
   - Mốc thành lập để tính 5+ năm.
   - Nếu thiếu bằng chứng: ẩn số liệu, không thay bằng số ước lượng.

3. **Chứng nhận/ủy quyền**
   - Xác minh từng thương hiệu, loại quan hệ, thời hạn và phạm vi chứng nhận.
   - Không gắn nhãn “Nhà phân phối uỷ quyền” cho record chỉ là đối tác/nhãn hàng
     hoặc hồ sơ đã hết hiệu lực.

4. **Giờ làm việc**
   - Xác nhận chính thức `T2–T7: 8:00–12:00, 13:00–17:00`.
   - Xác nhận có/không làm Chủ nhật.

5. **Copy Hero và đích CTA**
   - Duyệt nội dung cuối.
   - Chốt “Đăng ký đại lý” mở modal hay sang trang đại lý.

## Kế hoạch triển khai

### P0 — Chốt dữ liệu và tài sản

1. Nhận ảnh công trình thật và kiểm tra kích thước, bản quyền, crop desktop/mobile.
2. Xác minh số liệu và hồ sơ chứng nhận.
3. Chốt copy Hero, giờ làm việc và đích của ba hành trình khách hàng.
4. Chụp backup các row `table_setting` và `table_photo` liên quan trước mọi thay
   đổi dữ liệu; không ghi vào tài liệu các thông tin riêng tư/credential.

### P1 — Hero và CTA

1. Chỉ bật một ảnh công trình thật cho Hero; giữ ba asset cũ để rollback, không xóa.
2. Sửa overlay theo vùng:
   - Tối đủ mạnh ở bên trái sau chữ.
   - Giảm đáng kể lớp phủ ở bên phải để công trình sáng và có chiều sâu.
   - Kiểm tra tương phản chữ ở desktop và mobile, không dùng một alpha cố định
     cho toàn ảnh.
3. Thay copy theo bản đã được duyệt.
4. Giữ `Xem sản phẩm`.
5. Đổi `Xem giải pháp` thành `Nhận tư vấn`, mở `#ptsContactModal`.
6. Thêm link phụ `Đăng ký đại lý`, mở `#ptsDealerModal` hoặc route đã chốt.
7. Không làm thay đổi form action, field name, CSRF hoặc backend gửi lead.

### P1 — Dẫn hướng theo nhu cầu

Sửa khối “BẠN ĐANG CẦN GÌ?” hiện có thành 3 card:

1. `Dành cho đại lý / nhà phân phối`
   - Mô tả định hướng khách mua đèn/thiết bị để kinh doanh.
   - Đích: modal đại lý hoặc landing page đại lý đã chốt.
2. `Giải pháp dự án chiếu sáng`
   - Dành cho khách tìm thiết bị và đơn vị triển khai công trình.
   - Đích phải là trang/nhóm giải pháp chiếu sáng phù hợp, không mặc định dùng
     URL chung nếu đã có landing page cụ thể.
3. `Tư vấn giải pháp theo nhu cầu`
   - Điện mặt trời, lưu trữ hoặc bơm nước.
   - Đích: `#ptsContactModal` hoặc landing page giải pháp đã chốt.

Không tạo thêm section mới và không để lại 4 card cũ song song.

### P1 — Menu và giờ làm việc

1. Menu:
   - Chỉ mục tương ứng route hiện tại có trạng thái active.
   - Khuyến nghị bỏ nền vàng cố định của “Sản phẩm”; có thể giữ icon catalog.
   - Active dùng màu vàng/gạch chân rõ ràng, hover không giống active sau khi
     chuột rời khỏi mục.
2. Giờ làm việc:
   - Cập nhật option sau khi được duyệt.
   - Header desktop dùng bản đủ ngắn để không làm vỡ hàng.
   - Footer và trang liên hệ dùng bản đầy đủ.
   - Mobile hiện ẩn giờ ở top bar nên footer phải chứa thông tin này.

### P2 — Khối tin cậy và chứng nhận

1. Thu gọn cột phải Hero thành cụm logo/đối tác công nghệ hoặc tín hiệu tin cậy
   ngắn, dành nhiều diện tích hơn cho ảnh công trình.
2. Chỉ dùng từ “ủy quyền” với thương hiệu có hồ sơ phù hợp.
3. Đưa giấy chứng nhận đầy đủ về section hiện có tại trang “Về chúng tôi”.
4. Cho phép bấm ảnh chứng nhận để mở bản lớn bằng lightbox hiện có của dự án;
   hỗ trợ đóng bằng bàn phím và có alt/caption hợp lý.
5. Nếu chứng nhận có hạn, cân nhắc hiển thị loại chứng nhận/thời hạn hoặc tắt
   record khi hết hiệu lực.

### P2 — Quản trị nội dung Hero và số liệu

Khuyến nghị đưa các nội dung thường thay đổi vào nhóm setting Admin thay vì tiếp
tục hard-code:

- Eyebrow, H1, mô tả.
- Nhãn và đích CTA nếu nhu cầu vận hành thường xuyên thay đổi.
- Từng số liệu gồm giá trị, nhãn và công tắc hiển thị.

Nếu muốn giới hạn phạm vi lần đầu, có thể giữ copy đã duyệt trong template nhưng
phải loại hoặc ẩn số liệu chưa được chứng minh. Không tự tạo một CMS mới chỉ để
phục vụ vài chuỗi ổn định.

## File dự kiến liên quan

- `domains/phucthinhsolar.com/public_html/templates/layout/slide.php`
  - Hero, copy, CTA, số liệu và khối trust.
- `domains/phucthinhsolar.com/public_html/assets/css/theme-v2.css`
  - Overlay theo vùng, bố cục Hero/trust, menu và responsive.
- `domains/phucthinhsolar.com/public_html/templates/index/index_tpl.php`
  - Khối “Bạn đang cần gì?”.
- `domains/phucthinhsolar.com/public_html/templates/layout/menu.php`
  - Logic/class active nếu cần; không đổi URL public.
- `domains/phucthinhsolar.com/public_html/templates/layout/header.php`
  - Cách hiển thị giờ làm việc ở top bar.
- `domains/phucthinhsolar.com/public_html/templates/layout/footer.php`
  - Giờ làm việc đầy đủ ở footer.
- `domains/phucthinhsolar.com/public_html/templates/about/about_tpl.php`
  - Link/lightbox chứng nhận.
- `domains/phucthinhsolar.com/public_html/sources/index.php`
  - Chỉ sửa nếu thay nguồn dữ liệu trust/partner hoặc setting động.
- `domains/phucthinhsolar.com/public_html/administrator/templates/setting/man/man_add_tpl.php`
  và `administrator/sources/setting.php`
  - Chỉ khi bổ sung field quản trị Hero/số liệu.
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-photo.php`
  - Chỉ khi cần sửa hướng dẫn/kích thước ảnh; không đổi cơ chế upload nếu không
    có yêu cầu mới.

Không refactor các phần trang chủ khác ngoài phạm vi này.

## Tiêu chí nghiệm thu

### Hero

- Chỉ có một ảnh công trình thật đã được duyệt; không còn ảnh ghép nhiều ngành
  hàng tự xuất hiện sau 6 giây.
- Chữ đọc rõ nhưng vùng công trình bên phải sáng và nhìn được chi tiết.
- H1, eyebrow và ảnh cùng truyền một phạm vi thương hiệu.
- `Xem sản phẩm` dẫn đúng route hiện tại.
- `Nhận tư vấn` mở đúng modal và không reload trang.
- `Đăng ký đại lý` mở modal/route đã chốt.

### Dẫn hướng và menu

- Ngay dưới Hero chỉ còn ba hành trình đã duyệt.
- Mỗi card dẫn đúng trang hoặc modal tương ứng.
- Trên mỗi route chỉ một mục menu mang trạng thái active.
- Hover, focus và active phân biệt được bằng mắt và bàn phím.

### Tin cậy và giờ làm việc

- Không còn số liệu hoặc nhãn ủy quyền thiếu nguồn xác minh.
- Chứng nhận đầy đủ xem lớn được tại trang “Về chúng tôi”.
- Giờ làm việc thống nhất ở header, footer và trang liên hệ.

### Responsive và hiệu năng

- Kiểm tra ít nhất desktop 1440/1280 và mobile 412/390.
- Hero không tràn ngang, CTA không chồng nhau, chữ không đè chủ thể ảnh.
- Crop mobile giữ được chủ thể chính.
- Ảnh Hero được tối ưu dung lượng, ưu tiên JPG chất lượng phù hợp với cấu hình
  upload hiện có; không upload file gốc nhiều MB nếu không cần thiết.
- Không làm regress cụm nút liên hệ nổi và khoảng chừa bên phải Hero desktop.

## Kiểm thử bắt buộc sau triển khai

1. PHP 7.4 lint cho toàn bộ PHP đã thay đổi.
2. HTTP smoke trang chủ, trang giới thiệu, trang liên hệ và các route CTA.
3. Playwright desktop/mobile:
   - Hero và crop ảnh.
   - Active/hover/focus menu.
   - Modal tư vấn và modal đại lý.
   - Ba card nhu cầu.
   - Lightbox chứng nhận.
4. Cập nhật visual baseline chỉ sau khi chủ project đã duyệt hình ảnh mới.
5. Vì thay template/header/footer dùng chung, chạy predeploy đầy đủ và commerce
   smoke theo chuỗi category → product → cart → checkout → confirmation.
6. Xác minh cache 7200 giây đã được làm mới sau cập nhật ảnh/cấu hình.

## Deploy và rollback

### Deploy

1. Hoàn thiện và duyệt trên Docker local.
2. Sao lưu chính xác các row setting/photo sẽ thay đổi và danh sách asset mới.
3. Đồng bộ code và asset qua quy trình release; không `docker cp` rồi coi như đã
   hoàn tất triển khai.
4. Chỉ deploy production khi có phép rõ ràng.
5. Sau deploy, làm mới cache và kiểm tra trực tiếp desktop/mobile production.

### Rollback

- Code: hoàn nguyên commit riêng của hạng mục Hero/trang chủ.
- DB: phục hồi các row `table_setting`/`table_photo` đã sao lưu.
- Ảnh: bật lại trạng thái của ảnh Hero cũ; không cần upload lại vì asset cũ
  không bị xóa.
- Cache: làm mới cache sau rollback và xác minh lại trang chủ.

## Thứ tự thực hiện khuyến nghị cho Claude

```text
XÁC MINH ẢNH + SỐ LIỆU + CHỨNG NHẬN
-> CHỐT COPY VÀ ĐÍCH CTA
-> BACKUP DỮ LIỆU LIÊN QUAN
-> HERO + CTA
-> 3 LỐI VÀO THEO NHU CẦU
-> MENU + GIỜ LÀM VIỆC
-> TRUST/CHỨNG NHẬN
-> PHP LINT + PLAYWRIGHT + COMMERCE SMOKE
-> REVIEW VISUAL
-> DOCUMENT + REPORT
```

Không bắt đầu bằng việc thay ảnh ngẫu nhiên hoặc tự viết số liệu/chứng nhận. Nếu
thiếu ảnh thật hay bằng chứng kinh doanh, tiếp tục hoàn thiện phần code có thể
kiểm thử trên local nhưng giữ nội dung chưa xác minh ở trạng thái ẩn/chờ duyệt.
