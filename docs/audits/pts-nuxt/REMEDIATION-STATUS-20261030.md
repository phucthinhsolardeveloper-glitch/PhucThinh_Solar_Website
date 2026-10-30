# PTS Nuxt remediation checkpoint — 2026-10-30

## Scope and checkout

- Repository/worktree: `/home/admin01/src/Web_PTS_PHP`
- Branch: `master`
- Checkpoint HEAD after final integration: `8c7d013a5deb1b1ddf11c67b9cd23fcea7e392f3`
- Local branch: 42 commits ahead of `origin/master`; nothing pushed or deployed.
- Pre-integration local HEAD: `010c6a1cc4d4fc879b2127181a8b833daa9ccaf0`.
- Production PHP, database, DNS, and containers were not changed.

## Integrated local work

- Policy detail: metadata date and related-card date formats now match the PHP formats (`8820a92`).
- Project detail: empty related-project images now use the PHP `noimage.png` fallback (`2ea20fb`).
- Project listing CTA: image stack, mobile font metrics, trust-row gap, title wrap, and background image treatment align with PHP source. Paired 390px screenshots show the photo and three-line paragraph; measured band heights were PHP 554.7px and Nuxt 551.6px (`6f57c7c`).
- News CTA: responsive icon, layout, action separator, padding, and heading breakpoints now follow PHP CSS (`139f87e`).
- News CTA visual follow-up: mobile band width, phone icon, typography, heading and paragraph wraps now match the PHP screenshot at 390px while retaining Be Vietnam Pro (`56163e3`, `f8ce1e5`, `0130e2c`). PHP and Nuxt screenshots use the same visible CTA content; Nuxt uses a local fixture because the public read-only API returns 404.
- Solutions CTA: heading/body sizes, hotline link styling, CTA geometry and mobile paragraph wrap now follow the PHP screenshots (`1217b90`, `6b05a70`). Image rendering was verified from paired desktop/mobile screenshots.
- Home hero normalization: CMS semantic slot matching now safely handles an unnamed row and preserves positional fallback (`673de74`).
- The previously integrated wide-container, solution TOC, CTA-background, news-share, and other work remains in local master.

Project CTA evidence:

- PHP: `.playwright-mcp/project-cta-php-band-mobile-final-20261030.png`
- Nuxt: `.playwright-mcp/project-cta-nuxt-band-mobile-final-20261030.png`
- News PHP mobile: `.playwright-mcp/news-cta-php-mobile-public-vi-20261030.png`
- News Nuxt mobile: `.playwright-mcp/news-cta-nuxt-mobile-parity-final-20261030.png`
- News PHP mobile copy-wrap: `.playwright-mcp/news-cta-php-mobile-residual-final-390.png`
- News Nuxt mobile copy-wrap: `.playwright-mcp/news-cta-nuxt-mobile-residual-final-390.png`
- News PHP desktop: `.playwright-mcp/news-cta-php-desktop-public-vi-20261030.png`
- News Nuxt desktop: `.playwright-mcp/news-cta-nuxt-desktop-final-vi-20261030.png`
- Solutions PHP mobile: `.playwright-mcp/solutions-cta-php-mobile-loaded-20261030.png`
- Solutions Nuxt mobile: `.playwright-mcp/solutions-cta-nuxt-mobile-final-20261030.png`
- Solutions Nuxt mobile copy-wrap: `.playwright-mcp/solutions-cta-nuxt-mobile-wrap-final-390-20261030.png`
- Solutions PHP desktop: `.playwright-mcp/solutions-cta-php-desktop-loaded-20261030.png`
- Solutions Nuxt desktop: `.playwright-mcp/solutions-cta-nuxt-desktop-final-20261030.png`

Each pair verifies the named CTA only. They do not prove whole-site visual parity.

## Verification on integrated master

- `npm test --prefix frontend`: **427/427 PASS**.
- `npm run typecheck --prefix frontend`: **PASS**.
- `npm run build --prefix frontend`: **PASS**, Nuxt 4.6.2/Nitro node-server build completed in 24.9s.
- `NITRO_SMOKE_MOCK_PHP=1 npm run smoke --prefix frontend`: **PASS**; preview and four hub/media routes returned 200/noindex, unknown route returned 404. This used the documented local PHP 404 fixture; it is not a live PHP/API integration test.
- `git diff --check`: **PASS**; the report has no trailing whitespace. The report commit leaves the working tree clean.

## Remaining issues and evidence limits

| Area | Status | Evidence / next action |
|---|---|---|
| News CTA visual comparison | PARTIAL | Real PHP public page and Nuxt component were captured at 1440px/390px; Nuxt fixture uses exact rendered PHP CTA fields because `GET /api/pts_v1_news.php` returns 404. Mobile title/body wraps now match. Desktop Nuxt band remains 4px wider (1372px vs 1368px); retained shared container gutter. |
| Solutions CTA visual comparison | PARTIAL | Real PHP public page and Nuxt component were captured at 1440px/390px with matching text/media. Desktop band height matches (214.55px); mobile body wrap now matches at 6 lines. Mobile Nuxt band remains about 9.1px shorter (501.69px vs 510.78px). CTA uses flat gold/radius 4px per approved design system instead of PHP's gradient/radius 7px. |
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
