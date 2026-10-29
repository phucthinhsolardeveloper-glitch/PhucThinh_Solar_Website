# Functional QA — Nuxt public migration

Date: 2026-10-29  
Nuxt worktree: `/home/admin01/src/pts-nuxt-full-20261028`  
Nuxt runtime: `http://127.0.0.1:3110` (`.output/server/index.mjs`, `NUXT_PHP_ORIGIN=http://127.0.0.1:8080`)  
PHP runtime: `http://127.0.0.1:8080`  
Scope: browser functional QA only. No source code, production, database, or deployment changes were made.

## Route matrix

| Route | Status | Result |
|---|---:|---|
| `/` | 200 | PASS; H1 and shell render. Repeated 404 preview-media console errors (F-02). |
| `/san-pham/` | 200 | PASS; catalog renders, filter links and pagination links are present. |
| `/danh-muc-san-pham/den-nlmt/` | 200 | PASS; category renders. |
| `/dai-ly/` | 200 | PASS; dealer content and form render. |
| `/lien-he/` | 200 | PASS; form renders and browser required-field validation works. |
| `/giai-phap/` | 200 | PASS; cards and CTA render. |
| `/du-an-da-thuc-hien/` | 200 | PASS; listing renders. |
| `/tin-tuc/` | 200 | PASS; listing and pagination render. |
| `/thu-vien-anh/` | 200 | PASS; empty dataset displays `Không tìm thấy kết quả`. |
| `/video/` | 200 | PASS; empty dataset displays `Không tìm thấy kết quả`. |
| `/gioi-thieu/` | 200 | PASS; content renders. |
| `/ho-tro/chinh-sach-bao-mat-va-bao-ve-du-lieu-ca-nhan/` | 200 | PASS; policy detail, TOC and share links render. |
| `/ho-tro/chinh-sach-giao-hang-va-kiem-hang/` | 200 | PASS; policy detail, TOC and share links render. |
| `/duoc-gi-khi-hop-tac-cung-phuc-thinh-solar/` | 502 page state | FAIL/F-01; route resolver backend unavailable. |
| `/du-an-dien-nlmt-ho-anh-quan/` | 502 page state | FAIL/F-01; route resolver backend unavailable. |

## Interaction checks

| Area | Steps | Result |
|---|---|---|
| Mobile menu | 390px: open header menu, inspect overlay, press Escape | PASS; `#ptsMobileMenu` opens, has close/search/menu controls, Escape closes and restores `aria-expanded=false`. Evidence: `evidence/functional/mobile-menu-open.png`. |
| News search | `/tin-tuc/` → enter `solar` in search | PASS; URL becomes `/tin-tuc/?q=solar`. |
| Catalog filter | `/san-pham/` → click price link | PASS; URL becomes `/san-pham/?price=1`. There are 3 price links and 11 brand links. |
| News pagination | `/tin-tuc/` → page 2 → Back → Refresh | PASS; `/tin-tuc/?p=2`, browser Back returns `/tin-tuc/`, refresh preserves route. |
| Contact validation | `/lien-he/` → submit empty form | PASS; browser reports 3 invalid required controls; no submission occurred. |
| Video modal | `/video/` → inspect main buttons | BLOCKED by current empty CMS dataset; 0 video buttons. Empty copy is present and correct. |
| Gallery lightbox | `/thu-vien-anh/` → inspect albums | BLOCKED by current empty CMS dataset; 0 album cards. Empty copy is present and correct. |
| Dynamic news/project detail | Direct URL above | FAIL/F-01; resolver fails before detail page can exercise related cards/share/CTA. Direct detail APIs themselves return 200 for both fixtures. |

## Issues

### F-01 — P1 — Public dynamic slug resolver unavailable

- Routes: `/duoc-gi-khi-hop-tac-cung-phuc-thinh-solar/`, `/du-an-dien-nlmt-ho-anh-quan/`
- Steps: open either public canonical URL directly, or click a dynamic card link; wait for SSR response.
- Actual: Nuxt renders the generic “Không tìm thấy nội dung” state with a 502 route-resolution failure.
- Evidence: `evidence/functional/news-detail.png`, `evidence/functional/project-detail.png`; runtime response from `/_migration/resolve-public-slug?slug=...` is `502 Public route resolver unavailable`.
- Root cause: PHP `http://127.0.0.1:8080/api/pts_v1_public_route_resolve.php` returns HTTP 404. The detail APIs do exist and return HTTP 200 (`pts_v1_news_detail.php`, `pts_v1_project_detail.php`), so this is a missing/mismatched resolver backend dependency, not an empty content fixture.
- Proposal: deploy/enable the resolver endpoint on the same PHP production runtime, then rerun direct URL, card click, canonical redirect, Back/Refresh, related-card and CTA checks. Do not mask this with frontend fallback routing because the resolver owns entity and canonical slug resolution.
- Blocker: requires PHP backend/API change and review; not changed in this audit.

### F-02 — P2 — Preview media requests return 404 on public pages

- Routes: reproduced on `/`, `/san-pham/`, `/dai-ly/`, `/lien-he/`, `/giai-phap/`, `/du-an-da-thuc-hien/`, `/tin-tuc/`, `/thu-vien-anh/`, `/video/`, `/gioi-thieu/`.
- Steps: load route with Playwright and collect browser console errors.
- Actual: repeated `Failed to load resource: 404 (Preview media not found)` errors. The pages still return 200 and render fallback/content, but missing media can affect hero/card visuals.
- Evidence: route screenshots under `evidence/functional/route-*.png`; console matrix captured during the route sweep.
- Root cause: Nuxt preview media URLs are not all available through the current PHP origin/runtime mapping.
- Proposal: inventory every failing media URL, map it to the PHP `/upload` or `/assets` source, and verify through the production cutover Caddy rules. Do not replace with invented/stock images.
- Blocker: exact failing URL list must be correlated with the mounted PHP media volume before changing routing.

## Evidence inventory

- `route-*.png`: full-page screenshots for the route matrix.
- `header-dropdown.png`: desktop header state.
- `mobile-menu-open.png`: mobile menu open state.
- `news-detail.png`, `project-detail.png`: dynamic detail failure states.

