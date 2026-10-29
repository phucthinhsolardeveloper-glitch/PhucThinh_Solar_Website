# PTS Nuxt migration — next execution plan

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
4. Tách sanitizer rich-text thành một gate độc lập trước khi có bất kỳ claim
   parity nội dung CKEditor nào.
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
