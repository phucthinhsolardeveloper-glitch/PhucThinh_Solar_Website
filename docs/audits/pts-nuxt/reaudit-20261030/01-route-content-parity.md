# Re-audit route và content parity: Nuxt so với PHP

> **Cập nhật remediation 2026-10-30:** F-02 trạng thái runtime BLOCKED trong phần dưới là snapshot source-only trước QA; route families đã được runtime audit tại report 04. Route tuyển dụng đã có page Nuxt HTTP 200 và noindex đúng khi danh mục chưa có bài. F-03 redirect alias đã được gọi theo sitemap, canonical target 200. Xem [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md).

- Ngày: 2026-10-30
- Phạm vi: inventory route/template public PHP và mapping page/component/API Nuxt; so sánh cấu trúc nội dung theo mã nguồn.
- Cách kiểm tra: đọc trực tiếp router, resolver, template PHP, Nuxt pages/components/server routes và hợp đồng UI/UX. Không dùng báo cáo audit cũ làm bằng chứng.
- Giới hạn: đây là kiểm tra tĩnh. Không mở runtime PHP/Nuxt, không gọi API production/local, không chụp màn hình hay so sánh dữ liệu CMS đang chạy. `PASS` chỉ áp dụng cho mapping có bằng chứng mã nguồn, không hàm ý visual/runtime parity.
- Trạng thái workspace lúc bắt đầu: có thay đổi chưa commit ở một số file khác; audit không sửa hoặc stage chúng. File này là artifact duy nhất được tạo trong phạm vi.

## Tóm tắt

Mã PHP định tuyến các trang public chính qua `libraries/router.php` và `templates/index.php`; các template chuyên biệt nằm dưới `templates/{product,news,about,daily,contact,index,order,...}`. Nuxt đã có page/component và API migration cho phần lớn nhóm route. Một số URL là redirect/alias có chủ đích; một số route public hiện render component đặt tên `Migration...PreviewPage`, vì vậy cần kiểm tra route middleware/config và runtime trước khi xem đó là bản thay thế production hoàn chỉnh.

Audit ban đầu chưa xác nhận runtime cho homepage. Đối chiếu bổ sung trên PHP/Nuxt local QA cho thấy section chiếu sáng PHP được biểu diễn bởi `HomeCampaigns` với cùng hợp đồng CMS/fallback, CTA, thứ tự và điều kiện hiển thị. Composition Nuxt khác tên/component nhưng không thiếu section này. Trang chủ vẫn cần review tổng thể vì hero typography/layout khác; riêng finding F-01 đã đóng.

## Route matrix

