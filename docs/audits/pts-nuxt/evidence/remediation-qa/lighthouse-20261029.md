# Lighthouse QA baseline — 2026-10-29

Artifact: fresh Nuxt Nitro build from remediation branch, local PHP QA origin `127.0.0.1:8088`, `NITRO_PREVIEW_MEDIA_PROXY=1`.

Command used:

```text
CHROME_PATH=/home/admin01/.cache/ms-playwright/chromium-1247/chrome-linux64/chrome
npx --yes lighthouse <url> --chrome-flags="--headless=new --no-sandbox --disable-dev-shm-usage" \
  --only-categories=performance,accessibility,seo --output=json
```

## Results

| Route | Performance | Accessibility | SEO | FCP | LCP | CLS | TBT |
|---|---:|---:|---:|---:|---:|---:|---:|
| `/` | 0.58–0.65 | 0.97 | 0.92 → **1.00 after meta fallback fix** | 2.4s | 16.2–17.8s | 0.006 | 291–512ms |
| `/san-pham/` | 0.87–0.92 | **1.00** | **1.00** | 2.4s | 2.4s | 0 | 177–336ms |
| `/tin-tuc/` | 0.47–0.69 | 0.96 | 1.00 | 2.3s | 15.7–17.7s | ~0 | 193–1,310ms |

The home rerun after the SEO fix reports `meta-description: 1`, accessibility `0.97`, SEO `1.00`; the catalog rerun reports `meta-description: 1`, canonical `1`, accessibility `1.00`, SEO `1.00`. Catalog image dimensions and news-card thumbnails were tightened in commits `20d41f4` and `74ed17f`; catalog performance improved to `0.92`. News performance remains variable because the audit still downloads large logo/media payloads and the local PHP proxy differs from production edge.

## Logo layout follow-up

Commit `9650ee3` declares intrinsic dimensions for the CMS header wordmark, footer emblem and trust badge. Commit `575401a` serves these logos through right-sized PHP thumbnail endpoints; commit `65f5a24` reduces NewsCard thumbnails from 760×428 to 640×360. Fresh Lighthouse runs on home/news now report `unsized-images: 1.00` and SEO `1.00`; a fresh news run measured LCP ~10.6s. Remaining performance loss is image delivery/payload size and variable local LCP, not layout reservation.

## Remaining performance work

- Add responsive image variants/`srcset` or edge resizing for large editorial images; Lighthouse identified multi-megabyte PNG/JPEG payloads.
- Re-run on the production-like Caddy topology before setting a release performance budget.
- This is a QA baseline, not a production performance guarantee.
