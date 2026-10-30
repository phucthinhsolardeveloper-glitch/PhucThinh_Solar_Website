# Ma trận parity PHP → Nuxt (inventory checkpoint 2026-10-27, base `07a50ea`)

> Inventory snapshot lịch sử. Current implementation checkpoint nằm trong
> [`NEXT-EXECUTION-PLAN.md`](NEXT-EXECUTION-PLAN.md); hàng F12 được cập nhật
> theo contract hiện tại nhưng visual gate vẫn chờ PHP preview hoạt động.

Đây là inventory theo code tại HEAD `07a50ea`; implementation checkpoint gần nhất
của preview là `ae811bb`. Các commit liên quan đã được đối chiếu gồm `c00aa0e`
cho filter/sort và `f0cf5ce` cho brand detail. “Preview” chỉ là route Nuxt nội
bộ có `noindex`; route public, canonical, redirect và production cutover vẫn
thuộc PHP cho tới khi có gate riêng. Dữ liệu được ghi theo code/contract hiện
tại, không suy diễn từ mock.

## Current code supplement — 2026-10-29

Các dòng F00–F22 dưới đây giữ nguyên tính lịch sử của inventory 2026-10-27.
Working tree tích hợp hiện đã bổ sung public Nuxt wrappers/middleware cho các
route nội dung và commerce trong manifest, canonical/OG/status helpers, category
hierarchy, brand root và các alias product; tất cả cờ public vẫn mặc định OFF.
Khi OFF, Nuxt chuyển hướng về PHP, còn account/Admin/API/media transformation
tiếp tục thuộc PHP. Đây là trạng thái source/build, không phải quyết định
cutover.

Build SSR cô lập ngày 2026-10-29 kiểm tra 26 route manifest khi cờ OFF; các
route có fallback đúng, gồm category path có slash và bare-slug details. Khi
bật cờ tạm ở process riêng, category và solution hub/detail trả 502/noindex khi
PHP API không listen, không render soft-200. `npm test` 263/263, typecheck và
build đều PASS. PHP/API ports 8080/8088 hiện đóng, nên live CMS/DB/SEO resolver,
media thật và visual review PHP↔Nuxt vẫn `BLOCKED`; không dùng smoke test này để
đổi status “PHP owner” hoặc tuyên bố public parity.

### Checkout handoff supplement — 2026-10-29

The F17 inventory row below remains a historical snapshot. Current source adds
an independent `checkoutPublicRouteEnabled` gate for Nuxt `/thanh-toan/`:
default OFF redirects to PHP `/gio-hang/`; when explicitly ON, the browser
submits a native URL-encoded POST directly to configured `phpPublicOrigin` at
`/gio-hang` (without a trailing slash). The form carries PHP's `thanhtoan`,
`form_csrf`, `checkout_token`, and `dataOrder[...]` names. Local
`Caddyfile.local-full-nuxt` has a POST-only matcher forwarding that path to PHP
when the Caddy listener is configured as the public origin. The Nitro
`POST /_migration/checkout` guard remains 410; order mutation is not proxied
through Nitro. PHP continues to own CSRF, idempotency, input/location/payment
validation, authoritative totals/shipping, order transaction, email, VNPay and
success redirect.

The no-trailing-slash POST path is required because `sources/order.php` emits
relative redirect locations (`gio-hang/`, `cam-on/?token=...`); posting to
`/gio-hang/` would nest those redirects under `/gio-hang/`. PHP validation
errors use session flash; the separate one-time `/_migration/checkout-notice`
GET adapter renders messages on Nuxt CartPage and carries allowlisted form
fields through memory-only Nuxt state to checkout. Browser and PHP origins must
use the same hostname for the PHP session cookie to accompany the direct form
POST, even when local dev uses different ports. Production route/flag ownership
is unchanged and the feature flag defaults OFF.

### Latest integration audit — 2026-10-29

- Integration HEAD `d17f828` contains all committed Lane UI and Batch B work;
  route wrapper/source smoke on the current build passed **26/26** with all
  public flags OFF. This confirms the PHP fallback first hop, not the final PHP
  response or visual parity.
- PHP canonical source now maps brand `idb` to `/thuong-hieu/{slug}/` and
  support/policy details to `/ho-tro/{slug}/`, matching the migration manifest.
  The regression script is present but still needs PHP CLI execution.