| Route/nhóm public | PHP source/template trực tiếp | Nuxt page/component/API | Kết quả tĩnh |
|---|---|---|---|
| `/` | `sources/index.php` → `templates/index/index_tpl.php` | `app/pages/index.vue` → `HomePageContent.vue`; `/_migration/home`, `/_migration/site` | PARTIAL tổng thể; section chiếu sáng PASS theo đối chiếu runtime local QA ở F-01 |
| `/san-pham/` | `sources/product.php` → `templates/product/product_tpl.php` (trang landing danh mục) | `app/pages/san-pham/index.vue` → `CatalogPage.vue` → `/_migration/catalog` | PARTIAL: mapping hiện diện; chưa kiểm tra nội dung/filters/dữ liệu live |
| `/danh-muc-san-pham/{path}/` | `sources/product.php` → `templates/product/product_tpl.php` / `product_list_tpl.php`; router hỗ trợ hierarchy 2–4 cấp | `app/pages/danh-muc-san-pham/[...slug].vue` → `CatalogPage.vue`; catalog API | PARTIAL: route hierarchy được xử lý; chưa kiểm tra mọi cấp/category query |
| `/san-pham/{slug}/` | `sources/product.php` → `templates/product/product_detail_tpl.php` | `app/pages/san-pham/[slug].vue` → `ProductPreviewPage.vue`; product detail + extras APIs | PARTIAL: các vùng chi tiết tách component; runtime/product fixture chưa so sánh |
| `/product/{slug}` và `/product/` | PHP 301 alias trong `libraries/router.php` | `app/pages/product/[slug].vue`, product alias middleware; route root có alias trong `[slug].vue` | PASS (mapping): alias được xử lý; không xác nhận HTTP status/runtime |
| `/giai-phap/` | `sources/news.php` với type giải pháp → `templates/news/service_list_tpl.php` | `app/pages/giai-phap.vue`; `SolutionsIntro`, `SolutionCards`, `SolutionsEquipment`, `SolutionsCta`, `EditorialRichText`; `/_migration/solutions` | PARTIAL: hub/API/editorial được nối; nội dung CMS và CTA chưa chạy |
| `/giai-phap/{slug}/` | `sources/news.php` → `templates/news/service_detail_tpl.php` | `app/pages/giai-phap/[slug].vue` → migration solution detail page/API | PARTIAL: route/page/API mapping hiện diện; chưa so sánh dữ liệu và rendering |
| danh mục giải pháp bare slug | PHP resolver `requick` và canonical redirect | `app/pages/[slug].vue` → resolver + `SolutionCategoryPage.vue` | PARTIAL: resolver/component có trong mã; chưa thực thi slug thật |
| `/du-an/` | PHP type `du-an` qua `sources/news.php` → `templates/news/project_tpl.php` | `app/pages/du-an/index.vue` 301 đến `/du-an-da-thuc-hien/` | N/A cho route cũ: redirect thể hiện chủ ý canonical hóa; route đích cần runtime verify |
| `/du-an-da-thuc-hien/`, category | PHP project listing và category qua `sources/news.php` → `templates/news/project_tpl.php` | `app/pages/du-an-da-thuc-hien/index.vue` → migration projects preview; bare slug → `ProjectCategoryPage.vue`; projects API | PARTIAL: route/list/category mapping hiện diện; chưa kiểm tra filter/pagination/real data |
| project detail | PHP `templates/news/project_detail_tpl.php` | `[slug].vue` → migration project detail; project detail API | PARTIAL: resolver/API mapping hiện diện; chưa so sánh media/related data |
| `/tin-tuc/` và category | `sources/news.php` → `templates/news/news_tpl.php` | `app/pages/tin-tuc/index.vue` → migration news preview; bare slug → `NewsCategoryPage.vue`; news API | PARTIAL: page/category mapping hiện diện; chưa đối chiếu CMS và lọc |
| news detail | PHP `templates/news/news_detail_tpl.php` | `[slug].vue` → migration news detail; news detail API | PARTIAL: resolver/API mapping hiện diện; chưa kiểm tra liên quan/CTA/media thực tế |
| `/gioi-thieu/` | `sources/about.php` → `templates/about/about_tpl.php` | `app/pages/gioi-thieu.vue` (cần xem nội dung import tại route khi runtime); about API/`AboutPreviewPage.vue` | PARTIAL: API/component tồn tại; page mapping/data runtime chưa xác nhận |
| `/ve-chung-toi/` | Router PHP redirect 301 sang `/gioi-thieu/` | `app/pages/ve-chung-toi.vue` redirect 301 sang `/gioi-thieu/`; `[slug].vue` cũng alias | PASS (mapping): canonical redirect cùng đích trong mã |
| `/dai-ly/` | `sources/daily.php` → `templates/daily/daily_tpl.php` | `app/pages/dai-ly.vue` → `DealerPageContent.vue`; dealer API | PARTIAL: intro/overview/form component và API có; không xác nhận trường hợp dữ liệu rỗng/đầy đủ |
| `/he-thong-dai-ly/` | Router PHP 301 sang `/lien-he/` | `[slug].vue` alias `/he-thong-dai-ly` sang contact | PASS (mapping): alias cùng đích trong mã |
| `/lien-he/` | `sources/contact.php` → `templates/contact/contact_tpl.php` | `app/pages/lien-he.vue` → `ContactPageContent.vue`; contact API | PARTIAL: hero/form/locations có component; dữ liệu và submit chưa runtime verify |
| `/thu-vien-anh/` và album detail | `sources/product.php`/album route và album templates | `app/pages/thu-vien-anh/index.vue` → migration gallery preview; bare slug → gallery detail | PARTIAL: index/detail route mapping hiện diện; nội dung/media chưa đối chiếu |
| `/video/` | `sources/video.php` → `templates/video/video_tpl.php` | `app/pages/video/index.vue` → migration video preview; video API | PARTIAL: route/API mapping hiện diện; chưa xác minh danh sách/media thực |
| `/tim-kiem/` | search route/template từ router/source | `app/pages/tim-kiem/index.vue` → search results component/API | PARTIAL: route mapping; chưa xác minh query/filter behavior |
| `/thuong-hieu/` và brand detail | product brand route qua `sources/product.php` | `app/pages/thuong-hieu/index.vue`, `[slug].vue` → brand/catalog components + API | PARTIAL: mapping có; chưa đối chiếu PHP brand template với dữ liệu |
| `/chinh-sach/{slug}/`, `/ho-tro/{slug}/` | bài viết qua PHP news resolver/template | `app/pages/chinh-sach/[slug].vue` redirect; `ho-tro/[slug].vue` → policy preview/API | PARTIAL: canonical path + API mapping; chưa kiểm tra nội dung policy live |
| `/gio-hang/`, `/thanh-toan/`, `/cam-on/` | `sources/order.php`, `sources/thankyou.php` → order templates | Nuxt cart/checkout/thanks pages, cart/checkout/order APIs | PARTIAL: ngoài nhóm nội dung editorial; cần commerce smoke test riêng để xác nhận parity |

