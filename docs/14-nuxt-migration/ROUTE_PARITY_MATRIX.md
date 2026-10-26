# Ma trận parity PHP → Nuxt (checkpoint 2026-10-26)

Đây là inventory tại `b5865ac` trên nhánh `feature/pts-nuxt4-migration`. “Preview”
chỉ là route Nuxt nội bộ có `noindex`; route public, canonical, redirect và form
vẫn thuộc PHP cho tới khi có gate riêng. Dữ liệu được ghi theo code/contract hiện
tại, không suy diễn từ mock.

| ID / route PHP | PHP owner | Nuxt hiện tại | Trạng thái dữ liệu | Chức năng còn thiếu / khác biệt | SEO / status | Rủi ro |
|---|---|---|---|---|---|---|
| F00 `/`, shell | `sources/allpage.php`, `templates/layout/*` | `SiteHeader`, `SiteFooter`, `SiteBreadcrumb`; `migration-shell-preview`, `migration-site-preview` | DTO site/navigation read-only; fallback rỗng khi upstream lỗi | Chưa ký menu grouping/logo/footer đầy đủ; search/cart/form vẫn PHP | Public PHP 200; preview noindex; chưa có canonical parity | Cao |
| F01 `/` | `sources/index.php`, `templates/index/index_tpl.php` | `migration-home-preview`, `/_migration/home` | CMS thật qua `pts_v1_home`; fallback không bịa content | Hero/sections/interactions và form chưa parity; chưa takeover public route | Public PHP SEO authority; preview noindex; JSON-LD chưa delegate | Cao |
| F02 `/san-pham/` | `sources/product.php`, `templates/product/product_tpl.php` | `migration-catalog-preview`, `/_migration/catalog` | Published product/category, bounded paging, media đã kiểm tra file | Chưa có giá/discount/stock, filter, merchandising sort, quote/cart | Public PHP canonical/SEO; preview noindex | Cao |
| F03 `/san-pham/{slug}/` | `sources/product.php`, `product_detail_tpl.php` | `migration-product-preview`, `/_migration/product-detail` | Published PDP, specs/media whitelist, canonical source map | Chưa có pricing, cart, reviews, related merchandising, document, CKEditor blocks, JSON-LD | Public PHP route; preview noindex | Rất cao |
| F04 `/danh-muc-san-pham/*` | `libraries/router.php`, `product_category_urls.php` | Chưa có public/preview category route riêng | Category chain có trong site/catalog DTO | Chưa chứng minh 1–4 level, alias, 301/404, filter/breadcrumb | PHP canonical/redirect owner | Cao |
| F05 `/tim-kiem/` | `sources/search.php` | Chưa có | PHP search/AJAX hiện hành | Chuẩn hóa query, noindex, reflected HTML và parity kết quả chưa có | PHP owner | Cao |
| F06 `/thuong-hieu/` | `sources/product.php` | Chưa có | UNKNOWN ngoài PHP hiện hành | Brand relation/paging chưa có contract | PHP owner | Trung bình |
| F07 `/giai-phap/` | `sources/solution_hub.php`, `sources/news.php` | `migration-solutions-preview`, `/_migration/solutions` | Published solutions, cards/equipment/CTA từ DTO | CKEditor/rich text, modal/gallery/interaction chưa parity; không public takeover | PHP public SEO; preview noindex | Cao |
| F08 `/giai-phap/{slug}/` | `sources/news.php`, `service_detail_tpl.php` | `migration-solution-detail-preview`, `/_migration/solution-detail` | Published solution, gallery/media allowlist, plain-text editorial | Rich HTML/TOC/image blocks, related/interaction/SEO JSON-LD deferred | PHP public canonical; preview noindex | Cao |
| F09 `/du-an-da-thuc-hien/` | `sources/project_hub.php`, `templates/news/project_tpl.php` | `migration-projects-preview`, `/_migration/projects` | Published projects, featured semantics, media tồn tại | Gallery/proof/related/SEO chưa đủ; CTA/form PHP | PHP public SEO; preview noindex | Cao |
| F09D `/du-an-{slug}/` | `sources/news.php`, `project_detail_tpl.php` | `migration-project-detail-preview`, `/_migration/project-detail` | Published project detail, plain-text preview | Gallery đầy đủ, proof fields, related, rich content chưa parity | PHP public route; preview noindex | Cao |
| F10 `/tin-tuc/` | `sources/news_hub.php`, `news_tpl.php` | `migration-news-preview`, `/_migration/news` | Published news, category/search/pagination/featured contract | Layout/filters tương tác, rich content, author/metadata chưa ký | PHP public SEO; preview noindex | Cao |
| F10D `/{slug}/` bài viết | `sources/news.php`, `news_detail_tpl.php` | `migration-news-detail-preview`, `/_migration/news-detail` | Published `type=tin-tuc`, related, safe plain text | CKEditor allowlist, view semantics, gallery/video, canonical/JSON-LD parity deferred | PHP resolver/canonical owner; preview noindex | Cao |
| F11 `/gioi-thieu/` | `sources/about.php`, `about/about_tpl.php` | `migration-about-preview`, `/_migration/about` | Published named CMS sections, safe media/text | Timeline/CTA/form/SEO field parity cần xác minh | PHP public SEO; preview noindex | Trung bình |
| F12 `/dai-ly/` | `sources/daily.php`, `daily/daily_tpl.php` | `migration-dealer-preview`, `/_migration/dealer` | Published dealer sections/hero/fallback semantics | POST `submit-newsletter2`, CSRF, reCAPTCHA và form luôn PHP | PHP public SEO; preview noindex | Cao |
| F13 `/lien-he/` | `sources/contact.php`, `contact/contact_tpl.php` | `migration-contact-preview`, `/_migration/contact` | Public setting/location/visual/map src allowlist | Form POST/upload/rate-limit/CSRF luôn PHP; map/layout parity chưa ký | PHP public SEO; preview noindex | Cao |
| F14 `/ho-tro/*`, alias | `libraries/router.php`, `sources/news.php` | Chưa có | UNKNOWN ngoài PHP resolver | 301/404/410 và content inventory chưa có | PHP redirect/canonical owner | Cao |
| F15 `/album/*`, `/video/*` | `sources/product.php`, `sources/video.php` | Chưa có | PHP media authority | Aspect ratio/embed/privacy/copyright chưa có contract | PHP owner | Cao |
| F16 `/gio-hang/` | `sources/order.php`, `api/cart.php` | Không migrate | PHP session/cart thật | Không tạo Nuxt cookie/session/cart thay thế | PHP, noindex theo legacy | Rất cao |
| F17 `/dat-hang/`, `/cam-on/` | `sources/order.php`, `sources/thankyou.php` | Không migrate | PHP order/payment/shipping | CSRF, idempotency, payment callback và conversion giữ PHP | PHP owner | Rất cao |
| F18 `/account/*` | `sources/user.php`, `templates/account/*` | Không migrate | PHP session/auth/order history | Không forward auth/cookie sang Nitro; password/account giữ PHP | PHP owner | Rất cao |
| F19 `/administrator/*` | `administrator/sources/*`, `administrator/templates/*` | Không migrate | CMS thật, không dùng trong QA parity | Không login Admin bằng harness; upload/CMS giữ PHP | PHP owner | Rất cao |
| F20 `/api/*` | `api/*.php` | Chỉ có Nitro read-only bridge nội bộ | v1 DTO GET-only cho các slice đã liệt kê | Existing AJAX/POST/HTML endpoints chưa thay thế; không arbitrary proxy | PHP API owner; Nitro preview adapter private | Rất cao |
| F21 `/upload/*`, `/thumbs/*`, `/watermark/*` | `router.php`, `public_watermark.php` | Không migrate; preview media bridge có allowlist | Media local thật nếu path/extension/file hợp lệ | Transform/cache/signature/redirect parity chưa ký | PHP media owner | Cao |
| F22 sitemap/robots/alias/JSON-LD | `head.php`, `strucdata.php`, `sitemap.php`, resolver | Không migrate | PHP metadata authority | Cần crawl diff, canonical/301/robots/JSON-LD gate trước cutover | PHP owner | Rất cao |

## Quy ước evidence

- `data`: DTO PHP read-only đã có validator/adapter tương ứng; HTTP 200 với CMS
  local vẫn phải được chạy lại tại checkpoint tích hợp.
- `noindex`: chỉ áp dụng cho preview Nuxt. Không dùng preview 200 để tuyên bố
  public route parity.
- Visual QA dùng manifest `tools/visual-tests/fixtures/migration-routes.json`;
  mỗi route chạy cùng fixture/browser ở 390×844, 768×900 và 1440×900. Báo cáo
  tách `dynamic-data-difference`, `ui-difference`, `mixed-data-and-ui-difference`
  và `runtime-or-route-error` theo fingerprint heuristic, cần review ảnh trước
  khi ký.
- Các ô `UNKNOWN` là điểm cần xác minh từ live PHP resolver/CMS, không phải đã
  đạt hoặc đã thất bại.
