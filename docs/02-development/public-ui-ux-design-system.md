# Hệ thống UI/UX public — nguồn chuẩn duy nhất

Cập nhật: 2026-09-24. Phạm vi: toàn bộ website public Phúc Thịnh Solar trên
desktop, iMac/2K, tablet và mobile.

Tài liệu này trả lời câu hỏi **giao diện phải như thế nào**. Cách đặt CSS vào
đúng lớp cascade nằm ở [frontend-guide.md](frontend-guide.md). Các quyết định
kiến trúc đã chốt và lý do nằm ở [ADR](../09-decisions/README.md).

## 1. Thẩm quyền và cách đọc trạng thái

Đây là nguồn chuẩn duy nhất cho UI/UX dùng chung. Không lấy giao diện hiện tại
của một trang làm chuẩn nếu nó mâu thuẫn với tài liệu này.

Thứ tự ưu tiên khi có xung đột:

1. quyết định mới nhất đã được chủ project duyệt và ADR tương ứng;
2. tài liệu này;
3. `AGENTS.md` và hướng dẫn triển khai frontend;
4. implementation hiện tại.

Ba nhãn được dùng trong tài liệu:

- **ĐÃ DUYỆT**: định hướng bắt buộc, code mới không được làm trái.
- **ĐANG ÁP DỤNG**: đã có token/component hoặc test dùng chung.
- **MỤC TIÊU**: chuẩn cần hội tụ dần; chưa được giả định toàn bộ legacy đã đạt.

ADR 0006 thay thế phần thứ tự/nội dung trang chủ của ADR 0001. Các nguyên tắc
chung trong ADR 0001 về product-first, mật độ và hạn chế trang trí vẫn còn hiệu
lực. Ngoại lệ mới phải được chủ project duyệt và ghi ADR; không tạo ngoại lệ
bằng một selector `!important` cục bộ.

## 2. Định hướng trải nghiệm

### 2.1 Nguyên tắc cốt lõi — ĐÃ DUYỆT

- Website là showroom bán hàng và tư vấn kỹ thuật, không phải landing page chỉ
  để trình diễn. Sản phẩm, danh mục, ứng dụng và hành động liên hệ phải dễ tìm.
- Mỗi section có một mục tiêu và một tiêu đề chính rõ ràng. Không lặp cùng thông
  điệp ở nhiều section.
- Không thêm eyebrow, gạch chân, nhãn nhỏ, họa tiết, card, border hoặc shadow
  chỉ để lấp khoảng trống.
- Tạo phân cấp bằng lưới, khoảng trắng, chữ và ảnh có mục đích. Bề mặt phẳng
  không đồng nghĩa với trang trắng trống trải.
- Ảnh và nội dung thật được ưu tiên. Không dùng ảnh AI/stock làm bằng chứng cho
  khách hàng, dự án, chứng nhận hoặc năng lực đã thực hiện.
- Nội dung marketing có thể thay đổi phải thuộc Admin, có trạng thái hiển thị
  và fallback an toàn; không chép cứng vào template chỉ để hoàn thiện layout.
- Học nhịp nội dung và khả năng bán hàng từ website tham chiếu, không sao chép
  máy móc nhận diện, nội dung hoặc hành vi.

### 2.2 Kiến trúc thông tin — ĐÃ DUYỆT

- Logo dẫn về trang chủ; header không có mục `Trang chủ`.
- Desktop có nút `Danh mục sản phẩm` và đúng năm nhóm: `Giới thiệu`, `Giải pháp`,
  `Dự án`, `Kiến thức`, `Hợp tác`.
- Tin tức, Đại lý và Liên hệ là nội dung con, không trở lại top-level.
- `/ve-chung-toi/` hiển thị nhãn `Giới thiệu`; giữ URL/slug public cũ để bảo toàn
  SEO và liên kết đã chia sẻ.
- `Giải pháp` và `Danh mục sản phẩm` đều có landing page. Menu cha phải click
  được; mobile phải có `Tất cả giải pháp`/`Xem tất cả sản phẩm`.

## 3. Nền tảng thị giác

### 3.1 Token màu — ĐANG ÁP DỤNG

Nguồn code: `assets/css/design-tokens.css`.

