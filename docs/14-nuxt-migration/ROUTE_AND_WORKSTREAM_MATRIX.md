# PHP -> Nuxt migration slices / ownership

Source root below = `domains/phucthinhsolar.com/public_html/`.
All public route claims must be confirmed against live PHP resolver, including bare slug aliases and historical redirects. No Nuxt catch-all before exhaustive collision tests.

| ID | Public feature | PHP source | Template / dependencies | Target / milestone | Acceptance |
|---|---|---|---|---|---|
| F00 | Shell / header / footer / menu / breadcrumb | `sources/allpage.php` | `templates/index.php`, `templates/layout/*`, CSS/JS loaders | `SiteHeader`, `SiteFooter`, `Breadcrumbs` (M2) | Same menu links, image, spacing, responsive and keyboard behavior |
| F01 | Homepage `/` | `sources/index.php` | `templates/index/index_tpl.php`, `home-story-v4` | Home (M4) | CMS content, hero, media, links, SEO and popup parity |
| F02 | Catalog `/san-pham/` | `sources/product.php` | `templates/product/product_tpl.php`, `product-category-v3.css` | Product index (M3) | 20-item pagination defaults where used, taxonomy, sort, quote-price logic |
| F03 | Product PDP `/san-pham/{slug}` | `sources/product.php` | `templates/product/product_detail_tpl.php`, `product_pdp_profiles.php`, specs/blocks/media | Product detail (M3) | Exact product, specifications, gallery, price/quote, document, JSON-LD |
| F04 | Product category hierarchy | `libraries/router.php`, `product_category_urls.php` | `sources/product.php`, catalog template | Nuxt category route (M3) | 1..4 levels, canonical, 301, 404, filters and breadcrumbs |
| F05 | Search `/tim-kiem/` | `sources/search.php` | Catalog template + query | Search (M3) | Same normalized query, results, robots noindex; no reflected HTML |
| F06 | Brand / taxonomy `/thuong-hieu/` | `sources/product.php` | Catalog template | Brand list (M3) | Same published brand relationships and pagination |
| F07 | Solution `/giai-phap/` | `sources/news.php`, `sources/solution_hub.php` | `templates/news/service_list_tpl.php` | Safe first pilot (M2) | Real CMS content, menu, lead CTA and SEO |
| F08 | Solution detail `/giai-phap/{slug}/` | `router.php`, `sources/news.php` | `service_detail_tpl.php` | Solution detail (M4) | Canonical/legacy aliases, rich text, related products |
| F09 | Projects `/du-an-da-thuc-hien/` | `sources/news.php`, `sources/project_hub.php` | `project_tpl.php`, `project_detail_tpl.php` | Projects (M4) | Gallery, proof fields, related links and canonical |
| F10 | Knowledge/news `/tin-tuc/` | `sources/news.php`, `sources/news_hub.php` | `news_tpl.php`, `news_detail_tpl.php` | News (M4) | Categories, rich content, pagination, author, metadata |
| F11 | About `/gioi-thieu/` | `sources/about.php` | `about/about_tpl.php` | About (M4) | CMS-driven sections / image fallback |
| F12 | Dealer `/dai-ly/` | `sources/daily.php`, `sources/allpage.php` | `daily/daily_tpl.php` | Dealer page (M4) | Read UI first; POST stays PHP with CSRF/recaptcha |
| F13 | Contact `/lien-he/` | `sources/contact.php`, `sources/allpage.php` | `contact/contact_tpl.php` | Contact page (M4) | Location, form submit, file upload/rate limit preserved |
| F14 | Policies / content `/ho-tro/*` and legacy aliases | `router.php`, `sources/news.php` | `news/news_detail_tpl.php` | Policies (M4) | Known 301/404/410 and current valid content |
| F15 | Gallery/video | `sources/product.php`, `sources/video.php` | `album/*`, `video/*` | Media routes (M4) | Original aspect ratio, embeds, privacy/copyright |
| F16 | Cart `/gio-hang/` | `sources/order.php`, `api/cart.php` | `order/order_tpl.php` | **STAY PHP** | Session/cart unchanged under hybrid routing |
| F17 | Checkout and thankyou `/cam-on/` | `sources/order.php`, `sources/thankyou.php` | `order/*` | **STAY PHP** | CSRF, idempotency, shipping/payment and one-shot conversion |
| F18 | Accounts `/account/*` | `sources/user.php` | `account/*` | **STAY PHP** | Auth, password flows, order history, no session leakage |
| F19 | Admin `/administrator/*` | `administrator/sources/*` | `administrator/templates/*` | **STAY PHP** | No CMS regressions; admin uploads reflect public frontend |
| F20 | Existing AJAX/endpoints `/api/*` | `api/*.php` | Cart, filter, pricing, payment, shipping | PHP owns /api | Preserve HTTP/POST/cookies, introduce JSON v1 separately |
| F21 | Dynamic media `/upload/*`, `/thumbs/*`, `/watermark/*` | `router.php`, `libraries/public_watermark.php` | Shared persistent media | **STAY PHP** | Image transforms, cache, signature and redirects |
| F22 | SEO and links `/sitemap.xml`, `/robots.txt`, aliases | `router.php`, `libraries/sitemap.php`, `head.php`, `strucdata.php` | Product JSON-LD, canonical, 301 | PHP owns until separate gate | Crawl diffs show unchanged indexable route identities |

## Business integration boundaries

- `sources/allpage.php` handles global menu data **and** several form POST handlers: isolate read-only projections; never require this file directly in a public GET API.
- Existing AJAX `api/product.php` returns HTML; `api/search_product.php` JSON-encoded HTML; `api/fillter_service.php` filter/pagination. Do not present these as new headless REST endpoints.
- Pricing authority: `libraries/public_pricing.php`, used by catalog/cart/checkout. Browser cannot invent discounts, price visibility or stock availability.
- Session authority: PHP `$_SESSION['cart']`, order idempotency and thankyou conversion. Nuxt cannot create alternative cookies/session identifiers.
- Redirect and canonical authority: `router.php`, `public_route_resolver.php`, `product_category_urls.php`, DB `#_url_redirect`.
- Images: preserve original uploaded assets, 1:1 product media vs content media, thumbnail/watermark; no copying uploads into Git.

## Workstream ticket contract

For each Fxx slice, the implementing agent must deliver:
1. Source file map and business data inputs, with unknowns marked VERIFY.
2. Contract example using **actual fixture data** or redacted schema, not fake products.
3. Vue component and route; default to no behavioral changes in fidelity phase.
4. Screenshot on 360/390/768/1366/1920; legacy and Nuxt same fixture/state/browser.
5. E2E parity for click/tab/form/filter/navigation where relevant.
6. SEO parity: URL, status, canonical, robots, OG, JSON-LD, breadcrumb, redirects.
7. Diff report, console/network errors, tests, rollback path and commit SHA.
8. No merge if any P0 item remains.
