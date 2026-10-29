# Phase 2 — Backend/API/Data audit

Ngày audit: 2026-10-29 (Asia/Bangkok)  
Phạm vi: PHP API hiện hành (/home/admin01/src/Web_PTS_PHP) và Nuxt worktree
/home/admin01/src/pts-nuxt-full-public-20261028.  
Quyền thay đổi: audit-only. Không sửa code sản phẩm, không commit, không deploy,
không ghi DB, không lưu cookie/PII/raw CMS response.

## 1. Checkpoint và phương pháp

Đã kiểm tra:

1. Inventory frontend/server/routes/_migration/*.ts và
   domains/phucthinhsolar.com/public_html/api/pts_v1_*.php.
2. Đối chiếu endpoint, query allowlist, status/error handling, schema validator,
   cache/session/CORS headers.
3. Gọi read-only API trên PHP 8080 (root/master) và PHP 8088 (preview).
4. Gọi Nuxt SSR bridges trên ports 3110/3111 (PHP origin 8080) và 3112
   (PHP origin 8088).
5. Chạy negative POST checks cho lead/cart/review và Playwright SSR smoke.
6. Ghi evidence không chứa PII tại
   docs/audits/pts-nuxt/evidence/backend/runtime-smoke-20261029.txt.

Heads tại thời điểm audit:

- Nuxt: aea5bc0 (codex/full-nuxt-public-ui-20261028).
- PHP root/master: 5f616c4.

## 2. Inventory route/API

Các read-only bridge sau có handler Nuxt và PHP endpoint tương ứng: site, home,
catalog, news, news-detail, projects, project-detail, solutions,
solution-detail, dealer, contact, about, gallery, gallery-detail, video,
brand-detail, product-detail, product-extras, policy-detail, search,
order-thanks, public-route-resolve.

Các bridge session/mutation có handler riêng: account, cart GET/POST,
checkout GET/POST, checkout wards, lead form GET/POST, review POST,
shipping quote POST, product document GET.

Các endpoint được kiểm tra tĩnh tồn tại trong PHP worktree, gồm cả
shipping_quote.php. Không phát hiện Nuxt handler nào trỏ tới một upstream URL
do request user cung cấp; upstream được tạo từ phpOrigin có allowlist.

## 3. Findings

### P1-BE-001 — Production PHP 8080 thiếu public route resolver

Affected routes: mọi bare slug news/project/category/gallery dùng
frontend/app/pages/[slug].vue.  
Nuxt file: frontend/server/routes/_migration/resolve-public-slug.get.ts:10-15.  
PHP expected file: domains/phucthinhsolar.com/public_html/api/pts_v1_public_route_resolve.php.

Reproduce:

    GET http://127.0.0.1:8080/api/pts_v1_public_route_resolve.php?slug=duoc-gi-khi-hop-tac-cung-phuc-thinh-solar
    → 404 text/html (legacy PHP 404 page)

    GET http://127.0.0.1:3110/_migration/resolve-public-slug?slug=duoc-gi-khi-hop-tac-cung-phuc-thinh-solar
    → 502 Public route resolver unavailable

The file exists in the Nuxt candidate PHP tree but does not exist in the current
PHP root/master tree. PHP 8088 preview has the endpoint and returns JSON. This is
a release blocker for bare-slug news/project routes until the endpoint and its
library dependencies are deployed to the production PHP tree.

Proposal: deploy pts_v1_public_route_resolve.php, public_route_resolver.php, and
pts_public_route_resolve_api.php together; then smoke every published bare slug
and 404 slug.

### P1-BE-002 — Resolver DTO field mismatch between PHP preview and Nuxt contract

Nuxt contract: frontend/shared/contracts/public-route-resolution.ts:15-53
requires canonical.  
PHP 8088 response observed: {schemaVersion, slug, kind, canonicalPath}.

Reproduce:

    GET http://127.0.0.1:8088/api/pts_v1_public_route_resolve.php?slug=duoc-gi-khi-hop-tac-cung-phuc-thinh-solar
    → 200 JSON with canonicalPath

    GET http://127.0.0.1:3112/_migration/resolve-public-slug?slug=duoc-gi-khi-hop-tac-cung-phuc-thinh-solar
    → 502 Invalid public route resolution contract

The candidate PHP source currently contains a newer DTO helper that emits
canonical; the running 8088 preview container is stale relative to that source.
This proves the deployment artifact/container can lag the checked-out source.

Proposal: pin the PHP API and library versions as one release artifact; add a
contract fixture test asserting exact field names and reject canonicalPath before
deployment.

### P2-BE-003 — Product detail 404 is converted to Nuxt 502

Nuxt file: frontend/server/routes/_migration/product-detail.get.ts:9-15.
The fetchMigrationPayload call lacks passThroughStatuses: [400, 404].

Reproduce:

    GET http://127.0.0.1:8080/api/pts_v1_product_detail.php?slug=unknown-slug
    → 404 PRODUCT_NOT_FOUND

    GET http://127.0.0.1:3110/_migration/product-detail?slug=unknown-slug
    → 502 PHP product detail data source unavailable

Impact: invalid/removed product URLs are reported as upstream outage instead of
preserving a normal 404/noindex response.

Proposal: pass through 400/404 like news/project detail handlers; add a negative
contract test for PRODUCT_NOT_FOUND.

### P2-BE-004 — PHP 8080 and 8088 projections are not the same release

Read-only calls returned 200 on both origins, but top-level fields differ:

- home 8088 adds summary.campaignsConfigured.
- projects 8088 adds intro and featuredBenefits; 8080 lacks both.
- dealer 8088 adds intro; 8080 lacks it.

The current Nuxt candidate has some compatibility fallback (projects intro,
dealer heroDescription), but this does not prove all future projection fields
are backward-compatible. Runtime QA against 8088 can therefore pass while the
production origin 8080 renders a different content shape.

Proposal: build and deploy PHP API + Nuxt from the same release SHA, publish a
machine-readable contract version, and run the same fixture matrix against both
origins before cutover.

### P2-BE-005 — Direct Nitro smoke produces media 404 without the Caddy media path

Playwright against direct Nuxt port 3110/3112 logged repeated Preview media not
found for /upload/* and /assets/*. The full Caddy configuration is expected to
proxy legacy media to PHP, so this is an architecture requirement rather than
proof that media is broken in the intended cutover topology.

Release condition: run browser smoke through Caddy, not direct Nitro, and assert
that representative CMS image URLs return 200 from the public origin.

## 4. Passed checks

- PHP 8080 and 8088 returned 200 for site/home/catalog/news/projects/solutions/
  dealer/contact/about/gallery/video read-only APIs.
- Nuxt bridges returned 200 for those list endpoints and for valid first-record
  news/project/solution/product detail fixtures.
- Gallery and video empty datasets returned 200 with total: 0; source copy is
  Không tìm thấy kết quả.
- Negative POST checks returned expected 400/419 responses for malformed lead,
  cart mutation, and review requests.
- No Access-Control-Allow-Origin was returned for an untrusted Origin.
- API responses include X-Content-Type-Options: nosniff and no-store headers.
- Session bridge code forwards only sanitized PHP session/member cookies and
  validates the configured PHP origin against an allowlist.

## 5. Data/CMS notes

Both current origins returned dealer stats 5, 50, 5000. The earlier 5/41/4.012
screenshot cannot be attributed to a code hardcode from this audit; it requires
identifying the production database/content snapshot. Home hero and campaign data
are CMS-backed when configured, with documented PHP fallback rows when no enabled
CMS rows exist. That fallback state remains a product/content verification item.

## 6. Release blockers and next actions

1. Blocker: deploy route resolver to PHP 8080 and align DTO field to canonical.
2. Blocker: rerun bare-slug SSR smoke through Caddy with PHP 8080 after resolver
   deployment.
3. Required fix: preserve product-detail 404 status in Nuxt.
4. Required release gate: compare 8080/8088 projections from the exact same
   artifact/DB snapshot; stop if top-level contract fields differ unexpectedly.
5. Required browser gate: verify CMS media through Caddy public routes.

No code product fix, commit, deployment, database write, cleanup, or backup action
was performed in this audit phase.
