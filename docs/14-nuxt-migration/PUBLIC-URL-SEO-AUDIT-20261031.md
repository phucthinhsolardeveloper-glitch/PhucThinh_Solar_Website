# GET audit: public PHP routes against Nuxt preview — 2026-10-31

## Scope and method

This audit compares the same 34 `legacyPath` values from
`tools/visual-tests/fixtures/migration-routes.json` against the local PHP
read-only bridge (`127.0.0.1:8088`) and Nuxt HMR (`127.0.0.1:3100`). It sends
GET requests only, follows redirects while keeping requests on loopback, and
parses the rendered HTML metadata. Canonicals are compared by path because the
PHP preview uses its local origin while Nuxt uses the verified production host.
No screenshot or database mutation was part of this audit.

Do not confuse these results with
`migration-seo-audit.cjs`'s `previewPath` run: those `/migration-*-preview`
pages intentionally have internal preview metadata and are useful for checking
noindex of those URLs, but do not establish metadata parity on the public paths.

## Results

| Check | Result | Meaning |
|---|---:|---|
| Manifest URLs return HTTP 200 on both local servers | 34/34 | Includes final response after redirect handling. |
| PHP canonical matches manifest expectation | 34/34 | Compared canonical paths. |
| PHP and Nuxt canonical paths match | 34/34 | Detail pages use bare canonical slugs where PHP does; slash URLs remain usable links. |
| Nuxt public-route preview is `noindex` | 34/34 | Local SEO indexability flags are now off while route display flags stay on. |
| Page titles match PHP | 34/34 | Includes the PHP brand suffix convention. |
| Alias redirect chains match | 34/34 | Four legacy aliases retain their 301 behavior. |
| Open Graph title matches | 34/34 | Title normalization is shared with the page title. |
| Open Graph description matches | 21/34 | Remaining differences need Admin/API fallback review. |
| Open Graph image path matches | 7/34 | Many Nuxt pages use current Admin hero media while PHP falls back to a logo; classify each before changing. |
| Open Graph type matches | 4/34 | PHP emits a legacy `object` value on many listing pages; do not copy it without deciding the valid public contract. |
| JSON-LD type sets match | 2/34 | PHP remains the public structured-data authority until the per-family entity fields are reviewed. |
| Routes with different internal href path sets | 34/34 | Route/query/link differences need manual classification; this is not itself proof of broken links. |

The PHP sitemap crawl remains **457/457 reachable**. The prior local href
reachability audit checked **362/362 targets** with zero failures. Those
reachability results do not prove that PHP and Nuxt expose the same navigation
choices; the route path sets above remain open for review.

The per-route GET evidence is stored in the ignored local artifact
`frontend/.migration-artifacts/seo-audit-public-paths-php8088-nuxt3100-20261031/report.json`.
The separate `seo-audit-php8088-nuxt3100-20261031` artifact contains the preview
URL crawl and sitemap/href reachability details.

## Corrections from this audit

- The ignored repository-root `.env` now turns off all 21 local
  `*_PUBLIC_ROUTE_INDEXABLE` settings while retaining the route display flags.
  Before this correction, public paths in the local Nuxt preview emitted
  `index, follow`. No tracked or production environment file was changed.
- `README.md` and `LOCAL-PREVIEW.md` now load the repository-root local `.env`
  before `npm run dev`; launching from `frontend/` without it loses the `:8088`
  upstream and causes data-backed pages to return 502. The instructions also
  distinguish HMR code edits from `.env` changes, which require a restart.
- `publicSeoTitle()` mirrors PHP `class.Seo::set()` by adding
  ` | Phúc Thịnh Solar` once. It is applied to public news/solution/project
  categories, dealer, gallery, video and policy metadata. Search and commerce
  titles were also aligned to PHP; project category H1 stays descriptive and is
  not replaced by a legacy duplicate heading.
- Earlier same-day detail-route fixes are in `9cd3b65`: PHP bare-slug canonical
  paths and brand-suffixed news title metadata now flow through the Nuxt DTOs.

## Verification

- `npm test`: **449/449 PASS**.
- `npm run typecheck`: **PASS**.
- `npm run build`: **PASS**. Existing non-fatal `INEFFECTIVE_DYNAMIC_IMPORT` and
  `PLUGIN_TIMINGS` warnings remain.
- `NITRO_SMOKE_MOCK_PHP=1 npm run smoke`: **PASS**, including base/hub/media
  200/noindex and unknown-route 404.
- Public-path GET matrix: **34/34 PASS** for status, canonical expectation,
  canonical parity, noindex, title and alias redirect chain.
- No PHP write, commerce POST, DB write, deployment or production request was
  made.

## Gates still open

1. Review the 34-route href differences and group them by intentional Nuxt URL
   mapping, missing destination, and query/filter behavior. Keep PHP URLs
   available for external links and SEO.
2. Resolve OG description/image/type and JSON-LD differences from authoritative
   Admin/PHP behavior. Do not treat `HTTP 200` or a noindex preview as SEO
   cutover approval.
3. Four hidden-record families still lack matching local DB fixtures; seven
   detailed PHP integration cases still skip for missing published fixtures.
   Do not create invented slugs or mutate data to fill this gap.
4. The owner still performs the visual inspection at `http://127.0.0.1:3100/`.
   The interrupted bulk screenshot run is not visual sign-off and will not be
   repeated unless requested.
5. PHP remains the production public owner. No push, deployment, DNS/Caddy
   switch, route ownership change or cutover decision is included here.
