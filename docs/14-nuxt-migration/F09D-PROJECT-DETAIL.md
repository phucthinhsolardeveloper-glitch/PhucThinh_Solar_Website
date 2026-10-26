# F09D — Nuxt project detail preview

## Scope

The read-only PHP projection is `api/pts_v1_project_detail.php` with pure DTO
helpers in `libraries/pts_public_project_detail_api.php`. It resolves only a
published `#_news` row with `type = du-an` by its public `slugvi`, without
including the legacy `sources/news.php` side effect that increments views.

The projection includes CMS-owned title, summary, cover, category, client,
address, dates, published project gallery/video media, and published related
products from `#_product_project` joined to published `san-pham` records. Media
paths are filename allow-listed and checked against the legacy upload root.

CKEditor `contentvi` is intentionally not rendered in the preview. The payload
states the deferred sanitizer gate and the Nuxt UI renders a truthful note; no
unsafe `v-html` is used.

## Preview contract

Open `/migration-project-detail-preview?slug=<public-project-slug>`. The page is
SSR-compatible, uses the shared SiteHeader/SiteFooter, emits `noindex`, keeps
the legacy canonical project path `/<slug>/`, and shows a visible empty state
when the API or CMS record is unavailable. It does not take over the public
project route or submit customer/contact data.

Coordinator-owned QA remains PHP 8.1 Docker/API integration, Nuxt build and
browser responsive verification. The lightweight PHP helper contract and Node
contract tests are local checks only.
