# 04 — Đối chiếu runtime, giao diện và responsive

> **Cập nhật sau remediation:** bảng URL trong file và TSV là snapshot ban đầu (1 route tuyển dụng handoff 307). Route đó hiện đã có Nuxt page và browser xác nhận 200/SEO noindex hợp lệ; các trạng thái finding khác đã được cập nhật tại [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md).

- Ngày: 2026-10-30
- Nhánh/checkout: `master`, HEAD lúc bắt đầu `e09f0bf`.
- PHP QA: `http://127.0.0.1:8080`; Nuxt QA: `http://127.0.0.1:3100`.
- Phạm vi: so sánh PHP cũ với Nuxt đang chạy ở local; chỉ đọc HTTP/DOM/CSS và ghi artifact audit. Không sửa product code, test suite, dữ liệu, production, commit hay deploy.
- Trạng thái source: Nuxt root có WIP chưa commit từ phiên khác; các ảnh/DOM Nuxt bên dưới phản ánh working tree đó, không khẳng định phản ánh riêng commit `e09f0bf`.
- Cách áp dụng Apple Design: rà hierarchy, typography, khoảng trắng, trạng thái, motion/accessibility và craft theo skill `apple-design`; xem đó là tiêu chí web UI, không áp quy tắc native Apple máy móc.

## 1. Kết quả kiểm tra trực tiếp

### 1.1 Toàn bộ URL trong sitemap PHP local

Đã đọc sitemap local PHP và gửi GET tới đủ **457 URL duy nhất** trên Nuxt, ghi kết quả thô tại [04-sitemap-route-results.tsv](04-sitemap-route-results.tsv).

| Kết quả Nuxt | Số URL | Kết luận |
|---|---:|---|
| HTTP 200 trực tiếp | 428 | Route render được trên Nuxt |
| HTTP 301 canonical/alias | 28 | Đích của cả 28 redirect đã được gọi tiếp và trả 200 trên Nuxt |
| HTTP 307 sang PHP | 1 | `/tin-tuyen-dung` chưa render bằng Nuxt; PHP vẫn trả trang tuyển dụng 200 |
| 404/5xx | 0 | Không thấy sitemap route trả lỗi cuối tại lượt kiểm tra này |

Đây là **baseline trước remediation**: 456/457 đường dẫn được Nuxt render trực tiếp hoặc redirect tới một trang Nuxt; `/tin-tuyen-dung` khi đó còn handoff PHP. Sau remediation, route này trả 200 từ Nuxt như ghi tại đầu báo cáo và trong `REMEDIATION-STATUS-20261030.md`. Không tính 28 alias là thiếu trang: các đích `/thuong-hieu/*/`, `/ho-tro/*/`, landing canonical và URL có slash đều trả 200.

`/tin-tuyen-dung` là khác biệt có bằng chứng: Nuxt trả 307 tới `http://127.0.0.1:8080/tin-tuyen-dung/`; PHP render title/H1 “Tin tuyển dụng”. `frontend/app/pages/[slug].vue` chủ động chuyển slug có disposition `legacy` về PHP. Runtime config Nuxt và Compose có default loopback/local origin (`frontend/nuxt.config.ts:190`, `docker-compose.nuxt-service.yml:22`). Đây là phần chưa chuyển đổi, đồng thời cần cấu hình origin trình duyệt có thể truy cập nếu tiếp tục dùng fallback ở môi trường ngoài máy local.

### 1.2 Ma trận viewport

Đã đo PHP và Nuxt ở 10 nhóm trang, 13 chiều rộng: **320, 360, 390, 414, 768, 1024, 1366, 1440, 1600, 1680, 1681, 1920, 2560px**. Tổng **260 lần đo** (10 route × 13 chiều rộng × 2 hệ), kết quả tại [05-responsive-results.tsv](05-responsive-results.tsv).

- Không thấy document/body horizontal overflow ở 260 lần đo; không thấy element nội dung thoát khỏi viewport ngoài vùng cuộn có chủ đích.
- Nuxt có đúng một H1 trên cả 10 route mẫu. PHP có 2 H1 trên `/du-an-da-thuc-hien/` và `/dai-ly/`; đây là cấu trúc legacy, Nuxt đang cải thiện.
- Điểm đo này xác nhận không tràn ngang ở route mẫu, không chứng minh mọi menu/dialog/touch state ở 457 URL đều đạt.

