# Route / template / API matrix — PHP → Nuxt

**Audit date:** 2026-10-29  
**Source checkouts:** PHP `/home/admin01/src/Web_PTS_PHP` (`master@5f616c4`), Nuxt `/home/admin01/src/pts-nuxt-full-public-20261028` (`codex/full-nuxt-public-ui-20261028@aea5bc0`).  
**Scope:** public route ownership, PHP template mapping, API bridge, Nuxt page/component mapping and release status. This file is an audit artifact, not a deployment manifest.

## Status rules and sample size

- The PHP side has **18 public template families** in `templates/` used by the front controller: home, about, contact, dealer, product list/detail, brand/category resolution, news list/detail, project list/detail, solution list/detail, policy, album list/detail, video, account, cart/order and thanks.
- The Nuxt side has **23 canonical/alias page files** plus **27 `migration-*` preview/internal pages**. Preview pages are excluded from canonical coverage.
- The PHP API directory contains **35 endpoint files**. They are grouped below as read DTOs, media/session actions and business mutations. “PASS” means source mapping exists; runtime PASS still requires the Caddy smoke gate.

## Public route matrix

| Public URL family | PHP source/template | PHP API / action | Nuxt page | Main Nuxt components | Status | Evidence / gap |
|---|---|---|---|---|---|---|
| `/` | `sources/index.php`, `templates/index/index_tpl.php` | `pts_v1_home.php` | `app/pages/index.vue` | `home/*`, `SiteHeader`, `SiteFooter` | PASS (runtime pending) | PHP Home hero reads CMS `hero-carousel` with fallback (`sources/index.php:59-135`); Nuxt DTO exists. |
| `/gioi-thieu/` | `sources/about.php`, `templates/about/about_tpl.php` | `pts_v1_about.php` | `app/pages/gioi-thieu.vue` | `about/AboutPreviewPage` | PASS (runtime pending) | Canonical Nuxt page and API bridge present. |
| `/ve-chung-toi/` | PHP router alias to about | no separate API | `app/pages/ve-chung-toi.vue` | redirect | PASS | Alias keeps public URL while redirecting to `/gioi-thieu/`. |
| `/lien-he/` | `sources/contact.php`, `templates/contact/contact_tpl.php` | `pts_v1_contact.php`, `pts_v1_lead_form.php` | `app/pages/lien-he/index.vue` | `contact/*` | PASS with visual evidence | Contact PHP/Nuxt images under `frontend/.migration-artifacts/contact-parity-20261028/`; form mutation remains server adapter. |
| `/dai-ly/` | `sources/daily.php`, `templates/daily/daily_tpl.php` | `pts_v1_dealer.php`, lead form | `app/pages/dai-ly/index.vue` | `dealer/*` | PARTIAL | Source is CMS-driven. Runtime data discrepancy remains: API seen as `5/50/5000`, prior PHP evidence `5/41/4.012`; do not hardcode. |
| `/he-thong-dai-ly/` | legacy PHP route/alias | contact/dealer data | `app/pages/he-thong-dai-ly.vue` | redirect | PASS | Redirects to `/lien-he/`; verify SEO status through Caddy. |
| `/san-pham/` | `sources/product.php`, `templates/product/product_tpl.php` | `pts_v1_catalog.php` | `app/pages/san-pham/index.vue` | `catalog/*` | PASS with visual evidence | Catalog parity artifacts exist in `frontend/.migration-artifacts/catalog-parity-20261028/`. |
| `/danh-muc-san-pham/{list...}/` | `sources/product.php`, product hierarchy resolver | `pts_v1_catalog.php` | `app/pages/danh-muc-san-pham/[...slug].vue` | `CatalogPage`, category nav/FAQ/CTA | PASS with runtime gate | PHP supports 1–4 segment hierarchy; Nuxt validates slug chain. Must smoke real CMS category paths; invalid slug should be 404, not soft 200. |
| `/thuong-hieu/{slug}/` | product source/brand resolver | `pts_v1_brand_detail.php` | `app/pages/thuong-hieu/[slug].vue` | `BrandDetailPage` | PARTIAL | Route and contract exist; local integration report had no published brand fixture. |
| `/san-pham/{slug}/` | product source/detail template | `pts_v1_product_detail.php`, `pts_v1_product_extras.php`, reviews | `app/pages/san-pham/[slug].vue` | `product-preview/*` | PARTIAL | DTO and components exist; local integration report had no published product fixture. |
| `/product/` | PHP router legacy alias | no page render | `app/pages/product/index.vue` | 301 redirect | PASS | Router fix uses regex literal `@^product/?$`; Nuxt alias also redirects to `/san-pham/`. |
| `/product/{slug}` | PHP legacy product alias | product resolver | `app/pages/product/[slug].vue` | 404/redirect adapter | PARTIAL | Requires real slug resolver and 301 smoke through edge. |
| `/giai-phap/` | `sources/solution_hub.php`, `templates/news/service_list_tpl.php` | `pts_v1_solutions.php` | `app/pages/giai-phap.vue` | `solutions/*` | PASS (runtime pending) | Landing and DTO projection exist. |
| `/giai-phap/{slug}/` | `templates/news/service_detail_tpl.php` | `pts_v1_solution_detail.php` | `app/pages/giai-phap/[slug].vue` | `SolutionDetailPage`, editorial/TOC | PASS with visual evidence | Sticky desktop TOC evidence: `frontend/.migration-artifacts/solution-toc-php-desktop.png` and Nuxt counterpart. |
| `/du-an-da-thuc-hien/` | `sources/project_hub.php`, `templates/news/project_tpl.php` | `pts_v1_projects.php` | `app/pages/du-an-da-thuc-hien/index.vue` | `ProjectsFeatured`, `ProjectCard`, `ProjectsCta` | PARTIAL → compatibility fixed | Current PHP payload may omit `intro`; validator/template now accept old payload. Must re-run Caddy smoke with PHP DTO projection. |
| `/du-an/` | legacy PHP alias | projects DTO | `app/pages/du-an/index.vue` | 301 redirect | PASS source | Redirects to `/du-an-da-thuc-hien/`; follow-up status/header smoke required. |
| `/{project-slug}/` | PHP `news/project_detail_tpl.php` | `pts_v1_project_detail.php` | `app/pages/[slug].vue` | `ProjectDetailPage`, gallery/equipment/CTA | PARTIAL | Resolver and component exist; local integration report lacked published project detail fixture. |
| `/tin-tuc/` | `sources/news_hub.php`, `templates/news/news_tpl.php` | `pts_v1_news.php` | `app/pages/tin-tuc/index.vue` | `NewsFeatured`, `NewsCard`, `NewsCta` | PASS (runtime pending) | CMS list DTO and image cards are mapped. |
| `/{news-slug}/` | `templates/news/news_detail_tpl.php` | `pts_v1_news_detail.php` | `app/pages/[slug].vue` | `NewsDetailArticle`, `NewsDetailRelated`, `EditorialShare` | PARTIAL | Card-image related and share exist; no local published detail fixture in integration report. |
| `/chinh-sach/{slug}/` | PHP news policy route | `pts_v1_policy_detail.php` | `app/pages/chinh-sach/[slug].vue` | redirect | PASS | 301 adapter to `/ho-tro/{slug}/`. |
| `/ho-tro/{slug}/` | policy/news source/template | `pts_v1_policy_detail.php` | `app/pages/ho-tro/[slug].vue` | `EditorialRichText`, `EditorialShare`, TOC/related cards | PASS source | Date/author, H2/H3 TOC, share and related images are implemented; runtime fixture still required. |
| `/thu-vien-anh/` | `sources/product.php`/album path, `templates/album/album_tpl.php` | `pts_v1_gallery.php` | `app/pages/thu-vien-anh/index.vue` | gallery cards/empty state | PASS source | Empty copy is `Không tìm thấy kết quả`; runtime media smoke pending. |
| `/thu-vien-anh/{slug}/` | `templates/album/album_detail_tpl.php` | `pts_v1_gallery_detail.php` | `app/pages/[slug].vue` | lightbox/gallery detail | PARTIAL | Keyboard/lightbox code exists; no published album fixture in integration report. |
| `/video/` | `sources/video.php`, `templates/video/video_tpl.php` | `pts_v1_video.php` | `app/pages/video/index.vue` | video cards/empty state | PASS source | Empty copy is `Không tìm thấy kết quả`; runtime media smoke pending. |
| `/tim-kiem/` | `sources/search.php` | `pts_v1_search.php` | `app/pages/tim-kiem/index.vue` | `SearchResultsPage` | PASS source | Public search is non-indexable by runtime flag; API bridge and validation exist. |
| `/gio-hang/` | `sources/order.php`, `templates/order/order_tpl.php` | `pts_v1_cart.php`, `pts_v1_cart_mutate.php` | `app/pages/gio-hang/index.vue` | `CartPage`, cart composable | PARTIAL | Session/cart mutation remains high-risk; must run category → product → cart smoke. |
| `/thanh-toan/` | `sources/order.php` / checkout flow | `pts_v1_checkout.php`, wards, shipping, payment | `app/pages/thanh-toan.vue` | checkout components | PARTIAL | Nuxt adapter exists; must verify session, CSRF, wards, payment callback and no PHP HTML fallback. |
| `/cam-on/` | `sources/thankyou.php`, `templates/order/thanks_tpl.php` | `pts_v1_order_thanks.php` | `app/pages/cam-on/index.vue` | order thanks | PARTIAL | Requires real order/session state; non-indexable. |
| `/account/*` | `sources/user.php`, `templates/account/*_tpl.php` | `pts_v1_account.php` | `app/pages/account/index.vue`, `[action].vue` | account adapter/forms | PARTIAL | Route coverage exists; requires authenticated session/CSRF smoke and logout action through PHP. |
| `/administrator/*` | PHP Admin templates | PHP session/Admin | none | PHP-owned | N/A | Explicit PHP exception in Caddy; not part of Nuxt public frontend migration. |
| `/api/*`, `/upload/*`, `/thumbs/*`, `/watermark/*`, docs/media | PHP endpoints/files | PHP-owned | none | PHP backend/media | N/A | Explicit backend/media exceptions; must remain available after cutover. |

