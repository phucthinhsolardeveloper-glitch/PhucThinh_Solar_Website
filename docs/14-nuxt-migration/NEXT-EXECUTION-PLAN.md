# PTS Nuxt migration — next execution plan

## Current integration checkpoint — 2026-10-31

- Latest ref inventory: local `master` and `updateUI` both point to
  `57aef9d`; `updateUI` has no unique commits, and
  `origin/feature/pts-nuxt-integration-20261027` is an ancestor of `master`, so
  no remote Nuxt feature commits are missing. `master` is 102 commits ahead of
  `origin/master`; nothing was pushed. The second worktree has clean source at
  the same SHA but retains ignored `backup/`, `.nuxt/`, and `node_modules/` data.
  A previous attempt to remove the duplicate local ref was followed by its
  recreation; retain the current state rather than racing an external checkout.
  The earlier “one worktree” inventory is superseded.
- PHP read-only API integration is **PASS**: the corrected follow-up runner
  reports 69 HTTP checks and 22 contract slices pass, 0 fail, 0 skip; the
  26-table snapshot is unchanged. Four hidden-record negative fixture families
  remain unavailable locally. See the ignored artifact report noted in the
  checkpoint document.
- Direct public-path SEO check: **34/34** status, expected canonical, PHP↔Nuxt
  canonical, Nuxt noindex, title and redirect checks pass. PHP sitemap **457/457**
  and internal href reachability **362/362** pass. OG description/image/type,
  JSON-LD, and per-route href path differences remain open; details are in
  [`PUBLIC-URL-SEO-AUDIT-20261031.md`](PUBLIC-URL-SEO-AUDIT-20261031.md).
  Hidden-record verification remains incomplete. PHP retains production public
  ownership; local Nuxt route flags remain viewable while indexability stays off.
- Latest full Nuxt verification before the current targeted SEO/schema and
  runner-test edits: `npm test` **452/452 PASS**, typecheck, build and smoke
  passed. Targeted checks for the latest edits passed; no extra full suite was
  run. Build warnings remain `INEFFECTIVE_DYNAMIC_IMPORT` and `PLUGIN_TIMINGS`.
- The owner requested stopping the full 34-route × 3-viewport browser run to
  inspect the Nuxt site directly. All logged route/viewport pairs returned 200,
  but the harness ended before writing its final report; screenshots are
  optional manual review material, not a parity approval.
- Development runtime: Nuxt HMR on `:3100`, PHP API/media bridge on `:8088`,
  and MariaDB remain active. Legacy PHP site on `:8080` is stopped. The root
  ignored `.env` now disables local route indexing; load it when starting Nuxt.
  Stale Nuxt snapshot/Caddy containers were removed; images and volumes remain.
- No production service, database data/volume, deploy, or route flag was touched.
  Local-only container cleanup is documented in the branch integration
  checkpoint; source and rollback images were retained.

## Updated gate status — 2026-10-31

| Gate | Status | Evidence / remaining condition |
|---|---|---|
| P0-A route/page inventory | Implemented locally | 34 manifest entries map to Nuxt pages; public resolver/status evidence is documented in the SEO crawl artifact. |
| P0-B PHP/API integration | PASS with fixture gaps | 69 HTTP + 22 slice PASS, 0 FAIL, 0 SKIP; DB snapshot unchanged. Four hidden-record fixture families remain unavailable. |
| P0-C browser QA | INCOMPLETE | A fresh Nuxt-only GET health probe passes 34/34 manifest routes for HTTP 200, expected canonical, one H1 and local noindex; report is `frontend/.migration-artifacts/current-nuxt-route-health-20261031.json`. This is not the required 3-viewport browser/PHP parity report or visual approval. |
| P1-A shared site shell | INCOMPLETE | Source contracts cover CMS-driven navigation, keyboard Escape/focus trap and scroll lock. A fresh browser interaction probe did not complete, so responsive keyboard/focus behavior and visual PHP parity have no new runtime approval. |
| P1-B solution rich-content gate | Partial PASS | PHP allowlist sanitizer and sanitized-envelope validation are covered by 23 Nuxt contract tests and the PHP 8.1 helper test; local Nuxt DTO/page returns 200, `noindex`, one H1 and generated TOC. CKEditor visual comparison against PHP remains open because the PHP public renderer is stopped and the full parity report is incomplete. |
| P1-C catalog business parity | INCOMPLETE | Current route/query contracts and category/PDP samples exist; pricing authority and complete filter/sort/pagination parity against the same PHP data remain open. Cart/quote continue to hand off to PHP. |
| P1-D content hub/detail parity | PARTIAL | Published news/project/solution/policy/brand samples and DTO mappings exist; same-data CMS visibility/category/media/related-content and full responsive parity are not closed. |
| P2-A SEO/canonical crawl | Partial PASS | Public URL status/canonical/title/redirect/noindex checks pass 34/34; sitemap 457/457 and href reachability 362/362. Current local Nuxt SSR returns valid JSON-LD on all 34 routes and BreadcrumbList on the 33 non-home routes; exact PHP parity, broader OG/href-path differences and hidden DB rows remain open. |
| P2-B hybrid route pilot | NOT STARTED | Local flags remain off and production Caddy still routes to PHP; edge allowlist and rollback rehearsal are not verified. |
| P3 production cutover | NO-GO | Depends on all P0–P2 gates, release checksum, production-like health/rollback evidence and owner approval. No production change is authorized or performed. |
| Preview indexing | PASS | Runtime probe returns `X-Robots-Tag: noindex, nofollow, noarchive` for all 27 `migration-*.vue` pages; focused test passes 1/1. This verifies indexing protection, not route data/render health. |

