# Catalog API contract

Endpoint: `GET /api/pts_v1_catalog.php`.

Allowed query keys are `category`, `page`, `per_page`, `brand`, `price`,
`subcategory`, `sort`, `keyword` and `is_need`. Category paths accept one to four
validated slug segments; `page` is bounded to 10000 and `per_page` to 48. Brand,
subcategory, price, sort and need filters use allowlisted values. Unknown keys or
malformed values return `400 BAD_QUERY`; unknown category paths return `404`;
non-GET methods return `405 METHOD_NOT_ALLOWED`. SQL filter values are bound as
parameters. Runtime errors do not expose database details.

The schema-version-1 response contains SEO/canonical data, the selected
category, category navigation, products, pagination, filter options, selected
filters, parity flags, and optional Admin-managed category content/support.
Product URLs keep the PHP path `/san-pham/{slug}` and category URLs use
`/danh-muc-san-pham/{slug...}/`. Only published products with valid names and
slugs are included. Product media must resolve to an existing allowed upload.

Product cards receive `powerWatts`, plain-text `quickSpecs`, and `pricing` from
the PHP projection. `quickSpecs` is built only from the existing `cong_suat`,
`waterproof_rating` and `lighting_time` columns; missing/placeholder values are
omitted. Price and quote mode remain decided by
`libraries/public_pricing.php`; Nuxt must not infer a price. When public prices
are disabled, the response uses quote mode and marks `parity.prices` as disabled.
Filters and sort report their current parity state in the response.

The Nuxt validator accepts the original product DTO during staggered PHP/Nuxt
rollouts and validates the extended `quickSpecs` values as bounded plain text.
The endpoint remains read-only: it does not create checkout, cookie or cart
business state, and it does not replace the legacy product routes.