- GET audit found production `/product/` returning 404 while integration source
  has a 301 alias, and production brand/policy canonical values disagree with
  the route contract. These are deployment/source drift findings; production
  was not modified.
- Contact/Dealer retry, About CTA media, solution equipment media path and PHP
  bridge redirect handling have source fixes/tests. Full frontend suite is
  278/278; typecheck/build PASS. PHP/CMS/API ports remain offline, so browser
  visual review, PHP contracts and complete redirect/canonical crawl remain open.
- Public feature flags remain OFF. Account/Admin/API/media transforms and
  commerce authority remain PHP-owned; no production cutover was made.

| ID / route PHP | PHP owner | Nuxt hiện tại | Trạng thái dữ liệu | Chức năng còn thiếu / khác biệt | SEO / status | Rủi ro |
|---|---|---|---|---|---|---|
| F00 `/`, shell | `sources/allpage.php`, `templates/layout/*` | `SiteHeader`, `SiteFooter`, `SiteBreadcrumb`; `migration-shell-preview`, `migration-site-preview` | DTO site/navigation read-only; fallback an toàn khi upstream lỗi | Điều hướng nội bộ trong preview đã trỏ về route Nuxt tương ứng; public shell, canonical và cutover vẫn PHP | Public PHP 200; preview noindex | Cao |
| F01 `/` | `sources/index.php`, `templates/index/index_tpl.php` | `migration-home-preview`, `/_migration/home` | CMS thật qua `pts_v1_home`; form Nuxt gửi qua PHP adapter bảo vệ | Catalog/filter và SEO public chưa parity đầy đủ; chưa takeover public route | Public PHP SEO authority; preview noindex | Cao |
| F02 `/san-pham/` | `sources/product.php`, `templates/product/product_tpl.php` | `migration-catalog-preview`, `/_migration/catalog` | Published product/category, bounded paging, media đã kiểm tra file; brand/mức giá/loại sản phẩm filter + 6 tùy chọn sắp xếp đã nối đúng `productPriceFilterSql()`/whitelist sort (commit `c00aa0e`) | "Tồn kho" không phải dữ liệu công khai của PHP: đã rà `#_product` và các template liên quan, không bịa projection. Cây danh mục và filter đã gộp vào một panel `BỘ LỌC` (commit `ae811bb`); quote/cart vẫn dùng backend PHP. | Public PHP canonical/SEO; preview noindex | Cao |
| F03 `/san-pham/{slug}` | `sources/product.php`, `product_detail_tpl.php` | `migration-product-preview`, `/_migration/product-detail`, `/_migration/product-extras` | Published PDP, pricing/content/specs/media/documents/extras theo contract; `publicProductEntityPath()` và catalog test dùng canonical không có slash cuối | Preview đã có gallery lightbox/zoom, quote/cart, FAQ, video, warranty/policy, related, reviews, consultation, anchors và Product/Breadcrumb/FAQ JSON-LD; public SEO/cutover chưa ký | Public PHP route; preview noindex | Rất cao |
| F04 `/danh-muc-san-pham/*` | `libraries/router.php`, `product_category_urls.php` | `migration-catalog-preview?category=...` | Category chain có trong site/catalog DTO | Chưa có public Nuxt route riêng; alias, 301/404 và đầy đủ filter/breadcrumb vẫn cần gate | PHP canonical/redirect owner | Cao |
| F05 `/tim-kiem/` | `sources/search.php` | `migration-search-preview`, `/_migration/search` | Nuxt render kết quả theo search semantics legacy qua adapter | Public route/SEO và đầy đủ parity kết quả chưa cutover | PHP owner; preview noindex | Cao |
| F06 `/thuong-hieu/`, `/thuong-hieu/{slug}/` | `sources/product.php`, `libraries/router.php` (dùng chung template `product/product` với F02) | `migration-catalog-preview`, `migration-brand-detail-preview`, `/_migration/brand-detail` | Brand detail resolve theo slug, sản phẩm lọc `id_brand` tái dùng DTO/giá/sort, phân trang (`f0cf5ce`); Admin `#_seo` title/description/keywords và ảnh thương hiệu đi qua DTO chỉ cho SEO/social metadata, không thêm logo/nội dung hiển thị; root `/thuong-hieu/` vẫn là catalog tất cả sản phẩm vì không có `idb` | Không dựng brand landing riêng; manifest dùng slug bằng chứng `longi`; root giữ đúng behavior PHP | PHP Admin SEO; preview noindex | Trung bình |
| F07 `/giai-phap/` | `sources/solution_hub.php`, `sources/news.php` | `migration-solutions-preview`, `/_migration/solutions` | Published solutions, cards/equipment/CTA từ DTO; TOC sinh từ H2/H3 đã sanitize; Admin SEO hub/taxonomy có DTO và Nuxt meta fallback | Rich-content, full SEO/status and interaction parity vẫn cần gate; không public takeover | PHP public SEO; preview noindex | Cao |
| F08 `/giai-phap/{slug}/` | `sources/news.php`, `service_detail_tpl.php` | `migration-solution-detail-preview`, `/_migration/solution-detail` | Published solution, gallery/media allowlist, editorial preview | Public canonical/redirect/SEO và parity rich content vẫn deferred | PHP public canonical; preview noindex | Cao |
| F09 `/du-an-da-thuc-hien/` | `sources/project_hub.php`, `templates/news/project_tpl.php` | `migration-projects-preview`, `/_migration/projects` | Published projects, featured semantics, media tồn tại | Gallery/proof/related/SEO và public cutover chưa ký | PHP public SEO; preview noindex | Cao |
| F09D `/{slug}/` project detail | `sources/news.php`, `project_detail_tpl.php` | `migration-project-detail-preview`, `/_migration/project-detail` | Published project detail theo bare slug resolver, preview route và related navigation | Gallery/proof/rich content/SEO public parity còn cần xác minh | PHP public route; preview noindex | Cao |
| F10 `/tin-tuc/` | `sources/news_hub.php`, `news_tpl.php` | `migration-news-preview`, `/_migration/news` | Published news, category/search/pagination/featured contract | Public canonical/SEO và full interaction parity chưa ký | PHP public SEO; preview noindex | Cao |
| F10D `/{slug}/` bài viết | `sources/news.php`, `news_detail_tpl.php` | `migration-news-detail-preview`, `/_migration/news-detail` | Published `type=tin-tuc`, related, preview detail | Rich content, gallery/video, canonical/JSON-LD public parity deferred | PHP resolver/canonical owner; preview noindex | Cao |
| F11 `/gioi-thieu/` | `sources/about.php`, `about/about_tpl.php` | `migration-about-preview`, `/_migration/about` | Published named CMS sections, safe media/text | Timeline/CTA/form/SEO field parity cần xác minh | PHP public SEO; preview noindex | Trung bình |
| F12 `/dai-ly/` | `sources/daily.php`, `daily/daily_tpl.php` | `migration-dealer-preview`, `/_migration/dealer` | DTO tách intro `dai-ly-1.descvi`, overview `dai-ly-2`, sản phẩm và stats published; rich text đi qua sanitizer | Bố cục 2 cột/hero/nav/form đã đối chiếu CSS nguồn trong code; so ảnh cuối và dữ liệu trình duyệt chờ PHP preview `:8088`; submit vẫn qua PHP adapter giữ CSRF, validation, rate-limit, reCAPTCHA khi cấu hình và DB/email | PHP public SEO; preview noindex | Cao |
| F13 `/lien-he/` | `sources/contact.php`, `contact/contact_tpl.php` | `migration-contact-preview`, `/_migration/contact` | Public setting/location/visual; map embed Google Maps sinh từ CMS address | Form render Nuxt, submit qua PHP adapter giữ CSRF/rate-limit/reCAPTCHA khi cấu hình/DB-email; `mapUrl` CMS vẫn là external link | PHP public SEO; preview noindex | Cao |
| F14 `/ho-tro/*`, alias | `libraries/router.php`, `sources/news.php` | `migration-policy-preview?slug=...`, `/_migration/policy-detail` | Policy/support detail dùng nội dung CMS; ảnh cover của chính policy đi qua DTO an toàn và dùng cho OG/Twitter, không lấy ảnh từ bài liên quan | 301/404/410, alias và public canonical vẫn do PHP resolver; route preview giữ noindex | PHP redirect/canonical owner | Cao |
| F15 `/thu-vien-anh/`, `/video/`, gallery detail `/{slug}/` (`type=thu-vien-anh`) | `libraries/router.php`, `sources/product.php`, `sources/video.php` | `migration-gallery-preview`, `migration-gallery-detail-preview`, `migration-video-preview`; `/_migration/gallery*`, `/_migration/video` | Read-only PHP DTO/API, safe media allowlist, album lightbox và YouTube preview; hub SEO image đọc `#_seopage.photo` theo `type=thu-vien-anh`/`video`, kiểm tra file trong `upload/seopage`, và được ưu tiên cho OG/Twitter trước ảnh card; ảnh nội dung giữ nguyên | Public canonical/redirect, full aspect-ratio/embed/privacy/copyright parity và cutover vẫn chưa ký; không invent gallery detail slug hoặc gọi nhầm `/album/*` là route PHP | PHP owner; Nuxt preview only | Cao |
| F16 `/gio-hang/` | `sources/order.php`, `api/cart.php` | `migration-cart-preview`, `/_migration/cart` | GUI Nuxt dùng PHP session/calculation; cart mutation có adapter CSRF riêng | PHP vẫn là session/business authority; public route chưa cutover | PHP authority; preview noindex | Rất cao |
| F17 `/gio-hang/` POST checkout, `/cam-on/` GET thanks | `sources/order.php`, `sources/thankyou.php`, `router.php` | `migration-checkout-preview`, `migration-order-success`, các `/_migration/*` adapter | PHP không có public `/dat-hang/`: checkout mutation gửi POST tại `/gio-hang/` rồi redirect `cam-on/?token=...`; Nuxt chỉ preview GUI/read-only audit | CSRF, idempotency, shipping/payment callback và conversion vẫn giữ PHP; crawler không POST và không tạo đơn | PHP owner; preview noindex | Rất cao |
| F18 `/account/*` | `sources/user.php`, `templates/account/*` | Không migrate | PHP session/auth/order history | Không forward auth/cookie sang Nitro; password/account giữ PHP | PHP owner | Rất cao |
| F19 `/administrator/*` | `administrator/sources/*`, `administrator/templates/*` | Không migrate | CMS thật, không dùng trong QA parity | Không login Admin bằng harness; upload/CMS giữ PHP | PHP owner | Rất cao |
| F20 `/api/*` | `api/*.php` | Nitro adapters GET cho dữ liệu/session; mutation công khai đi trực tiếp tới PHP cùng origin | v1 DTO và adapter GET cho search, cart, lead form, review, policy, product extras; cart POST qua Nitro tới PHP giữ PHPSESSID; lead-form POST Nitro trả 405, review POST Nitro cũ trả 410; checkout preview chỉ GET, POST Nitro không forward tạo đơn | PHP endpoint/session/security vẫn là authority; mutation rate-limited giữ IP client qua cùng origin; không arbitrary proxy và chưa thay thế public API | PHP API owner; Nitro GET adapter private | Rất cao |
| F21 `/upload/*`, `/thumbs/*`, `/watermark/*` | `router.php`, `public_watermark.php` | Không migrate; preview media bridge có allowlist | Media local thật nếu path/extension/file hợp lệ | Transform/cache/signature/redirect parity chưa ký | PHP media owner | Cao |
| F22 sitemap/robots/alias/JSON-LD | `head.php`, `strucdata.php`, `sitemap.php`, `robots.txt`, resolver | PDP preview có Product/Breadcrumb/FAQ JSON-LD | PHP vẫn là metadata authority; JSON-LD preview không thay public canonical/OG/sitemap/robots/redirect | Crawler read-only ghi status/final URL/redirect chain/title/H1/canonical/robots/OG/JSON-LD/href và `/robots.txt` + `/sitemap.xml`; chưa cutover | PHP owner; preview noindex | Rất cao |