### 1.3 Trang và tính năng được đối chiếu runtime

| Khu vực | Kết quả | Bằng chứng và diễn giải |
|---|---|---|
| Trang chủ | PARTIAL | PHP và Nuxt đều có hero H1, slide/banner và các nhóm sản phẩm, chiếu sáng, giải pháp, quy trình, đại lý, giới thiệu, liên hệ. PHP có block `pts-home-lighting` riêng; Nuxt gom nội dung chiếu sáng vào composition catalog/campaign. Chưa chứng minh từng CTA/ảnh/thứ tự trong block PHP được giữ nguyên. Xem F-01 trong `01-route-content-parity.md`. Ảnh cùng route: [PHP](evidence/home-php-1440.png), [Nuxt](evidence/home-nuxt-1440.png). |
| Nguồn hero trang chủ | PASS tại QA | PHP `sources/index.php` lấy record `hero-carousel` từ CMS, dùng fallback đóng gói khi chưa có record; endpoint `api/pts_v1_home.php` và Nuxt `/_migration/home` cùng cấp payload. Local API trả 4 slide CMS đang bật; Nuxt dùng `HomeHero.vue`. Không phải hero chỉ hardcode. Cần nhớ đây là local QA data, không đọc production CMS. |
| Catalog `/san-pham/` | PASS trong mẫu | H1, hero/banner, danh sách nhóm sản phẩm, hỗ trợ chọn sản phẩm, footer hiện ở cả hai. CTA “Cần hỗ trợ chọn sản phẩm phù hợp?” có trong PHP và Nuxt. Ảnh: [PHP](evidence/category-php-1440.png), [Nuxt](evidence/category-nuxt-1440.png). |
| Danh mục `/danh-muc-san-pham/den-nlmt/` | PASS trong mẫu | Hai bên có hero, menu loại đèn, bộ lọc/sản phẩm, khối “Chọn đèn đúng nhu cầu”, FAQ và CTA cuối. Nuxt dùng wording “Chọn đèn đúng nhu cầu” thay nhãn khái quát “Ứng dụng phù hợp”; nội dung ứng dụng “Đường giao thông / Sân nhà & sân vườn / Khuôn viên & công trình” có render. Component render ứng dụng/FAQ theo data: `CatalogPage.vue:436-449`. |
| PDP | PASS nội dung mẫu; PARTIAL visual | Gallery chính, thumbnail, mô tả, điểm nổi bật, ứng dụng, thông số, video, bảo hành, FAQ, CTA và sản phẩm liên quan có mặt. Ở mẫu UFO-1000, cả PHP và Nuxt đều có 1 ảnh gallery chính; raw `img` tổng của page khác nhau do ảnh related/logo/icon và PHP có một số `src` trỏ lại URL trang, không được dùng raw img count làm bằng chứng thiếu gallery. Nuxt ẩn trường thông số không có dữ liệu thay cho placeholder PHP “Đang cập nhật” — thay đổi có chủ đích, không phải mất dữ liệu có thật. Visual: Nuxt thêm border/shadow nhẹ quanh gallery, ảnh nhìn nhỏ hơn trong khung; PHP gallery trần và thoáng hơn. Ảnh: [PHP](evidence/pdp-php-1440.png), [Nuxt](evidence/pdp-nuxt-1440.png). |
| Giải pháp detail | PASS / N/A cho tính năng mới | Nội dung, ảnh, section và CTA chính có ở cả hai. TOC sticky trái là cải tiến mới; runtime Nuxt: `position: sticky` tại 768/900/1440px, static tại 390px. API/CMS content hiện tại tạo danh sách mục. Đây không phải phần thiếu ở PHP cần parity. |
| Dự án listing | PASS trong mẫu | Cùng các dự án mẫu và CTA “Bạn có công trình cần tư vấn?”; Nuxt H1 rõ, PHP có hai node H1. Nuxt card hover khác style PHP; xem UI-04. |
| Dự án detail | PASS nội dung mẫu | “Dự án khác” cùng 3 tên dự án và CTA tư vấn xuất hiện ở cả hai. PHP trả một số gallery image `src` là URL detail hiện tại, trong khi Nuxt dùng đường dẫn ảnh upload hợp lệ; số lượng `img` tổng không biểu thị mất gallery. |
| News listing/detail | PASS nội dung mẫu; PARTIAL motion | Listing và detail render bài liên quan; news detail có share và nội dung bài viết/TOC. Nuxt detail đưa ảnh liên quan thật vào card; một số `img` PHP ở sample có URL nguồn trỏ lại URL detail. Không thấy feature “bài liên quan” hoặc share bị thiếu ở Nuxt. Hover card Nuxt mạnh hơn CSS final PHP. |
| Policy detail | PASS nội dung mẫu | Nuxt có ngày/tác giả, H1, TOC, share, tin liên quan; PHP có nội dung và tin liên quan nhưng page mẫu không có H1. Nuxt TOC sticky từ breakpoint `nav` (~1321px), static dưới đó; không phải yêu cầu sticky từ 768 như solution detail. |
| Đại lý | PARTIAL dữ liệu so với claim ngoài | Local PHP V1 API `api/pts_v1_dealer.php` trả CMS stats `5`, `50`, `5000`; `DealerPageContent.vue` lấy `/api/pts_v1_dealer.php` qua `/_migration/dealer`, component render `dealer.stats`, không hardcode các con số. Số `5+/41+/4.012+` được báo từ môi trường khác không tái hiện ở local API; PHP counter trên page local còn đứng `0+` khi chưa kích hoạt/quan sát animation. Chưa có production payload để kết luận 41/4.012 là số đúng hiện nay. Trạng thái: BLOCKED cho việc xác nhận production data; không ghi Nuxt sai trên bằng chứng local. |
| Gallery / Video empty state | PASS | Cả hai local page hiện đúng “Không tìm thấy kết quả”; không hiện copy “đang được cập nhật”. Dữ liệu gallery/video local đang rỗng. |
| Liên hệ | PASS nội dung; design delta | Form, hotline, địa chỉ và hero cùng hiện. Nuxt thêm H1 chung “Liên hệ Phúc Thịnh Solar” phía trên hero; PHP dùng thông điệp tư vấn làm H1 trong hero. Nuxt hero nền navy phẳng; PHP có gradient xanh/hero copy. Đây là khác biệt composition/type, không mất form/media. Ảnh: [PHP](evidence/contact-php-1440.png), [Nuxt](evidence/contact-nuxt-1440.png). |
| Search/cart/checkout | PARTIAL | Search và cart render 200 ở mẫu; checkout GET `/thanh-toán/` là route Nuxt mới/empty-cart state, PHP checkout cũ gắn với POST `/gio-hang`. Không lấy URL mới này để tuyên bố mất tính năng PHP. Luồng commerce và submit đơn chưa chạy trong lượt audit UI. |

