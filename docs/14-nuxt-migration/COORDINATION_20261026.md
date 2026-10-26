# PTS Nuxt migration — Codex coordination checkpoint, 2026-10-26

## Repository and deployment
- Base: `master@5a48863`. Integration worktree: `/home/admin01/src/pts-nuxt4-migration`.
- Integration branch: `feature/pts-nuxt4-migration`; last merge `f403024` (API).
- Public PHP site, local DB, CMS, checkout, master, production Caddy/VPS unchanged. No push.

## Workstream A — Solutions read-only PHP DTO (integrated)
- Worktree `/home/admin01/src/pts-nuxt-api-solutions-20261026` and feature branch removed **after** merge.
- Implementing commit `984e881`, merge `f403024`.
- New `api/pts_v1_solutions.php`, `libraries/pts_public_solutions_api.php`, PHP contract test.
- Coordinator PHP 8.1 local Docker lint PASS, deterministic contract tests 19/19 PASS, method gate PASS.
- Still NOT verified: HTTP endpoint against actual local CMS database, editorial rich-text safe rendering, complete parity with all source responsive states. No public Nuxt route enabled.

## Workstream B — Nuxt public shell (awaiting visual parity)
- Worktree `/home/admin01/src/pts-nuxt-public-shell-20261026`
- Branch `feature/pts-nuxt-public-shell-20261026`
- Commits: `5ec763a` frontend components/preview and `3c89ce9` scoped task contract.
- Files: `frontend/app/components/{SiteHeader,SiteFooter,SiteBreadcrumb}.vue`, `frontend/app/assets/css/shell.css`, `frontend/app/pages/migration-shell-preview.vue`, token additions.
- Codex typecheck PASS (with existing non-blocking Volar/vue-router compatibility warning), npm test PASS, npm build PASS.
- Coordinator verified HTTP SSR preview 200, noindex, rendered nav, unknown route 404, independent of Codex's sandbox socket restriction.
- **NOT MERGED** because layout still requires visual regression QA against 16 PHP baseline screenshots; preview uses safe fallback if local backend unavailable.
- Important parity gaps: actual approved logo asset from CMS missing in DTO (renders brand text); navigation submenu grouping currently inferred from slug patterns in Vue instead of backend CMS taxonomy; footer address/policy/media and cart count not available in public DTO; preview does not prove functional parity. Fix these via server API contract and frontend follow-up before merging/releasing.

## Next Codex worktrees / order
1. API Navigation/brand extension: read-only PHP DTO for logo, precise navigation/category grouping as rendered by current `sources/allpage.php`, CMS footer public links, actual contact data. Preserve publish statuses and no private options leakage. No frontend edits.
2. Nuxt shell follow-up on existing branch: consume new DTO, delete slug heuristics, render authentic logo/menu/footer and compare desktop/mobile with baseline.
3. QA branch from combined tested integration: automated screenshots, layout diff, keyboard nav, media, SEO and memory. Only merge modules after report accepted.
4. Solutions pilot route: consume `pts_v1_solutions` contract and port current PHP page; note rich-text editorial sanitization gate before parity can pass.

## Resource and safety
- WSL memory ~7.8 GiB and 4 GiB swap. Keep heavy builds/browsers serialized.
- Sandbox Codex may block Docker socket / local port bind. Coordinator may execute **read-only** local Docker PHP checks and SSR HTTP smoke independently; do not escalate sandbox or use production.
- Preserve main worktree's seven unrelated uncommitted docs; never stash/reset.
