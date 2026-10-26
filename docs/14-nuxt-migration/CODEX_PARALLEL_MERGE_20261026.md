# PTS Nuxt — parallel Codex integration checkpoint (2026-10-26)

## Isolation and release state
- Legacy baseline: master at 5a48863. Migration branch: feature/pts-nuxt4-migration.
- Before this batch: e7030f5. Integrated after both waves: f841d56 before this documentation commit.
- The main website master, original seven uncommitted doc files, PHP production, CMS, DB and deploy scripts **were not changed**.
- No GitHub push, PR, production cutover, backend replacement, or public Nuxt route enabled.
- All six child worktrees from these waves (including previous public-shell branch) were merged and removed. Only original master and Nuxt migration worktree remain.

## Wave 1 (3 simultaneous Codex agents + existing shell)
- Existing public shell preview: merge 91106ef, components header/footer/breadcrumb, internal preview only.
- CMS navigation + footer API: implementation 060b920, merge 9c0854f. Keep public site schemaVersion=1; additive navigationSchemaVersion=1 to avoid breaking existing frontend validator.
- Solutions internal preview: implementation 2f8a46c, merge 2605a5f. Typed API validator, Nitro read-only adapter and noindex preview; editorial rich text still intentionally deferred for sanitizer.
- Browser visual QA harness: implementation 6a51271, merge 5190099. Explicit local route mapping and Playwright screenshots + heuristic diff; never silently approves parity.

## Wave 2 (2 simultaneous Codex agents)
- CMS-backed Nuxt site shell: implementation 13ddf82, merge 9179d1e. Logo/nav/catalog/footer typed optional fields, no slug grouping guess; preserves fallback when no local API.
- Read-only product catalog API: implementation dd2b56f, merge f841d56. PHP list DTO, canonical category/product URLs, published filter, bounded paging, sanitized media, explicit parity gaps. Bad query input returns 400; non-GET returns 405. Prices/filter/sort NOT ported.

## Verified tests
- PHP 8.1 local Docker: navigation API lint + DTO and method tests PASS; catalog API/helper lint PASS, DTO tests and 400/405 gates PASS; solution API PHP tests passed in previous checkpoint.
- Integrated frontend npm test: 11/11 PASS.
- Integrated Nuxt typecheck: exit 0; pre-existing Vue Router/Volar plugin path warning remains unresolved.
- Integrated production build: PASS; Nitro node-server output generated.
- Integrated HTTP smoke: 3 internal preview routes HTTP 200 and robots noindex; unported /giai-phap/ and /san-pham/ do not exist in Nuxt (404 expected; PHP must remain owner at proxy).
- QA harness: 6/6 unit tests PASS; actual Chromium comparison of local PHP /giai-phap/ and Nuxt internal solution preview at 390x844/1440x900: both HTTP 200, no pageerror/overflow; 33.9% mobile and 26.7% desktop changed pixels. **NOT parity**: Nuxt solution preview was fallback because current PHP local server serves master without new endpoint. Artifacts stored only in ignored frontend/.migration-artifacts/agent-runs/solutions-visual-compare/.

## Open gates / next execution tickets
1. **P0: local integration API**: run PHP migration branch as isolated PHP local container sharing read-only fixture DB and media to verify /api/pts_v1_site.php, /api/pts_v1_solutions.php, /api/pts_v1_catalog.php over real HTTP with published CMS data. No production DB/account.
2. **P0: visual fidelity**: compare shared shell logo/menu/footer and complete solutions page against same fixture across desktop/mobile states. Do not claim source parity until measured. Motion and new features are Phase B, excluded.
3. **P0: rich text**: safe sanitization/allowlist for CKEditor editorial content before solution parity; preserve text, links, images safely.
4. **P1: catalog API parity**: pricing/visibility, product sort, filters, pagination/count and image transforms need matched legacy business logic; current catalog endpoint intentionally omits prices/filters/sort. Do not expose as completed commerce page.
5. **P1: warnings**: Vue Router/Volar compatibility warning and 7 high-severity npm advisory results reported by npm ci need triage before production release (assess runtime vs dev dependency impact, no blind forced upgrade).
6. **P1: CI/rollback**: complete route allowlist and production rollback rehearsal; not permitted to deploy without owner approval.

## Operating procedure
- Parallel worktrees may own disjoint PHP API and frontend component paths, then must commit/tests/review/merge into the long-lived Nuxt integration branch and remove completed worktrees.
- Codex sandbox blocks local Docker socket and port bind in some sessions; coordinator may run local read-only Docker and SSR tests, but never bypass sandbox to access production.
- Do not reset the original master worktree or its seven modified documentation files.
