# Nuxt migration checkpoint — 2026-10-31

## Scope and current checkout

- Checkout: `/home/admin01/src/Web_PTS_PHP`, branch `master`, HEAD `57aef9d`.
- `git fetch origin` completed. The known Nuxt feature ref has no commits missing
  from master (`master...origin/feature/pts-nuxt-integration-20261027` = `198 0`).
  The checkout is 102 commits ahead of `origin/master`; nothing was pushed.
- The latest inventory finds local `master` and `updateUI` refs at the same SHA,
  plus two worktrees. `updateUI` has no unique commits but was recreated after a
  prior removal attempt; its worktree contains ignored `backup/`, `.nuxt/`, and
  `node_modules/` data, so both are retained. Existing dirty UI/test work in
  `master` was not staged or reset.
- No production PHP, database, DNS, container, or backup state was changed.

## Latest follow-up — route and rich-text verification

- The local Nuxt manifest probe passed **34/34** routes for HTTP 200, expected
  canonical, one H1 and noindex. Its metadata-only report is retained at
  `frontend/.migration-artifacts/current-nuxt-route-health-20261031.json`.
  It is Nuxt-only and does not close visual or PHP-renderer parity.
- The PHP solution-detail API and Nuxt adapter returned 200 for a published
  solution fixture; sanitized HTML was accepted and Nuxt SSR produced 27 TOC
  links. Focused Nuxt tests passed 23/23 and the isolated PHP 8.1 helper passed.
  Same-data CKEditor visual parity remains open.
- Local `master` and `updateUI` refs remain at the same SHA; the Nuxt remote
  feature branch is already an ancestor of `master`. The duplicate local ref
  was recreated after an attempted removal, so it is retained. No push, deploy,
  production request or DB write occurred.

## Current runtime evidence

- `npm run build` completed successfully after the middleware split fix (Nuxt
  4.6.2, Nitro node-server; output 9.1 MB / 2.38 MB gzip, 26.3 s). The 23 page
  references now use Nuxt named middleware strings rather than static imports.
  The `INEFFECTIVE_DYNAMIC_IMPORT` warnings are gone; only non-blocking
  `PLUGIN_TIMINGS` notices remain.
- Focused route/SEO tests passed (29/29); the full frontend suite passed
  (468/468); `npm run typecheck` passed.
- The current built Nitro server, behind the temporary Caddy harness on
  `127.0.0.1:8099`, returned 200 for `/`, `/san-pham/`, `/tin-tuc/`, and both
  real dynamic slugs. `/product/` and `/ve-chung-toi/` redirected to canonical
  paths with 301; an unknown route returned 404; preview retained its noindex
  header. API returned 200 JSON and a real logo returned 200 `image/png`.
- Local Nuxt SSR at `127.0.0.1:3100` returned 200 for:
  - `/duoc-gi-khi-hop-tac-cung-phuc-thinh-solar/` (news detail)
  - `/du-an-dien-nlmt-ho-anh-quan/` (project detail)
- The Nuxt `/_migration/public-route-resolve` adapter returned 200 for both
  slugs and the expected `kind` / `canonicalPath` fields.
- A temporary local Caddy QA harness on `127.0.0.1:8099` proxied API/media to
  the existing PHP bridge and public HTML to freshly built Nitro on
  `127.0.0.1:3121`. Sample results:
  - `/`, `/san-pham/`, `/tin-tuc/`, `/du-an-da-thuc-hien/`: 200 HTML.
  - `/product/` → 301 `/san-pham/`; `/ve-chung-toi/` → 301 `/gioi-thieu/`.
  - Both real news/project detail slugs: 200 SSR; unknown page and resolver slug:
    404.
  - `/api/pts_v1_site.php`: 200 JSON; a real logo under `/upload/photo/`: 200
    `image/png`; `/migration-about-preview`: 200 with the expected noindex header.
- The QA Caddy container and built Nitro preview process were stopped after the
  smoke; its temporary config was deleted. The harness was a minimal local
  proxy, not the production Caddyfile or production Compose stack.
- The Docker PHP bridge at `127.0.0.1:8088` returned 200 for the news resolver
  request with `kind: news` and `canonicalPath` matching the Nuxt contract.
- Current rich-text follow-up: the PHP 8.1 solution-detail helper test passed,
  and focused Nuxt solution/editorial contract tests passed **23/23**. The direct
  PHP detail endpoint at `:8088/api/pts_v1_solution_detail.php` and the Nuxt
  `/_migration/solution-detail` adapter both returned 200 for the published
  family-solar slug; the response reports sanitized HTML and client-derived TOC.
  Nuxt SSR returned 200/noindex, one H1, and 27 TOC links. This confirms the
  local sanitizer/DTO/render path, not visual CKEditor parity with PHP.
