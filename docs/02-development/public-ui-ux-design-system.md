# Hợp đồng UX, dữ liệu và accessibility cho public UI

Cập nhật: 2026-11-04. Phạm vi: toàn bộ website public Phúc Thịnh Solar trên
desktop, iMac/2K, tablet và mobile.

Tài liệu này giữ hợp đồng sản phẩm/UX, dữ liệu, URL/SEO, media, responsive,
accessibility và behavior. **Ngôn ngữ thị giác hiện hành** — token, vật liệu,
composition, radius, shadow và motion — nằm trong
[`docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md`](../15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md).
Cách đặt CSS vào đúng lớp cascade nằm ở [frontend-guide.md](frontend-guide.md);
các quyết định lịch sử và phạm vi còn hiệu lực nằm ở [ADR](../09-decisions/README.md).

## 1. Thẩm quyền và cách đọc trạng thái

Đây là nguồn chuẩn cho các hợp đồng UX và kỹ thuật được nêu trong tài liệu này;
không còn là nguồn thẩm mỹ duy nhất. Không lấy implementation legacy làm luật
nếu nó mâu thuẫn với owner direction hoặc design language hiện hành.

Thứ tự ưu tiên khi có xung đột:

1. chỉ đạo mới nhất, rõ ràng của chủ project và rào chắn an toàn/nghiệp vụ;
2. design language hiện hành cho quyết định thị giác;
3. tài liệu này cho dữ liệu, IA/behavior, URL/SEO, media, responsive và accessibility;
4. ADR trong phạm vi được ghi rõ; hướng dẫn triển khai frontend;
5. implementation hiện tại.

Ba nhãn được dùng trong tài liệu:

- **ĐÃ DUYỆT**: định hướng bắt buộc, code mới không được làm trái.
- **ĐANG ÁP DỤNG**: đã có token/component hoặc test dùng chung.
- **MỤC TIÊU**: chuẩn cần hội tụ dần; chưa được giả định toàn bộ legacy đã đạt.

ADR 0009 hiện điều chỉnh cấu trúc/nội dung trang chủ và yêu cầu visual parity
trong các task migration/parity. Visual parity không phải giới hạn cho task
creative redesign được giao rõ. Các ADR bề mặt phẳng/radius cũ đã được
supersede cho quyết định trình bày bởi design language hiện hành; xem ADR
0002/0007/0009 để biết phạm vi lịch sử và ngoại lệ. Không tạo thay đổi token
shared ngầm bằng selector `!important` cục bộ.

### 1.1 Nền tảng component và motion — ĐÃ DUYỆT

- Nuxt UI là lớp component mặc định cho giao diện Nuxt: button, input, select,
  textarea, form field, modal, dropdown, tabs, toast và trạng thái tải/lỗi.
  Dùng API, semantics và accessibility của component trước; chỉ thêm Tailwind
  CSS hoặc CSS riêng khi cần khớp token và bố cục Phúc Thịnh Solar.
- Không dựng lại component Nuxt UI bằng HTML thô chỉ để tránh cấu hình style.
  HTML native vẫn phù hợp với phần tử chuyên dụng không có bản tương đương,
  như file input, nội dung biên tập hoặc cấu trúc semantic đơn giản.
- Motion-V và native View Transitions đều có thể dùng theo hướng thiết kế đã
  chọn. Motion phải có mục đích, không làm thao tác chậm, không gây layout shift,
  tôn trọng `prefers-reduced-motion` và có trạng thái fallback phù hợp.
- Component mới/được nâng cấp ưu tiên Nuxt UI + primitive shared theo vai trò;
  CSS riêng được phép khi phục vụ composition của route và vẫn dùng token/thương
  hiệu hiện hành. Không ép mọi trang vào một visual template.
- Khi cải tiến legacy, hội tụ theo từng component/flow và giữ API, validation,
  CSRF, trạng thái lỗi/thành công hiện có; không cần chuyển HTML thuần hàng loạt
  nếu chưa thuộc phạm vi trải nghiệm đang sửa.

## 2. Định hướng trải nghiệm

### 2.1 Nguyên tắc cốt lõi — ĐÃ DUYỆT

- Website là showroom bán hàng và tư vấn kỹ thuật, không phải landing page chỉ
  để trình diễn. Sản phẩm, danh mục, ứng dụng và hành động liên hệ phải dễ tìm.
