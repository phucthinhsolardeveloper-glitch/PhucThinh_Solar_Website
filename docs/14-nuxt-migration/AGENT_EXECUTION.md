# Agent workflow — Phase A faithful PHP-to-Nuxt conversion

## Isolation and ownership
Integration branch: `feature/pts-nuxt4-migration` at `/home/admin01/src/pts-nuxt4-migration`.
Base master: `5a488633ec3b434a22505449b21a04ac981e9cd5`.
Do not modify / reset original master, especially its uncommitted documentation.

1. Codex API owner: `domains/.../public_html/api/pts_v1_*.php`, `libraries/pts_public_*_api.php` and PHP contract tests. No Vue/CSS edits.
2. Claude Frontend owner: `frontend/app/*`, `frontend/shared/*`; does not edit PHP, deploy or legacy CSS.
3. QA owner: `frontend/tests/*`, `tools/visual-tests/tests/*migration*`, route snapshots and reports; cannot silently update goldens.
4. Integration owner: review commits, build/typecheck, parity gates, feature matrix and merge conflicts; doesn't redesign.

## Branch/worktree discipline
- Keep one long-lived migration branch.
- Child branches from current integration SHA named `feature/pts-nuxt-<slice>-YYYYMMDD`.
- Create child worktrees in separate sibling directories. Assign disjoint file ownership.
- Commit small verified slices; use cherry-pick / PR after code review and tests.
- Rebase or merge latest master into migration only when differences have passed regression; do not rewrite unknown histories.
- Remove completed child worktrees and merged branch refs; keep migration branch until Phase A is complete.
- One heavy build/browser QA at a time on the 8 GiB WSL allocation.

## CI gate definitions
G0: correct base SHA, Git clean on migration worktree, production metadata comparison.
G1: route + behavior inventory, versioned contract, safe fixture, no prod credentials.
G2: Nuxt dev/build/SSR/404/robots preview PASS.
G3: PHP API data equivalence, input validation, HTTP statuses, no unexpected DB writes.
G4: shell and per-route screenshot states; semantic accessibility and responsive PASS.
G5: product filters, search, images, form and cart handoff, checkout/account unchanged.
G6: SEO crawl parity, 301/404/noindex/sitemap, performance and RAM benchmark.
G7: canary rollback rehearsal with feature flag; only then owner-approved production cutover.

## Coding rules
- Explicit TypeScript types for public DTOs and response errors; runtime validation at trust boundaries.
- `useAsyncData`/`useFetch` for SSR data; no browser-only fetching for indexable SEO content.
- One canonical backend source of pricing/publish state; never reconstruct price rules in Vue.
- No legacy CSS/jQuery wholesale. Preserve image/copy/layout fidelity with scoped styles and tokens.
- Use Git tracked lockfile and reproducible `npm ci`; no floating unreviewed UI dependencies.
- Treat PHP CMS rich text as untrusted HTML; sanitize on server or allowlist before rendering.
- Feature flag default OFF. Caddy only allowlists verified page GET routes; /api, /administrator, /account, cart, payment and media always PHP.
- No access to production admin during QA; isolated fixture only.
- Don't deploy, modify VPS routing, push or open a PR unless separately requested.

## Next checkpoint for implementation
1. Check Nuxt production build + typecheck and local SSR preview.
2. Freeze a representative public and local fixture baseline for header, solution, catalog and PDP.
3. Extract PHP read-only v1 DTO for site config/navigation with tests.
4. Port global shell first using screenshot/DOM evidence, not a generated redesign.
5. First route: a controlled `/giai-phap/` pilot on local proxy only.
