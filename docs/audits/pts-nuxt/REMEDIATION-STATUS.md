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
| REM-P1-001 | P1 | A | PHP QA worktree | DONE-QA | Commit `50c1183`; PHP 8.1 lint/helper + live QA resolver news/project/not-found/invalid slug PASS. Production master unchanged. |
| REM-P1-002 | P1 | B | REM-P1-001 contract definition | DONE-QA | Commit `5ccc73d`; legacy DTO normalization, resolver status handling, product 400/404 pass-through; 139 tests/typecheck PASS. |
| REM-P1-003 | P1 | F/G | REM-P1-001 + 002 | DONE-QA | Rebuilt Nitro + Caddy 8099: public/dynamic routes 200, aliases 301, no PHP HTML fallback observed. Production edge still unverified. |
| REM-P2-001 | P2 | B | none | DONE-QA | Product unknown slug returns 404 in rebuilt Nitro; negative test included. |
| REM-P2-002 | P2 | D | none | DONE-QA | Commit `b59bb35`; Home container/H1, mobile controls 44px, Lucide benefit icons; typecheck and Caddy smoke PASS. |
| REM-P2-003 | P2 | E | none | DONE-QA | Commit `6ad9ad5`; 27 preview route rules plus inventory test; targeted tests PASS and Caddy headers verified. |
| REM-P2-004 | P2 | C | resolver + fixtures | DONE-SOURCE / PARTIAL-RUNTIME | Commit `10f3ee3`; targeted surface assertions PASS. Gallery/video modal still lacks populated CMS fixture for browser click proof. |
| REM-P2-005 | P2 | F | QA media mounts | DONE-QA | Caddy 8099 real `/upload/photo/*.png` assets 200; `/assets`/thumbs/watermark full production mounts remain unverified. |
| REM-P2-006 | P2 | G | A/B/C/F | DONE-QA / FOLLOW-UP | Independent QA: 139/139, typecheck, resolver/product-404 PASS; fresh rebuilt Caddy smoke PASS. Re-run after final integration required. |
| REM-P3-001 | P3 | E | none | TODO | Route manifest/check becomes automated |
| REM-P3-002 | P3 | F | QA edge | BLOCKED | Lighthouse/CWV baseline not run; requires stable QA edge/browser profile. |

## Execution policy

- Chỉ QA/staging/local; không sửa PHP `master`, production DNS/DB, hoặc deploy production.
- Agent không được reset/clean dirty WIP; chỉ stage file ownership của mình.
- Mỗi nhóm phải gửi commit SHA (nếu commit được an toàn), test, evidence và known risk.
- Không đóng issue nếu chưa có test và evidence; agent G phải kiểm tra độc lập phần A–F.

## Current counts

- P0: 0 mở.
- P1: 0 còn mở trên QA; production cutover gate vẫn **BLOCKED** cho tới khi review/deploy riêng.
- P2: 0 code blocker còn mở trên QA; gallery/video populated-fixture proof và full media mount là PARTIAL.
- P3: 2 còn mở (route manifest automation, CWV/Lighthouse).
- Production: **chưa deploy**.

