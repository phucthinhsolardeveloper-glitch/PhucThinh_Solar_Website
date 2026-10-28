# Phúc Thịnh Solar — Nuxt frontend (worktree only)

This application is **not** the production website and must **not** be routed
publicly yet. PHP/CMS, checkout, session and MariaDB are unchanged.

## Run locally (WSL)
```bash
cd /home/admin01/src/pts-nuxt-integration-20261027/frontend
npm ci
npm run dev
# http://127.0.0.1:3100/migration-hub-preview
```

## Migration gates
1. Lock PHP source/data/visual baseline.
2. Introduce versioned **read-only** PHP data endpoints using existing business logic.
3. Rebuild approved components and pages with real API data.
4. Compare screenshots and full behavioral tests, including SEO and 404/301.
5. Switch allowlisted Caddy GET routes only after explicit approval.

No legacy global CSS/JS import. No PHP direct DB access from Nuxt.
No production deploy without an explicit separate authorization.

## Capture PHP baseline screenshots (read-only)
Run from the migration worktree, with the existing shared Playwright browser binaries:

```bash
cd tools/visual-tests
npm ci
PLAYWRIGHT_BROWSERS_PATH=/home/admin01/src/Web_PTS_PHP/tools/visual-tests/.browsers \
  node scripts/capture-migration-baseline.cjs
```

Reports are local-only at `frontend/.migration-artifacts/legacy-baseline/`.
The initial capture compares first-fold layouts; product detail and interactive
states require further tests before claiming visual parity.
