# Phase 3 — Release & Quality audit

Ngày audit: 2026-10-29  
Worktree: `codex/full-nuxt-public-ui-20261028` (`aea5bc0`)  
Phạm vi: kiểm tra read-only build, test, typecheck, SEO/source, Caddy/Compose và các release gate. Không sửa source sản phẩm, không commit, không deploy.

## Kết quả kiểm thử tự động

| Hạng mục | Kết quả | Bằng chứng |
|---|---|---|
| `npm run typecheck` | PASS (exit 0) | `evidence/release/typecheck-20261029.log` |
| `npm test` | PASS — 134/134, fail 0 | `evidence/release/test-20261029.log` |
| `npm run build` | PASS — Nitro build complete, 7.44 MB / 1.93 MB gzip | `evidence/release/build-20261029.log` |
| Production-like Caddy/Compose deploy | BLOCKED | Chưa có production SSH/DNS/container checkpoint; chỉ có rehearsal 8094 ở Phase 2 |
| Core Web Vitals/Lighthouse | BLOCKED | Chưa có đo trên production-like public edge |
| Commerce category→product→cart→checkout→confirmation | BLOCKED | Thiếu fixture/session/order/payment có thể xác minh; route matrix ghi PARTIAL |
| Auth/account CSRF/session end-to-end | PARTIAL | Negative lead 400, cart/review 419 và allowlist/CORS đã pass; chưa có authenticated browser flow |

## Findings

### REL-P1-001 — Chưa đủ bằng chứng production cutover/rollback

- Module: `deploy/vps/Caddyfile`, `deploy/vps/docker-compose.yml`, release scripts.
- Expected: một edge production-like chạy đủ Nuxt public HTML, PHP API/media/session exception, health-check và rollback candidate.
- Actual: source có cấu hình Caddy/Compose và rollback script, nhưng audit không có SSH/DNS/deploy checkpoint; Caddy 8094 chỉ là rehearsal local.
- Evidence: `evidence/release/seo-proxy-inspection-20261029.txt`, route matrix release checklist, Phase 2 Caddy screenshots.
- Root cause: chưa thực hiện release rehearsal bằng image/checksum và môi trường production-like.
- Proposal: dựng release candidate immutable, chạy smoke toàn bộ route/alias/API/media qua một edge, ghi checksum/health/rollback rồi mới cân nhắc cutover.
- Release blocker: **YES**.

### REL-P1-002 — Dynamic route resolver vẫn là blocker release

- Module: bare-slug route `[slug].vue` và PHP `pts_v1_public_route_resolve.php`.
- Expected: news/project/category/gallery slug public resolve được trên PHP origin production và trả canonical DTO đúng contract.
- Actual: PHP 8080 thiếu endpoint; Nuxt trả 502 cho dynamic news/project. Preview 8088 còn trả `canonicalPath`/`kind` lệch contract `canonical`/`entity`.
- Evidence: `03-functional.md` F-01; `04-backend.md` P1-BE-001/P1-BE-002; dynamic failure screenshots/logs.
- Root cause: endpoint/DTO giữa PHP root, preview checkout và Nuxt contract chưa đồng nhất.
- Proposal: deploy endpoint cùng contract version, chạy contract test trên đúng PHP image production và smoke từng loại entity.
- Release blocker: **YES**.

### REL-P2-003 — SEO preview/noindex chưa phủ hết route nội bộ

- Module: `frontend/app/pages/migration-about-preview.vue`, `frontend/nuxt.config.ts`.
- Expected: mọi `migration-*` preview/internal route đều `noindex, nofollow, noarchive`.
- Actual: config routeRules chỉ phủ một phần; `migration-about-preview.vue` không có evidence noindex tương đương.
- Evidence: `01-migration.md` MIG-P2-005; `evidence/release/seo-proxy-inspection-20261029.txt`.
- Root cause: noindex khai báo phân tán theo page, không có guard/prefix toàn cục.
- Proposal: routeRule/prefix hoặc composable bắt buộc cho toàn bộ preview route; crawler smoke header/meta.
- Release blocker: **YES** nếu preview host/public URL có thể crawl.

