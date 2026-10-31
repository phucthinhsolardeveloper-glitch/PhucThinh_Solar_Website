# Branch integration and local QA — 2026-10-31

## Repository state

- Integrated code is on local `master`; the UI/migration work is committed in
  `d7cd955` and the detail canonical/title correction in `9cd3b65`. At the
  start of this follow-up checkpoint, `master` was 81 commits ahead of
  `origin/master`; the SEO/policy-schema and PHP test-runner corrections below
  are local-only. No push was performed.
- After `git fetch origin --prune`, local inventory contains only `master` and
  one worktree at `/home/admin01/src/Web_PTS_PHP`. The remote
  `feature/pts-nuxt-integration-20261027` is an ancestor of `master`; the remote
  PHP router-fix branch was deleted and its commits are already ancestors.
- `nuxt-progress-codex` was not merged wholesale because its changes weakened
  this repository's execution contract and installed an automatic Stop hook.
  Its safe read-only coverage audit was integrated selectively. The rejected
  history remains recoverable at tag
  `archive/nuxt-progress-codex-20261031`.
- The initial inventory found 91 unstaged working-tree paths, including public
  UI work, tests and migration pages. They were reviewed and committed as
  `d7cd955`; follow-up detail SEO and current public-route audit fixes are in
  later local commits. No WIP from another live worktree was staged. This
  checkpoint began with only the scoped SEO, schema, runner and report files
  modified; it does not include unrelated user work.
- Integration rollback point: `f9a453e`; the archived branch tag above preserves
  the omitted work independently.

## PHP read-only integration run

Runner: `scripts/test-pts-public-api-integration.php`, executed in the local
PHP 8.1 preview container against `http://127.0.0.1`. The runner used GET,
rejected-method and invalid-CSRF requests; it excluded tests that submit valid
form/review mutations.

- Initial HTTP checks: **61 PASS, 0 FAIL, 7 SKIP**. A follow-up run after
  fixing the runner's `slugvi` fixture mapping and solution envelope expectation:
  **69 PASS, 0 FAIL, 0 SKIP**.
- PHP contract slices: **22 PASS, 0 FAIL**.
- Snapshot across 26 read-model tables: **unchanged** before/after.
- The seven skips were caused by stale test-runner mappings, not absent published
  records. Four negative hidden-record fixture families still lack local DB rows;
  they remain a separate open gate and were not fabricated.
- Slice tests were aligned to current DTO contracts in
  `scripts/test-pts-public-product-detail-api.php`,
  `scripts/test-pts-public-projects-api.php`, and
  `scripts/test-pts-public-solutions-category.php`. They now cover approved PDP
  profile illustrations and CMS custom specs, the current one-level project
  category DTO, and the current one-level solution group DTO.
- The run exposed a map URL validation gap: a path such as `/maps.evil.example`
  passed the old prefix check. `ptsContactMapUrl()` now accepts `/maps` or a
  `/maps/...` path boundary only. Its PHP contract slice passed in the aggregate
  run.
- Full report: ignored artifact
  `frontend/.migration-artifacts/php-integration-current-20261031/report.md`
  and `report.json`.

## SEO and route crawl

Read-only command used:

```bash
node tools/visual-tests/scripts/migration-seo-audit.cjs \
  http://127.0.0.1:8088 http://127.0.0.1:3100 \
  --manifest tools/visual-tests/fixtures/migration-routes.json \
  --output frontend/.migration-artifacts/seo-audit-php8088-nuxt3100-20261031 \
  --timeout-ms 10000 --max-redirects 8
```

- Manifest mapping: **34/34** routes resolve to existing Nuxt pages.
- Both origins returned **200 for 34/34 manifest routes** during the crawler
  run. Four legacy aliases returned their expected 301 and canonical target.
- PHP sitemap: **457/457 URLs** reachable; internal href targets: **362/362**
  reachable. Href path differences across the legacy and preview surfaces are
  classified for manual review, not treated as public parity.
- Nuxt `/robots.txt` and `/sitemap.xml` returned 404, as expected for the
  noindex preview. PHP remains the public canonical and metadata owner.
- Hidden negative fixtures: the hidden product returned 404 without canonical;
  four entity families remain `NEEDS VERIFICATION` because matching hidden rows
  are absent from the local DB.
- This crawler uses the manifest's internal `previewPath` values. It proves
  preview noindex, but is not evidence of metadata parity on the public URL
  paths. Full output is in the ignored artifact directory above. An earlier
  crawl against `:8080` is invalid evidence because that local service has an
  empty/mis-mounted web root and returned 403/404. The corrected direct public
  path comparison is recorded in
  [`PUBLIC-URL-SEO-AUDIT-20261031.md`](PUBLIC-URL-SEO-AUDIT-20261031.md).

## Browser capture and development runtime cleanup