- `frontend/tests/preview-noindex.test.mjs` and
  `frontend/tests/public-route-resolve-contract.test.mjs` passed (5/5).
- PHP lint passed for the resolver endpoint and library in the local backend
  container (PHP 8.1.34). SHA-256 checks confirmed those two container files
  match the current checkout source.
- `127.0.0.1:3100/migration-about-preview` and
  `127.0.0.1:3100/migration-shell-preview` returned 200 with
  `X-Robots-Tag: noindex, nofollow, noarchive`. The earlier report that the
  about preview lacked noindex is superseded by the current route rule and smoke.

## Remaining release gates

- `127.0.0.1:8080` (PHP origin used by the production topology) and
  `127.0.0.1:8094` (local Caddy edge) were not listening during this checkpoint.
  The working resolver response came through the local bridge at 8088; this does
  not prove the production PHP image contains the same code or data.
- `docker compose -f deploy/vps/docker-compose.yml config --quiet` exited 1
  before config validation because required deployment values are absent
  (`DB_NAME`, `DB_USER`, image names, `SITE_DOMAIN`, `ACME_EMAIL`). No placeholder
  secrets were supplied and no Compose services were started.
- The current-HEAD Caddy smoke above is a sampled route/API/media check, not a
  full route matrix. The production Caddyfile/Compose stack, commerce
  session/CSRF/payment flow, full visual breakpoint review, immutable release
  image/checksum, and rollback rehearsal were not exercised. Production cutover
  remains **NO-GO**.
- The route resolver is verified for two published slugs only. Product, brand,
  solution, policy, gallery, aliases, missing/hidden entities, and invalid slugs
  still need their own runtime coverage through the release edge.

## Follow-up — Compose definition check (2026-10-31)

- `docker compose -f deploy/vps/docker-compose.yml --env-file
  deploy/vps/stack.env.example config --quiet` exited 0. This validates Compose
  interpolation and schema using the tracked non-secret staging example; it
  does not start containers or validate live secret values, image availability,
  mounts, health, PHP/database compatibility, or the production environment.
- `deploy/vps/secrets/` contains only its README in this checkout. No runtime
  secret files or production `.env` were read, created, or changed.
- The earlier bare `config --quiet` failure remains accurate for invocation
  without an env file. With the example file, Compose syntax is **PASS**; runtime
  release and production cutover remain **NO-GO** for the gates above.
- `git fetch origin --prune` reconfirmed the only known Nuxt feature branch is
  fully contained in local `master` (`origin/feature/...` is 198 commits behind
  with zero commits missing from `master`). There is one worktree. Local `master`
  remains 102 commits ahead of `origin/master`; nothing was pushed.
- Read-only CMS runtime checks through the local Nuxt dev server (`127.0.0.1:3100`)
  and PHP bridge (`127.0.0.1:8088`) confirmed the site DTO exposes seven product
  categories and the root catalog has 372 products. The real
  `/danh-muc-san-pham/den-nlmt/` category returned 203 products; the real JINDIAN
  500W product slug resolved as `pdp` to its canonical product path. An unknown
  slug returned 404.
- Playwright opened that category and product detail at 390px and 1440px with
  Vietnamese locale: all four SSR pages returned 200 with the expected Vietnamese
  H1 and canonical URL, no page errors, and no document horizontal overflow.
  This closes the sample category/product-fixture gap for the local dev + PHP
  bridge path only; it does not replace the production release-edge smoke or
  commerce session/cart/checkout checks.

## Follow-up — production image + local Caddy edge (2026-10-31)

- `npm audit fix` updated 23 packages within the existing semver ranges, including
  Nuxt from 4.6.2 to 4.6.3. The upstream Nuxt advisory lists 4.6.0–4.6.2 as
  affected and 4.6.3 as patched:
  https://github.com/nuxt/nuxt/security/advisories/GHSA-9v7c-q64q-vv33.
- After the lockfile update, frontend tests passed **468/468**, `npm run typecheck`
  passed, and the production Dockerfile built the Nuxt image successfully
  (9.32 MB / 2.38 MB gzip). `npm audit --omit=dev` still reports 14 transitive
  advisories (2 low, 12 moderate); npm only offered resolving the remaining
  chain by forcing Nuxt down to 4.1.3. That breaking downgrade was not applied.
