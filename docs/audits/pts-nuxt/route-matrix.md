# Route / template / API matrix — PHP → Nuxt

**Audit date:** 2026-10-31

**Source checkout:** `/home/admin01/src/Web_PTS_PHP` (`master@57aef9d`). The known remote Nuxt feature branch is fully contained in this `master`; local `updateUI` also points to the same SHA and has no unique commits. Its second worktree contains ignored backup/build/dependency data and remains in place. `master` is 102 commits ahead of `origin/master`; no push was made.
**Scope:** public route ownership, PHP template mapping, API bridge, Nuxt page/component mapping and release status. This file is an audit artifact, not a deployment manifest.

## Status rules and sample size

- The PHP side has **18 public template families** in `templates/` used by the front controller: home, about, contact, dealer, product list/detail, brand/category resolution, news list/detail, project list/detail, solution list/detail, policy, album list/detail, video, account, cart/order and thanks.
- The Nuxt side has **23 canonical/alias page files** plus **27 `migration-*` preview/internal pages**. Preview pages are excluded from canonical coverage.
- The PHP API directory contains **35 endpoint files**. They are grouped below as read DTOs, media/session actions and business mutations. `PASS source` means mapping exists; `LOCAL QA` is loopback/local data evidence only. Neither status is production release evidence.

## Public route matrix