## API endpoint grouping

| Group | Endpoints | Owner after cutover | Audit status |
|---|---|---|---|
| Read-only public DTO | `pts_v1_site`, `home`, `about`, `contact`, `catalog`, `brand_detail`, `product_detail`, `product_extras`, `solutions`, `solution_detail`, `projects`, `project_detail`, `news`, `news_detail`, `policy_detail`, `gallery`, `gallery_detail`, `video`, `dealer`, `search`, `public_route_resolve` | PHP API called by Nuxt SSR | PASS source / runtime smoke pending |
| Session/account/cart/checkout | `pts_v1_account`, `cart`, `checkout`, `checkout_wards`, `shipping_quote`, `order_thanks` | Nuxt Nitro adapter → PHP business API | PARTIAL; commerce smoke required |
| Mutations | `pts_v1_cart_mutate`, `pts_v1_lead_form`, `review`, `comment`, `payment_vnpay`, legacy `cart.php`, `product.php` and related | PHP business layer via controlled adapter | PARTIAL; verify CSRF/session/redirect behavior |
| Media/utility | `thumbs`, `watermark`, `upload`, `robots`, `sitemap`, `district`, `ward`, `ward-city`, `config`, `addons`, `color`, `fillter_service`, `search_product`, `video` | PHP | N/A for Nuxt HTML; edge smoke required |

