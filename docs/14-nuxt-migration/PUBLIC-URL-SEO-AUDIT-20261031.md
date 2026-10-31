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
| Open Graph description matches | 27/34 after the follow-up below | The initial 21/34 comparison is retained in the ignored route artifact; six empty-description fallbacks now match PHP's site-setting behavior. |
| Open Graph image path matches | 7/34 | Many Nuxt pages use current Admin hero media while PHP falls back to a logo; classify each before changing. |
| Open Graph type matches | 4/34 | PHP emits a legacy `object` value on many listing pages; do not copy it without deciding the valid public contract. |
| JSON-LD type sets match | 29/34 in the retained comparison | Current Nuxt SSR was compared with the earlier PHP route snapshot; the later policy-schema source change has not had a fresh full comparison. |
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
2. Reconcile remaining OG description/image/type and JSON-LD differences from
   authoritative Admin/PHP behavior. The source-aligned fallback fixes below
   are targeted; retained full-route counts remain stale. Do not treat `HTTP
   200` or a noindex preview as SEO cutover approval.
3. Four hidden-record families still lack matching local DB fixtures. The seven
   earlier public-detail skips were caused by stale runner field/envelope
   mappings and were cleared in the read-only follow-up (69 HTTP PASS, 0 FAIL,
   0 SKIP). Do not create invented slugs or mutate data to fill the hidden-record
   gap.
4. The owner still performs the visual inspection at `http://127.0.0.1:3100/`.
   The interrupted bulk screenshot run is not visual sign-off and will not be
   repeated unless requested.
5. PHP remains the production public owner. No push, deployment, DNS/Caddy
   switch, route ownership change or cutover decision is included here.

## Follow-up — empty Open Graph descriptions — 2026-10-31

PHP's shared head uses the public site setting (`$setting['namevi']`) when a
route has no configured SEO description. The public site DTO exposes that same
value as `brand.name`. Nuxt had instead emitted custom search copy or only the
brand label on search, news category, and support-policy routes. The shared
`publicSeoDescription()` helper and those three route owners now use the CMS
SEO description first and the site setting as the empty-value fallback.

Verification: the complete Node suite passes **452/452**, Nuxt typecheck and
production build pass, and direct GETs to all four news category routes, search,
and the policy detail return HTTP 200 with `og:description` equal to
`pts_v1_site.php`'s `brand.name`. This six-route check uses the previous
PHP-vs-Nuxt audit for the PHP side and the current PHP API value for its source
setting. A full two-renderer rerun is intentionally unavailable while the PHP
HTML preview is disabled (direct page requests through the API bridge return
403). Remaining OG-description differences are the category/solution/project
custom descriptions versus PHP's generic fallback, plus trailing whitespace
in legacy contact/dealer metadata; review those separately rather than
discarding more specific CMS text.

## Follow-up — breadcrumb structured data — 2026-10-31

`SiteBreadcrumb` now emits a validated `BreadcrumbList` from its displayed
CMS-backed labels and public paths. It escapes JSON-LD text, accepts only
canonical-host URLs, and skips internal preview routes and PDP routes that
already emit a product breadcrumb schema. News/project category crumbs now link
to the category URL already present in their validated DTOs. The catalog root
breadcrumb used `href` although the component contract expects `to`; correcting
that restores the actual link and lets the same verified path enter the schema.

Verification compared current Nuxt SSR for the 34 manifest paths with the PHP
type-set snapshot from the earlier two-renderer audit: **34/34 HTTP 200**, zero
JSON-LD parse failures, **32/34** routes emit a breadcrumb list, and exact type
sets match on **29/34** (up from 2/34). Remaining differences in that snapshot:
product detail adds FAQPage/Question/Answer and has PHP-vs-Nuxt global
address/image type differences; news detail has an extra PostalAddress type.
The later source change adds validated `NewsArticle` data from the policy DTO
and policy canonical URL; it likely closes the policy route's missing article
types, but this has not been confirmed by a fresh SSR comparison. The local PHP
HTML renderer stays disabled, so this is not a fresh two-renderer run.

## Follow-up — policy structured data source correction — 2026-10-31

The public policy template now emits `NewsArticle` JSON-LD from validated
policy DTO fields: canonical URL, title, author, publisher, optional cover,
validated publication date and description. It emits only for a successful
public route response; internal preview paths remain excluded. The targeted
policy contract and typecheck passed after the source change. The retained 29/34
type-set comparison above predates this change and has not been rerun.

## Follow-up — source-aligned social images and organization fields — 2026-10-31

PHP `about.php`, `contact.php`, `daily.php` (dealer) and `video.php` set the
social image only from the Admin SEO-page photo; the shared head falls back to
the public site logo when that field is empty. Nuxt now passes only those SEO
photos on the four corresponding pages, allowing the shared logo fallback to
apply instead of substituting story, contact-visual, dealer-hero or video
thumbnail content. The shared Organization JSON-LD now includes `legalName`
from the public site setting and `addressRegion: Gia Lai`, matching
`strucdata.php`. Email remains omitted because the public site DTO does not
expose it.

Verification: `node --test tests/public-seo.test.mjs` passed **14/14**. Nuxt
SSR GETs for `/gioi-thieu/`, `/lien-he/`, `/dai-ly/` and `/video/` returned
HTTP 200 and the public logo as `og:image`. No production or PHP HTML page
renderer was used. The retained 34-route image and JSON-LD counts above are
still historical snapshots; this targeted verification does not establish a
fresh full-route parity result.

## Follow-up — legacy solution-category links

The retained public-path href matrix contained two PHP-only destinations that
returned 404 on local Nuxt: `/danh-muc-giai-phap/doanh-nghiep/` and
`/danh-muc-giai-phap/ho-gia-dinh/`. The read-only route resolver identifies
both slugs as published `solution-category` entities whose Nuxt canonical
paths are `/doanh-nghiep` and `/ho-gia-dinh`.

Nuxt now handles `/danh-muc-giai-phap/:slug/` as a legacy alias. It redirects
with 301 only when the PHP resolver confirms `solution-category`, preserves
the query string, and returns 404 for an unknown slug. Runtime verification
confirmed both aliases return 301 to the resolver-provided canonical paths;
the unknown-slug path returns 404. Rechecking the **129 distinct PHP-only
href targets** from the retained 34-route matrix against local Nuxt returned
**129/129 HTTP 200 after redirects**. The targeted resolver/alias checks pass
**5/5**, and Nuxt typecheck passes.

This proves those retained PHP-only targets resolve on the current local Nuxt
preview. It does not establish exact href parity: the matrix also has Nuxt-only
paths and was captured before this fix. A fresh PHP-vs-Nuxt HTML comparison
remains unavailable because the local PHP HTML renderer is disabled.