| Public URL family | PHP source/template | PHP API / action | Nuxt page | Main Nuxt components | Status | Evidence / gap |
|---|---|---|---|---|---|---|
| `/` | `sources/index.php`, `templates/index/index_tpl.php` | `pts_v1_home.php` | `app/pages/index.vue` | `home/*`, `SiteHeader`, `SiteFooter` | LOCAL QA | Root and site DTO returned 200 on the built Nuxt image through the loopback Caddy rehearsal; no production Caddy claim. |
| `/gioi-thieu/` | `sources/about.php`, `templates/about/about_tpl.php` | `pts_v1_about.php` | `app/pages/gioi-thieu.vue` | `about/AboutPreviewPage` | LOCAL QA | Included in the 34-route local Nuxt SSR crawl; legacy alias also exercised through loopback Caddy. |
| `/ve-chung-toi/` | PHP router alias to about | no separate API | `app/pages/ve-chung-toi.vue` | redirect | LOCAL QA | Loopback Caddy returned 301 to `/gioi-thieu/`. |
| `/lien-he/` | `sources/contact.php`, `templates/contact/contact_tpl.php` | `pts_v1_contact.php`, `pts_v1_lead_form.php` | `app/pages/lien-he/index.vue` | `contact/*` | PASS with visual evidence | Contact PHP/Nuxt images under `frontend/.migration-artifacts/contact-parity-20261028/`; form mutation remains server adapter. |
| `/dai-ly/` | `sources/daily.php`, `templates/daily/daily_tpl.php` | `pts_v1_dealer.php`, lead form | `app/pages/dai-ly/index.vue` | `dealer/*` | PARTIAL | Source is CMS-driven. Runtime data discrepancy remains: API seen as `5/50/5000`, prior PHP evidence `5/41/4.012`; do not hardcode. |
| `/he-thong-dai-ly/` | legacy PHP route/alias | contact/dealer data | `app/pages/he-thong-dai-ly.vue` | redirect | PASS | Redirects to `/lien-he/`; verify SEO status through Caddy. |
| `/san-pham/` | `sources/product.php`, `templates/product/product_tpl.php` | `pts_v1_catalog.php` | `app/pages/san-pham/index.vue` | `catalog/*` | PASS with visual evidence | Catalog parity artifacts exist in `frontend/.migration-artifacts/catalog-parity-20261028/`. |
| `/danh-muc-san-pham/{list...}/` | `sources/product.php`, product hierarchy resolver | `pts_v1_catalog.php` | `app/pages/danh-muc-san-pham/[...slug].vue` | `CatalogPage`, category nav/FAQ/CTA | PASS with runtime gate | PHP supports 1–4 segment hierarchy; Nuxt validates slug chain. Must smoke real CMS category paths; invalid slug should be 404, not soft 200. |
| `/thuong-hieu/{slug}/` | product source/brand resolver | `pts_v1_brand_detail.php` | `app/pages/thuong-hieu/[slug].vue` | `BrandDetailPage` | LOCAL QA | 11 current CMS brands returned detail DTOs and passed SSR/H1/canonical checks through the built image and loopback Caddy. Production edge remains unverified. |
| `/san-pham/{slug}/` | product source/detail template | `pts_v1_product_detail.php`, `pts_v1_product_extras.php`, reviews | `app/pages/san-pham/[slug].vue` | `product-preview/*` | LOCAL QA | Current Nuxt dev + PHP API bridge returned 200 for JINDIAN JD-18300 (300W), with product H1 and canonical path; prior QA also exercised category → PDP at mobile/desktop widths. Cart/checkout mutations remain unverified. |
| `/product/` | PHP router legacy alias | no page render | `app/pages/product/index.vue` | 301 redirect | LOCAL QA | Loopback Caddy returned 301 to `/san-pham/`. |
| `/product/{slug}` | PHP legacy product alias | product resolver | `app/pages/product/[slug].vue` | 404/redirect adapter | LOCAL QA (Nuxt; edge pending) | Current published PDP resolves and 301s to `/san-pham/{slug}`; an unknown slug returns 404. Production-like edge behavior remains unverified. |
| `/giai-phap/` | `sources/solution_hub.php`, `templates/news/service_list_tpl.php` | `pts_v1_solutions.php` | `app/pages/giai-phap.vue` | `solutions/*` | PASS (runtime pending) | Landing and DTO projection exist. |
| `/giai-phap/{slug}/` | `templates/news/service_detail_tpl.php` | `pts_v1_solution_detail.php` | `app/pages/giai-phap/[slug].vue` | `SolutionDetailPage`, editorial/TOC | LOCAL QA | Current `:8088` PHP DTO and Nuxt `:3100` adapter return 200 with PHP-sanitized HTML; SSR returns 200/noindex, one H1, and 27 TOC links for the family-solar fixture. 5 published CMS solutions previously passed built-image SSR/H1/canonical checks. CKEditor visual parity remains open; prior sticky-TOC screenshots are historical. |
| `/du-an-da-thuc-hien/` | `sources/project_hub.php`, `templates/news/project_tpl.php` | `pts_v1_projects.php` | `app/pages/du-an-da-thuc-hien/index.vue` | `ProjectsFeatured`, `ProjectCard`, `ProjectsCta` | PARTIAL → compatibility fixed | Current PHP payload may omit `intro`; validator/template now accept old payload. Must re-run Caddy smoke with PHP DTO projection. |
| `/du-an/` | legacy PHP alias | projects DTO | `app/pages/du-an/index.vue` | 301 redirect | LOCAL QA (Nuxt; edge pending) | Redirects to `/du-an-da-thuc-hien/` and preserves `category` and `p`; canonical and destination return 200. Production-like edge status/header smoke remains open. |
| `/{project-slug}/` | PHP `news/project_detail_tpl.php` | `pts_v1_project_detail.php` | `app/pages/[slug].vue` | `ProjectDetailPage`, gallery/equipment/CTA | LOCAL QA | 3 published project slugs resolved as `project` and passed SSR/H1/canonical checks through the built image and loopback Caddy. |
| `/tin-tuc/` | `sources/news_hub.php`, `templates/news/news_tpl.php` | `pts_v1_news.php` | `app/pages/tin-tuc/index.vue` | `NewsFeatured`, `NewsCard`, `NewsCta` | PASS (runtime pending) | CMS list DTO and image cards are mapped. |
| `/{news-slug}/` | `templates/news/news_detail_tpl.php` | `pts_v1_news_detail.php` | `app/pages/[slug].vue` | `NewsDetailArticle`, `NewsDetailRelated`, `EditorialShare` | LOCAL QA | 6 published news slugs resolved as `news` and passed SSR/H1/canonical checks through the built image and loopback Caddy. |
| `/chinh-sach/{slug}/` | PHP news policy route | `pts_v1_policy_detail.php` | `app/pages/chinh-sach/[slug].vue` | redirect | PASS | 301 adapter to `/ho-tro/{slug}/`. |
| `/ho-tro/{slug}/` | policy/news source/template | `pts_v1_policy_detail.php` | `app/pages/ho-tro/[slug].vue` | `EditorialRichText`, `EditorialShare`, TOC/related cards | LOCAL QA | All 7 current footer policies passed SSR/H1/canonical checks through the built image and loopback Caddy. |
| `/thu-vien-anh/` | `sources/product.php`/album path, `templates/album/album_tpl.php` | `pts_v1_gallery.php` | `app/pages/thu-vien-anh/index.vue` | gallery cards/empty state | LOCAL API QA (empty fixture) | API returns 200 with `total=0`; empty-state source exists. A published album detail fixture is unavailable locally. |
| `/thu-vien-anh/{slug}/` | `templates/album/album_detail_tpl.php` | `pts_v1_gallery_detail.php` | `app/pages/[slug].vue` | lightbox/gallery detail | PARTIAL | Keyboard/lightbox code exists; no published album fixture in integration report. |
| `/video/` | `sources/video.php`, `templates/video/video_tpl.php` | `pts_v1_video.php` | `app/pages/video/index.vue` | video cards/empty state | LOCAL API QA (empty fixture) | API returns 200 with `total=0`; empty-state source exists. A published video detail fixture is unavailable locally. |
| `/tim-kiem/` | `sources/search.php` | `pts_v1_search.php` | `app/pages/tim-kiem/index.vue` | `SearchResultsPage` | PASS source | Public search is non-indexable by runtime flag; API bridge and validation exist. |
| `/gio-hang/` | `sources/order.php`, `templates/order/order_tpl.php` | `pts_v1_cart.php`, `pts_v1_cart_mutate.php` | `app/pages/gio-hang/index.vue` | `CartPage`, cart composable | PARTIAL | Session/cart mutation remains high-risk; must run category → product → cart smoke. |
| `/thanh-toan/` | `sources/order.php` / checkout flow | `pts_v1_checkout.php`, wards, shipping, payment | `app/pages/thanh-toan.vue` | checkout components | PARTIAL | Nuxt adapter exists; must verify session, CSRF, wards, payment callback and no PHP HTML fallback. |
| `/cam-on/` | `sources/thankyou.php`, `templates/order/thanks_tpl.php` | `pts_v1_order_thanks.php` | `app/pages/cam-on/index.vue` | order thanks | PARTIAL | Requires real order/session state; non-indexable. |
| `/account/*` | `sources/user.php`, `templates/account/*_tpl.php` | `pts_v1_account.php` | `app/pages/account/index.vue`, `[action].vue` | account adapter/forms | PARTIAL | Route coverage exists; requires authenticated session/CSRF smoke and logout action through PHP. |
| `/administrator/*` | PHP Admin templates | PHP session/Admin | none | PHP-owned | N/A | Explicit PHP exception in Caddy; not part of Nuxt public frontend migration. |
| `/api/*`, `/upload/*`, `/thumbs/*`, `/watermark/*`, docs/media | PHP endpoints/files | PHP-owned | none | PHP backend/media | N/A | Explicit backend/media exceptions; must remain available after cutover. |