- Mỗi section có một mục tiêu và một tiêu đề chính rõ ràng. Không lặp cùng thông
  điệp ở nhiều section.
- Không thêm eyebrow, gạch chân, số liệu, nhãn hay họa tiết vô nghĩa để lấp
  khoảng trống. Nhãn/kicker có trong CMS hoặc cần thiết cho hiểu nội dung được
  phép khi hợp hướng thiết kế.
- Dùng lưới, khoảng trắng, typography, media, surface và motion theo composition
  được chọn; flat surface là một lựa chọn, không phải yêu cầu cho mọi section.
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
| Hành động chính | `--pts-gold-gradient` | `#e0b654 → #c9962e` | CTA và trạng thái chọn có nền vàng |
| Gold hover | `--pts-gold-light` | `#e0b654` | đầu sáng của gradient vàng |
| Gold chữ đậm | `--pts-gold-dark` | `#9c7420` | text-link trên nền sáng nếu đạt contrast |
| Chữ phụ | `--pts-muted` | `#64748b` | mô tả, metadata |
| Đường có nghĩa | `--pts-line` | `#e5e7eb` | input, table, focus/selection boundary |
| Nền trung tính | `--pts-surface` | `#f8fafc` | vùng chức năng có lý do, không bọc card tùy ý |
| Chip thường | `--pts-chip-bg` | `#eef2f7` | filter/tab chưa chọn |
| Chip hover | `--pts-chip-bg-hover` | `#dfe6ef` | hover filter/tab |

Public filter links use the shared `.public-filter-chip` treatment. Other
white/gray action buttons use `.public-neutral-button`; both use a borderless,
subtle white-to-gray gradient and a light raised shadow. Selected filters keep
their established group color (navy or the approved gold gradient). Gold actions
keep the approved gradient with a soft highlight and low shadow; avoid a hard
raised edge or thick 3D bevel.
Each location keeps its own size and spacing; do not apply the neutral treatment
to gold actions or selected gold catalog controls.

Product secondary actions, inactive catalog chips and pagination controls use
a thin navy outline over the neutral gradient. Selected chips retain their
approved active color. The desktop catalog filter column uses a light slate
surface; its mobile drawer remains white.

Header-v3 giữ token màu riêng để khớp PHP, nhưng mọi nút/nền tương tác dùng màu
vàng (kể cả nút header) hiển thị cùng gradient thương hiệu; token header vẫn
được giữ cho viền, chữ và các chi tiết không phải nền CTA.

Không tạo biến thể navy/gold mới trong CSS trang. Mọi bề mặt fill vàng đậm,
gồm CTA, lựa chọn đang bật, badge và nền icon, dùng gradient chung từ
`--color-gold-400` sang `--color-gold-500`, chữ/icon navy đậm và radius 4px cho
control; không đổi gradient thành màu phẳng ở hover/active. Nền kem/vàng nhạt
trang trí, ảnh, logo, viền, chữ và icon vàng tĩnh vẫn dùng token semantic phù
hợp, không ép thành gradient. Chữ thường phải đạt WCAG AA: tối thiểu 4.5:1; chữ
lớn tối thiểu 3:1; focus/control cũng tối thiểu 3:1.

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

- Các quy tắc bề mặt phẳng và radius 0–5px trong phiên bản trước đã được
  supersede. Dùng token theo vai trò trong design language hiện hành:
  `--radius-btn` 4px, `--radius-sm` 6px, `--radius-md`/`--radius-card` 10px,
  `--radius-lg` 16px, `--radius-xl` 24px; pill chỉ cho vai trò phù hợp.
- Card, border, shadow, glass và grid-paper là công cụ composition, không lệnh
  phải dùng hoặc phải tránh. Chọn theo nội dung và canvas của route; tránh shell
  lồng nhau, surface lặp và hiệu ứng trang trí không giúp hiểu/hoàn thành việc.
- Dùng shadow theo token `--pts-shadow-*`; border cần giữ contrast và không làm
  mất ranh giới control/focus. Breadcrumb theo treatment hiện hành ở document 12.

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

### 4.4 Bảo toàn nội dung trang chủ — ADR 0009

