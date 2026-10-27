# PTS Nuxt migration — next execution plan

Kế hoạch gốc được lập tại `feature/pts-nuxt4-parity-qa-20261026`, branched from
`feature/pts-nuxt4-migration` ở `b5865ac`. Current-state sync: HEAD
`57c786f`, với checkpoint triển khai `3b9cade..57c786f` và navigation
`35d0c5f`. Không merge/push/deploy production, không ghi DB trong đợt sync này.

## Kết luận audit hiện tại

- PHP vẫn là owner của public resolver, canonical/redirect, pricing authority,
  session/cart calculation, order/payment, account, admin và media transform.
  Nuxt hiện có các preview noindex cho home, catalog, PDP, search, cart,
  checkout, order success, editorial/corporate pages, policy/support detail và
  các detail route; form/review/order mutations đi qua adapter PHP allowlist.
- Các adapter Nitro dùng chung trách nhiệm ghép `runtimeConfig.phpOrigin` với
  endpoint allowlist, timeout và contract guard; rule query/slug, CSRF/session
  forwarding và cache header vẫn thuộc từng module theo nghiệp vụ.
- `frontend/server/utils/migration-api.ts` là điểm dùng chung mới cho phần lặp
  trên. Các validator DTO vẫn nằm trong `frontend/shared/contracts/*`; PHP DTO
  vẫn nằm cạnh `api/pts_v1_*.php` và `libraries/pts_public_*_api.php`, tránh tạo
  thêm projection song song.
- Các gap trước cutover: production route/hybrid routing; catalog stock parity
  (filter theo loại sản phẩm/thương hiệu/mức giá và sort đã nối thật ở `c00aa0e`,
  xem F02 trong ROUTE_PARITY_MATRIX.md); public canonical/OG/sitemap/robots/
  redirect parity ngoài PDP preview JSON-LD; brand/gallery/video/account
  routes; final visual/browser QA và deployment gates. Không dùng preview 200
  để claim production migration.

## QA nền tảng chuẩn

| Lớp | Lệnh / công cụ | Gate |
|---|---|---|
| DTO contract | `npm test --prefix frontend` | Validator và fallback không bịa dữ liệu |
| Nitro/TypeScript | `npm run typecheck --prefix frontend` | Không lỗi TS; warning Volar/vue-router phải ghi rõ |
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
| P0-A | Freeze route/resolver inventory | `docs/14-nuxt-migration/ROUTE_PARITY_MATRIX.md`, route manifest; chỉ đọc `libraries/router.php`, resolver, templates | Không | Cập nhật route/status/redirect evidence; mọi UNKNOWN có lệnh xác minh |
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
5. Chỉ sau khi P0 đạt mới giao catalog filter/sort/stock hoặc SEO crawl; không trộn
   business migration vào công cụ QA.

## Rollback / production state

Rollback code của batch là revert các commit trên child branch hoặc bỏ worktree
`/home/admin01/src/pts-nuxt4-parity-qa`; không có migration/schema/DB write,
không đổi container production, không đổi DNS/Caddy public, không push và không
deploy. Worktree gốc `/home/admin01/src/pts-nuxt4-migration` giữ nguyên.