### REL-P2-004 — Commerce/auth chưa có regression evidence đủ

- Module: cart, checkout, account, payment callback.
- Expected: session + CSRF + wards/shipping + payment redirect + confirmation chạy từ category đến `/cam-on/`.
- Actual: chỉ có contract/negative tests; chưa có authenticated/order fixture và payment sandbox evidence.
- Evidence: `route-matrix.md` các dòng cart/checkout/account/thanks PARTIAL; backend negative smoke.
- Root cause: môi trường audit không có dữ liệu đơn hàng/session hợp lệ.
- Proposal: chuẩn bị fixture/sandbox account và chạy browser regression không ghi production DB.
- Release blocker: **YES** cho commerce cutover; **NO** cho các route nội dung độc lập.

### REL-P2-005 — Media/origin path chưa được xác minh qua edge

- Module: `/upload/*`, `/assets/*`, `/thumbs/*`, `/watermark/*`.
- Expected: media PHP exception hoạt động qua Caddy, Nuxt public HTML không fallback vào legacy CSS/JS.
- Actual: direct Nitro trả 404 media; Caddy rule có proxy nhưng chưa có production-like smoke.
- Evidence: `04-backend.md` P2-BE-005; Caddy config trong `evidence/release/seo-proxy-inspection-20261029.txt`.
- Root cause: Nitro origin không phải public topology; edge behavior chưa được checkpoint.
- Proposal: smoke media asset đại diện qua Caddy, kiểm tra content-type/cache và HTML network không gọi PHP legacy assets ngoài exception.
- Release blocker: **YES** nếu media không tải trong public edge.

### REL-P3-006 — Accessibility/visual quality debt còn tồn tại

- Module: Home/header/catalog.
- Expected: shared container, hit target ≥44×44, accessible H1 spacing, shared icon system.
- Actual: Home container lệch ở 1440/1920; mobile controls 38×38; H1 accessible name dính chữ; benefit dùng glyph Unicode.
- Evidence: `02-uiux.md` UIUX-001..004 và ảnh tại `evidence/uiux/`.
- Root cause: section-specific layout/icon implementation.
- Proposal: sửa theo design-system, sau đó chạy keyboard/screen-reader smoke và breakpoint matrix.
- Release blocker: **NO** (UIUX-001 là blocker nếu Home được chọn làm cutover chuẩn).

### REL-P3-007 — Không có CWV/performance baseline

- Module: public edge, image/media loading.
- Expected: có số đo LCP/CLS/INP và budget cho desktop/mobile trên build release.
- Actual: build thành công nhưng chưa có Lighthouse/Web Vitals measurement.
- Evidence: kiểm tra release logs; không có artifact CWV.
- Proposal: đo trên Caddy release candidate tại 390/1440, lưu report và đặt budget trước Go.
- Release blocker: **NO**, nhưng là gate chất lượng chưa hoàn tất.

## Pass có điều kiện

- TypeScript, unit/contract tests và production build pass ở snapshot hiện tại.
- Caddy đã cấu hình rõ public HTML → Nuxt, API/Admin/media/session → PHP; không kết luận runtime production cho tới khi có edge smoke.
- Security cơ bản từ Phase 2: CORS origin lạ không được mở, `nosniff`/no-store có mặt, mutation thiếu CSRF/session bị từ chối.
- Redirect source có `/product/`, `/du-an/`, `/ve-chung-toi/`, `/chinh-sach/*`; cần kiểm tra status/Location qua edge trước release.

## Giới hạn

- Không chạy production, không truy cập/ghi DB production, không sửa code sản phẩm.
- Agent release chuyên trách không hoàn tất artifact sau khi bị treo; các kiểm tra Phase 3 trong file này được main agent thực hiện read-only và lưu log nêu trên.