| Vai trò | Token | Giá trị | Cách dùng |
|---|---|---:|---|
| Thương hiệu chính | `--pts-navy` | `#0a2251` | tiêu đề, trạng thái chọn, nền thương hiệu |
| Navy đậm | `--pts-navy-deep` | `#061537` | nền đậm, chữ trên nền sáng khi cần |
| Navy sáng | `--pts-navy-light` | `#123a7a` | biến thể nền có chủ đích |
| Hành động chính | `--pts-gold` | `#c9962e` | CTA chính, điểm nhấn hữu ích |
| Gold hover | `--pts-gold-light` | `#e0b654` | hover/focus CTA vàng |
| Gold chữ đậm | `--pts-gold-dark` | `#9c7420` | text-link trên nền sáng nếu đạt contrast |
| Chữ phụ | `--pts-muted` | `#64748b` | mô tả, metadata |
| Đường có nghĩa | `--pts-line` | `#e5e7eb` | input, table, focus/selection boundary |
| Nền trung tính | `--pts-surface` | `#f8fafc` | vùng chức năng có lý do, không bọc card tùy ý |
| Chip thường | `--pts-chip-bg` | `#eef2f7` | filter/tab chưa chọn |
| Chip hover | `--pts-chip-bg-hover` | `#dfe6ef` | hover filter/tab |

Không tạo biến thể navy/gold mới trong CSS trang. Không dùng gradient, kem hoặc
vàng cho mọi bề mặt khiến CTA mất thứ bậc. Chữ thường phải đạt WCAG AA: tối
thiểu 4.5:1; chữ lớn tối thiểu 3:1; focus/control cũng tối thiểu 3:1.

- Public storefront mặc định dùng giao diện sáng, không phụ thuộc cài đặt theme
  của hệ điều hành. Công tắc dark mode chỉ được hiển thị sau khi toàn bộ route,
  trạng thái và nội dung CMS đã đạt kiểm tra tương phản AA ở theme tối. Tới lúc đó
  giữ công tắc ẩn và không nạp lại lựa chọn tối cũ từ trình duyệt. Dark mode hoàn
  chỉnh vẫn phải giữ nền sáng có chủ đích cho ô nhập liệu và vùng ảnh sản phẩm.

### 3.2 Typography — ĐANG ÁP DỤNG/MỤC TIÊU

- Font public: `Be Vietnam Pro`, fallback `Plus Jakarta Sans`, `Mulish`, font hệ
  thống. Không tự nạp font khác ở một trang.
- Mỗi trang có đúng một `h1`. Section dùng `h2`, mục con dùng `h3`; không chọn
  heading theo kích thước mong muốn.
- Nội dung CKEditor bắt đầu từ `h2`, không chép lại H1/hero vào editor.
- Heading ưu tiên câu ngắn, ngắt theo cụm nghĩa. Tên riêng như `Phúc Thịnh Solar`
  không bị tách tùy tiện; dùng copy/layout hợp lý, không ép bằng `<br>` theo một
  viewport.
- Body tối thiểu 16px trên mobile. Không thu chữ để chữa layout; sửa grid,
  container hoặc copy trước.
- Thang chữ dùng chung thuộc `typography-system.css`. CSS trang chỉ được thêm
  vai trò đặc thù đã được duyệt, không định nghĩa lại body/H1 toàn trang.

### 3.3 Khoảng cách và nhịp dọc — ĐANG ÁP DỤNG

- Section desktop: 64px; mobile: 48px.
- Hub top: 32px desktop, 24px mobile; hub bottom: 56px desktop, 40px mobile.
- Khoảng cách phải diễn tả quan hệ: nội dung cùng nhóm gần nhau, section khác
  mục tiêu xa nhau. Không dùng `min-height`, margin lớn hoặc spacer rỗng để cân
  với cột bên cạnh.
- Trong layout hai cột, chiều cao form không được đẩy phần nội dung tiếp theo
  của cột trái xuống. Nhóm nội dung liên quan nằm chung một flow; form có thể
  sticky nhưng không sở hữu chiều cao của các section không liên quan.

Thang spacing semantic cần hội tụ dần (**MỤC TIÊU**, chưa thay hàng loạt legacy):

| Token dự kiến | Giá trị | Dùng cho |
|---|---:|---|
| `space-1` | 4px | icon/text micro gap |
| `space-2` | 8px | phần tử liên quan chặt |
| `space-3` | 12px | control nội bộ |
| `space-4` | 16px | gutter mobile, item gap |
| `space-6` | 24px | nhóm nội dung/card gap |
| `space-8` | 32px | block trong section |
| `space-12` | 48px | section mobile |
| `space-16` | 64px | section desktop |