## 2. Token, nền, chữ và khoảng cách

### 2.1 Màu nền: token không phải nguyên nhân chính của cảm giác thiếu navy

- `frontend/app/assets/css/tokens.css` **không chứa palette Tailwind chính**; file này chủ yếu reset box sizing/focus và có thêm CSS `.dark`. Tailwind v4 tokens nằm trong `frontend/app/assets/css/main.css` ở `@theme`.
- Bộ màu PTS nền tảng trùng PHP `design-tokens.css`: navy `#0a2251`, deep navy `#061537`, navy-light `#123a7a`; gold `#c9962e`, light `#e0b654`, dark `#9c7420`; muted `#64748b`; surface `#f8fafc`; line `#e5e7eb`.
- Nuxt có palette riêng `hdr-*` (`#c69a45`, `#b68732`, `#8a6526`, ink `#10243a`), song PHP `header-v3.css` cũng dùng bộ header riêng. Runtime header khớp với PHP; vấn đề là có hai nguồn palette và chưa có owner decision, không phải token navy/gold nền tảng bị đổi nhầm.
- Deep navy và CTA/footer đã được build, không thiếu class tổng quát: runtime `/san-pham/` có hero/support band/footer; `/du-an-da-thuc-hien/` có CTA band/footer; `/tin-tuc/` có CTA support/footer; `/lien-he/` có hero/footer. CSS computed values có `#061537` và `#0a2251` trên các khối tương ứng. Phần khác biệt là component cụ thể dùng nền đặc/gradient, chiều cao, padding và nội dung khác nhau.

### 2.2 Spacing/container