## Route evidence và quy ước status

- Checkpoint code/docs hiện tại: integration `ab6c3f4`; local hub:
  `http://localhost:3100/migration-hub-preview`.
- Validation đã ghi nhận ở checkpoint trước: 11 PHP file/library mới qua
  `php -l`, `npm test` 117/117, typecheck exit 0 không còn warning vue-router/Volar
  không fatal và production build PASS. PHP 8.1/Docker và full browser manifest
  không thuộc lane này.
- Preview GUI hiện bao phủ home, catalog, PDP, search, cart, checkout, order
  success, solutions/detail, projects/detail, news/detail, about, contact,
  dealer và policy/support detail. Đây không phải danh sách public route đã
  cutover.

- `data`: DTO PHP read-only đã có validator/adapter tương ứng; HTTP 200 với CMS
  local vẫn phải được chạy lại tại checkpoint tích hợp.
- `noindex`: chỉ áp dụng cho preview Nuxt. Không dùng preview 200 để tuyên bố
  public route parity.
- Visual QA dùng manifest `tools/visual-tests/fixtures/migration-routes.json`;
  mỗi route chạy cùng fixture/browser ở 390×844, 768×900 và 1440×900. Báo cáo
  tách `dynamic-data-difference`, `ui-difference`, `mixed-data-and-ui-difference`
  và `runtime-or-route-error` theo fingerprint heuristic, cần review ảnh trước
  khi ký.
