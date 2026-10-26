# F12 dealer preview parity

Preview route: `/migration-dealer-preview` (noindex). PHP remains the owner of public `/dai-ly/`, CSRF, reCAPTCHA and `POST submit-newsletter2`.

## Read contract

`GET /api/pts_v1_dealer.php` exposes only published `dai-ly-1/2/3`, `hinh-anh-dai-ly`, `dealer-hero`, `cam-ket-dai-ly`, `phan-hoi-dai-ly` and `so-lieu-thong-ke-dai-ly`. It returns plain text, validated local media paths and allowlisted links; private DB fields and raw HTML are excluded. Unknown query keys and non-GET requests are rejected.

Hero fallback is used only when no `dealer-hero` rows are configured. A configured but hidden/missing-image row suppresses fallback, matching the Admin hide rule.

The Nuxt CTA links to `/dai-ly/#dang-ky-dai-ly`; it never creates a CSRF token or submits a form. No public Nuxt `/dai-ly/` route is added.

## Files and verification

New files are limited to the F12 allowlist in `TASK-F12-DEALER.md`. Run `node --test frontend/tests/dealer-contract.test.mjs` and `php scripts/test-pts-public-dealer-api.php` when the target PHP runtime is available. Nuxt build/browser/Docker/production checks are intentionally outside this task.