Không tạo giá trị 17/19/27px mới nếu một token gần nhất giải quyết được. Giá trị
đặc thù chỉ hợp lệ khi phụ thuộc kích thước ảnh/control hoặc đã được đo.

### 3.4 Bề mặt, border, radius và shadow — ĐÃ DUYỆT

- Card nội dung/sản phẩm/danh mục/bài viết hòa vào nền section: mặc định không
  nền gần giống nền trang, không border, không shadow và không shell lồng nhau.
- Radius mục tiêu: `0` cho shell/bề mặt nội dung; `2–5px` cho ảnh, input, CTA
  chữ và boundary có nghĩa. Hình tròn/pill chỉ dùng cho icon button, avatar,
  trạng thái hoặc chip có lý do chức năng.
- Shadow chỉ dùng cho dropdown, modal, phần tử nổi/fixed hoặc overlay cần phân
  biệt mặt phẳng. Hover không làm card nhảy lên nếu không có ý nghĩa thao tác.
- Border chỉ dùng cho form control, bảng, focus, trạng thái chọn hoặc ranh giới
  thao tác. Breadcrumb không có border/gạch chân dưới.

Token semantic mục tiêu: `radius-none:0`, `radius-sm:3px`, `radius-control:4px`,
`radius-round:999px` chỉ cho vai trò chức năng; `elevation-0:none`,
`elevation-popover` và `elevation-modal`. Không có `card-shadow` mặc định.

## 4. Hợp đồng layout và responsive

### 4.1 Container ngang — ĐANG ÁP DỤNG

Từ 1600px, container public dùng chung công thức:

```css
min(calc(100vw - clamp(96px, 8vw, 220px)), 2200px)
```

Ví dụ: viewport 1920px chừa 76.8px mỗi bên; viewport 2560px chừa 180px mỗi
bên. Trang con phải cân giữa và cùng mép nội dung với trang chủ. Không đặt một
`max-width` riêng theo trang chỉ vì ảnh chụp ở một màn hình trông vừa mắt.

Header là ngoại lệ cấu trúc, không phải page content: tại đoạn chuyển tiếp
1600–1699px, shell điều hướng được phép giữ tối thiểu `--pts-container` để logo,
menu và nhóm hành động không va nhau. Ngoại lệ này không được truyền xuống
breadcrumb, hero hoặc nội dung trang.

Quy tắc owner:

- Mỗi trục chỉ có **một** wrapper sở hữu gutter.
- Không cộng padding của `.wrap-main`, `.container`, `.uk-container` và section
  lồng nhau. Wrapper pass-through phải có padding bằng 0.
- Full-bleed chỉ dành cho media/marketing band đã ghi trong hợp đồng trang; copy
  bên trong vẫn inset đúng một lần.
- Grid dùng `minmax(0,1fr)` và con trực tiếp dùng `min-width:0` để nội dung không
  đẩy cột ra ngoài.
- Rail cuộn ngang thiếu item dùng safe-center; không `justify-content:center`
  theo cách làm mất item đầu.

### 4.2 Mobile — ĐANG ÁP DỤNG

- `<=767px`: gutter duy nhất 16px; `<=359px`: 12px.
- Không gây horizontal overflow ở 320px.
- Cột desktop chuyển một cột theo thứ tự nội dung/chuyển đổi hợp lý, không chỉ
  theo thứ tự DOM cũ.
- Danh sách ngang có mục đích được cuộn bằng chuột/trackpad/touch và ẩn scrollbar
  thị giác; không tự xuống nhiều hàng làm kéo dài trang.
- Full-bleed mobile có thể bỏ radius; control và entity card vẫn giữ boundary
  chức năng của nó.

### 4.3 Ma trận bắt buộc

Kiểm tra tối thiểu tại `320, 360, 390, 414, 768, 1024, 1366, 1440, 1600,
1920, 2560px`. 1920 và 2560 đại diện PC Full HD và iMac/2K; không kết luận
responsive ổn chỉ từ laptop 1366px.

### 4.4 Trang chủ Full Visual Parity — ĐÃ DUYỆT THEO CHỈ ĐẠO TRỰC TIẾP (ADR 0009)