## Follow-up — current dirty-source build and smoke — 2026-10-31

- `npm run build` on the current Nuxt worktree passed with Nuxt 4.6.3/Nitro
  node-server output (9.11 MB, 2.39 MB gzip). The only reported notice was
  `PLUGIN_TIMINGS`; the build did not report ineffective dynamic imports.
- `NITRO_PHP_ORIGIN=http://127.0.0.1:8088 NITRO_PHP_ORIGIN_ALLOWLIST=127.0.0.1:8088 npm run smoke`
  passed: preview plus four hub/media routes returned 200/noindex and an unknown
  route returned 404. The smoke used the existing local API bridge and stopped
  its temporary built-server process on completion.
- After the latest UI/SEO follow-ups, `npm test` passed **472/472** and
  `npm run typecheck` passed. The built Nuxt SSR server also returned 200 for
  the real homepage against the local PHP API bridge; its SSR hero retains the
  left-anchored content group with centered copy and the translucent CTA class.
  Focused browser measurement at 1064×482 confirmed the block is left of the
  viewport center. `git diff --check` passed.
- This does not rebuild the retained QA Docker image or exercise the Caddy/
  production Compose edge, PHP-rendered parity, commerce mutations, or visual
  signoff. Production remains untouched.

## Follow-up checkpoint — current checkout, 2026-10-31

- At the start of this follow-up, `master@1acad02` was 94 commits ahead of
  `origin/master`; the Nuxt feature branch is an ancestor, and the branch/
  worktree inventory contained only `master` and one worktree. No additional
  integration branch remained in the local or fetched remote inventory.
- All 27 internal migration page paths returned the required noindex response
  header from local Nuxt `:3100`; `node --test tests/preview-noindex.test.mjs`
  passed 1/1. This closes the preview indexing protection check, but not the
  broader SEO parity or release gates above.
- Current PHP source and local API bridge `:8088` resolved a known published
  news slug; Nuxt `/_migration/public-route-resolve` returned the matching
  `kind`/`canonicalPath` DTO with HTTP 200. This confirms one current preview
  fixture, not full resolver entity coverage or PHP `:8080`/production parity.
- The retained href matrix contained two broken PHP solution-category aliases.
  Nuxt now returns 301 to the resolver-provided `/doanh-nghiep` and
  `/ho-gia-dinh` pages; unknown aliases remain 404. All 129 distinct PHP-only
  href targets and 98 Nuxt-only href targets now return HTTP 200 after
  redirects on local Nuxt. The Nuxt-only set includes brand routes/filters,
  catalog/search query states, PDP paths, editorial bare slugs, and the dealer
  slash alias. Exact same-data href/filter parity remains unverified because
  the PHP HTML capture is historical and the local PHP renderer is disabled.
- The alias contract tests pass 5/5 and Nuxt typecheck passes after correcting
  a numeric textarea prop. Focused route/catalog/brand/search/editorial tests
  pass 57/57. The route probes and both target-set results are recorded in
  `PUBLIC-URL-SEO-AUDIT-20261031.md`.
- A second review found the 36 catalog/search/brand PHP pagination links using
  `p` were silently ignored by Nuxt components that only read `page`. These
  flows now normalize legacy `p` to the API's `page`; news-category already
  handled `p`. Runtime samples for catalog, category, brand root and search
  confirm `p=2` returns the same first products as `page=2`, differs from page
  1, and stays noindex. Focused checks pass 18/18 and typecheck passes.
- Query results were compared only against the active local Nuxt API. Exact
  page-content parity against PHP remains unverified while the PHP HTML
  renderer is disabled.
- No production action was taken. PHP remains the public owner; visual signoff,
  SEO/href classification, missing hidden-record fixtures, and release/Caddy
  rehearsal remain open.

## Follow-up checkpoint — OG image source alignment — 2026-10-31

- Re-ran current Nuxt SSR over all 34 manifest public paths and compared
  normalized OG image paths to the retained PHP HTML snapshot: **34/34 HTTP
  200**, **32/34 image paths match**. Nuxt preserves the PHP banner-as-social
  fallback for product catalog/category paths but not the brand root; projects
  and news hubs and solution details use only their PHP-backed SEO image before
  the shared logo fallback.
- Two solution-category paths remain different: the PHP snapshot has category
  photos, while the current local solutions DTO has no category SEO image and
  renders the logo. No historical asset was hardcoded because current local
  category media cannot be verified against a live PHP page render.
