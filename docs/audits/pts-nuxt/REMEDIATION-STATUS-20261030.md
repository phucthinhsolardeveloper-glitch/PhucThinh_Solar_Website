# PTS Nuxt remediation checkpoint — 2026-10-30

## Scope and checkout

- Repository/worktree: `/home/admin01/src/Web_PTS_PHP`
- Branch: `master`
- Checkpoint HEAD: `6663f336e4f56fc6d2cf3418d0adcbb6da582e85`
- Local branch: 30 commits ahead of `origin/master`; nothing pushed or deployed.
- Pre-integration local HEAD: `010c6a1cc4d4fc879b2127181a8b833daa9ccaf0`.
- Production PHP, database, DNS, and containers were not changed.

## Integrated local work

- Policy detail: metadata date and related-card date formats now match the PHP formats (`8820a92`).
- Project detail: empty related-project images now use the PHP `noimage.png` fallback (`2ea20fb`).
- Project listing CTA: image stack, mobile font metrics, trust-row gap, title wrap, and background image treatment align with PHP source. Paired 390px screenshots show the photo and three-line paragraph; measured band heights were PHP 554.7px and Nuxt 551.6px (`6f57c7c`).
- News CTA: responsive icon, layout, action separator, padding, and heading breakpoints now follow PHP CSS (`139f87e`). Source tests pass, but no paired runtime screenshot is available.
- Home hero normalization: CMS semantic slot matching now safely handles an unnamed row and preserves positional fallback (`673de74`).
- The previously integrated wide-container, solution TOC, CTA-background, news-share, and other work remains in local master.

Project CTA evidence:

- PHP: `.playwright-mcp/project-cta-php-band-mobile-final-20261030.png`
- Nuxt: `.playwright-mcp/project-cta-nuxt-band-mobile-final-20261030.png`

These screenshots verify the project-listing CTA only. They do not prove whole-site visual parity.

## Verification on integrated master

- `npm test --prefix frontend`: **426/426 PASS**.
- `npm run typecheck --prefix frontend`: **PASS**.
- `npm run build --prefix frontend`: **PASS**, Nuxt 4.6.2/Nitro node-server build completed in 25.0s.
- `NITRO_SMOKE_MOCK_PHP=1 npm run smoke --prefix frontend`: **PASS**; preview and four hub/media routes returned 200/noindex, unknown route returned 404. This used the documented local PHP 404 fixture; it is not a live PHP/API integration test.
- `git diff --check`: **PASS** before checkpoint; working tree clean after report creation is pending.

## Remaining issues and evidence limits

| Area | Status | Evidence / next action |
|---|---|---|
| News CTA visual comparison | BLOCKED | Local PHP `/tin-tuc/` returned 404; no Nuxt/PHP preview listener was available. Responsive implementation has source-contract tests, but needs real paired screenshots before visual PASS. |
| Solutions CTA visual comparison | BLOCKED | PHP source and Nuxt source/test cover image opacity, gradient, and responsive composition. No runtime pair was available; verify image stacking and appearance with a QA fixture/runtime. |
| Dealer statistics | BLOCKED on data snapshot | PHP and Nuxt both read published CMS rows and pass `solieu` through the DTO; Nuxt has no hardcoded 41/4012/50/5000 values. Current API/database snapshot was unavailable, so the historical `5/41/4.012` versus `5/50/5000` difference cannot be attributed to code. |
| Home hero runtime parity | PARTIAL | Semantic CMS slot mapping is fixed and tested; current active CMS ordering/media could not be captured as a PHP/Nuxt screenshot pair. |
| Project detail and policy detail visual parity | PARTIAL | Source-backed fixes/tests exist; no same-data browser screenshots were available. Policy related-card author also remains absent from the PHP policy API/DTO; resolving it requires a separately reviewed API contract change, not fabricated Nuxt data. |
| Production-like release gate | BLOCKED | No live PHP contract/data test, Caddy/Compose production-like smoke, rollback verification, or production deployment was performed in this checkpoint. |

No completion percentage is reported: unavailable CMS snapshots and browser evidence make a meaningful denominator impossible for the remaining visual/data checks.

## Local branch/worktree reconciliation

Merged this checkpoint's feature branches into local master and removed their clean, merged worktrees/refs. Only the master worktree remains. Five older unmerged refs were deliberately preserved after read-only comparison; none is safe or useful to merge wholesale:

- `codex/main-integration-candidate-20261029` — stale 57-file snapshot; image changes are already superseded on master.
- `codex/nuxt-gallery-video-gate-20261028-latest` — stale test/documentation; its test weakens Admin SEO assertions.
- `codex/nuxt-gallery-video-gate-delivery-20261028` — duplicates the stale test change and handoff docs.
- `nuxt-progress-codex` — DMUX/operations tooling, not product implementation.
- `stage4-batch-b-cart-checkout-20261028` — handoff documentation only.

These refs remain local and unmerged; no cleanup was done to their branch history.

## Release assessment

**NO-GO for replacing the production PHP frontend at this checkpoint.** Local tests, typecheck, build, and mock-backed SSR smoke pass, but real CMS/API data, several required screenshot comparisons, and production-like Caddy/Compose/rollback checks remain unverified. No production state changed. `origin/master` is the rollback base; the pre-integration local checkpoint is `010c6a1`.
