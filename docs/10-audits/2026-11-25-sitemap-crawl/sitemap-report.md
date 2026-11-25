# Read-only sitemap crawl

- Checked: 2026-11-25T04:28:19.715507+00:00
- Declared URLs: 440; unique crawled: 440
- Sitemap files: 1; sitemap issues: 0; page URLs requiring review: 0; page redirects: 0
- All 440 sitemap entries were HTML: HTTP 200 at the declared URL; no missing canonical, canonical mismatch, or `noindex` meta robots found.

## Sitemap files

| Status | URL | Final URL |
|---:|---|---|
| 200 | https://phucthinhsolar.com/sitemap.xml | https://phucthinhsolar.com/sitemap.xml |

## URLs requiring review

| Status | URL | Final URL | Robots | Canonical | Error |
|---:|---|---|---|---|---|
| — | None | — | — | — | 440/440 returned 200 at their declared URL; all had self-matching canonical and indexable meta robots. |

## Additional read-only SEO checks

- `/robots.txt`: returns the canonical sitemap URL and disallows admin, API, cart/checkout, and configured faceted query keys. The `price` and `keyword` rules are present.
- `GET /danh-muc-san-pham/pin-luu-tru` redirects `301` to `/danh-muc-san-pham/pin-luu-tru/`.
- The category URL with `?price=1` and search URL with `?keyword=pin` return HTTP 200 and include `noindex, nofollow, noarchive` meta robots. Existing frontend tests also cover filtered/empty indexability and the robots rules.
- A deliberately unknown product URL and unknown public path both return 404. A live 410 check was not run because no verified hidden-product fixture was available; the existing regression test covers the code path.
- Category page SEO API reports `Hybrid / lưu trữ | Phúc Thịnh Solar` for the Hybrid category.

## Merchant and commerce notes

- Public GET catalog data returned 12 priced Hybrid products (24,379,000–155,427,000 VND) and 10 priced storage products (31,238,000–1,852,573,000 VND). The new price bands divide these current distributions into usable groups. These observed prices are a point-in-time audit input, not a promise that future catalog prices will remain in the same ranges.
- Product JSON-LD already omits Offer availability unless Admin verifies `merchant_availability=in_stock`; GTIN/MPN are passed only from validated Merchant DTO fields. Existing backend and frontend regression tests cover these rules.
- Checkout recalculates from the server product record. The code review found catalog price sorting used regular price while cards, filters and checkout use sale price when set; the patch aligns sort with the effective purchase price.
- Live X3-MIC-8 page used storage/backup use-case copy; its public `inverter_type` specification is empty while the catalog inventory classifies it as grid-tie. The fix keys this verified model to a grid-tie default and also honors an explicit `Hòa lưới` type for other models. Hybrid use cases remain unchanged. Manufacturer reference: [SolaX X3-MIC G2 datasheet](https://www.solaxpower.com/uploads/file/solax-x3-mic-g2-datasheet-en.pdf).

Google Search Central guidance used for canonicalization and sitemap review: [canonicalization](https://developers.google.com/search/docs/crawling-indexing/canonicalization), [canonical signals](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls), and [ecommerce pagination/filter URLs](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading).

## Limits

This live audit used unauthenticated GET requests only. No cart, checkout, Admin, production DB, cookie, or form mutation was made. It did not verify Google-selected canonicals/Search Console indexing. The documented two-origin migration audit script is absent from this worktree; the new sitemap crawler does not compare PHP and Nuxt route parity.
