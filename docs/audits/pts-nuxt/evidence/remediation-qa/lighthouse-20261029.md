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
| `/san-pham/` | 0.87–0.89 | **1.00** | **1.00** | 2.4s | 2.4s | 0 | 258–336ms |
| `/tin-tuc/` | 0.69 | 0.96 | 1.00 | 2.3s | 15.7s | ~0 | 193ms |

The home rerun after the SEO fix reports `meta-description: 1`, accessibility `0.97`, SEO `1.00`; the catalog rerun reports `meta-description: 1`, canonical `1`, accessibility `1.00`, SEO `1.00`. Performance remains variable because the audit downloads large below-fold imagery and the local PHP media proxy differs from production edge.

## Remaining performance work

- Add responsive image variants/`srcset` or edge resizing for large editorial images; Lighthouse identified multi-megabyte PNG/JPEG payloads.
- Re-run on the production-like Caddy topology before setting a release performance budget.
- This is a QA baseline, not a production performance guarantee.
