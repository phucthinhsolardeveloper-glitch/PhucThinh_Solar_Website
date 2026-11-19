# Phúc Thịnh Solar — Merchant brand correction (2026-11-19)

## Production data correction actually performed

- Production code release stays 9ca55f14 (no new Docker image or migration). Catalog has 399 products, 330 visible.
- Before: 21 visible products lacked brand_id; 8 of 315 live Merchant items had no brand.
- After one guarded SQL transaction: 15 products assigned brand_id based on manufacturer/distributor documents; 6 visible products remain without brands; **one** of 315 live Merchant items remains without brand (JD-128L).
- Live before/after XML audit: 315 IDs unchanged; exactly 7 brand fields added. Names, descriptions, prices, availability, category, links and extra images unchanged.
- Before-backup verified by SHA256: /srv/pts-laravel13/backups/20261119-merchant-brand-data-before-correction/catalog-before.sql.gz
- Execution script and receipt: /srv/pts-laravel13/releases/9ca55f14/brand-correction.php and brand-correction-apply.log.
- Guard conditions: each product must have expected ID, original SKU, matching name, be visible, and have brand_id NULL; product updates only touched brand_id and updated_at. Two newly added brands are initially hidden (is_visible=0) to prevent thin public brand pages. Legacy and verified feed caches were invalidated.

## Evidence-backed mappings

| Product ID(s) | Assigned brand | Evidence |
| --- | --- | --- |
| 335 | BLUE CARBON | Manufacturer: https://www.bluecarbontech.com.cn/en/product/1075.html ; distributor: https://enovatech.com.vn/products/den-duong-nang-luong-mat-troi-56w-blue-cacbon-bct-olk2-0p-mono |
| 343, 347 | JINDIAN | JD-L400: https://hoangphatlighting.vn/jd-l400-jindian-3392-15.html ; JD-L200NEW public tender: https://dauthau.asia/en/result/awarded-contractor/install-solar-lighting-system-1257955.html |
| 359, 361 | Sharp | Sharp official NUJD550: https://www.sharp.eu/sharp-550-wp-nujd550 ; 361 product names Sharp 395Wp |
| 360 | LONGi | https://www.dhcsolar.com/san-pham/tam-pin-mat-troi-longi-450w/ |
| 382, 384 | JINDIAN | JD198L: https://www.ntechsolutions.vn/quat-dung-tich-dien-nang-luong-mat-troi-jindian-jd198l.html ; JD188L: https://www.nhatthuc.com.vn/quat-nang-luong-mat-troi-jindian-jd-188l |
| 372, 373, 374 | THIPHA Cable (new brand, not separately published) | Manufacturer: https://thiphacable.com/wp-content/uploads/2024/01/catalogue-dan-dung-022024.pdf |
| 393, 394, 395, 396 | Cát Vạn Lợi (new brand, not separately published) | Manufacturer: https://www.catvanloi.com/ ; product group: https://www.catvanloi.com/giai-phap-ong-ruot-ga-loi-thep-cho-he-thong-dien-hien-dai.html |

## Six brand entries deliberately not fabricated

IDs 354 (unbranded garden light), 375 (generic PV clamp), 381 (Blue Fan, actual maker unclear), 383 (JD-128L, no conclusive exact Jindian manufacturer label), 391 (generic Nano electrical tape), 392 (Apolo foam spelling/label unclear). Do not infer a brand from a partial SKU or misspelling: inspect actual packaging/manufacturer proof.

## Remaining independent blockers

- Merchant readiness on current production: 13 visible products missing product codes (MISA/ERP source of truth), 5 missing price, 1 SolaX D150 Pro Shopping image 454x454 below internal 500px gate. A manufacturer product image exists for D150 without “Pro”; model equivalence / media permission has not been established, so it was NOT substituted.
- All 330 visible products lack verified merchant availability and a confirmed GTIN/MPN or evidence that manufacturer identifiers do not exist. Verified feed flag is OFF. Legacy feed still labels 315 items in_stock and identifier_exists=no by its old published logic; these statements should not be regarded as verified stock or manufacturer IDs.
- No Google-account performance measurements were invented: OpenSEO GSC status not_connected, HYPD connections return zero Search Console sites, zero GA4 properties, zero Merchant accounts; GSC Wizard cannot retrieve data under current access. Account authorization is required for Search Console / GA4 Data / Merchant API read-only diagnostics.
- Admin already provides Merchant readiness report and CSV; staff can address the remaining per-SKU blockers by label, invoice, MISA warehouse source and supplier datasheets. Do not guess manufacturer IDs, prices or warehouse inventory.