- Trang chủ hiển thị đầy đủ Hero banner slider theo chuẩn giao diện PHP production (ảnh banner nhóm thiết bị nổi bật, slide controls, chỉ số slide, tiêu đề, kicker và nút xem sản phẩm/tư vấn lắp đặt).
- Trang chủ hiển thị đầy đủ các section nghiệp vụ: Danh mục sản phẩm, Sản phẩm theo nhóm, Chiếu sáng/ứng dụng, Giải pháp theo từng nhu cầu (tabs), Quy trình 5 bước, Đăng ký đại lý, Về chúng tôi, và Form tư vấn liên hệ.
- Mọi dữ liệu hình ảnh/nội dung lấy từ API backend PHP DTO hoặc fallback an toàn theo đúng thiết kế gốc.

## 5. Hợp đồng component dùng chung

### 5.1 Header và navigation

- Một header chung, không tạo header riêng cho hub.
- Logo, menu, ngôn ngữ, tìm kiếm, điện thoại, giỏ hàng và CTA có cùng baseline,
  hit area và trạng thái ở mọi route.
- Ở mobile, tăng kích thước logo chỉ được tăng phần hình trong hàng header; không
  được làm hàng header cao/dày hơn hoặc đẩy nội dung đầu trang xuống bất thường.
- Hamburger mobile có hit area tối thiểu 44×44px và chuyển 3 gạch ↔ X bằng
  `transform`/`opacity` đồng bộ; không để pseudo-element hoặc thanh trong suốt
  thừa xuất hiện giữa các gạch. Chuyển trạng thái phải mượt và không làm layout
  nhảy.
- Bộ chọn ngôn ngữ là chức năng desktop; không đưa cụm ngôn ngữ vào sidebar
  mobile nếu không có nhu cầu sử dụng tương đương đã được duyệt.
- Dropdown có thể có shadow/radius nhẹ vì là bề mặt nổi. Menu cha vẫn có đường
  vào landing bằng click/keyboard, không hover-only.
- Nút `Danh mục sản phẩm` trên desktop mở mega-menu overlay khi rê chuột (thiết
  bị có hover) hoặc click/keyboard; menu không tự mở theo hover trên cảm ứng.
  Overlay có thể đóng bằng nút đóng, click vùng nền hoặc Escape; focus được đưa
  vào menu khi mở có chủ ý, giữ trong menu khi Tab và trả về nút mở khi đóng.
  Panel desktop trình bày ba cột lối tắt 4–4–2: Chiếu sáng, Điện mặt trời,
  Thiết bị ứng dụng. Mỗi lối tắt khớp category đang hiển thị trong Admin qua
  slug public và lấy URL thật từ dữ liệu; panel mặc định không dàn toàn bộ cấp
  gốc/cấp con. Lối tắt có danh mục con mở flyout theo hover/focus, còn danh mục
  không có con không hiển thị mũi tên. Các nhóm còn lại đi qua landing `Xem tất cả sản phẩm` hoặc menu
  mobile. Tìm kiếm dùng route tìm sản phẩm hiện có; chân panel có lối tư vấn,
  không lặp bộ chọn ngôn ngữ của header.
- Mobile menu có nút đóng/mở dễ hiểu, focus được và không che logo.
- Mobile drawer có ô tìm sản phẩm gửi tới cùng chức năng tìm kiếm hiện có;
  nhóm danh mục sản phẩm mở inline dạng accordion khi người dùng bấm, không tự
  bung nhóm con khi vào menu. Danh mục nhiều cấp dùng URL và dữ liệu Admin hiện hữu; không ẩn nhóm
  chỉ để khớp số lượng trong mockup. Có lối xem tất cả sản phẩm, nút nhận tư vấn
  ở đáy drawer và nút đóng; tên danh mục dài được phép xuống dòng, không cắt cụt.
  Không hiển thị bộ chọn ngôn ngữ trong mobile drawer.

### 5.2 Breadcrumb

- Một hàng ngắn trước nội dung chính; không border, nền card, shadow hay khoảng
  trắng lớn phía dưới.
- Cùng mép container với section kế tiếp. Item cuối là vị trí hiện tại, không
  giả làm link nếu không cần.

### 5.3 Button, link và icon button

- Primary: nền gold phẳng, chữ navy; secondary: nền trong suốt/trắng, border
  navy; text-link: chữ + icon hướng, không bọc thành pill tùy ý.