A rerun started against PHP `:8088` and Nuxt `:3100` at 390×844, 768×900, and
1440×900 for all 34 manifest routes. Every logged route/viewport returned HTTP
200 on both sides. The project owner asked to stop the bulk visual run so they
could inspect the Nuxt site directly; it was interrupted after route capture,
before the harness wrote its final report. The two generated partial-capture
folders (316 MB total) were removed after measuring them to keep the ignored
artifact directory clean. No more bulk browser comparison is planned unless
requested.

The local Linux `docker` shim segfaulted, but the Windows Docker CLI
`docker.exe` connected to Docker Desktop. Inspection found `:8088` was also the
Nuxt process's configured PHP API/media upstream. Removing that container made
Nuxt return 502; it was immediately restored from
`docker-compose.nuxt-preview.yml`, and Nuxt root plus `/_migration/site` then
returned 200. Keep this read-only, loopback-bound PHP bridge while developing
Nuxt; do not open it as the frontend. The separate legacy PHP website container
`pts-php81-local` on `:8080` was stopped and removed to avoid confusing it
with the Nuxt frontend. The PHP source remains in the repository and can be
started for a separate PHP task. MariaDB remains healthy. The old stopped Nuxt snapshot
and Caddy rehearsal containers were removed; images, named volumes, database,
uploads, and source were retained. Restart the legacy PHP website only for a
separate PHP task with `docker compose -f docker-compose.local.yml up -d web`.

Active local review URL is `http://127.0.0.1:3100/`. Nuxt HMR remains running;
PHP API/media bridge is `127.0.0.1:8088`; MariaDB remains local. No production
service or data was touched.

## Remaining work

1. The owner will manually inspect the Nuxt UI at `:3100`; rerun browser
   comparison only if requested. The cancelled capture is not a visual sign-off.
2. Source the four missing hidden-record fixture families from the local DB,
   without creating or mutating data.
3. Review the 362 href targets and JSON-LD/OG differences, then update the
   migration route matrix. Keep PHP public ownership and Nuxt noindex until the
   remaining gates have evidence and a separate cutover decision.

## Current verification and production state

Current frontend evidence before the latest SEO/test-harness edits: `npm test`
**452/452 PASS**, `npm run typecheck`, `npm run build`, and
`NITRO_SMOKE_MOCK_PHP=1 npm run smoke` pass. Latest targeted checks passed for
the changed SEO, policy schema, and PHP runner contracts; a new full-suite run
was intentionally not started. The direct
public-path GET audit passes status/canonical/noindex/title/redirect checks for
34/34 routes. Detailed OG, JSON-LD, and href findings plus remaining gates are
in [`PUBLIC-URL-SEO-AUDIT-20261031.md`](PUBLIC-URL-SEO-AUDIT-20261031.md).

The local ignored `.env` keeps every public-route indexability flag false while
leaving local routes available to inspect. The documented dev command loads
that root `.env` before starting Nuxt; its API/media dependency remains the
read-only bridge on `:8088`. `:8080` remains stopped. No production service,
database data/volume, deploy, public route switch, push or backup was changed.

## Follow-up verification — current checkout

- Current `master` is `1acad02` and is 94 commits ahead of `origin/master`;
  `origin/feature/pts-nuxt-integration-20261027` is an ancestor. The current
  branch/worktree inventory contains only `master` and one worktree; the
  working tree was clean at inventory time. No remaining Nuxt integration
  branch is present in the local or fetched remote branch inventory.
- Runtime noindex probe covered every one of the 27 `migration-*.vue` page
  files at `127.0.0.1:3100`; all 27 responses included
  `X-Robots-Tag: noindex, nofollow, noarchive`. The focused
  `node --test tests/preview-noindex.test.mjs` check also passes (1/1).
- The current PHP source and local `:8088` API bridge both resolve a known
  published article slug using the active DTO (`kind` + `canonicalPath`); the
  Nuxt `/_migration/public-route-resolve` adapter returns the same result with
  HTTP 200. This closes the earlier source/preview DTO mismatch for this
  fixture only; it does not verify every entity family or PHP `:8080`/production.
- The retained public href matrix exposed two broken solution-category aliases.
  Nuxt now redirects `/danh-muc-giai-phap/:slug/` to the resolver-confirmed
  canonical path; both known aliases return 301 and an unknown slug returns
  404. All 129 distinct legacy-only href targets from that matrix now return
  HTTP 200 after redirects on local Nuxt. The separate Nuxt-only href set and
  full PHP-vs-Nuxt path parity remain open.
- Focused resolver/alias tests pass **5/5** and Nuxt typecheck passes. The
  typecheck initially exposed `rows="4"` on a Nuxt UI textarea; changing it to
  numeric `:rows="4"` cleared the error.
- The probe verifies preview indexing protection only. It does not establish
  route data/render health for pages that require a slug or category query.
  PHP HTML preview at `:8088` remains unavailable (root responds 403); the
  API/media bridge is not a PHP page-rendering origin.
- Changes in this follow-up are limited to Nuxt alias handling, the textarea
  numeric prop, and evidence docs. No database, container, production route,
  deployment, or backup was changed.