## PHP/CSS/JS ownership conclusion

1. PHP templates and legacy CSS/JS are still present and executable in the PHP checkout. This is expected for rollback and backend operation, but it means source-level “old frontend removed” is **not PASS**.
2. The cutover Caddy configuration explicitly prevents public requests from falling through to PHP templates or legacy CSS/JS. If deployed and verified, the runtime ownership becomes Nuxt for public HTML and PHP for API/Admin/media/actions.
3. Nuxt has no direct legacy CSS/JS imports. Any visual mismatch observed in current QA is therefore attributable to Nuxt token/layout/section implementation or data/media origin, not PHP CSS overriding the Nuxt document—provided traffic is actually through the cutover Caddy path.

## Release gate checklist

- [ ] Production-like Caddy/Compose config validated with one release checksum.
- [ ] Public route matrix above smoke-tested through one edge; record status and `Location` for aliases.
- [ ] All canonical pages return Nuxt HTML; `/_nuxt/*` assets return 200; PHP `assets/css/*` and `assets/js/*` are not requested by public HTML.
- [ ] API/media/Admin/session-action exceptions still return from PHP.
- [ ] Real CMS fixtures exist for at least one product, brand, solution, project, news, policy and gallery detail.
- [ ] Commerce flow passes category → product → cart → checkout → confirmation with CSRF/session/payment checks.
- [ ] Preview routes are noindex; canonical routes have canonical URLs and correct 404 behavior.
- [ ] Rollback candidate retains previous PHP/Nuxt image, Caddyfile and Compose; no DB/upload rollback is attempted.
