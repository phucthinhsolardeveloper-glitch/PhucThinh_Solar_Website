# PTS Nuxt migration — baseline manifest (2026-10-26)

## Verified starting point
- Parent source: `/home/admin01/src/Web_PTS_PHP`
- GitHub origin: `phucthinhsolardeveloper-glitch/PhucThinh_Solar_Website`
- Base branch: `master`
- Base SHA: `5a488633ec3b434a22505449b21a04ac981e9cd5`
- Migration branch: `feature/pts-nuxt4-migration`
- Migration worktree: `/home/admin01/src/pts-nuxt4-migration`
- Main worktree has seven pre-existing modified docs; **do not stash/reset/commit them**.
- No changes to PHP runtime, production Docker or database.
- Production image SHA and active release metadata: **NOT VERIFIED**.
- Public browser screenshot and local QA database parity against base: **NOT YET CAPTURED**.

## Source of truth
1. Existing PHP route/logic and approved UI/UX: `docs/02-development/public-ui-ux-design-system.md`.
2. Real pages rendered with a fixed local fixture and matching browser settings.
3. Verified PHP security, checkout, pricing and image handling (never duplicate in frontend).
4. Source code state in this migration branch, only after passing parity checks.

## Stop conditions
- Do not send live traffic to Nuxt before PHP-vs-Nuxt parity and separate deployment approval.
- Do not switch SEO/canonical routing based on slug guessing.
- Never import the full legacy jQuery/CSS bundle into Nuxt.
- Never use production data/account for QA.
- Never remove PHP account/cart/checkout/admin/payment callbacks during Phase A.

## First technical checkpoint
- [x] Create isolation branch and worktree from latest local master.
- [x] Scaffold Nuxt SSR frontend in `frontend/`.
- [x] Nuxt 4.6.2 build/typecheck (exit 0), SSR preview HTTP 200, noindex and unknown route HTTP 404 verified. Vue Router 4.6.4 emits a non-fatal Volar plugin compatibility warning; investigate before scaling up.
- [x] Capture 16 first-fold PHP local screenshots (8 routes × 390px/1440px) and route metadata in `frontend/.migration-artifacts/legacy-baseline/`; all HTTP 200, with no document horizontal overflow, missing H1/canonical or pageerror at capture. Data fixture freeze, more viewports, PDP and interactive states still pending.
- [ ] Confirm production release SHA, then assess whether any deltas must be integrated into baseline.
- [ ] Implement PHP data contracts with equivalent filtering/visibility rules.