- Giữ các section nghiệp vụ và lối vào bán hàng đã duyệt: Hero/banner, Danh mục,
  Sản phẩm theo nhóm, Chiếu sáng/ứng dụng, Giải pháp theo nhu cầu, Quy trình,
  Đăng ký đại lý, Giới thiệu và Form tư vấn.
- Exact PHP visual parity áp dụng khi task là migration/parity. Redesign được giao
  rõ có thể thay đổi layout/art direction, nhưng không tự ý xóa các section,
  business path hoặc dữ liệu thật. Hình ảnh/nội dung lấy từ API/CMS hoặc fallback
  an toàn; không bịa media, claim hoặc số liệu.

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
- Nhãn điều hướng desktop dùng chữ navy đậm và không có nút mũi tên riêng cạnh
  nhãn. Menu con mở khi rê chuột hoặc khi focus bằng bàn phím; bấm nhãn cha vẫn
  đi tới landing tương ứng.
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
- Từ 1321px, hiển thị điều hướng ngang với logo compact và hotline dạng icon.
  Từ 1681px mở rộng logo và hiện số hotline; màn hình cảm ứng vẫn dùng drawer.
  Header màn rất rộng được phép nới theo container trang. Hai chế độ phải loại
  trừ lẫn nhau tại breakpoint, không để menu tràn vào logo hoặc nhóm thao tác.
- Mobile drawer có ô tìm sản phẩm gửi tới cùng chức năng tìm kiếm hiện có;
  nhóm danh mục sản phẩm mở inline dạng accordion khi người dùng bấm, không tự
  bung nhóm con khi vào menu. Danh mục nhiều cấp dùng URL và dữ liệu Admin hiện hữu; không ẩn nhóm
  chỉ để khớp số lượng trong mockup. Có lối xem tất cả sản phẩm, nút nhận tư vấn
  ở đáy drawer và nút đóng; tên danh mục dài được phép xuống dòng, không cắt cụt.
  Không hiển thị bộ chọn ngôn ngữ trong mobile drawer.

### 5.2 Breadcrumb

- Dùng shared component, cùng mép content container và semantics rõ; item cuối là
  vị trí hiện tại, không giả làm link.
- Dùng dải breadcrumb hiện hành từ design language; không ép route theo kiểu
  trần/phẳng hoặc thêm shell/card tùy tiện.

### 5.3 Button, link và icon button

- Primary: nền gradient gold chung, chữ/icon navy; secondary: nền trong
  suốt/trắng, border navy; text-link: chữ + icon hướng, không bọc thành pill
  tùy ý.
- Cùng vai trò dùng chung semantic token và trạng thái; kích thước/composition có
  thể khác khi nhiệm vụ của route khác nhau.
- Trạng thái bắt buộc: default, hover, `:focus-visible`, active/selected,
  disabled và loading. Disabled không chỉ đổi màu mà phải ngăn thao tác.
- Hit target tối thiểu 44×44px. Nút chỉ có icon phải có `aria-label`/tooltip phù
  hợp; không dùng icon mơ hồ thay cho nhãn quan trọng.

### 5.4 Icon — MỤC TIÊU

- Icon giao diện phổ thông ưu tiên `UIcon` của Nuxt UI với bộ Lucide cục bộ đã
  cài qua `@iconify-json/lucide`; không tải icon từ CDN khi chạy production.
- Inline SVG tự thiết kế được giữ cho icon đặc thù nhận diện, danh mục hoặc
  minh họa riêng của Phúc Thịnh Solar khi icon thư viện không khớp. Gom mapping
  dùng lại vào component/registry; giữ stroke và khung kích thước nhất quán
  trong cùng cụm.
- Ảnh icon do Admin quản lý được giữ khi là dữ liệu nghiệp vụ/thiết kế; render
  bằng `object-fit: contain` và khung kích thước ổn định. Không thay ảnh CMS
  bằng icon phỏng đoán.
- Không dùng Font Awesome còn thiếu CSS/font, emoji hoặc nhiều nét icon không
  liên quan trong cùng một cụm. Unicode chỉ dùng cho ký hiệu văn bản đơn giản,
  không thay icon-only control.
- Kích thước chuẩn: 16px trong text/control, 20px cho action, 24px cho điểm nhấn.
  Icon trang trí dùng `aria-hidden="true"`; icon truyền nghĩa phải có nhãn.
