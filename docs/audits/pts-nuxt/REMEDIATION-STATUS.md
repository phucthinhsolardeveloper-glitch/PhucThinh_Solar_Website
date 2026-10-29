# REMEDIATION-STATUS

Ngày bắt đầu: 2026-10-29  
Worktree: `/home/admin01/src/pts-nuxt-full-public-20261028`  
Nuxt branch: `codex/full-nuxt-public-ui-20261028`, base `aea5bc0`  
PHP QA branch dự kiến: `codex/qa-route-resolver` (worktree riêng, không đụng PHP master).

## Dependency graph

```text
PHP resolver endpoint + DTO contract
              │
              ├── Nuxt resolver bridge / bare-slug pages ──┐
              │                                             ├── Caddy route + browser E2E
              └── API contract tests                         │
                                                            └── Release gate

Nuxt product 404 semantics ── API negative tests ────────────┘

UI baseline fixes ── responsive screenshots/accessibility ───┘

Media/SEO/Caddy QA ── integration smoke ─────────────────────┘
```

## Backlog

| ID | Sev | Owner | Dependency | Status | Acceptance |
|---|---|---|---|---|---|
| REM-P1-001 | P1 | A | PHP QA worktree | DONE-QA | Commit `50c1183`; PHP 8.1 lint/helper + live QA resolver news/project/not-found/invalid slug PASS. Nested category/product/news/project/solution/policy/gallery validation: **8/8 DTO PASS**. Production master unchanged. |
| REM-P1-002 | P1 | B | REM-P1-001 contract definition | DONE-QA | Commit `5ccc73d`; legacy DTO normalization, resolver status handling, product 400/404 pass-through; final suite 140/140 and typecheck PASS. |
| REM-P1-003 | P1 | F/G | REM-P1-001 + 002 | DONE-QA / EDGE-PARTIAL | Rebuilt Nitro + valid local Caddy 8099 smoke: public/dynamic routes 200, aliases 301, no PHP HTML fallback observed. A later host-network Caddy attempt was blocked by Docker isolation (not treated as a product 502); production edge still unverified. |
| REM-P2-001 | P2 | B | none | DONE-QA | Product unknown slug returns 404 in rebuilt Nitro; negative test included. |
| REM-P2-002 | P2 | D | none | DONE-QA | Commit `b59bb35`; Home container/H1, mobile controls 44px, Lucide benefit icons; typecheck and Caddy smoke PASS. |
| REM-P2-003 | P2 | E | none | DONE-QA | Commit `6ad9ad5`; 27 preview route rules plus inventory test; targeted tests PASS and Caddy headers verified. |
| REM-P2-004 | P2 | C | resolver + fixtures | DONE-SOURCE / PARTIAL-RUNTIME | Commit `10f3ee3`; targeted surface assertions PASS. Gallery/video modal still lacks populated CMS fixture for browser click proof. |
| REM-P2-005 | P2 | F | QA media mounts | DONE-QA / INVENTORY-PARTIAL | Với `NITRO_PREVIEW_MEDIA_PROXY=1`, Playwright 390/1440 trên `/san-pham/` có 0 media lỗi; `/assets`, `/upload`, `/thumbs` fixture thật trả 200. Watermark và full production media inventory vẫn chưa đóng. |
| REM-P2-006 | P2 | G | A/B/C/F | DONE-QA / FOLLOW-UP | Independent QA: 140/140 final tests, typecheck, resolver/product-404 PASS; fresh rebuilt Caddy smoke PASS. |
| REM-P2-007 | P2 | G | REM-P2-006 | DONE-QA | Catalog `/san-pham/` canonical was missing in SSR; added `useHead` canonical and source assertion. Fresh Nitro artifact SSR now returns HTTP 200 with canonical `/san-pham/` (evidence `performance-a11y-20261029.md`). |
| REM-P2-008 | P2 | D/F | Lighthouse baseline | PARTIAL / OPEN | Home/catalog SEO metadata fallback now passes Lighthouse; baseline still flags large image payloads, unsized images and slow LCP on home/news. Add responsive image variants or edge resizing, then rerun on production-like Caddy. |
| REM-P3-001 | P3 | E | none | DONE-QA | `frontend/tests/preview-noindex.test.mjs` scans all `migration-*.vue` routes and verifies every route has an explicit `X-Robots-Tag: noindex` rule; test PASS. |
| REM-P3-002 | P3 | F | QA edge | DONE-QA / EDGE-PARTIAL | Lighthouse baseline đã chạy trên fresh Nitro với media proxy QA; scores/metrics lưu tại `lighthouse-20261029.md`. Production-like Caddy rerun và performance budget vẫn cần hoàn tất. |

## Execution policy

- Chỉ QA/staging/local; không sửa PHP `master`, production DNS/DB, hoặc deploy production.
- Agent không được reset/clean dirty WIP; chỉ stage file ownership của mình.
- Mỗi nhóm phải gửi commit SHA (nếu commit được an toàn), test, evidence và known risk.
- Không đóng issue nếu chưa có test và evidence; agent G phải kiểm tra độc lập phần A–F.

## Current counts

- P0: 0 mở.
- P1: 0 còn mở trên QA; production cutover gate vẫn **BLOCKED** cho tới khi review/deploy riêng.
- P2: 1 issue tối ưu hóa còn mở (ảnh/LCP); gallery/video populated-fixture proof và watermark/full media inventory là PARTIAL. Media browser 404 đã được truy nguyên về cấu hình QA thiếu `NITRO_PREVIEW_MEDIA_PROXY=1` và đã xác minh lại 0 lỗi với cấu hình đúng.
- P3: 0 issue kiểm thử nền còn mở; production-like performance budget vẫn phụ thuộc REM-P2-008.
- Production: **chưa deploy**.

