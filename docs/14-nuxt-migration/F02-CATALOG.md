# F02/F04 — Nuxt catalog

The internal `/migration-catalog-preview` and flag-off public catalog wrappers
reuse the same Nuxt catalog page. The page reads category/site navigation and
products through Nitro adapters from PHP `pts_v1_catalog.php`. Public routes
remain controlled by their explicit feature flags and fall back to PHP while
disabled.

## Current parity

- Category navigation uses the validated one-to-four-level PHP hierarchy and
  preserves canonical legacy paths.
- The read-only catalog DTO supplies published products, real media, power and
  quick specification text (`W`, chống nước, thời gian chiếu sáng), pricing or
  quote mode, pagination, available filters, selected filters, and supported
  sort state.
- PHP remains the authority for product visibility, price visibility,
  category/filter resolution, merchandising order, and canonical URLs.
- The Nuxt validator accepts the legacy v1 product shape while deployments are
  staggered and validates the extended quick-spec fields.
- The filter rail appears from 1002px upward; at 1001px and below the catalog
  keeps the product list in a single column and opens the same filters from a
  modal drawer. The drawer closes on Escape or after a filter link is selected,
  and returns focus to its trigger when closed.
- Preview routes stay `noindex`; public indexability is controlled separately
  from the route-enabled flags.

See [CATALOG-CONTRACT.md](CATALOG-CONTRACT.md) for the current query and DTO
contract. The API change is additive and GET-only; it does not move Admin,
pricing, cart, checkout, or product ownership to Nuxt.