- Hình tròn chỉ bọc icon action/trạng thái, không bọc mọi icon danh sách.
- Không dùng icon để bù cho tiêu đề/nhãn chưa rõ nghĩa.

### 5.5 Chip, tab, badge và pagination

- Chip/filter chọn treatment theo current token và trạng thái; không mặc định
  một radius hay nền cho mọi rail/tab.
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
- Input/select/textarea giữ hit area, label, error/focus behavior và contrast nhất
  quán. Radius/surface lấy từ token hiện hành, không phải giới hạn cũ 2–5px.
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

- Giữ đầy đủ các section nghiệp vụ theo ADR 0009; chọn visual composition theo
  current design language. Task migration/parity giữ baseline PHP; task redesign
  có thể khác presentation theo phạm vi và hướng được chọn.

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

- Một H1 và mục tiêu section rõ. Không bịa nhãn/số liệu; có thể dùng kicker,
  chapter label, line hoặc motif khi thuộc nội dung/hướng thiết kế và không gây
  nhiễu cho thứ bậc thông tin.
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
| Màu, radius, elevation, motion, surface primitives | `frontend/app/assets/css/main.css` và design language 2026-11 |
| Thang chữ public | `typography-system.css` |
| Tỷ lệ/fit ảnh | `media-system.css` |
| Container/gutter/breakpoint cấu trúc | `responsive-architecture.css` |
| Shared route shell | component owner tương ứng; Nuxt không có global flat-surface override |
| Header/footer | `header-v3.css`, `footer-v3.css` |
| Commerce/PDP/cart/checkout | `commerce-experience.css`, `product-detail-v2.css` |
| Biến thể chỉ một route | CSS trang, nạp trước các lớp shared |

CSS trang không tạo token navy/gold tùy tiện hoặc thay container/semantic chung
không có lý do. Page composition, surface và local component được phép tạo theo
route khi dùng palette/token hiện hành và có owner rõ. Nếu component dùng từ hai
nơi trở lên, chuyển về shared layer hoặc component có API/test phù hợp.

Nội dung marketing biến đổi phải đi theo chuỗi:

```text
Admin field + hướng dẫn ảnh/copy -> validation -> DB -> source -> template
-> fallback khi trống -> test trạng thái có/không dữ liệu
```

### CTA cuối trang

CTA cuối nội dung có thể dùng `PublicCtaBanner.vue` như một lựa chọn shared để
giữ ảnh nền, lớp phủ navy, độ tương phản và responsive. Nội dung, ảnh, hotline,
Zalo và URL vẫn lấy từ DTO/Admin hoặc fallback đã duyệt; không gộp nội dung
nghiệp vụ của các trang thành một câu cố định.

Ba biến thể được phân theo mục đích:

| Biến thể | Dùng ở | Bố cục |
|---|---|---|
| `corporate` | Giới thiệu, trang thương hiệu/giải pháp cần hotline hoặc nhiều kênh liên hệ | Dải ảnh nền có phủ navy; thông điệp bên trái, liên hệ/CTA bên phải |
| `editorial` | Kiến thức, danh sách dự án và trang chi tiết dự án | Dải ảnh nền rộng; tiêu đề/mô tả và điểm tin cậy bên trái, hotline/CTA gom bên phải |
| `catalog` | Danh mục sản phẩm và thương hiệu | Dải ảnh nền thấp gọn; thông điệp ngắn và một CTA chính trên cùng hàng |

Desktop dùng khung chiều rộng phù hợp composition; dưới 760px các biến thể chuyển
thành một cột, giữ thứ tự nội dung rồi đến thao tác. Khi chọn dạng ảnh nền, ưu
tiên ảnh CMS phù hợp ngữ cảnh; nếu field ảnh trống hoặc
ảnh CMS không tải được, `PublicCtaBanner.vue` dùng ảnh WebP dự phòng theo biến
thể (`cta-corporate-bg.webp`, `cta-editorial-bg.webp` hoặc `cta-catalog-bg.webp`).
Ảnh dự phòng chỉ tạo không khí thị giác, không dùng làm bằng chứng dự án, đội ngũ,
chứng nhận hay năng lực thực tế. Nút hành động dùng gradient vàng chuẩn; đường dẫn
gọi, Zalo và mở form giữ theo DTO. CTA dùng radius button token hiện hành;
treatment surface/viền theo ngữ cảnh và không được làm giảm contrast.

