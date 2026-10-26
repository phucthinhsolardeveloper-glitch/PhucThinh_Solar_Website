# PHÚC THỊNH SOLAR — SHARED-FIRST NUXT CONVERSION PLAN
Ngày 2026-10-26. Owner: ChatGPT kiến trúc/QA; Codex CLI thực thi; người dùng duyệt UX/cutover.

## 0. Tài liệu nền và trạng thái
Kế hoạch gốc 260 dòng đã được lập: PTS_Nuxt4_Frontend_Migration_Plan_2026-10-26.md (file tải trong hội thoại).
Tài liệu sống trong repo: docs/14-nuxt-migration/AGENT_EXECUTION.md, BASELINE.md, ROUTE_AND_WORKSTREAM_MATRIX.md, LOCAL-PREVIEW.md, CODEX_WAVE3_20261026.md, CODEX_WAVE4_20261026.md, CODEX_WAVE5_20261026.md.
Nuxt: feature/pts-nuxt4-migration@b5865ac lúc audit; original master 5a48863 có bảy docs sửa chưa commit; KHÔNG checkout/reset/stash/commit chúng.
Codex người dùng vừa khởi chạy: /home/admin01/src/pts-nuxt4-parity-qa, nhánh feature/pts-nuxt4-parity-qa-20261026. Khi audit chưa thấy thay đổi Git; đây là phần QUYỀN SỞ HỮU QA/visual/route inventory; tránh giao trùng. Stitch worktree riêng, giữ nguyên.
Chưa xác nhận prod SHA, chưa phê duyệt public cutover, không deploy.

## 1. Đừng nhầm 16 route preview thành 16 hệ thống
Mã hiện tại: frontend/app/app.vue chỉ NuxtPage; từng page preview lặp SiteHeader, SiteFooter, fallbackSite, useFetch, breadcrumb, noindex. Có 13 Nitro /_migration GET adapters lặp getMethod/phpOrigin/timeout/502/no-store; các hợp đồng public-*.ts tự lặp record/text/url/image validation. Backend PHP có nhiều API đọc CMS đang chạy qua local 8088.
Chuyển theo HỌ TEMPLATE + LUỒNG, không tiếp tục mở một worktree cho mỗi page.

| Họ | Dùng cho | UI/controller dùng chung |
| --- | --- | --- |
| T0 Shell | Tất cả | header, footer, mobile nav, breadcrumb, layout, error, data/SEO |
| T1 Home | / | hero/banner, danh mục nổi bật, sản phẩm, content blocks |
| T2 Catalog | listing, category 1-4 cấp, brand, search | filters, pagination, listing grid, quote/price; giữ PHP business rules |
| T3 Product Detail | mọi profile sản phẩm | gallery, specs, tabs, related, CTA; cart/checkout PHP |
| T4 Editorial Listing | Tin tức, Dự án, Giải pháp | featured, category, cards, pagination |
| T5 Editorial Detail | article, project, solution, policy | hero, sanitized CKEditor, TOC, related, gallery |
| T6 Corporate/Lead | Giới thiệu, Liên hệ, Đại lý | content blocks, map, locations, PHP CSRF form handoff |

PHASE A: UI giống PHP/giữ nghiệp vụ; PHASE B mới cải tiến Apple-like. Không copy CSS/jQuery cũ toàn bộ hoặc để AI tạo bố cục marketing tùy ý.