- Cùng vai trò phải cùng màu, radius, chiều cao và hover trên mọi trang.
- Trạng thái bắt buộc: default, hover, `:focus-visible`, active/selected,
  disabled và loading. Disabled không chỉ đổi màu mà phải ngăn thao tác.
- Hit target tối thiểu 44×44px. Nút chỉ có icon phải có `aria-label`/tooltip phù
  hợp; không dùng icon mơ hồ thay cho nhãn quan trọng.

### 5.4 Icon — MỤC TIÊU

- Dùng một nguồn đang có là Font Awesome trong cùng một cụm; không trộn emoji,
  PNG và nhiều nét icon cạnh nhau.
- Kích thước chuẩn: 16px trong text/control, 20px cho action, 24px cho điểm nhấn.
  Icon trang trí dùng `aria-hidden="true"`; icon truyền nghĩa phải có nhãn.
- Hình tròn chỉ bọc icon action/trạng thái, không bọc mọi icon danh sách.
- Không dùng icon để bù cho tiêu đề/nhãn chưa rõ nghĩa.

### 5.5 Chip, tab, badge và pagination

- Chip/filter thường: nền xám-xanh, radius 3px; active: navy + chữ trắng.
- Tab phải cho biết trạng thái bằng màu và semantics, dùng keyboard được.
- Badge là metadata ngắn, không phải CTA. Không tạo badge chỉ để trang trí.
- Pagination có vùng bấm tối thiểu 44px, current state rõ, không pill quá mức.

### 5.6 Card

- Card là một entity click được hoặc một nhóm thông tin có ranh giới logic,
  không phải mặc định cho mọi đoạn nội dung.
- Một card có thứ tự ổn định: media → loại/metadata nếu cần → tên → mô tả ngắn
  → hành động. Không lặp CTA và không phủ nhiều lớp nền mờ lên ảnh.
- Product card: ảnh 1:1 contain, tên không cắt tùy tiện, CTA nhất quán.
- News: ảnh 16:9. Project: ảnh chuẩn 4:3. Solution/dealer/contact editorial: 8:5.
- Cả card chỉ click được khi semantics/accessibility rõ; tránh link lồng link.

### 5.7 Form

- Label luôn hiện; placeholder chỉ là ví dụ, không thay label. Field bắt buộc có
  dấu và thông báo lỗi gắn với field.
- Input/select/textarea cùng chiều cao, radius 2–5px, border và focus ring thống
  nhất. Không dùng shadow/radius lớn để biến form thành một thiết kế khác site.
- Validation server-side là bắt buộc; mutation giữ session/quyền và CSRF theo
  `AGENTS.md`. Không xóa dữ liệu người dùng sau lỗi có thể sửa.
- Form dài chia nhóm theo ý nghĩa, không theo nhu cầu lấp cột. Submit có loading,
  chống gửi lặp và feedback thành công/thất bại.
- Modal/form mở từ CTA phải preselect đúng nhu cầu nếu CTA đã nói rõ, ví dụ
  `Hợp tác đại lý`.

### 5.8 Table, modal, carousel và trạng thái hệ thống

- Table chỉ dùng cho dữ liệu cần so sánh; mobile cho cuộn ngang có chỉ dẫn hoặc
  chuyển cấu trúc có chủ đích, không co chữ đến không đọc được.
- Modal trap focus, đóng bằng Escape/nút rõ ràng, trả focus về trigger.
- Carousel có điều khiển keyboard/touch, không auto-play nội dung cần đọc; ảnh
  trong carousel phải xử lý lazy-load để không bị trắng.
- Empty/loading/error/fallback là trạng thái bắt buộc. Không render khung rỗng,
  ảnh vỡ hoặc section chỉ còn tiêu đề khi Admin chưa có dữ liệu.

### 5.9 Footer và floating action

- Footer dùng chung một cấu trúc và dữ liệu doanh nghiệp thật; không thêm footer
  riêng theo trang.
- Nút chat/back-to-top không che CTA, form, carousel control hoặc nội dung ở
  320px; có vùng an toàn cho mobile browser và keyboard.

### 5.10 Commerce, cart và checkout responsive

- Bộ tăng/giảm số lượng trên trang chi tiết và trong giỏ là một component dùng
  chung cho mọi sản phẩm. Nhãn, nút giảm, ô số lượng và nút tăng nằm cùng một
  hàng; nút thao tác có vùng chạm tối thiểu 42px, ô số lượng không bị co hoặc
  tràn ngang ở 320px.