- Focused metadata/API contract tests pass **50/50**; full frontend tests pass
  **459/459**, and Nuxt typecheck passes. Other SEO differences, hidden-record
  fixtures, complete visual review, same-data PHP-vs-Nuxt href parity, and
  release/Caddy rehearsal remain open. Production remains PHP-owned; no
  production request or mutation, deploy, push, DB write, or route cutover was
  performed.

Historical checkpoint notes below describe earlier worktrees and service states;
use the dated checkpoint above for the current local integration state.

## Historical checkpoint — 2026-10-30

- Nuxt public route ownership no longer uses per-route `*PublicRouteEnabled`
  gates. Implemented route wrappers render directly; CMS visibility toggles and
  the separate SEO indexability setting remain in effect. The 2026-10-29
  checkout notes below document the prior gated rollout and are historical.
- Current local source is `master` at `31aefea` with uncommitted working-tree
  changes. `npm test` 396/396, `npm run typecheck`, `npm run build`, and
  `git diff --check` pass.
- Local Nuxt rehearsal was rebuilt and is healthy at `127.0.0.1:3101`; all 33
  manifest GETs return 200 or the expected 301 canonical alias redirect. The
  old image remains tagged for rollback. Production/Caddy/DNS/DB are untouched.
- Icon and interaction audit findings are tracked in
  [`ICON-INTERACTION-UI-AUDIT-20261030.md`](ICON-INTERACTION-UI-AUDIT-20261030.md).
  Full visual screenshot review, header touch submenu behavior, search modal
  background semantics, and 320px floating-action overlap remain open.

## Branch integration and preview coverage — 2026-10-31

- `origin/feature/pts-nuxt-integration-20261027` is already an ancestor of
  `master`; it has no commits missing from the local integration branch.
- The archived ref `archive/nuxt-progress-codex-20261031` has three commits
  outside `master` (including its merge commit). Its remaining changes combine
  dmux workflow/Stop-hook behavior with an earlier coverage-audit revision, so
  the tag is not merged wholesale. The safe read-only audit is already present
  as `audit-nuxt-progress.cjs` at `bdd4eb9`; alias paths may share a preview page,
  and filesystem scanning is limited to `frontend/app/pages`. Its seven unit
  tests pass. Against the current root, all **34/34 manifest route entries**
  map to an existing page file, with zero missing pages; 27 migration page
  files exist and six are outside the public migration manifest.
- Five unmanifested pages are internal runtime/shell/site/hub/UI-lab utilities
  and declare noindex. `migration-gallery-detail-preview.vue` is a real detail
  preview component, but no verified gallery slug is in the route manifest;
  add one only when backed by a real PHP/DB fixture. Do not invent a slug.
- This audit proves only route-manifest-to-page-file coverage. It does not prove
  visual parity, public routing, SEO, API behavior, commerce, security or release
  readiness. Production, DB, feature flags and routing were not touched.

## Follow-up — archived refs and rich-text negative coverage — 2026-10-31

- Rechecked refs and worktrees: only `master` and the known remote Nuxt feature
  ref are branches; the feature ref and applicable backup tags are ancestors of
  `master`. The only other commits on the archived progress tag are dmux
  workflow/hook changes plus its merge; these remain excluded. The two unique
  safety-tag commits are legacy PHP bulk-admin/UI work, not missing Nuxt code.
- Stash snapshots were left unchanged. `stash@{5}` contains an older checkout
  draft that collects order details and posts an order from the preview; it was
  not applied because current Nuxt preview is intentionally read-only and its
  checkout POST returns 410. This keeps the checkout safety invariant intact.
- Expanded `scripts/test-pts-public-solution-detail-api.php` with a rich-text
  attack fixture covering event handlers, inline CSS, `javascript:` links, SVG,
  remote images and entity-encoded script markup. The PHP 8.1 image ran this
  slice test in an isolated `--network none` container: **11 PASS, 0 FAIL**.
  The sanitizer implementation was unchanged; this adds regression coverage.
- Built local QA image `web_pts_php-nuxt-service:qa-current-wip-20261031-r4`
  (`sha256:a5aaac65378c99dbeed8654c04485a1bda38ad53781803ec54796b2818abb02d`).
  Isolated runtime smoke returned 200 for site data, home, a category resolved
  from the API, PDP and noindex preview; unknown route returned 404. The QA
  container/network were removed and the local API bridge was restored to its
  original network. This is not full PHP-renderer parity or a production gate.

## Commerce checkout handoff — 2026-10-29

- Nuxt `/thanh-toan/` is now behind the independent
  `checkoutPublicRouteEnabled` flag (Nuxt config and compose default `false`).
  Flag OFF redirects to PHP `/gio-hang/`, where the legacy checkout remains
  available. The flag is not enabled in production.
