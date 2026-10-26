# F01 — Homepage PHP to Nuxt: one complete read-only pilot
Branch: feature/pts-nuxt-home-pilot-20261026 based on feature/pts-nuxt4-migration@a44b91a.
Read AGENTS.md, docs/14-nuxt-migration/{ROUTE_AND_WORKSTREAM_MATRIX,LOCAL-PREVIEW}.md. Source PHP sources/index.php (223 lines), templates/index/index_tpl.php and nested index partials, assets/css/home* and homepage content layout. Current PHP reference http://127.0.0.1:8080/ (local). Migration PHP 8088 uses integration worktree (does NOT see this branch until merged).
GOAL: faithful read-only homepage MVP using real PHP CMS content, source order, approved fallback behavior and valid assets. No arbitrary AI generic redesign. Do not copy PHP, don't import legacy global CSS. Read source before coding.
- New GET-only PHP /api/pts_v1_home.php and helper libraries/pts_public_home_api.php; use existing api/config.php read-only $d/$cache/$setting, reused queries from sources/index.php. At minimum heroes, campaigns, featured categories/products, links where verifiably safe, and a content summary. Honor explicit CMS hide vs never-configured defaults. No private data/unknown fields, no pricing fabricated; no PHP error leakage; 405 on non-GET; validate output and media. Avoid any POST or checkout.
- Typed frontend/shared/contracts/public-home.ts, Nitro fixed upstream frontend/server/routes/_migration/home.get.ts, SSR **preview only** frontend/app/pages/migration-home-preview.vue (noindex). Compose existing SiteHeader/SiteFooter with public site DTO and actual home DTO, safe fallback when API not accessible. Components under frontend/app/components/home/**, CSS under frontend/app/assets/css/home-preview.css. External unported PHP links must remain plain document navigation, no Nuxt route claiming homepage.
- Create deterministic PHP tests scripts/test-pts-public-home-api.php and Node tests frontend/tests/home-contract.test.mjs; document gaps, original source mapping, and required parity in docs/14-nuxt-migration/F01-HOME.md.
OWN FILES ONLY these new files or folder paths and F01 task doc. Do not modify shared shell, existing site/solutions/catalog PHP endpoint, frontend/app.vue, Nuxt config, Docker, master, prod.
Resource: do NOT npm ci or run browser concurrently with other Codex. Node --test okay; for PHP CLI/Docker sandbox blocked, report and leave implementation uncommitted (coordinator can run Docker PHP tests). No push/merge/deploy. Return file list, tests and limitations Vietnamese. Aim functional MVP; explicitly defer unsupported homepage behavior, don't make up feature parity.

## F01 implementation checkpoint (2026-10-26)

### Source mapping

- `sources/index.php`: CMS projections for `hero-carousel`, `home-campaign`, featured `product_list`, published products, `giai-phap`, `quy-trinh`, `home-about`, SEO setting and public contact options are read by `api/pts_v1_home.php` through the existing read-only `api/config.php` bootstrap.
- `templates/index/index_tpl.php`: section order is preserved as hero → category/product discovery → campaigns → solutions → process → dealer → about → contact. The Nuxt contact area links to PHP `/lien-he/#yeu-cau-tu-van`; it does not submit a new form.
- Media is emitted only when it is a basename with an approved image extension and exists below the PHP upload/static roots. Links are normalized to safe same-site paths or explicit `http(s)` links; Nuxt uses plain anchors for unported PHP routes.

### Delivered files

- PHP: `domains/phucthinhsolar.com/public_html/api/pts_v1_home.php`, `domains/phucthinhsolar.com/public_html/libraries/pts_public_home_api.php`, `scripts/test-pts-public-home-api.php`.
- Nuxt: `frontend/shared/contracts/public-home.ts`, `frontend/server/routes/_migration/home.get.ts`, `frontend/app/pages/migration-home-preview.vue`, `frontend/app/components/home/**`, `frontend/app/assets/css/home-preview.css`, `frontend/tests/home-contract.test.mjs`.

### Known gaps / required parity work

- Hero carousel has manual controls only; autoplay, keyboard roving-tabindex and the PHP JS timing behavior remain deferred.
- Homepage PHP contact POST, CSRF, reCAPTCHA and success/error states remain PHP-owned and are intentionally not ported in this read-only pilot.
- The source contains additional about-point and richer CMS fields; this DTO intentionally exposes only the fields used by the preview. Product prices, discounts, stock and order actions are not exposed.
- Exact PHP image transforms/watermark behavior, SEO image metadata/JSON-LD and responsive screenshot parity still require coordinator-side PHP/Docker/browser QA.
- CMS hide-vs-never-configured semantics are preserved for hero/campaign rows; approved homepage fallback copy/assets are used only when the corresponding type has never been configured. Solutions/process/about fallbacks remain the source-approved bounded fallback projection.

### QA / operational state

- `node --test frontend/tests/home-contract.test.mjs`: pass (Node 24.20.0; contract safety checks).
- PHP lint and `scripts/test-pts-public-home-api.php`: not run because PHP CLI is unavailable in this sandbox; coordinator must run them in the PHP/Docker environment.
- No `npm ci`, browser, Docker, push, merge, deploy, production mutation, or commit was performed. Working tree changes remain uncommitted for coordinator review.