- Popup giỏ hàng và trang checkout dùng cùng hợp đồng dòng sản phẩm, theo đúng
  bốn cột ngữ nghĩa `ảnh | tên | thành tiền | số lượng`. Header và dòng dữ liệu
  phải dùng cùng class/cùng vị trí grid; không dựa vào auto-placement khi thứ tự
  DOM khác thứ tự hiển thị. Với item đặt cột tường minh, đặt cả `grid-row` để
  tránh item sau bị đẩy sang hàng mới.
- Trên desktop/laptop, tên sản phẩm, giá và bộ tăng/giảm dùng chung một trục giữa
  theo chiều dọc. Nút `Xóa` là hành động phụ nằm dưới bộ đếm và không được tham
  gia vào chiều cao dùng để căn giữa, nếu không bộ đếm sẽ bị kéo lên so với tên.
- Hợp đồng số lượng hiện tại là số nguyên `1–999`. Ô nhập phải hiển thị trọn ba
  chữ số, không đè nút cộng/trừ; khi đổi kích thước ô phải đổi đồng bộ cột grid,
  counter và vùng nút `Xóa` ở cả popup lẫn checkout. Luôn test `999`, tên dài và
  tổng tiền từ hàng trăm triệu đến hàng tỷ thay vì chỉ test giá trị `1`.
- Lựa chọn phương thức thanh toán dùng một radio input thật gắn với label. Radio
  hiển thị nằm giữa theo chiều dọc của thẻ, còn nội dung label căn trái và có
  khoảng đệm đủ để không đè lên radio; trạng thái chọn và `:focus-visible` phải
  nhìn thấy rõ.
- Checkout mobile chỉ có một wrapper sở hữu gutter ngang. Không cộng padding
  lần hai cho section, nhóm sản phẩm hoặc thẻ thanh toán khiến các mép bị lệch
  so với phần còn lại của trang.

## 6. Hệ thống ảnh và media — ĐÃ DUYỆT

| Ngữ cảnh | Tỷ lệ | Fit | Ghi chú |
|---|---:|---|---|
| Sản phẩm/gallery | 1:1 | `contain` | không crop, không phóng ảnh nhỏ quá kích thước tự nhiên |
| Logo/chứng nhận | theo khung | `contain` | không crop |
| Tin tức | 16:9 | `cover` | chủ thể ở vùng an toàn |
| Dự án | 4:3 | `cover` | implementation card 8:5 hiện là drift cần xử lý |
| Solution/nội dung/đại lý/liên hệ | 8:5 | `cover` | Admin ghi rõ tỷ lệ field |
| Campaign catalog | 16:5 | `cover` | chữ do frontend render, không đóng chữ vào ảnh |

Ảnh cover giữ chủ thể trong vùng an toàn trung tâm 70–75%. Mỗi field Admin phải
nêu tỷ lệ, kích thước khuyến nghị, mục đích và preview crop. Ảnh thiếu dùng
fallback trung tính đúng loại; không dùng một placeholder cho mọi ngữ cảnh.

## 7. Hợp đồng theo loại trang

### Trang chủ

- Full Visual Parity theo chuẩn PHP Production (ADR 0009). Hiển thị đầy đủ Hero banner slider và các section: Danh mục, Sản phẩm theo nhóm, Chiếu sáng/ứng dụng, Giải pháp (tabs), Quy trình 5 bước, Đăng ký đại lý, Về chúng tôi, Form tư vấn liên hệ.

### Danh mục và chi tiết sản phẩm

- Landing danh mục truy cập được; filter/sort là control, không là card trang
  trí. Nội dung SEO không chặn đường tới sản phẩm.
- Chi tiết ưu tiên gallery, tên, thông số quyết định, tư vấn/mua hàng, rồi nội
  dung dài. Ảnh 1:1 contain; điều hướng nội dung cuộn ngang và bảng vẫn dùng
  được ở mobile.
- Hero PDP desktop dùng hai cột cân nhau. Tối đa bốn thông số quyết định hiển
  thị theo lưới 2×2 từ field có cấu trúc; không dùng giá trị mẫu hoặc “đang cập
  nhật” để lấp ô. Ở 320px lưới chuyển một cột để giữ khả năng đọc.