- With the flag ON, the browser submits a native URL-encoded POST directly to
  the configured PHP public origin at `/gio-hang` (without a trailing slash),
  using PHP's `thanhtoan`, `form_csrf`,
  `checkout_token`, and `dataOrder[...]` fields. `Caddyfile.local-full-nuxt`
  already forwards POST `/gio-hang` to local PHP when the configured public
  origin is the Caddy listener. Nitro's
  `POST /_migration/checkout` intentionally remains HTTP 410 and never writes
  orders. PHP still owns session, CSRF, idempotency, validation, authoritative
  price/shipping, order transaction, email, payment and success redirect.
- PHP redirects from `sources/order.php` use relative locations such as
  `gio-hang/` and `cam-on/?token=...`. Posting to `/gio-hang` without the final
  slash makes the browser resolve these at the site root; `/gio-hang/` would
  incorrectly nest them under `/gio-hang/`. Local direct Nuxt access on
  `:3101` can hand off to PHP `:8080` through `phpPublicOrigin`; browser and PHP
  origins must use the same hostname so the PHP session cookie is sent. The
  Caddy matcher supports the hybrid front-door path when configured as origin.
- PHP validation failures store session flash and redirect back to `/gio-hang`.
  When Nuxt cart is enabled, CartPage reads the one-time notice through the
  separate `/_migration/checkout-notice` GET adapter and carries allowlisted
  form fields in memory-only Nuxt state to checkout; SiteHeader does not consume
  it. Successful legacy redirects to `/cam-on/?token=...` (or VNPay); its
  existing order-success flag decides whether Nuxt or PHP serves the return.
- Verification here is structural and GET-only; no checkout POST, order, DB
  mutation, production deployment or route-flag change was performed.

## UI contrast follow-up — 2026-10-29 21:47 +07:00

- Đã sửa `HomeHero.vue` tại `59d791a`: ảnh hero trống/lỗi giờ chuyển section sang
  nền `slate-50`, ẩn overlay gradient và giữ chữ navy đọc được; ảnh tải bình thường
  không thay đổi giao diện production.
- Các chữ xanh nhạt trên nền navy ở footer/dealer/CTA được đối chiếu computed style
  và design token, đạt tương phản; giữ nguyên vì là màu link/nội dung phụ đã duyệt.
- `npm run build` PASS và container `pts-nuxt-service-rehearsal` đã rebuild healthy
  tại `127.0.0.1:3101`; smoke route không có 5xx hoặc lỗi runtime.

## Verification checkpoint — local container rebuild — 2026-10-29 21:27 +07:00

- `master` đã fast-forward từ `e7c68e4` lên `eb2732d`; WIP trước đó được giữ nguyên
  trong `stash@{0}` (`checkpoint master WIP before Nuxt integration eb2732d`), không
  xóa dữ liệu hay stage lại ngoài ý muốn.
- Rebuild local-only `docker-compose.nuxt-service.yml` hoàn tất từ HEAD mới; container
  `pts-nuxt-service-rehearsal` chạy healthy tại `127.0.0.1:3101`. Không đổi Caddy,
  DNS, production container, DB volume hoặc public flag mặc định.
- Container smoke các route public trả 200 hoặc fallback redirect hợp lệ; log 2 phút
  gần nhất không có `error`, `fatal` hay `unhandled`. `npm test` **364/364 PASS** và
  `npm run typecheck` **PASS** trên đúng HEAD master.
- Negative GET smoke trên container xác nhận PDP hidden fixture và các slug không tồn
  tại cho catalog/news/solution/project đều trả **404, không canonical**; bốn hidden
  fixture theo DB vẫn giữ `NEEDS VERIFICATION` vì local DB không có bản ghi ẩn tương ứng.
- Full visual compare mới nhất giữa PHP `:8080` và Nuxt preview `:3112` đạt **99/99**
  route × viewport; không có lỗi preview request/media/runtime/overflow. Một số CLS
  được ghi nhận chỉ nằm ở PHP reference và không làm Nuxt gate fail. Evidence:
  `frontend/.migration-artifacts/migration-comparison-20261029-final6/`.
- File `.env` local hiện bật một nhóm route preview để xem trực tiếp (About,
  Solutions, Catalog, PDP, Brand, Search, Cart, Order Success); các route còn lại
  vẫn fallback PHP. Đây là override local, không phải cấu hình production.

## Verification checkpoint — 2026-10-29 21:17 +07:00

- Worktree `feature/ui-layout-upgrade-20261029` đã build từ source hiện tại; chưa
  đổi production, Docker topology, DB schema, DNS/Caddy hoặc public flags mặc định.
- `npm test` **364/364 PASS**, `npm run typecheck` **PASS**, visual harness tests
  **22/22 PASS**, build SSR **PASS**, `git diff --check` **PASS**.
- Public flags ON: `frontend/.migration-artifacts/nuxt-route-gates-20261029-final/report.json`
  đạt **34/34 PASS** (33 manifest routes + hidden PDP fixture 404, không canonical).
  Non-canonical slash variants trả 301 về path chuẩn.
- Public flags OFF: cùng manifest đạt **33/33 PASS**, mọi route kết thúc ở PHP
  loopback `:8080`; không follow redirect ra ngoài local.
