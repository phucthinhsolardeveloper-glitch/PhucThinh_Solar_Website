# Nuxt full-public cutover candidate

Checkpoint: 2026-10-28. Owner directive supersedes the 2026-10-27 staged-pilot
proposal: canonical public pages must render through Nuxt, with no PHP HTML/CSS/JS
fallback. This commit contains a review candidate only. No production SSH, DNS,
container restart, database mutation, or deployment has occurred.

## Candidate topology

```text
Internet -> Caddy -> Nuxt SSR -> PHP backend -> MariaDB
                   └────────── PHP Admin/API/media actions
```

`deploy/vps/Caddyfile` sends all public requests to `nuxt:3000` after handling
explicit backend exceptions. There is no PHP fallback for public GET/HEAD pages
or legacy `/assets/css/*` and `/assets/js/*`. Nuxt SSR is configured with the
internal PHP origin `http://web:80`, allowlisted exactly as `web:80`.

`deploy/vps/docker-compose.yml` defines the immutable Nuxt image as a service on
the private `edge` network. Caddy waits for both PHP and Nuxt healthchecks. Nuxt
health checks the canonical home route, which also exercises the configured PHP
backend. The release package now includes checksummed PHP, Nuxt source, and
runtime/deployment tools. `deploy-release.sh` builds both images, installs the
matching Caddy/Compose files, validates both configs, recreates the services,
and waits for both healthchecks. It records previous PHP/Nuxt image values and
previous Caddy/Compose files for rollback. `rollback-release.sh` restores that
whole previous release and checks health before reporting success.

## PHP exception routes

Only these requests remain PHP-owned at the public proxy:

- `/api/*` for read DTOs, cart/account/checkout mutations, reviews, shipping,
  and VNPay callbacks.
- `/administrator` and `/administrator/*` for the PHP Admin and its relative
  Admin assets.
- `/upload/*`, `/thumbs/*`, `/watermark/*`, and `/wp-content/uploads/*` for CMS
  media, image transforms, and retained WordPress media.
- `/assets/images/site-visuals/*` image files and supported PDF/DOC/XLS(X)
  documents under `/assets/`; public PHP CSS and JavaScript are excluded.
- `/robots.txt` and `/sitemap.xml`.
- GET `/account/dang-xuat[/]` and `/ngon-ngu/{vi|en}[/]` as session actions.

All account document routes, cart, checkout, catalog, project/news/policy pages,
solutions, dealer, gallery, video, and aliases fall through to Nuxt. Account and
commerce POSTs must reach Nuxt Nitro adapters; the adapters call the PHP business
backend without returning PHP templates. A PHP mutation endpoint that emits a
legacy page is not acceptable for the final cutover.

## Validation and release gate

Local full-Nuxt rehearsal is separate from production Compose and does not change
production state. Before a production release, reviewers/operators must verify:

1. The release bundle checksum includes the PHP archive, frontend source archive,
   deployment tools, and `RELEASE.txt` for one commit.
2. `docker compose config --quiet` and Caddy `validate` pass with the release
   environment before services are recreated.
3. PHP and Nuxt healthchecks are healthy; canonical pages, aliases, 404s,
   `/robots.txt`, `/sitemap.xml`, API, Admin, media and payment callback behavior
   are smoke-checked through Caddy.
4. Browser verification shows public HTML and `/_nuxt/*` assets come from Nuxt;
   `/assets/css/*` and `/assets/js/*` are not served by PHP.
5. The rollback candidate retains the prior PHP image, Nuxt image (if any),
   Caddyfile, and Compose file. No DB or upload rollback is included because
   this release does not migrate those stores.

The owner authorized the implementation candidate, not a live production deploy.
Deployment, SSH, DNS, service restart, and production validation remain a
separate reviewed operation. Current production state is unchanged until that
reviewed operation is explicitly performed.

## Route and SEO ownership

Caddy serves canonical public routes through Nuxt. Nuxt implements the public
aliases (including `/product/`, `/ve-chung-toi/`, and `/he-thong-dai-ly/`) and
entity/category resolution from PHP data APIs. APIs, Admin, and backend actions
remain PHP-owned. Missing public routes must return a real Nuxt 404; a soft-200 or
PHP template fallback fails review. Existing public slugs remain unchanged.

The previous single-route pilot, `PTS_NUXT_HYBRID_ENABLED` flag, and PHP public
HTML fallback described in the 2026-10-27 proposal are retired by this owner
directive. Do not re-enable them in a later release.