- Nuxt có `--container-page`, `--spacing-gutter:34px`, breakpoints và radius trong `main.css`; không thấy token semantic dùng chung cho section (`space-section`) hoặc scale tương đương toàn site. Vue components phối hợp Tailwind spacing mặc định và arbitrary px (ví dụ `py-8`, `py-10`, `p-[22px_18px]`, `pt-[15px]`). Đây là nguồn dễ làm nhịp section lệch, nhưng phải đánh giá theo vai trò cụ thể; không được coi mọi giá trị arbitrary là lỗi.
- PHP dùng `--pts-gutter:32px` rồi 34px/36px theo màn hình và `--pts-container` 1560/1680/1920px; Nuxt dùng gutter 12/16/24/34px và từ 1600px mở theo `min(calc(100vw - clamp(96px, 8vw, 220px)), 2200px)`. Tại 2560px Nuxt container tối đa 2200px, PHP 1920px; đây là thay đổi bố cục ultrawide rõ ràng theo hợp đồng Nuxt, không parity pixel.
- Hợp đồng UI đặt section spacing mục tiêu 64px desktop/48px mobile. Vì không có token semantic và chưa đo tất cả section pairs nên kết luận là PARTIAL, không báo tất cả section bị lệch.

### 2.3 Typography

Đo computed style trên browser local cho bốn route đại diện (`/`, `/tin-tuc/`, solution detail, contact):

| Đoạn | PHP | Nuxt | Ghi chú |
|---|---|---|---|
| body, 390px | 17px | 16px | Nuxt đạt ngưỡng tối thiểu UI contract, thấp hơn PHP 1px |
| body, 1440px | 18px | 16px | Khác 2px trên nền tảng desktop |
| body, 1920px | 20px | 16px | PHP có scale 2K, Nuxt chưa có scale body tương đương |
| Home H1, 390px | 40.95px | 29.25px | Hero text Nuxt nhỏ hơn đáng kể và canh khác |
| Home H1, 1440px | 50px | 54.72px | Nuxt lớn hơn |
| Home H1, 1920px | 62.4px | 72px | Nuxt lớn hơn |
| News H1, 1440px | 46.08px | 44px | Khác nhẹ |
| Solution H1, 1440px | 46.08px | 42px | Khác nhẹ |

PHP typography scale nằm ở `assets/css/typography-system.css:11-24,139-174`; Nuxt khai font Be Vietnam Pro self-host ở `main.css` nhưng không đặt body breakpoint scale tương tự. Nuxt font-face được browser xác nhận đã load trên route; font family source cùng tên. Đây là **visual redesign/type scale delta**, không chứng minh font sai; tuy nhiên nếu yêu cầu parity với PHP thì hiện lệch rõ ở body desktop và home hero. Không dùng cỡ chữ thay thế tùy tiện nếu mục tiêu mới là Apple-style redesign.

## 3. Motion, hover, icon và accessibility

### 3.1 Motion khác nhau có bằng chứng source

- PHP final project override tại `assets/css/project-hub.css:170-172` bỏ transform/shadow khi hover card; Nuxt `ProjectCard.vue` vẫn `hover:-translate-y-1.5 hover:shadow-md` và ảnh `scale-105`.
- PHP news final surface override `assets/css/flat-surface-system.css:157-158` bỏ card shadow/border; PHP image hover còn `scale(1.025)` ở `news-hub.css`. Nuxt `NewsCard.vue:40,57` nâng card `-translate-y-1` và scale ảnh `1.05` trong 500ms. Chưa chạy pointer hover browser ở lượt này; đây là khác biệt class/CSS đã xác minh, còn cảm nhận runtime hover cần nghiệm thu trực quan.
- Các hiệu ứng mới như PDP mouse zoom/lightbox transition (`ProductPreviewGallery.vue`), sticky header hide/reveal (`SiteHeader.vue`), TOC scrollspy và các gallery modal là phần nâng cấp; không coi việc không có animation tương đương PHP là feature bị mất.

### 3.2 Reduced motion

Nuxt global media query rút animation/transition còn `0.01ms` (`main.css:222-237`); HomeHero và PDP lightbox có logic `useReducedMotion`, FloatingContact có `motion-reduce:*`. Tuy nhiên header vẫn dùng transform hide/reveal và không có behavior riêng để giữ header ổn định khi người dùng bật Reduce Motion (`SiteHeader.vue:483`). Kết luận: được giảm duration nhưng chưa nhất quán về trạng thái ít chuyển động. Chưa bật OS reduced-motion trong browser ở lượt này.