- SEO GET-only rerun đạt sitemap **457/457 URL, 0 fail/redirect**, href target
  **361/361 HTTP 200**, và PDP hidden fixture **404/no canonical**. Gate tổng
  vẫn `NEEDS VERIFICATION` vì DB local không có hidden record cho catalog/news/
  project/solution; audit cố ý exit 1 theo policy, không bịa slug. Evidence:
  `frontend/.migration-artifacts/seo-audit-final-20261029-rerun/`.
- Visual compare PHP `:8080` ↔ Nuxt `:3112` đạt **99/99 preview health gates**
  (33 route × 3 viewport) ở
  `frontend/.migration-artifacts/migration-comparison-20261029-final5/`; full-page
  PNG là evidence để review theo section, không phải pixel-perfect sign-off.
  Harness đã sửa lỗi phân loại `--public-routes` và giới hạn chờ ảnh lazy để
  không báo leak/treo giả.
- Adapter đã siết query/slug/bounds theo PHP; shell đã loại hydration mismatch
  của GTranslate; route middleware đã chuẩn hóa slash khi flag bật. Account/Admin,
  API, media transform, session/cart calculation và order/payment vẫn giữ PHP owner.
- Còn mở trước cutover: review thủ công các full-page PNG, hidden fixture cho
  bốn family chưa có record DB phù hợp, session order-success hợp lệ (không tạo
  đơn), sitemap/SEO authority và staging/hybrid approval. Không được xóa PHP.

## Checkpoint nền — 2026-10-29

- Nguồn tích hợp vẫn là `feature/pts-nuxt-integration-20261027` tại `d17f828`;
  thay đổi hiện tại còn WIP chưa commit. Không stage/commit phần việc của các lane
  dùng chung.
- Manifest đã mở rộng lên 33 route. First-fold browser gate đạt 99/99 lượt
  (33 route × 390/768/1440); full-page capture có 33 route desktop. Sau đó sửa
  H1 project category để khớp PHP (`Dự án Cá nhân`/`Dự án Doanh nghiệp`) và
  chụp lại hai route ở 3 viewport: 6/6 HTTP 200, không lỗi media/overflow/runtime.
  Ma trận và bằng chứng nằm trong
  [`UI-ACCEPTANCE-AUDIT-20261028.md`](UI-ACCEPTANCE-AUDIT-20261028.md).
- `npm test` **311/311 PASS**, `npm run typecheck` **PASS**, build snapshot mới
  **PASS** (25s), `git diff --check` **PASS**. Build còn warning bundler
  `INEFFECTIVE_DYNAMIC_IMPORT`/`PLUGIN_TIMINGS`, không fatal.
- Public canonical compare: 31 route × 3 viewport = 93/93 HTTP 200 hai phía.
  Flags OFF: 32 legacy path duy nhất fallback về PHP. Tất cả preview Nuxt vẫn
  noindex; flag chỉ bật trong process test riêng và các process đó đã dừng.
- SEO GET audit: sitemap `PASS` (457 `<loc>`, 0 fail/redirect); href mapping
  `PASS` (33/33 route, 380/380 target HTTP 200; khác path chỉ để review tay).
  Hidden-slug gate `NEEDS VERIFICATION`: 1/5 PASS; bốn họ không có record ẩn
  phù hợp trong DB local nên không tạo slug giả. `tin-tuyen-dung` là category
  rỗng có thật, tách sang `manualReviewRoutes`; PHP trả 200/index, Nuxt fallback
  về PHP cho tới khi chốt chính sách category rỗng.
- Đã sửa thêm một parity lỗi: H1/tile project category trước đó bỏ chữ “Dự án”.
  Regression test cùng test phân loại manual-review route được cập nhật.
- Dealer stats và PDP data projection đã được xác minh ở checkpoint 2026-10-29
  bên dưới. Còn mở: policy category rỗng và fixture session hợp lệ để kiểm tra
  mã đơn/conversion; checkout vẫn là preview read-only. Bản integration tại
  checkpoint `d17f828` từng trả `/cam-on/` 404 khi Nuxt flag ON và thiếu token.
  Re-audit trong worktree riêng `feature/ui-layout-upgrade-20261029` đã sửa
  trạng thái chung thành 200/noindex theo PHP; thay đổi còn WIP, chưa gộp. Không
  tạo đơn để thử.
- Local PHP/API và MariaDB container đang chạy. `:8090` không listen và không có
  Nuxt/Caddy cutover container hiện tại; không rebuild/đổi container, Caddy,
  DB, production hay feature flags. Public cutover chưa hoàn tất.
- PDP/Dealer đã được review trực tiếp trên preview cô lập sau source delta.
  Việc tiếp theo là chốt policy category rỗng, fixture an toàn cho chi tiết
  order-success/conversion và href khác path; sau đó nghiệm thu trên service
  preview mà owner dùng để xem. Không bật public/indexability flags cho tới khi
  các gate đó đóng.

Kế hoạch gốc được lập tại `feature/pts-nuxt4-parity-qa-20261026`, branched from
`feature/pts-nuxt4-migration` ở `b5865ac`. Các SHA cũ trong phần kế hoạch bên
dưới là mốc lịch sử, không phải HEAD hiện tại.