## Current local evidence — 2026-10-31

- Built QA image: `web_pts_php-nuxt-service:qa-current-wip-20261031`,
  `sha256:75aafac789b23d06be0e640cc31d0ed929fe127d55ee87a3131a170969047a6a`.
- QA image before the last mobile CTA label refinement:
  `web_pts_php-nuxt-service:qa-current-wip-20261031-r2`,
  `sha256:3cfbc3639f09e3425b6957824519849871d683fcfa1cdccc9d0c0f3add9dbd2c`.
  Its isolated container returned 200 with H1/canonical for home, one real
  category and one PDP; preview was noindex, unknown route 404, and a Nuxt JS
  asset plus a proxied thumbnail returned 200. Container/network were removed;
  the uniquely tagged QA image remains locally available.
- The same image passed through the checked-in local Nuxt Caddy route split
  (upstream names adapted only for the isolated network): exact page
  H1/canonical, both legacy aliases (301), API JSON, upload (200), preview
  noindex and unknown-route 404 passed. The current PHP bridge is API-only, so
  this edge run does not establish full PHP media/Admin/session/POST behavior.
- Current source after the mobile CTA refinement passed the focused commerce UI
  suite (15/15), `npm run build`, and built-Nitro smoke. Playwright at 390px and
  320px measured quote and purchase buttons at the same Y and 44px height, with
  no document horizontal overflow. The `r2` Docker image does not contain this
  final refinement.
- Through a temporary loopback Caddy edge, 32 current CMS detail URLs passed
  SSR 200, H1 and route-family canonical checks: 5 solution, 3 project, 6 news,
  7 policy and 11 brand. The tested `/ve-chung-toi/`, `/product/`, and
  `/danh-muc-giai-phap/doanh-nghiep/` aliases returned 301; a missing slug
  returned 404. API, internal asset, upload original and thumbnail probes
  returned 200 with expected JSON/image content types.
- This temporary edge sent `/api/*` and `/upload/*` to the API-only PHP bridge;
  all other paths went to Nuxt. It is **not** `deploy/vps/Caddyfile` and does
  not establish production TLS/host/health behavior or PHP's DB-backed
  thumbnail-generation path. QA containers/network were removed; the image tag
  remains local.
- A separate 34-route Nuxt crawl and 164/164 extracted href reachability check
  passed against Nuxt dev. Its PHP comparison is invalid because `:8088` is an
  API/media bridge, not a PHP HTML renderer (public HTML returned 403).
- A fresh read-only request to the current Nuxt dev server (`127.0.0.1:3100`)
  returned 200 for `/danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi/`
  and `/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-18300-300w`, each with
  its expected Vietnamese H1 and canonical URL. This confirms SSR/data wiring
  for one category and one PDP on the current checkout; it does not exercise
  cart mutations or replace the production-edge gate.