- Inspection of the rebuilt runtime image's `.output/server/package.json` and
  installed module paths found no separate runtime installations of the audited
  build-chain packages (`nuxt`, `nitropack`, `@vercel/nft`, `archiver`,
  `deepmerge`, `glob`, `minimatch`). The Dockerfile copies only `.output` into
  the runtime stage. This narrows the remaining audit finding to the source/build
  dependency graph; it is not a complete image SBOM or vulnerability scan.
- The new local QA image is `sha256:aa2d0c4616b5b32cae3c0a79b22e8cf8da587b0736b4b9c26f348f3efe4be157`;
  pre-update image is retained locally as
  `web_pts_php-nuxt-service:qa-before-nuxt-463-20261031`. These are local QA
  tags, not immutable release artifacts.
- A temporary Caddy container using the route structure from
  `Caddyfile.local-full-nuxt` validated successfully and routed to the rebuilt
  Nuxt container plus the existing PHP bridge. Through that edge: site API 200,
  original logo/product uploads 200 `image/png`, category and canonical PDP 200,
  `/ve-chung-toi/` → 301 `/gioi-thieu/`, `/product/` → 301 `/san-pham/`, unknown
  slug 404, and the about preview carried `X-Robots-Tag: noindex, nofollow,
  noarchive`. A trailing-slash PDP request redirected 301 to its no-slash
  canonical path.
- Three `curl --path-as-is` dot-segment requests returned 404 through the Caddy /
  Nuxt 4.6.3 edge. Playwright on the rebuilt image confirmed category/PDP H1 and
  canonical at 390px and 1440px, with no browser page errors or horizontal
  overflow.
- The API-only PHP bridge deliberately denies direct `/thumbs/*` and `/assets/*`
  (`docker/nuxt-preview-api-only.conf` allows only API and uploaded originals),
  so routing all media to that bridge produced expected 403s. A second, isolated
  PHP media probe used the same PHP image, read-only webroot/upload/thumbs
  mounts, tmpfs caches/logs, and a private network with no DB attachment. A
  temporary Caddy route split sent API traffic to the restricted bridge and
  media traffic to this probe. Through that edge, the cached wordmark thumbnail,
  site-visual PNG/WebP, upload original and public-notice thumbnail all returned
  200 with the expected image MIME types.
- Playwright through this media-capable edge still found one broken footer
  emblem image: its original upload returns 200, but the exact
  `520x426x3` derived thumbnail is absent from the read-only local thumbs volume.
  The isolated probe cannot generate this cache because the normal PHP front
  controller requires DB access for a cache miss; it returned an HTML bootstrap
  error instead. No DB or thumbs volume was modified. First-generation behavior
  for this missing thumbnail and the full production media topology remain
  unverified; the sampled existing media fixtures pass.
- Temporary Caddy and Nuxt containers/network were removed after QA. Existing
  `pts-nuxt-api-bridge` and healthy `pts-mariadb-local` remained running; ports
  18091/3101 are closed. No production service or database was touched.

## Follow-up — hero alignment and footer media fallback (2026-10-31)

- `HomeHero.vue` no longer shifts the content vertically using viewport-width
  arithmetic. Its top inset now follows viewport height; slide caption and
  controls align to the page gutter, with extra separation between them.
- In a fresh dev-runtime check, both the footer emblem derivative and original
  image returned valid PNGs. The isolated media probe from the earlier check had
  exposed that the thumbnail can be missing when the PHP media handler cannot
  reach its normal DB-backed cache generation path. `SiteFooter.vue` now retries
  an uploaded brand logo from its original URL after thumbnail decode failure,
  then shows the text brand if that also fails.
- Focused homepage/shell contracts passed (35/35), `npm run typecheck` and
  `npm run build` passed, `git diff --check` passed, and the local Nuxt homepage
  still returned 200. No production service, CMS data, upload, or cache was
  changed. Full-browser visual verification and production media behavior remain
  release checks, not proven by the source tests or local dev response.
- A fresh read-only migration SEO crawl covered 34 manifest routes. Nuxt
  returned 200 for all 34, and all 164 extracted internal href targets resolved
  on Nuxt. The PHP origin returned 403 for all public HTML because `:8088` is
  the API/media-only bridge, so metadata comparison, sitemap discovery, and
  hidden-product comparison from that run are invalid/incomplete. Nuxt preview
  routes were noindex; Nuxt sitemap 404 is expected for this local preview.
  `NITRO_PHP_ORIGIN=http://127.0.0.1:8088 ... npm run smoke` passed the built
  SSR checks (five preview/hub/media paths 200/noindex, unknown route 404).
  This remains local route reachability evidence, not PHP parity or Caddy-edge
  release evidence. Crawler artifacts are under `/tmp/pts-seo-audit-current-20261031`.