## Historical checkpoint — 2026-10-28

- Nguồn tích hợp: worktree `/home/admin01/src/pts-nuxt-integration-20261027`,
  branch `feature/pts-nuxt-integration-20261027`, HEAD `8133b8b`; UI acceptance
  audit đang ở working tree, chưa commit.
- Code audit đã đóng các P1 có source proof cho catalog/PDP/brand, home/contact,
  about/dealer, editorial CTA, project media/related content và checkout preview.
  Chi tiết route theo route tại
  [`UI-ACCEPTANCE-AUDIT-20261028.md`](UI-ACCEPTANCE-AUDIT-20261028.md).
- Sau diff hiện tại: `npm test` 179/179 PASS, `npm run typecheck` PASS,
  `npm run build` PASS trong 27.6s, `git diff --check` PASS. Build có warning
  `INEFFECTIVE_DYNAMIC_IMPORT`/`PLUGIN_TIMINGS`, exit 0.
- Visual/runtime gate vẫn BLOCKED: Nuxt `127.0.0.1:3100/` trả 404,
  `/_migration/site` trả 502; PHP preview/API `8080`/`8088` không listen; không
  có PHP CLI. Không dùng Lane 1 screenshots (build trước) làm sign-off cho diff
  này. Preflight giữ feature flags OFF và pilot About chưa sẵn sàng.
- Production, DB, Caddy, container, feature flags và ports `3101`/`8090` không
  bị thay đổi. Không chạy cleanup/backup, không commit/push/merge/deploy.

## Kết luận audit hiện tại

- PHP vẫn là owner của public resolver, canonical/redirect, pricing authority,
  session/cart calculation, order/payment, account, admin và media transform.
  Nuxt hiện có các preview noindex cho home, catalog, PDP, search, cart,
  read-only checkout state, order success, editorial/corporate pages,
  policy/support detail và các detail route. Cart, lead, review và shipping
  adapters vẫn có PHP integration; checkout preview POST trả 410, không tạo
  order và không forward sang PHP.
- Các adapter Nitro dùng chung trách nhiệm ghép `runtimeConfig.phpOrigin` với
  endpoint allowlist, timeout và contract guard; rule query/slug, CSRF/session
  forwarding và cache header vẫn thuộc từng module theo nghiệp vụ.
- `frontend/server/utils/migration-api.ts` là điểm dùng chung mới cho phần lặp
  trên. Các validator DTO vẫn nằm trong `frontend/shared/contracts/*`; PHP DTO
  vẫn nằm cạnh `api/pts_v1_*.php` và `libraries/pts_public_*_api.php`, tránh tạo
  thêm projection song song.
- Các gap trước cutover: production route/hybrid routing; catalog visual/browser
  parity cuối. Filter theo loại sản phẩm/thương hiệu/mức giá, sort và panel danh
  mục đã nối thật ở `c00aa0e`, `ae811bb`; brand detail đã có preview ở
  `f0cf5ce`; gallery/video internal previews đã được tích hợp ở checkpoint
  2026-10-28. Còn lại là public canonical/OG/sitemap/robots/redirect parity,
  full media/browser parity, account routes và deployment gates.
  Không dùng preview 200 để claim production migration.

## Checkpoint source audit — 2026-10-29

- PDP media/content projection đã được kiểm lại trên 372 URL sitemap: toàn bộ
  GET API trả 200; ứng dụng CMS được tách thành section riêng, hướng dẫn lựa
  chọn chỉ dùng thông số có giá trị. Không đưa ảnh/copy profile fallback của
  template PHP vào Nuxt khi Admin chưa quản lý media/nội dung tương ứng.
- Dealer stats đã đối chiếu lại với PHP `:8080`, PHP migration `:8088` và
  template: `5 / 50 / 5000`; DTO/UI không hardcode số. Rich text của support
  được xuất qua sanitizer và render giữ cấu trúc đoạn/list.
- Frontend suite sau sửa: `311/311` PASS. Typecheck/build chạy ở bản sao cô lập;
  kết quả và giới hạn visual được ghi tại
  [`UI-ACCEPTANCE-AUDIT-20261028.md`](UI-ACCEPTANCE-AUDIT-20261028.md).
- Local preview phải dùng `scripts/nuxt-preview-launch.py` để upstream vào
  PHP migration `:8088`; PHP legacy `:8080` không có cùng DTO schema. Nuxt
  source mặc định vẫn là `:8080`, vì vậy deploy/preview không qua launcher phải
  cung cấp `NUXT_PHP_ORIGIN` đúng môi trường.
- Đã chụp hai route PDP/Dealer ở ba viewport trên Nuxt SSR cô lập `:3116` sau
  source delta: 6/6 preview health gates PASS; đã review thủ công full-page
  desktop và ảnh responsive mobile/tablet. Nuxt không có broken media, overflow,
  runtime error, failed request hoặc preview-link leak. PHP reference ghi nhận
  10 PDP và 15 Dealer broken-media requests, được phân loại là lỗi phía PHP,
  không tính thành lỗi Nuxt. Đây là review của hai route, không phải sign-off
  toàn bộ 33 route hay pixel-diff tự động; bằng chứng ở audit delta bên cạnh.