## 2. Chuẩn dùng chung — thứ tự cần ưu tiên
S1 Nitro fixed PHP client: một module server utils nhận ALLOWLIST endpoint, GET only, query validators, timeout, status 400/404/405/502, response unknown validator, không forward cookies/headers, không có arbitrary upstream URLs, safe no-store. Thí điểm refactor 2-3 adapters, regression giữ response như cũ; sau đó áp dụng cho 13 adapters. Không thay PHP session/cart.
S2 Shared DTO primitives: shared/contracts/core.ts cho record/text/slug/public URL/image/pagination, thống nhất schemaVersion=1; contract fixtures hidden/empty/unpublished/no media. Không thay API shape đang có; không suy đoán giá, tồn kho, publish state.
S3 Shared layout: layouts/migration.vue hoặc layout component, composables/usePublicSite.ts và shared SSR site data, one-request shell per page, breadcrumb, SEO/noindex, loading/error/empty, CSS tokens; thí điểm migration-about-preview.vue, tránh sửa cùng lúc 16 trang.
S4 PHP CMS safe content: riêng libraries/pts_public_richtext.php và test XSS để có HTML đã sanitize (h1-h6,p,a,img,ul,ol,strong,em,table theo dữ liệu CKEditor thực). Giữ headings/tables/images thay vì chuyển thành plain text; không v-html raw CKEditor; không render CSS/script/iframe unsafe.
S5 SEO + shared origin: SEO helper lấy canonical/meta/OG/JSON-LD từ PHP authority, preview noindex; xác định 301/404 + URL route ownership. Tạo LOCAL hybrid proxy allowlist để PHP giữ /api, /upload, /thumbs, /administrator, login/cart/checkout/form/POST và fallback, Nuxt chỉ nhận GET verified. Rollback local một lệnh. KHÔNG sửa VPS prod config nếu chưa phê duyệt.
S6 QA parity: worktree parity-qa do người dùng đã giao. Screenshot PHP vs Nuxt cùng fixture 390/768/1440, sau đó 360/1366/1920; captures nav/hover/menu, keyboard, mobile, filter, products, SEO, status, network, console, text/markup, media. Không cập nhật golden tự động. HTTP 200 đơn thuần không là parity.