- Read-only CMS GETs supplied current detail fixtures for 5 solution cards, 3
  projects, 6 news articles, 7 footer policies, and all 11 catalog brands. The
  public slug resolver classified all project/news samples correctly; all 32
  detail URLs returned SSR 200 with an H1 and a canonical path matching that
  route family's contract (news/project bare-slug canonicals omit the trailing
  slash). Album and video collections are empty in this local CMS response, so
  those two detail families still have no published fixture to exercise. No
  CMS records or PHP data were changed.
- Repeated that same 32-fixture check against a short-lived local process running
  the built Nitro `.output/server/index.mjs`, with upstream requests restricted
  to the loopback PHP bridge allowlist. All 32 SSR pages again returned 200 with
  H1 and family-correct canonical paths. The process was terminated after the
  check. This verifies the built Node server plus API bridge, not the production
  Caddy/Compose edge, PHP HTML renderer, or payment flow.
- Built the current `frontend/Dockerfile` as local image tag
  `web_pts_php-nuxt-service:qa-current-wip-20261031`, image ID
  `sha256:75aafac789b23d06be0e640cc31d0ed929fe127d55ee87a3131a170969047a6a`.
  A temporary Nuxt container ran `healthy` on an internal-only Docker network
  shared with the API bridge; it was not attached to the MariaDB network. Inside
  that image all 32 detail fixtures again passed SSR/H1/canonical checks, and a
  representative built asset, upload original, and thumbnail each returned
  valid PNG bytes. The QA container and temporary network were removed; the new
  QA image remains available for local inspection. Existing `latest` and
  rollback image tags were not modified. Host port publishing is unavailable on
  Docker's internal-only network, so verification ran inside the container and
  does not count as a public Caddy-edge smoke.
- Added a temporary loopback Caddy edge in front of that QA image. Caddy routed
  `/api/*` and `/upload/*` to the API-only PHP bridge and all remaining requests
  to Nuxt; `/thumbs/*` therefore exercised Nuxt's bridge proxy/fallback path.
  Caddy config validation passed. Through `127.0.0.1:18091`, the same 32
  CMS-backed detail routes passed SSR/H1/canonical checks, the three tested
  legacy aliases returned 301 to their expected paths, an unknown slug returned
  404, and API, site asset, upload original, and thumbnail responses returned
  200 with expected JSON/image content types. Nuxt/Caddy QA containers, the
  temporary network, and temporary config were removed afterward. This is a
  local routing rehearsal only: it does not execute the production Caddyfile,
  TLS/host/health-check configuration, direct PHP thumbnail generation, or
  production media topology.

## Disposition

No branch merge was needed from the known feature ref. The current local master
contains the feature history, while the remote master remains 102 commits behind;
this checkpoint does not authorize a push. Continue with the existing UI WIP and
close release gates only when the corresponding local release environment and
fixtures are available. No claim of production cutover is made.

## Follow-up — current worktree verification (2026-10-31)

- Rechecked branch/worktree state: `master` is at `57aef9d`, the only worktree,
  and the known remote Nuxt feature ref is an ancestor of `master`. There are no
  missing commits to merge from that branch. Local `master` remains 102 commits
  ahead of `origin/master`; no push or production change was made.
- The current frontend worktree passed `npm test` (**469/469**),
  `npm run typecheck`, and `npm run build` (Nuxt 4.6.3/Nitro node-server;
  output 9.11 MB / 2.39 MB gzip; 26.4 s). `git diff --check` passed.
- Built Nitro smoke passed: five preview/hub/media routes returned 200 with
  noindex, and an unknown route returned 404. Against that fresh build and the
  loopback PHP API bridge, homepage, the real solar-light category, and JINDIAN
  JD-18300 (300W) PDP returned SSR 200 with the expected H1/canonical. The same
  category/PDP also returned 200 from the current Nuxt dev server. The temporary
  built server was stopped after checking.
- This verifies current source build and sampled local SSR only. It does not
  update the prior QA Docker image, validate the production Caddy/Compose path,
  compare full PHP-rendered HTML, or exercise cart/checkout/payment mutations.
  Production cutover remains **NO-GO**; no deployment, push, container change,
  CMS write, upload/cache change, or database operation was performed.