- PDP dùng luồng dọc: gallery + buy box, điều hướng mục, mô tả, hướng dẫn lựa
  chọn sinh từ thông số thật, bảng thông số toàn chiều ngang, chính sách bảo
  hành, đánh giá, form tư vấn và sản phẩm liên quan. Không dựng khối tài liệu,
  video hoặc ảnh ứng dụng khi Admin chưa có dữ liệu tương ứng; FAQ chỉ được lấy
  từ field kỹ thuật thực tế hoặc nội dung do Admin quản lý.
- CTA báo giá cuộn đến form tư vấn trong trang; sản phẩm có giá vẫn giữ nguyên
  số lượng, Mua ngay và Thêm giỏ hàng. Chính sách bảo hành chỉ liệt kê đúng mục
  bảo hành thay vì gom toàn bộ chính sách hỗ trợ dưới một nhãn.

### Giới thiệu, Giải pháp, Dự án và Kiến thức

- Một H1, một mục tiêu/section, không eyebrow/line trang trí.
- Giải pháp và bài kiến thức dài dùng CKEditor H2/H3/list/table/image; hero,
  navigation, related items và CTA/form thuộc template/Admin field chuyên trách.
- Dự án/chứng nhận/năng lực chỉ dùng bằng chứng xác minh được.

### Đại lý và Liên hệ

- Hai cột desktop phải bắt đầu cùng nhịp; form nằm cạnh nội dung từ đoạn đầu nếu
  có đủ chiều rộng, không tạo khoảng trống lớn do ghép các section không liên quan.
- Mobile đưa form về thứ tự chuyển đổi hợp lý. CTA có nhu cầu rõ phải mở form đã
  chọn sẵn nhu cầu tương ứng.
- Contact hero, form và hệ thống chi nhánh dùng cùng container, typography và
  nhịp dọc với các hub khác.

## 8. Accessibility và interaction — ĐÃ DUYỆT

- Mọi chức năng dùng được bằng keyboard; focus-visible không bị xóa.
- Thứ tự DOM hợp lý với thứ tự đọc; không dùng CSS order làm nghĩa nội dung sai.
- Ảnh có `alt` theo mục đích; ảnh trang trí alt rỗng. Form/input có label; landmark
  và heading đúng cấp.
- Hit target tối thiểu 44×44px; không phụ thuộc chỉ vào màu để truyền trạng thái.
- Tôn trọng `prefers-reduced-motion`; animation ngắn, có mục đích, không gây
  dịch layout.
- Zoom 200% và text reflow không mất nội dung/chức năng.

### 8.1 Hiệu năng cảm nhận

- Ảnh đầu trang/LCP có kích thước đúng, `srcset/sizes` phù hợp và không lazy-load
  nếu nằm trong viewport đầu. Ảnh dưới fold mới lazy-load.
- Khai báo kích thước hoặc `aspect-ratio` trước khi ảnh tải để tránh CLS.
- Không nạp thư viện/animation riêng chỉ cho hiệu ứng trang trí. Interaction có
  phản hồi ngay; thao tác mạng hiển thị loading và không cho gửi lặp.
- Không đánh đổi khả năng đọc, crop đúng hoặc keyboard interaction chỉ để đạt
  một điểm số máy đo; nhưng mọi regression hiệu năng phải được ghi nhận.

## 9. Ownership CSS và dữ liệu

| Trách nhiệm | Chủ sở hữu |
|---|---|
| Màu, chip, vai trò shared | `design-tokens.css` |
| Thang chữ public | `typography-system.css` |
| Tỷ lệ/fit ảnh | `media-system.css` |
| Container/gutter/breakpoint cấu trúc | `responsive-architecture.css` |
| Bề mặt phẳng | `flat-surface-system.css` |
| Header/footer | `header-v3.css`, `footer-v3.css` |
| Commerce/PDP/cart/checkout | `commerce-experience.css`, `product-detail-v2.css` |
| Biến thể chỉ một route | CSS trang, nạp trước các lớp shared |

CSS trang không được định nghĩa lại token, container chung, body typography,
shared button/chip/card/header/footer. Nếu component dùng ở từ hai nơi trở lên,
di chuyển về lớp shared hoặc tạo component mới có hợp đồng và test.

Nội dung marketing biến đổi phải đi theo chuỗi:

```text
Admin field + hướng dẫn ảnh/copy -> validation -> DB -> source -> template
-> fallback khi trống -> test trạng thái có/không dữ liệu
```

## 10. Anti-pattern bị cấm