## Findings

### F-01 — Homepage: section chiếu sáng PHP được ánh xạ vào HomeCampaigns của Nuxt

- ID: F-01
- Mức độ: P2
- Route: `/`
- Trạng thái: PASS (đối chiếu runtime local QA, 30/10/2026)
- Release blocker: NO
- PHP kỳ vọng: homepage có section riêng `pts-home-lighting` tại `templates/index/index_tpl.php:213-237`, ngoài danh mục (`174-209`) và các section giải pháp/quy trình/đại lý/about/contact (`241-379`).
- Nuxt thực tế theo mã: `HomePageContent.vue:53-57` render `HomeCatalog`, `HomeCampaigns`, `HomeEditorial`, `HomeContact`; không có component tên lighting. `HomeCatalog.vue` chứa nhóm key `lighting` (xem `:11-16`, `:69-70`); `HomeCampaigns.vue:15-40` render các campaign theo ảnh/CTA. `HomeEditorial.vue` có solutions/process/dealer/about.
- Bằng chứng runtime: PHP `/api/pts_v1_home.php` và Nuxt `/_migration/home` trả cùng QA summary: 4 hero, 7 categories, 0 campaigns, `campaignsConfigured=0`, 4 solutions, 5 process và 3 product groups. Campaign dataset vắng mặt nên cả hai dùng cùng ba fallback theo cùng thứ tự: tên, mô tả, ảnh site-visuals và đích `/san-pham/`, `/giai-phap/`, `/san-pham/`. Khi có CMS record, PHP và adapter Nuxt cùng lọc record hiển thị, yêu cầu ảnh, giữ thứ tự `numb,id desc` và giới hạn ba item. Khi có record nhưng tất cả bị tắt, PHP ẩn section và Nuxt `campaignsConfigured` ngăn fallback/ẩn section. Cả hai render cùng heading/copy CTA “Tư vấn chọn đèn” tới `#yeu-cau-tu-van`; Nuxt còn chuyển intent `Đèn năng lượng mặt trời` qua state form.
- Nguyên nhân gốc: khác biệt composition có chủ ý: PHP dùng section `pts-home-lighting`; Nuxt tách thành `HomeCatalog` và `HomeCampaigns`. Dữ liệu/CTA campaign không bị mất trong local QA.
- Công việc còn lại: không cần thêm component hay duplicate section. Tổng thể home vẫn còn design deltas được ghi riêng ở report runtime (typography/layout), không thuộc F-01.
- Exact files: `domains/phucthinhsolar.com/public_html/templates/index/index_tpl.php:213-237`; `frontend/app/components/home/HomePageContent.vue:44-60`; `frontend/app/components/home/HomeCatalog.vue:11-16`; `frontend/app/components/home/HomeCampaigns.vue:15-40`; `frontend/app/components/home/HomeEditorial.vue:91-256`.

### F-02 — Runtime parity cho các route dùng migration preview chưa được chứng minh trong lần audit tĩnh