- A follow-up browser check of the current category page exposed a 24px vertical
  offset between quote and priced product actions in the same grid row. The
  action group now uses the card's remaining flex space so all four sampled
  cards align; Playwright measured the primary action at `y=907.5` and detail
  action at `y=959.5` for every card at 1440px. The focused commerce UI suite
  passed **15/15** after this fix, and the final source was rebuilt and passed
  the built-Nitro smoke. This remains local browser evidence, not PHP visual
  parity or production verification.
- Built the final current worktree as the separate local QA image
  `web_pts_php-nuxt-service:qa-current-wip-20261031-r2`, image ID
  `sha256:3cfbc3639f09e3425b6957824519849871d683fcfa1cdccc9d0c0f3add9dbd2c`
  (411,113,276 bytes). Its container reached `healthy` on a temporary network
  shared only with `pts-nuxt-api-bridge`; Nuxt had no MariaDB network attachment.
  Through its loopback port, homepage/category/PDP returned 200 with expected
  H1/canonical, a preview was 200/noindex, an unknown URL was 404, and a Nuxt
  asset plus one proxied thumbnail returned 200. The QA container, temporary
  network, and the bridge's temporary network attachment were removed; the
  uniquely tagged QA image remains. Existing `latest` and rollback tags were
  not changed.
- Routed that current QA image through Caddy 2 using the checked-in
  `Caddyfile.local-full-nuxt` with only its two upstream addresses rewritten to
  container DNS names on the temporary QA network; `caddy adapt --validate`
  passed. Through the loopback edge, home/category/PDP returned 200 with exact
  H1/canonical, `/ve-chung-toi/` and `/product/` returned the expected 301s,
  API JSON and an uploaded logo returned 200, preview returned 200/noindex, and
  an unknown URL returned 404. The available API-only bridge is not the full
  PHP media/Admin/session service, so this does not verify PHP thumbnail
  generation, Admin or commerce POST behavior. Caddy/Nuxt containers, network,
  temporary bridge attachment and temporary config were removed; the original
  API bridge network and dev listener were confirmed unchanged.
- A later mobile browser check found the quote CTA wrapping at 390px and
  becoming 7px taller than neighboring purchase buttons. `CatalogProductGrid.vue`
  now uses the full label on desktop and “Liên hệ báo giá” on mobile while
  retaining the complete accessible name. At 390px and 320px, quote and purchase
  buttons both measured 44px high at the same Y coordinate; document width
  matched the viewport. The focused commerce UI suite passed **15/15** after
  this change. Current source then passed `npm run build` (Nuxt 4.6.3; 9.11 MB /
  2.39 MB gzip; 29.9 s) and built-Nitro smoke. The `r2` Docker image above
  predates this final mobile-label refinement; no new image or Caddy edge run was
  made for this template-only change.

## Follow-up — compact pagination and product actions (2026-10-31)

- Catalog pagination now uses Lucide single/double chevrons instead of text
  labels; previous/next/first/last controls retain Vietnamese accessible names.
  Brand and search pagination use labeled single chevrons. The focused
  pagination contract passed, and browser checks at 1440px/390px confirmed the
  44px control targets, current-page state and no client errors.
- Product secondary actions use the shared white-gray gradient with a thin navy
  outline; gold CTAs are unchanged. The desktop catalog filter column has a
  slate surface, while its mobile drawer remains white. Focused UI tests passed
  **3/3**; browser checks confirmed the rendered outline and sidebar surface.
- `npm run build`, `npm run typecheck`, and `NITRO_SMOKE_MOCK_PHP=1 npm run smoke`
  passed. A fresh local image was built as
  `web_pts_php-nuxt-service:qa-current-wip-20261031-r3`, image ID
  `sha256:4d36b5a4fcfee33811a8870d42591e35a2f1f5d83bc94599b012b588b356bb43`
  (411,123,738 bytes).
- The image ran healthy on an internal-only temporary network. Nuxt had no
  MariaDB network attachment; the read-only PHP API bridge was temporarily
  connected to that network and restored to its original network afterward.
  Inside the container, `/_migration/site`, home, the solar-light category,
  JINDIAN JD-18500 PDP and the about preview returned 200; `/product/` returned
  301 to `/san-pham/`; the preview returned `noindex`; and an unknown path
  returned 404. Docker did not publish the QA container port because the network
  is internal-only, so these checks ran via `docker exec`; this is not a Caddy
  edge or production topology check.
- QA container/network were removed after the check. The API bridge and local
  MariaDB remained healthy on their original network, the Nuxt dev listener
  remained available, and the `r3` image tag is retained. No production,
  database, upload, rollback image, `latest` tag, or public route was changed.