- Sửa riêng từng trang bằng margin/padding ngẫu nhiên để “nhìn vừa” một màn hình.
- Có nhiều `max-width` cho cùng cấp container hoặc gutter owner lồng nhau.
- Dùng `min-height`/spacer rỗng để cột trái cao bằng form cột phải.
- Thu font, crop ảnh sai tỷ lệ hoặc chèn `<br>` cứng để chữa overflow.
- Bo 12–24px, pill, gradient và shadow trên card/control không có lý do.
- Hardcode màu gần giống token; trộn icon; dùng emoji làm icon giao diện.
- `!important` mới mà không ghi cascade owner và lý do.
- Chép nội dung marketing có thể thay đổi vào PHP/CSS/ảnh.
- Chỉ test 1366px hoặc chỉ nhìn screenshot mà không đo bounding box/overflow.

## 11. Definition of Done cho thay đổi UI/UX

Một thay đổi chỉ hoàn tất khi:

1. xác định route/source/template, component owner và dữ liệu Admin liên quan;
2. đối chiếu tài liệu này và ADR, không tạo ngôn ngữ riêng theo trang;
3. có trạng thái default/hover/focus/disabled/loading/error khi liên quan;
4. không overflow ở 320px và không lệch container ở 1920/2560px;
5. kiểm tra keyboard, focus, contrast, label/alt và touch target;
6. kiểm tra dữ liệu dài/ngắn/trống, ảnh dọc/ngang/thiếu và fallback;
7. chạy PHP lint đúng phiên bản nếu sửa template, Playwright liên quan và
   `git diff --check`;
8. cập nhật docs/ADR/test khi thay hợp đồng dùng chung;
9. báo cáo file, test, rủi ro, deploy và rollback bằng tiếng Việt.

Test tối thiểu nên gồm homepage, category, PDP, about, solution, project, news,
dealer và contact. Với thay đổi container/shared component, phải đo cùng selector
ở toàn bộ route thay vì chỉ screenshot một trang.

## 12. Khoảng cách giữa chuẩn và implementation hiện tại

Không sửa tất cả bằng một đợt refactor lớn. Bảng này ngăn người sau hiểu nhầm
legacy là quyết định thiết kế:

| Drift đã biết | Chuẩn | Hướng xử lý |
|---|---|---|
| `theme-v2.css` còn radius 10–20px, pill và shadow | 0 hoặc 2–5px, phẳng | thay dần theo component + visual regression |
| Token cũ `--pts-radius:12px` còn được dùng | radius theo vai trò | tách token semantic rồi migrate consumer |
| Breadcrumb legacy còn border ở vài selector | không border | gom về component breadcrumb shared |
| Project card đang dùng 8:5 trong media CSS | dự án 4:3 | xác minh dữ liệu/crop rồi đổi cùng test |
| Một số hub hardcode màu gần token | chỉ token shared | audit và thay theo component owner |
| Cascade còn nhiều `!important` | owner rõ, specificity thấp | giảm dần, không cleanup ngoài task |
| Frontend CSS có nhiều thế hệ | shared layer thắng có chủ đích | không xóa legacy trước khi đo coverage |

### Lộ trình hội tụ an toàn

1. **Khóa chuẩn:** dùng tài liệu này, ADR và test container/media hiện có.
2. **Inventory:** lập danh sách component/selector theo route và trạng thái.
3. **Token semantic:** bổ sung spacing/radius/control/elevation token, không đổi
   hàng loạt consumer trong cùng commit.
4. **Shared component:** ưu tiên breadcrumb, button/form, card/media, icon rồi
   mới đến page composition.
5. **Migrate theo lát dọc:** mỗi đợt gồm component + các route dùng nó + test.
6. **Xóa legacy cuối cùng:** chỉ khi chứng minh không còn consumer bằng search,
   runtime và visual regression.

## 13. Quy trình thay đổi chuẩn

- Sửa lỗi implementation để khớp chuẩn: cập nhật code/test, không cần ADR mới.
- Thay đổi hướng thiết kế, IA, tỷ lệ ảnh, container, component shared hoặc Admin
  ownership: cần chủ project duyệt, ADR và cập nhật tài liệu này trước/cùng code.
- Ngoại lệ tạm thời phải ghi route/component, lý do, owner, ngày hết hạn và test
  ngăn lan sang trang khác.
- Rollback UI thường là bỏ stylesheet/rule/component mới và khôi phục test tương
  ứng; không xóa asset/data Admin khi rollback presentation.