- Còn chờ: xác nhận valid order-success session an toàn; bốn nhóm
  negative-slug chưa có hidden row trong DB; rà href khác path bằng review thủ
  công; staging/hybrid routing và full 33-route visual review sau source delta.
  Public flags, production, database và container Nuxt chưa đổi.

## QA nền tảng chuẩn

| Lớp | Lệnh / công cụ | Gate |
|---|---|---|
| DTO contract | `npm test --prefix frontend` | Validator và fallback không bịa dữ liệu |
| Nitro/TypeScript | `npm run typecheck --prefix frontend` | Không lỗi TS; vue-router 5.4.0 đã khớp Nuxt 4.6.2; `.nvmrc` pin Node 24.20.0; `npm audit --omit=optional` hiện 0 vulnerability sau overrides; CI workflow đã khai báo |
| Nuxt artifact | `npm run build --prefix frontend` | SSR build PASS, không bật public route |
| PHP API | `php scripts/test-pts-public-*-api.php` trong PHP 8.1 Docker | GET/405/400/404, published filter, media safety |
| HTTP | `curl`/Playwright tới `:8080`, `:8088`, `:3100` | Status, `Allow`, noindex, canonical ownership |
| Browser | `tools/visual-tests/scripts/migration-compare.cjs` | 390×844, 768×900, 1440×900; screenshot/header/diff |
| Runtime browser | migration harness | console/pageerror/requestfailed, broken media, horizontal overflow |
| Responsive/SEO | report `report.json` + `summary.md` | title/canonical/robots/H1/nav/hrefs; diff phân loại heuristic |

Lệnh parity thực tế:

```bash
PLAYWRIGHT_BROWSERS_PATH=/home/admin01/src/Web_PTS_PHP/tools/visual-tests/.browsers \
NODE_PATH=/home/admin01/src/pts-nuxt4-migration/tools/visual-tests/node_modules \
node tools/visual-tests/scripts/migration-compare.cjs \
  http://localhost:8080 http://localhost:3100 \
  --manifest tools/visual-tests/fixtures/migration-routes.json \
  --output frontend/.migration-artifacts/migration-comparison
```

Output gồm ảnh `*-legacy.png`, `*-preview.png`, `*-diff.png`, ảnh header nếu
trang có banner, `report.json` và `summary.md`. Harness mặc định read-only,
loopback-only, reduced motion, không login/mutation, không update golden. Phân
loại `dynamic-data-difference` chỉ là heuristic khi fingerprint geometry giống
nhưng text/media/link khác; không được dùng thay visual review.

## Task có thể giao song song

Mỗi task dưới đây phải tạo child worktree từ SHA mới nhất của nhánh tích hợp,
chỉ sửa file ownership tương ứng, chạy focused test rồi bàn giao SHA/report.

| Thứ tự | Task | Owner file scope | Phụ thuộc | Definition of done |
|---|---|---|---|---|
| P0-A | Freeze route/resolver inventory | `docs/14-nuxt-migration/ROUTE_PARITY_MATRIX.md`, route manifest; chỉ đọc `libraries/router.php`, resolver, templates | Không | Cập nhật route/status/redirect evidence; mọi điểm chưa biết có lệnh xác minh |
| P0-B | Local fixture/API integration | PHP 8.1 Docker, `api/pts_v1_*.php`, `libraries/pts_public_*_api.php`, PHP contract scripts của từng slice | P0-A | GET thật với DB fixture, positive/negative status, không DB write; không sửa public route |
| P0-C | Platform QA/CI gate | `tools/visual-tests/scripts/migration-*`, `tools/visual-tests/tests/migration-*`, package scripts CI nếu cần | P0-A | Chạy manifest đủ 3 viewport; report ảnh/JSON/Markdown có status, console, media, overflow, SEO và classification |
| P1-A | Shared site shell parity | `frontend/shared/contracts/public-site.ts`, `frontend/app/components/SiteHeader.vue`, `SiteFooter.vue`, shell CSS/tests | P0-B, P0-C | Logo/menu/footer grouping theo CMS thật; mobile keyboard/Escape/focus; visual diff không còn P0 |
| P1-B | Solutions rich-content gate | `libraries/pts_public_solution_detail_api.php`, sanitizer module mới, solution detail contract/components/tests | P0-B, P1-A | Allowlist HTML/media/TOC có test XSS-negative; so sánh CKEditor thật; không dùng `v-html` chưa gate |
| P1-C | Catalog business parity | catalog PHP projection/contract + catalog preview files/tests | P0-B, P1-A | Pricing authority, filters/sort/pagination/category URLs được chứng minh; cart/quote vẫn bàn giao PHP |
| P1-D | Content hub/detail parity | news/projects/about PHP projections, contracts/components/tests | P0-B, P1-A | Published/hidden/category/canonical/media/related semantics và responsive evidence |
| P2-A | SEO/canonical/redirect crawl | PHP resolver/SEO read-only audit + `docs/14-nuxt-migration/*` | P0-A, P1 slices | Crawl diff route/status/301/404/robots/canonical/JSON-LD; chưa cutover |
| P2-B | Hybrid route pilot | Caddy/local proxy allowlist + runbook; không mở cart/api/admin/media | P1-A, P1-B, P2-A | Chỉ một GET route được feature flag OFF mặc định, rollback rehearsal, local HTTP evidence |
| P3 | Production cutover review | release docs/config only after approval | Tất cả P0–P2 | Owner approval riêng; backup/rollback/health check; ngoài phạm vi batch này |