### 3.3 Icon

PHP Font Awesome filled được thay bằng inline SVG/stroke ở nhiều Nuxt component; đây là đổi bộ icon chứ không phải mất action. Tuy vậy stroke/fill và size hiện do từng component quyết định. Chưa xác nhận lỗi glyph; ghi nhận là visual consistency cần review.

## 4. Findings mở

### UI-01 — P2 — Slug tuyển dụng vẫn rời Nuxt về PHP

- **Trạng thái:** FAIL (migration parity); **release blocker:** YES nếu gỡ PHP frontend.
- **Route:** `/tin-tuyen-dung/`.
- **Expected:** trang public PHP cũ tiếp tục hoạt động với nội dung tuyển dụng, nhưng sau chuyển đổi route phải được Nuxt render hoặc canonical redirect sang trang Nuxt tương đương.
- **Actual:** local PHP trả 200 và H1 “Tin tuyển dụng”; Nuxt sitemap request trả 307 tới PHP. Trên browser theo redirect vẫn thấy trang PHP, không phải Nuxt.
- **Bằng chứng:** `04-sitemap-route-results.tsv` row `/tin-tuyen-dung`; `frontend/app/pages/[slug].vue:66-73`; PHP output `domains/phucthinhsolar.com/public_html/templates/...` qua route dynamic; local page screenshot/response không cần thêm.
- **Root cause:** resolver phân loại slug này là `legacy`; chưa có Nuxt page/content component cho recruitment.
- **Đề xuất:** port route/template và API CMS hoặc quyết định canonical Nuxt đích nếu nội dung đã được chuyển. Trước cutover, kiểm tra `NUXT_PUBLIC_PHP_PUBLIC_ORIGIN`; default loopback không thể dùng làm URL cho browser từ máy người dùng.

### UI-02 — P2 — Typography nền và home hero lệch scale PHP

- **Trạng thái:** PARTIAL (design delta đã đo; chủ ý redesign chưa có decision record riêng); **release blocker:** NO.
- **Route/component:** toàn site, nổi nhất `/` và content listing/detail.
- **Expected vs actual:** PHP body 17/18/20px theo mobile/desktop/2K; Nuxt body 16px tại cả ba widths. H1 home tại 390px PHP 40.95px, Nuxt 29.25px; tại desktop/2K Nuxt lớn hơn PHP. Home title cũng chuyển từ trái sang giữa.
- **Bằng chứng:** browser computed style; ảnh home trong `evidence/`; PHP typography file `assets/css/typography-system.css:11-24,139-174`; Nuxt `frontend/app/assets/css/main.css` và `HomeHero.vue`.
- **Root cause:** Nuxt component dùng scale responsive khác, không carry over PHP global base/2K scale. Đây có thể là lựa chọn thiết kế mới, không phải dữ liệu thiếu.
- **Đề xuất:** chủ project duyệt hướng typography Apple redesign. Nếu cần parity, thiết lập scale theo role/viewport; nếu giữ redesign, ghi rõ đây là thay đổi chủ ý để không sửa ngược về PHP máy móc.

### UI-03 — P2 — Desktop navigation chỉ chuyển ngang từ 1681px

- **Trạng thái:** FAIL so với PHP tại desktop phổ biến; có thể là thay đổi responsive chủ ý trong WIP; **release blocker:** NO.
- **Route/component:** header toàn site, 1440–1680px.
- **Expected vs actual:** PHP 1440 hiển thị menu ngang; Nuxt tại 1440/1680 hiển thị nút hamburger và ẩn `Điều hướng chính`; nav ngang chỉ bật từ 1681px.
- **Bằng chứng:** screenshot `home-php-1440.png` và `home-nuxt-1440.png`; `SiteHeader.vue:504-505,539,612`; breakpoint comment trong `main.css` và các file docs đang WIP.
- **Root cause:** breakpoint Nuxt được đặt 1681px để nhóm logo/nav/actions không va chạm; nó khác hành vi PHP.
- **Đề xuất:** xác nhận hamburger trên desktop 1440–1680 là mục tiêu sản phẩm. Nếu không, hạ breakpoint sau khi đo toàn bộ nhóm header ở 1321–1680; không chỉ đổi breakpoint rồi để overlap.

