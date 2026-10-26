# Codex task: faithful Nuxt shared shell — Phase A, 2026-10-26

Working branch: `feature/pts-nuxt-public-shell-20261026`; base integration `764cb41`.
IMPORTANT: This is faithful conversion, not redesign. Read `AGENTS.md`, `docs/02-development/public-ui-ux-design-system.md`, `docs/14-nuxt-migration/AGENT_EXECUTION.md` before modifying.

## Source references
- PHP: `domains/phucthinhsolar.com/public_html/templates/layout/{header,footer,css,js}.php`, `templates/index.php`, `sources/allpage.php`.
- CSS: `assets/css/design-tokens.css`, `header-v3.css`, `footer-v3.css`, `responsive-architecture.css`, `typography-system.css`.
- Site payload existing DTO: `frontend/shared/contracts/public-site.ts` (brand + categories).
- 16 baseline captures already obtained, under read-only sibling directory `/home/admin01/src/pts-nuxt4-migration/frontend/.migration-artifacts/legacy-baseline/`, including `home-mobile-390.png`, `home-desktop-1440.png`, `solutions-*.png`. PHP local source `http://127.0.0.1:8080` is read-only, production is off limits.

## Deliverable
- Implement Nuxt/Vue reusable public shell components under `frontend/app/components/`, styles under `frontend/app/assets/css/`. Build `SiteHeader.vue`, responsive product-category navigation/mega-menu, `SiteFooter.vue`, `SiteBreadcrumb.vue` only as justified by actual PHP source. Group components as reasonable without bloat.
- Preserve brand navy/gold, PTS Precision flat square corners, responsive 390/1440 behavior, logo routes, desktop navigation categories (no top-level Home), functional mobile nav with keyboard/escape/focus and reduced-motion support.
- Content source: public site data contract/props/SSR for available fields. Known fixed top-level IA labels and hrefs from AGENTS may be encoded, but do NOT invent CMS submenu/media, phone, or marketing text. If API unavailable, render safe absent states; never copy raw PHP markup or import old global styles.
- Add a **preview-only noindex** internal `/migration-shell-preview` page to exercise header/footer; avoid changing `app.vue` globally, public /giai-phap/ or any production URL.
- Tests: typecheck and build if resources allow, plus accessibility-minded interactions, console errors; document any visual gap against reference with evidence. Do not declare parity without screenshot comparison.

## Boundaries
May modify only `frontend/app/components/**`, `frontend/app/assets/css/**`, `frontend/app/pages/migration-shell-preview.vue`, related Nuxt app-scoped tests, and docs under `docs/14-nuxt-migration/tasks`.
Never edit backend PHP/API, Caddy, docker, checkout, master, or another worktree.
Do NOT run docker builds/full Playwright browser at same time as API Codex; WSL only ~8GB.
No git push, no production, no merge. Commit on this branch after passing available tests and report SHA, exact test results and limitations in Vietnamese.

Definition of done: shell component structure exists, no old CSS JS copied, no broken typecheck/build, internal preview noindex and honest remaining parity gaps.