## 3. Lịch chạy và file ownership cho Codex
Codex 0 — QA đang giao bởi user:
  Worktree: /home/admin01/src/pts-nuxt4-parity-qa
  Own: tools/visual-tests/**migration**, frontend/tests/**migration parity**, docs/14-nuxt-migration/NEXT-EXECUTION-PLAN.md, route matrix.
  Không giao trùng, không merge khi còn P0, không sửa app/runtime nếu chưa thông báo.

Codex A — shared PHP client + DTO validation (ưu tiên #1):
  Branch: feature/pts-nuxt-shared-data-20261026
  Worktree: /home/admin01/src/pts-nuxt-shared-data-20261026
  New files owner: frontend/server/utils/php-public-client.ts; frontend/shared/contracts/core.ts; frontend/tests/shared-data*.test.mjs.
  Existing files permitted as SMALL pilot: frontend/server/routes/_migration/{site,about,projects}.get.ts; corresponding DTO types if needed.
  Must preserve GET/headers/error status/schemaVersion, no cookie forwarding; compare live local fixture response. Unit tests incl malformed query/timeout/404/hidden/502. Do not touch PHP, UI/layout, QA, prod.
  Done: 3 pilot adapters use single safe client and strict contract tests; no regression.

Codex B — common layout, CSS primitives (ưu tiên #1):
  Branch: feature/pts-nuxt-shared-layout-20261026
  Worktree: /home/admin01/src/pts-nuxt-shared-layout-20261026
  New files owner: frontend/app/layouts/migration.vue; app/composables/usePublicSite.ts; app/components/layout/**; app/assets/css/layout-*.css; frontend/tests/shared-layout*.test.mjs.
  Existing file permitted as pilot: frontend/app/pages/migration-about-preview.vue ONLY; avoid direct editing shared DTO + Nitro + PHP + visual QA.
  Need SiteHeader/Footer site DTO fetched once in SSR, correct noindex, reuse semantic class/tokens; error fallback, accessible desktop/mobile; before/after visual comparator and no hydration regression.
  Do not refactor other 15 preview pages until pilot passes.

Codex C — safe CMS HTML (ưu tiên #2, bắt đầu khi A/B gần xong):
  Branch: feature/pts-nuxt-richtext-safety-20261026
  New files: domains/phucthinhsolar.com/public_html/libraries/pts_public_richtext.php; scripts/test-pts-public-richtext.php; frontend/app/components/editorial/**; docs/14-nuxt-migration/RICHTEXT-CONTRACT.md.
  No simultaneous edits to 3 detail PHP controllers. Pilot one solution detail after reviewer approval.
  Must pass CKEditor fixture and malicious payload tests; preserve public image/anchor/heading/list/table semantics, reject script/event/javascript URLs/unsafe iframe; no new unsafe external dependency.

Codex D — local hybrid route wiring (ưu tiên #3):
  Branch: feature/pts-nuxt-local-hybrid-20261026
  New files owner: deploy/local-nuxt-hybrid/**, scripts/test-local-hybrid*.sh, docs/14-nuxt-migration/LOCAL-HYBRID.md.
  No edits deploy/vps or production Caddy/Docker. Test GET allowlist to Nuxt, backend POST PHP, /api, media, account, admin, cart, checkout, invalid slugs, 301, 404, session, rollback.
  Only start after data/layout and QA source route-matrix unblock.

Hardware rule: WSL allocation ~7.8 GiB, swap ~4 GiB often mostly used. Maximum 2 concurrent CODE agents plus QA light; serialize Nuxt build, Docker tests and Playwright Chromium. More agents ≠ faster when memory thrashes.
Workflow: create child worktrees from current clean integration SHA; small commits/tests; external QA reviewer runs integration npm test/typecheck/build once; merge only with green tests; clean child worktree/branch; never overwrite user QA branch.

## 4. Template migration after common foundation (vertical slice GO gates)
V1 Commerce (priority highest): catalog/category -> PHP-backed sort/filter/quote/price -> PDP details -> PHP cart and checkout. Build shared product components; verified source PHP public_pricing.php; no price calculator in Vue. Check every active search/brand/category/canonical/redirect path.
V2 Editorial: one listing and one detail working end-to-end with shared sanitized content; apply to news, projects, solutions and policy, then tests SEO, TOC, gallery, related.
V3 Corporate: about/contact/dealer through reusable content/location/form CTA, PHP form real CSRF via same-origin hybrid path.
V4 Homepage: connect all rails and hero CTAs to migrated or PHP-owned verified routes.
V5 SEO + canary staging: compare sitemap/robots/OG/JSON-LD/canonical/redirect, route ownership, performance; rehearsed rollback. Production only after user GO.

## 5. Definition of Done cho từng nhánh / vertical journey
[ ] Source PHP/template/SQL mapped: published+hidden+fallback verified against same CMS fixture.
[ ] Code uses shared DTO/layout/client, not copy-paste shell/adapter/validators.
[ ] PHP data and all price/filter/checkout business rules remain one source of truth.
[ ] Error and empty/loading states render correctly, status/canonical/noindex appropriate.
[ ] Screenshots same fixture at 390,768,1440; DOM headings/links, functionality, interactions, keyboard and accessibility checked.
[ ] Real local PHP HTTP, frontend unit tests, typecheck, Nuxt build, Chromium no pageerror/hydration/broken media/overflow; diff explained.
[ ] Security: no raw CMS HTML, arbitrary PHP proxy, leaked cookies/secrets, POST to wrong host.
[ ] One approved commit/merge, worktree cleanup; local hybrid rollback if touched.

## 6. Báo cáo tiến độ cần dùng, không đếm preview
Báo cáo theo: số **họ template đã chuẩn hóa**, số **API/DTO reuse**, số **hành trình nghiệp vụ đã đạt functional + visual + SEO parity**, P0 blockers, trạng thái GO/NO-GO.
Bằng chứng hiện tại: 16 Nuxt preview routes và 13 GET adapters; 52/52 unit PASS từ Wave 5, smoke Chromium PASS; nhưng CHƯA có vertical journey nào được nghiệm thu đầy đủ toàn bộ route/business/SEO/performance gates để cho phép cutover production.

Link preview: http://localhost:3100/migration-hub-preview
PHP local reference: http://localhost:8080
PHP migration API: http://localhost:8088

## 7. Quy tắc phối hợp với Codex đã chạy trước khi tài liệu này tạo
Yêu cầu Codex đang làm parity xuất docs/14-nuxt-migration/NEXT-EXECUTION-PLAN.md và test evidence. Sau khi Codex này commit, reviewer so sánh file-list với S1-S5: task trùng phải loại hoặc điều chỉnh ownership TRƯỚC KHI start Codex A/B. Không merge toàn bộ code refactor nếu không chứng minh giảm duplicate mà vẫn giữ behavior.
