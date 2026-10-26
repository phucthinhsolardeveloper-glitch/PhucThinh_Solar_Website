# PHP Product Catalog JSON contract — Phase A migration
Branch feature/pts-nuxt-catalog-api-20261026 from integration@5190099.
Read AGENTS.md; docs/14-nuxt-migration/ROUTE_AND_WORKSTREAM_MATRIX.md; PHP sources/product.php, templates/product/product_tpl.php, api/product.php, api/fillter_service.php, api/config.php, and libraries/public_pricing.php, product_category_urls.php, product_sort.php, product_price_filter.php, product_need_filter.php, product_power_filter.php.

Goal: safe read-only initial product category/listing JSON DTO for Nuxt. Do NOT reproduce or modify legacy pricing, sorting, filtering or visibility logic without verified delegation. Prefer minimal scope published categories and safe products by category; if filters/price cannot be faithfully delegated, omit and document TODO. Product name, canonical URL, media only when public and validated. Prepared queries for user-controlled values, capped pagination, HTTP GET only; 405 on POST and 400 bad query; no raw DB error. No unauthorised private data/HTML; no checkout/session/cookie changes.
Implement new files api/pts_v1_catalog.php, libraries/pts_public_catalog_api.php and scripts/test-pts-public-catalog-*.php. Include pure deterministic DTO test for hidden/invalid records, bounds, canonical URL, private field omission and error method; document schema and parity gaps in docs/14-nuxt-migration/CATALOG-CONTRACT.md and TASK-CATALOG-API.md. Use actual public_pricing.php for any fields related to price or OMIT such fields with explicit note. Do not create public Nuxt pages yet.
Allowed edits ONLY these new files and scoped docs. Do not edit existing PHP source/template/router/pricing/cart, Nuxt, deployment, master or production. PHP CLI may be unavailable in agent sandbox; if tests blocked by Docker socket, report and leave uncommitted; coordinator can run PHP 8.1 local Docker. No push/deploy/merge. Work accurately rather than expanding scope. Report concise Vietnamese tests+files+limitations.

## Implementation checkpoint

Phase A contract is documented in `CATALOG-CONTRACT.md`. The implementation
omits price, legacy filters and merchandising sort until parity is verified;
no legacy source, template, router, checkout/session behavior or deployment
file is changed.
