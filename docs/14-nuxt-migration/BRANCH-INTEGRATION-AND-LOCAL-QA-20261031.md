# Branch integration and local QA — 2026-10-31

## Repository state

- Integrated code is on local `master` at `f9a453e` (merge of the preserved UI
  branch backup). Local branch inventory contains only `master`; the worktree
  inventory contains only `/home/admin01/src/Web_PTS_PHP`.
- `master` is 75 commits ahead of `origin/master`. This is local integration
  state; no push or production action was performed.
- `nuxt-progress-codex` was not merged wholesale because its changes weakened
  this repository's execution contract and installed an automatic Stop hook.
  Its safe read-only coverage audit was integrated selectively. The rejected
  history remains recoverable at tag
  `archive/nuxt-progress-codex-20261031`.
- The initial inventory found 91 unstaged working-tree paths, including public
  UI work from the active review, tests, docs, and newly added migration pages.
  They were preserved and not staged as part of branch consolidation. This
  checkpoint adds documentation and API-test changes; the working tree remains
  unclean even though there is one active branch and one worktree.
- Integration rollback point: `f9a453e`; the archived branch tag above preserves
  the omitted work independently.

## PHP read-only integration run

Runner: `scripts/test-pts-public-api-integration.php`, executed in the local
PHP 8.1 preview container against `http://127.0.0.1`. The runner used GET,
rejected-method and invalid-CSRF requests; it excluded tests that submit valid
form/review mutations.

- HTTP checks: **61 PASS, 0 FAIL, 7 SKIP**.
- PHP contract slices: **22 PASS, 0 FAIL**.
- Snapshot across 26 read-model tables: **unchanged** before/after.
- Seven detailed entity checks remain untestable because the local DB has no
  published brand, product, product-extras, solution, project, news, or policy
  fixture. The runner does not invent fixture slugs.
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
- The report is not a SEO cutover approval: metadata/JSON-LD differences and
  preview noindex remain open. Full output is in the ignored artifact directory
  above. An earlier crawl against `:8080` is invalid evidence because that local
  service has an empty/mis-mounted web root and returned 403/404.

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
`pts-php81-local` on `:8080` was stopped (not removed) to avoid confusing it
with the Nuxt frontend. MariaDB remains healthy. The old stopped Nuxt snapshot
and Caddy rehearsal containers were removed; images, named volumes, database,
uploads, and source were retained. Restart the legacy PHP website only for a
separate PHP task with `docker compose -f docker-compose.local.yml up -d web`.

Active local review URL is `http://127.0.0.1:3100/`. Nuxt HMR remains running;
PHP API/media bridge is `127.0.0.1:8088`; MariaDB remains local. No production
service or data was touched.

## Remaining work

1. The owner will manually inspect the Nuxt UI at `:3100`; rerun browser
   comparison only if requested. The cancelled capture is not a visual sign-off.
2. Source published entity fixtures for the seven skipped integration checks
   and four missing hidden-record checks from the local DB, without creating or
   mutating data.
3. Review the 362 href targets and JSON-LD/OG differences, then update the
   migration route matrix. Keep PHP public ownership and Nuxt noindex until the
   remaining gates have evidence and a separate cutover decision.

## SEO metadata follow-up

Read-only HTML comparison on the actual Nuxt public routes found that PHP uses
bare-slug canonical URLs for project and news detail pages, while Nuxt had used
the trailing-slash link URL. News detail titles also lacked the brand suffix
that PHP adds. The DTOs now keep navigable article/project links unchanged and
expose the PHP canonical separately; news SEO titles mirror PHP's brand suffix.
Both slash and bare-slug forms resolve in Nuxt and emit the same bare-slug
canonical. Local Nuxt and PHP bridge checks returned HTTP 200 and matching title
text plus canonical paths for both records (normalize away the local host on
the PHP preview canonical).

Focused frontend contract checks passed 21/21; full frontend tests passed
447/447; Nuxt typecheck and production build passed. The two PHP API contract
scripts passed in PHP 8.1 with read-only mounts and networking disabled. No
database writes were made. This closes only the two observed metadata cases;
it does not replace the remaining route-wide SEO review or owner visual review.

No deploy, production request, DB write, cleanup, or backup change was made.