- Các điểm phụ thuộc dữ liệu CMS có command kiểm chứng trong
  `SEO-CRAWL-GATES.md`; nếu slug fixture không tồn tại, report phải giữ nguyên
  404/needs-verification, không thay bằng dữ liệu bịa.
- Policy fixture local đã được đối chiếu từ `site.footer.policies` ngày
  2026-10-27; manifest dùng slug publish đầy đủ
  `chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat` thay cho slug rút gọn 404.

## Explicit pre-cutover gates

| Gate | PASS khi | Evidence bắt buộc |
|---|---|---|
| 301/302/307/308 aliases | Alias cũ có đúng status + destination canonical, không loop; route canonical cuối không còn redirect ngoài chủ đích | `redirectChain` trong `report.json`, các route `kind=alias` trong manifest |
| 404/410 / hidden | Slug thiếu/ẩn/retired trả đúng 404 hoặc 410, không soft-200 và không có canonical giả | Thêm route negative vào manifest rồi chạy cùng command; ghi status + final URL |
| Canonical ownership | PHP public route canonical đúng path/entity; Nuxt preview noindex và không được coi là public canonical owner | `canonical`, `robots`, `ownership`, `expectedCanonicalPath` |
| Robots / sitemap | `robots.txt` đúng status, không staging leakage, khai báo sitemap; mọi `<loc>` là canonical URL reachable | `resources.*.robots`, `resources.*.sitemap`, sau đó crawl sample `<loc>` |
| OG basic | PHP route có `og:title`, `og:description`, `og:url`, `og:image` phù hợp canonical; preview chỉ là evidence nội bộ | `page.og` hai phía; không pass chỉ vì HTTP 200 |
| JSON-LD | Type/entity URL hợp với page canonical; không bịa Product/Article/FAQ/Organization fields | `page.jsonLd.types` và review URL trong HTML evidence |
| Href mapping | Internal hrefs không trỏ 404; preview mapping được phân loại preview-vs-public, không so path mù quáng | `hrefMapping.onlyLegacy/onlyPreview` và status crawl follow-up |