- ID: F-02
- Mức độ: P2
- Route: `/tin-tuc/`, `/du-an-da-thuc-hien/`, `/thu-vien-anh/`, `/video/`, `/ho-tro/{slug}/`, cùng detail routes tương ứng
- Trạng thái: BLOCKED (runtime evidence chưa được cung cấp/thu thập trong phạm vi read-only static audit)
- Release blocker: NO
- PHP kỳ vọng: các trang đi qua PHP source/template chuyên dụng; ví dụ news `templates/news/news_tpl.php`, project `project_tpl.php`/`project_detail_tpl.php`, album và video templates.
- Nuxt thực tế theo mã: index routes import component/page có tên `Migration...PreviewPage`; news `app/pages/tin-tuc/index.vue:1-8`, projects `app/pages/du-an-da-thuc-hien/index.vue:1-8`, gallery `app/pages/thu-vien-anh/index.vue:1-8`, video `app/pages/video/index.vue:1-8`, policy `app/pages/ho-tro/[slug].vue:1-8`. Server API tương ứng tồn tại trong `frontend/server/routes/_migration/`.
- Bằng chứng tái hiện: code paths xác nhận các route wrappers đang delegate sang migration preview components thay vì page component cùng tên route; tên gọi không chứng minh thiếu tính năng, nên không đánh FAIL chỉ từ naming.
- Nguyên nhân gốc: chưa xác lập. API và component đã có, nhưng chưa kiểm tra runtime response, hydration/error state, media, responsive, SEO, content density hoặc interaction.
- Công việc đề xuất: chạy page-family comparison trên cùng dữ liệu: listing + category + detail; xác nhận status/redirect, filters, pagination, related items, CTA, ảnh/video và fallback khi API trống/lỗi. Ghi screenshot hoặc DOM comparison theo thiết bị.
- Exact files: các Nuxt page wrappers nêu trên; API `frontend/server/routes/_migration/news.get.ts`, `projects.get.ts`, `gallery.get.ts`, `video.get.ts`, `policy-detail.get.ts`, `news-detail.get.ts`, `project-detail.get.ts`; PHP routing tại `domains/phucthinhsolar.com/public_html/libraries/router.php:271-318,372-386`; templates `templates/news/news_tpl.php`, `news_detail_tpl.php`, `project_tpl.php`, `project_detail_tpl.php`, `album/album_tpl.php`, `album/album_detail_tpl.php`, `video/video_tpl.php`.

### F-03 — Route `/ve-chung-toi/` là redirect alias, cần giữ nhất quán với mục tiêu URL legacy

- ID: F-03
- Mức độ: P3
- Route: `/ve-chung-toi/`
- Trạng thái: PASS (mapping), runtime HTTP status chưa verify
- Release blocker: NO
- PHP kỳ vọng theo code hiện tại: redirect 301 tới `/gioi-thieu/` (router mapping `legacy-about`).
- Nuxt thực tế: `frontend/app/pages/ve-chung-toi.vue:1-5` cũng redirect 301 tới `/gioi-thieu/`; root slug alias có mapping tương tự.
- Bằng chứng: PHP router và Nuxt redirect cùng canonical path. Tài liệu UI/UX hiện cũng ghi nhãn “Giới thiệu” cho `/ve-chung-toi/` nhưng đồng thời nói giữ URL public; cần diễn giải “giữ URL” là duy trì alias/SEO hay phục vụ trực tiếp cùng URL. Code PHP hiện hành đã redirect, không render trang tại URL cũ.
- Nguyên nhân gốc: sự khác biệt giữa wording tài liệu và route implementation PHP; chưa rõ ý định mới nhất nếu tài liệu yêu cầu phục vụ trực tiếp URL cũ.
- Công việc đề xuất: xác nhận với owner trong quyết định routing sau này; cho audit content parity, Nuxt khớp hành vi PHP hiện tại.
- Exact files: `domains/phucthinhsolar.com/public_html/libraries/router.php:43-45`; `frontend/app/pages/ve-chung-toi.vue:1-5`; `docs/02-development/public-ui-ux-design-system.md:52-57`.

## Phân biệt mức độ bằng chứng

### Đã xác minh bằng mã nguồn

- PHP router có route riêng cho product hierarchy 2–4 cấp, solution landing và các alias legacy; resolver chọn dữ liệu theo slug/type.
- PHP `templates/index.php` bọc template theo `$template`, còn source/template mapping cho các nhóm được định nghĩa trong `libraries/router.php`.
- Nuxt có page entry cho product catalog/PDP, solutions/detail, dealer, contact, projects, news, gallery, video, policy, search, brand, cart/checkout/thanks.
- Homepage Nuxt gắn các nhóm section qua component và `/_migration/home`; homepage PHP có section lighting riêng.
- Các đường dẫn alias được chỉ ra trong matrix có mapping static tương ứng.

### Chưa xác minh runtime

- HTTP status/redirect thực tế qua middleware, SSR và reverse proxy.
- Payload Admin/CMS thật, số lượng item, thứ tự, nội dung ẩn/hiện, fallback khi thiếu media.
- So sánh visual parity, responsive, accessibility, carousel/tab/filter/pagination, các CTA và form submit.
- Quan hệ related products/projects/news; canonical/meta/structured data được render trên từng route.
- Coverage mọi slug lịch sử và URL query/legacy.
- Commerce flow category → product → cart → checkout → confirmation.

## Phạm vi artifact và xác minh

- Tạo mới file báo cáo này; không thay đổi PHP/Nuxt product code, không sửa báo cáo audit khác, không stage/commit/merge/push.
- Không chạy test hay gọi môi trường production. Kết quả là audit mã nguồn tĩnh, không phải nghiệm thu giao diện/runtime.