- Album and video lists currently contain no published local fixture, so their
  detail routes remain unverified with real CMS data. Commerce mutations,
  checkout, payment and confirmation have not been run end to end.

## API endpoint grouping

| Group | Endpoints | Owner after cutover | Audit status |
|---|---|---|---|
| Read-only public DTO | `pts_v1_site`, `home`, `about`, `contact`, `catalog`, `brand_detail`, `product_detail`, `product_extras`, `solutions`, `solution_detail`, `projects`, `project_detail`, `news`, `news_detail`, `policy_detail`, `gallery`, `gallery_detail`, `video`, `dealer`, `search`, `public_route_resolve` | PHP API called by Nuxt SSR | LOCAL QA for sampled data/route families; full endpoint and production-edge matrix remains open |
| Session/account/cart/checkout | `pts_v1_account`, `cart`, `checkout`, `checkout_wards`, `shipping_quote`, `order_thanks` | Nuxt Nitro adapter → PHP business API | PARTIAL; commerce smoke required |
| Mutations | `pts_v1_cart_mutate`, `pts_v1_lead_form`, `review`, `comment`, `payment_vnpay`, legacy `cart.php`, `product.php` and related | PHP business layer via controlled adapter | PARTIAL; verify CSRF/session/redirect behavior |
| Media/utility | `thumbs`, `watermark`, `upload`, `robots`, `sitemap`, `district`, `ward`, `ward-city`, `config`, `addons`, `color`, `fillter_service`, `search_product`, `video` | PHP | N/A for Nuxt HTML; edge smoke required |

## PHP/CSS/JS ownership conclusion

1. PHP templates and legacy CSS/JS are still present and executable in the PHP checkout. This is expected for rollback and backend operation, but it means source-level “old frontend removed” is **not PASS**.
2. Current `deploy/vps/Caddyfile` still proxies all requests to `web:80` (PHP); it does not yet send public HTML to Nuxt. `Caddyfile.local-full-nuxt` and the temporary QA Caddy configuration are rehearsal-only. A deployment Caddy route split, its Compose wiring and its health/rollback behavior remain release work.
3. Nuxt has no direct legacy CSS/JS imports. Any visual mismatch observed in current QA is therefore attributable to Nuxt token/layout/section implementation or data/media origin, not PHP CSS overriding the Nuxt document—provided traffic is actually through the cutover Caddy path.

## Release gate checklist

- [ ] Production-like Nuxt Caddy/Compose config (the current Caddyfile is still PHP-only) validated with one release checksum.
- [ ] Public route matrix above smoke-tested through one edge; record status and `Location` for aliases.
- [ ] All canonical pages return Nuxt HTML; `/_nuxt/*` assets return 200; PHP `assets/css/*` and `assets/js/*` are not requested by public HTML.
- [ ] API/media/Admin/session-action exceptions still return from PHP.
- [ ] Real CMS fixtures exist for at least one product, brand, solution, project, news, policy and gallery detail.
- [ ] Commerce flow passes category → product → cart → checkout → confirmation with CSRF/session/payment checks.
- [ ] Preview routes are noindex; canonical routes have canonical URLs and correct 404 behavior.
- [ ] Rollback candidate retains previous PHP/Nuxt image, Caddyfile and Compose; no DB/upload rollback is attempted.

## Follow-up — alias and media-fixture probes — 2026-10-31

Read-only requests against the current local Nuxt dev server verified that a
published `/product/{slug}` alias resolves to its PHP-canonical PDP and returns
301, while an unknown slug returns 404. `/du-an/` returns 301 to
`/du-an-da-thuc-hien/` and preserves `category` and `p`; the destination and
canonical PDP both return 200. These results cover Nuxt SSR only; no Caddy/edge
container is running, so production-like edge status and headers remain open.

The local read-only PHP APIs for gallery and video each return HTTP 200 with
`total=0`. No published local detail fixture is available for either family;
this does not establish that production content is empty, and no fixture was
created or database changed.

## Follow-up — current Nuxt-only route and rich-text checks — 2026-10-31

- GET-only health probe against all **34/34** public manifest paths on local
  Nuxt `:3100`: HTTP 200, expected canonical path, one H1 and `noindex` passed
  for every route. Metadata-only evidence is retained in
  `frontend/.migration-artifacts/current-nuxt-route-health-20261031.json`.
  This does not establish PHP HTML parity or browser visual parity.
- The solution-detail API on the PHP bridge and its Nuxt adapter returned 200
  for `dien-mat-troi-ho-gia-dinh`; payload HTML carries the PHP sanitizer
  envelope and Nuxt SSR emits 27 TOC links. The PHP 8.1 helper and focused Nuxt
  contract tests passed. Visual parity against a same-data PHP render remains
  unverified while the public PHP renderer is stopped.