Không giao đồng thời các task chiếm Docker/build/browser nặng. PHP API owner
không sửa Vue/CSS; frontend owner không sửa PHP; QA owner không cập nhật golden
âm thầm. Khi task gặp dữ liệu/CMS chưa xác minh, ghi `NEEDS VERIFICATION` và
chuyển checkpoint thay vì bịa fixture.

## Hạng mục có thể triển khai ngay sau batch này

1. Chạy local PHP 8.1 contract/integration trên fixture hiện có, sau đó chạy
   manifest parity trên đúng `localhost:8080` và `localhost:3100`.
2. Sửa các lỗi runtime/HTTP/console/overflow mà report đánh dấu; không sửa chỉ
   vì pixel ratio khi classification là data-only và nội dung còn khác contract.
3. Chốt shell CMS/navigation trước khi triển khai từng page slice; đây là owner
   chung của mọi preview.
4. Giữ sanitizer rich-text và TOC là gate riêng đã có regression coverage; chỉ
   xác nhận CKEditor parity sau khi có cùng dữ liệu và ảnh render PHP/Nuxt để so
   sánh trực tiếp.
5. P0/P2 dùng manifest và crawler read-only; chỉ sau khi P0 đạt mới ký SEO
   canonical/redirect hoặc giao hybrid route. Không trộn business migration vào
   công cụ QA.

## Rollback / production state

Rollback code của batch là revert các commit trên child branch hoặc bỏ worktree
`/home/admin01/src/pts-nuxt4-parity-qa`; không có migration/schema/DB write,
không đổi container production, không đổi DNS/Caddy public, không push và không
deploy. Worktree gốc `/home/admin01/src/pts-nuxt4-migration` giữ nguyên.

Dry-run production-readiness, hook Caddy tương lai, allowlist fail-closed,
canary thresholds và gate matrix được chốt tại
[`PRODUCTION-CUTOVER-DRY-RUN.md`](PRODUCTION-CUTOVER-DRY-RUN.md). Checkpoint này
không thêm Nuxt service production, không nối runtime flag và không đổi public
route ownership.

## Follow-up — catalog media and news article alignment — 2026-10-31

- News detail now omits its empty TOC column. Articles without generated H2/H3
  entries use one centered reading column (`max-width: 920px`); articles with a
  TOC retain the existing two-column layout. Local SSR for a published article
  with no headings confirmed no TOC and a centered 920px content area.
- Current local PHP/API data exposes 27 published product-category paths.
  Nuxt's API returned the matching canonical category path for all 27, and all
  27 route and hero image requests returned HTTP 200 after packaging the 41
  currently referenced curated site-visual assets in `frontend/public`.
  Clicking the `Đèn Phá` category link also opened its matching path, heading,
  selected state and hero image. The asset bundle adds about 68.8 MiB to the
  frontend public tree; review image optimization separately before release.
- Focused catalog/editorial/link tests pass **51/51** and Nuxt typecheck passes.
  A focused social-metadata follow-up aligns five empty-description routes to
  the PHP site-setting fallback; it does not replace the open 34-route
  two-renderer SEO comparison.
- Production remains PHP-owned. No database, PHP business logic, route flags,
  Caddy, deployment or production service was changed.

## Follow-up — Nuxt route health and solution rich-text — 2026-10-31

- Reused the 34-route public manifest against local Nuxt `:3100` with GET only.
  All **34/34** routes returned HTTP 200, the manifest's expected canonical
  path, exactly one H1 and local `noindex`. The metadata-only result is retained
  at `frontend/.migration-artifacts/current-nuxt-route-health-20261031.json`.
  PHP `:8088` is API-only, so this does not establish PHP HTML parity or close
  the 3-viewport browser gate.
- The direct PHP solution-detail DTO and Nuxt adapter both returned 200 for the
  published family-solar fixture. PHP content uses the validated sanitized-HTML
  envelope; Nuxt SSR contains one H1 and 27 generated TOC links. Focused Nuxt
  contracts passed **23/23**, and the isolated PHP 8.1 solution-detail helper
  test passed. Sanitizer/TOC behavior is covered; visual CKEditor parity remains
  open pending a same-data PHP render.
- Local branch inventory has two refs (`master`, `updateUI`) at the same SHA and
  no missing remote Nuxt feature commits. The duplicate ref/worktree is retained
  because another checkout recreated/uses it and contains ignored data. No
  production state was changed.