## 10. Anti-pattern bị cấm

- Sửa riêng từng trang bằng margin/padding ngẫu nhiên để “nhìn vừa” một màn hình.
- Có nhiều `max-width` cho cùng cấp container hoặc gutter owner lồng nhau.
- Dùng `min-height`/spacer rỗng để cột trái cao bằng form cột phải.
- Thu font, crop ảnh sai tỷ lệ hoặc chèn `<br>` cứng để chữa overflow.
- Dùng token radius/elevation theo vai trò sai mục đích; xếp nhiều lớp card,
  glass hoặc shadow mà không giúp định hướng, đọc hoặc thao tác.
- Hardcode màu gần giống token; trộn icon; dùng emoji làm icon giao diện.
- `!important` mới mà không ghi cascade owner và lý do.
- Chép nội dung marketing có thể thay đổi vào PHP/CSS/ảnh.
- Chỉ test 1366px hoặc chỉ nhìn screenshot mà không đo bounding box/overflow.

## 11. Definition of Done cho thay đổi UI/UX

Một thay đổi chỉ hoàn tất khi:

1. xác định route/source/template, component owner và dữ liệu Admin liên quan;
2. đối chiếu current design language và các hợp đồng trong tài liệu này; composition riêng theo route được khuyến khích khi phục vụ user task;
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

## 12. Snapshot lịch sử về implementation (2026-10-31)

Các dòng dưới đây ghi lại nhận định tại ngày lập tài liệu, không phải chuẩn thị
giác hiện tại và không được dùng để yêu cầu redesign mới quay lại bề mặt phẳng,
radius 2–5px hoặc bỏ motion. Dùng chúng làm evidence lịch sử khi rà soát đúng
thời điểm; trước thay đổi hiện tại hãy kiểm tra source/QA mới.

| Drift đã biết | Chuẩn | Hướng xử lý |
|---|---|---|
| `theme-v2.css` có radius/pill/shadow nhiều mức | kiểm tra so với token theo vai trò hiện hành | chỉ sửa khi còn lệch visual language/QA mới |
| Token cũ `--pts-radius:12px` còn được dùng | đối chiếu semantics với token hiện hành | migrate khi có consumer drift thực tế |
| Breadcrumb legacy có nhiều cách render | shared breadcrumb semantics và treatment hiện hành | gom khi có lỗi/đợt đồng bộ component phù hợp |
| Project card đang dùng 8:5 trong media CSS | dự án 4:3 | xác minh dữ liệu/crop rồi đổi cùng test |
| Một số hub hardcode màu gần token | chỉ token shared | audit và thay theo component owner |
| Cascade còn nhiều `!important` | owner rõ, specificity thấp | giảm dần, không cleanup ngoài task |
| Frontend CSS có nhiều thế hệ | shared layer thắng có chủ đích | không xóa legacy trước khi đo coverage |

### Lộ trình hội tụ an toàn

1. **Chốt hướng:** dùng owner brief và current design language; giữ các data/SEO/accessibility contract của tài liệu này.
2. **Inventory:** lập danh sách component/selector theo route và trạng thái.
3. **Token semantic:** dùng radius/elevation/motion token hiện hành; chỉ thêm token khi role mới không biểu diễn được.
4. **Shared component:** ưu tiên semantics/API và ổn định behavior; page composition vẫn được thiết kế theo từng route.
5. **Migrate theo lát dọc:** mỗi đợt gồm component + các route dùng nó + test.
6. **Xóa legacy cuối cùng:** chỉ khi chứng minh không còn consumer bằng search,
   runtime và visual regression.

## 13. Quy trình thay đổi chuẩn

- Sửa implementation để khớp owner direction/current design language và các hợp
  đồng data/SEO/accessibility: cập nhật code/test, không cần ADR mới.
- Thay đổi hướng thiết kế shared, IA, media contract, container, component shared
  hoặc Admin ownership: cần owner decision và cập nhật ADR/tài liệu liên quan.
- Kicker, motif, surface, radius hay motion không phải “ngoại lệ” chỉ vì khác
  implementation cũ; phải hợp token, task, content và accessibility.
- Rollback UI thường là bỏ stylesheet/rule/component mới và khôi phục test tương
  ứng; không xóa asset/data Admin khi rollback presentation.
