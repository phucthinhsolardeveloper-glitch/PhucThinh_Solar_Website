# Phúc Thịnh Solar — Nuxt frontend (worktree only)

This application is **not** the production website and must **not** be routed
publicly yet. PHP/CMS, checkout, session and MariaDB are unchanged.

## Run locally (WSL)
```bash
cd /home/admin01/src/pts-nuxt4-migration/frontend
npm ci
npm run dev
# http://127.0.0.1:3100/migration-preview
```

## Migration gates
1. Lock PHP source/data/visual baseline.
2. Introduce versioned **read-only** PHP data endpoints using existing business logic.
3. Rebuild approved components and pages with real API data.
4. Compare screenshots and full behavioral tests, including SEO and 404/301.
5. Switch allowlisted Caddy GET routes only after explicit approval.

No legacy global CSS/JS import. No PHP direct DB access from Nuxt.
No production deploy without an explicit separate authorization.