### UI-04 — P2 — Hover card Nuxt nâng/đổ bóng mạnh hơn PHP final

- **Trạng thái:** FAIL visual-system parity ở hover; chưa browser-reproduced pointer state; **release blocker:** NO.
- **Khu vực:** news/project cards.
- **Expected vs actual:** PHP final override làm phẳng project card và tắt transform; news card bỏ surface/shadow và chỉ còn image zoom nhẹ. Nuxt project card nhấc 6px + shadow-md; news card nhấc 4px và ảnh scale 1.05/500ms.
- **Bằng chứng:** PHP `assets/css/project-hub.css:170-172`, `assets/css/flat-surface-system.css:157-158`, `assets/css/news-hub.css`; Nuxt `ProjectCard.vue` và `NewsCard.vue:40,57`.
- **Root cause:** effect được định nghĩa trong class component Nuxt, không ánh xạ theo PHP final override và design-system phẳng.
- **Đề xuất:** giữ hover dành cho affordance nhưng bỏ lift/shadow nơi PHP/system đã phẳng; thống nhất mức image zoom và duration.

### UI-05 — P2 — Homepage lighting section được biểu diễn qua HomeCampaigns

- **Trạng thái:** VERIFIED PASS — composition có chủ ý; **release blocker:** NO.
- **Route:** `/`.
- **Bằng chứng:** PHP `/api/pts_v1_home.php` và Nuxt `/_migration/home` trên local QA trả summary giống nhau: hero 4, categories 7, campaigns 0, `campaignsConfigured=0`, solutions 4, process 5, product groups 3; bảy category DTO giống nhau. Không có campaign CMS nên cả hai dùng cùng 3 fallback (name/description/image/link/order). Source PHP và Vue cũng cùng điều kiện: record có ảnh + bật `hienthi`, tối đa 3 theo thứ tự CMS; khi có record nhưng đều tắt thì ẩn section, không tự bật fallback. Heading, mô tả và CTA tư vấn cùng anchor; Nuxt thêm state intent cho form.
- **Kết luận:** đây là composition có chủ ý (`HomeCatalog` + `HomeCampaigns`), không thiếu section/copy/CTA theo bằng chứng local. Không tạo section trùng. Kết luận CMS fields dựa trên QA local và source mapping; chưa gọi production CMS.

### UI-06 — P3 — Tailwind spacing chưa có semantic scale đủ dùng chung

- **Trạng thái:** PARTIAL, source-confirmed; **release blocker:** NO.
- **Actual:** Nuxt `main.css` có gutter/container/radius, nhưng spacing các component dựa vào Tailwind mặc định/arbitrary px. PHP có gutter/container và component section CSS riêng; hai hệ khác nhau ở ultra-wide.
- **Bằng chứng:** `frontend/app/assets/css/main.css:160-206,288-310`; PHP `assets/css/theme-v2.css:14-23`; ví dụ `NewsCard.vue`, `SolutionsEquipment.vue`, `CatalogPage.vue`.
- **Đề xuất:** sau khi chủ project chốt typography/header, gom semantic spacing cho section/gutter trước; không token hóa mọi micro-gap nếu không cải thiện consistency.

### UI-07 — P3 — Palette header và palette PTS có hai owner

- **Trạng thái:** PARTIAL; runtime header khớp PHP; **release blocker:** NO.
- **Actual:** shared PTS tokens đúng nhau; thêm `hdr-*` palette song song. PHP cũng có `header-v3.css` dùng màu header riêng.
- **Bằng chứng:** PHP `assets/css/design-tokens.css:27-56`, `assets/css/header-v3.css`; Nuxt `frontend/app/assets/css/main.css:88-157`.
- **Đề xuất:** quyết định `hdr-*` là exception có tên hay hợp nhất vào token thương hiệu; không đổi riêng component trước khi có quyết định vì sẽ tạo color jump.

### UI-08 — P3 — Dark CSS latent trái với public light-only contract

- **Trạng thái:** PARTIAL, chưa tái hiện do không có switch public; **release blocker:** NO.
- **Actual:** `tokens.css:4-57` có `html.dark`, body navy, remap text/surface; config hiện fallback light và docs yêu cầu storefront light-only.
- **Đề xuất:** tắt/gate CSS `.dark` đến khi theme tối được duyệt và kiểm tra contrast toàn CMS, hoặc cập nhật contract nếu theme tối đã là mục tiêu.