Crawler command và scope chi tiết nằm tại `SEO-CRAWL-GATES.md`.

## Gaps trước cutover

- Chốt production route cutover/hybrid routing; giữ canonical public PHP cho tới
  khi có quyết định và rollback gate được phê duyệt.
- Source Nuxt đã có canonical/OG/status cho các public route có flag, nhưng cần
  crawl PHP thật để ký sitemap/robots/redirect/404/JSON-LD parity; Nuxt chưa là
  metadata owner và JSON-LD hiện mới có trên PDP preview.
- Catalog filter (loại sản phẩm/thương hiệu/mức giá), sort và panel danh mục đã
  nối thật (`c00aa0e`, `ae811bb`); "tồn kho" không phải gap thật (không có trên
  site PHP, xem F02).
  Brand detail `/thuong-hieu/{slug}/` đã có preview (`f0cf5ce`); còn lại
  gallery/video và account vẫn ngoài Nuxt (account giữ nguyên bên PHP theo
  thiết kế, xem F18, không phải việc cần làm). `/administrator/*`, `/account/*`,
  `/upload/*`, `/thumbs/*`, `/watermark/*` và `/api/*` là PHP-owned/excluded
  gates, không đưa vào crawler preview manifest.
- Hoàn tất visual/browser QA cuối trên 26 route ở 3 viewport khi PHP/API local
  hoạt động, rồi mới xét các production deployment gates.