### UI-09 — P3 — Reduced motion chưa xử lý trạng thái header

- **Trạng thái:** PARTIAL, source-confirmed; chưa browser-emulate OS preference; **release blocker:** NO.
- **Actual:** duration global 0.01ms nhưng transform hide/reveal header vẫn đổi trạng thái; component behavior không giống PHP header `transition:none`.
- **Bằng chứng:** Nuxt `main.css:222-237`, `SiteHeader.vue:483`; PHP `header-v3.css` reduced-motion rule; Apple skill `motion.md`/`accessibility.md`.
- **Đề xuất:** khi reduced motion, giữ header luôn hiện hoặc bỏ dịch chuyển; sau đó xác minh một lần bằng emulation.

### UI-10 — P3 — PDP báo CMS thiếu ảnh cả khi URL ảnh bị lỗi mạng

- **Trạng thái:** source-confirmed; khác biệt so với PHP error fallback chưa kiểm chứng; **release blocker:** NO.
- **Actual:** `ProductPreviewGallery.vue` gom “không có URL” và “tất cả URL lỗi tải” thành copy “CMS chưa có ảnh sản phẩm hợp lệ.”
- **Đề xuất:** tách trạng thái chưa cấu hình media với media URL hỏng; đưa hành động retry/đổi ảnh phù hợp. Không kết luận PHP tốt hơn khi chưa ép lỗi URL tương đương.

### UI-11 — P2 (legacy) — Race condition tải địa bàn trong checkout tồn tại ở cả hai hệ

- **Trạng thái:** source-confirmed legacy defect; không phải Nuxt regression; chưa runtime reproduce; **release blocker:** NO cho UI parity, nên sửa trước khi tin cậy báo phí.
- **Actual:** đổi Tỉnh/TP nhanh có thể để response Phường/Xã hoặc phí giao hàng cũ ghi đè lựa chọn mới ở cả PHP JS và Nuxt.
- **Bằng chứng:** report `03-functional.md` mục IA-01; Nuxt `frontend/app/pages/thanh-toan.vue:39-51,74-88`; PHP `assets/js/apps.js:1149-1156`, `assets/js/functions.js:587-605`.
- **Đề xuất:** request sequence/AbortController và chỉ nhận response nếu Tỉnh/TP/Phường/Xã còn khớp snapshot.

## 5. Đối chiếu với Apple Design skill

- **Hierarchy / Familiarity:** Nuxt giữ nhóm thông tin/route chính, nhưng header hamburger trên 1440–1680 thay đổi hành vi desktop mà PHP đã quen thuộc.
- **Typography / Legibility:** body desktop nhỏ hơn PHP; home H1 thay đổi mạnh theo viewport. Cần chủ ý duyệt theo nhịp đọc, không chỉ theo screenshot desktop.
- **Simplicity / Craft:** CTA navy/gold và navy footer đã có; không cần thêm surface/card để bù section thiếu. Project/news card hover hiện nhấc mạnh hơn contract phẳng.
- **Motion / Accessibility:** slider và PDP motion mới có reduced-motion xử lý một phần; header hide/reveal cần policy rõ.
- **References:** skill `apple-design/SKILL.md`; HIG notes `design-principles.md`, `layout.md`, `typography.md`, `accessibility.md`, `motion.md`, `buttons.md`, `images.md`, `cross-platform.md`.

## 6. Giới hạn và trạng thái Git

- Runtime là local PHP + local Nuxt với dataset QA; không truy cập production DB/CMS/browser, không thể xác nhận số liệu 5/41/4.012 đã nêu ở môi trường khác.
- Không thực hiện E2E form submit/checkout, hover pointer automation, lỗi mạng ảnh cưỡng bức, screen-reader test hoặc full API contract suite trong lượt này.
- Ngay từ đầu đã có WIP ngoài phạm vi ở `README.md`, `docs/02-development/*`, `docs/14-nuxt-migration/LOCAL-PREVIEW.md`, `frontend/app/assets/css/main.css`, `frontend/app/components/SiteHeader.vue`, `frontend/nuxt.config.ts`; giữ nguyên, không stage.
- Artifacts mới của lượt audit: các file trong `docs/audits/pts-nuxt/reaudit-20261030/`.
- Không thay đổi production state, database, backup hay rollback point.
