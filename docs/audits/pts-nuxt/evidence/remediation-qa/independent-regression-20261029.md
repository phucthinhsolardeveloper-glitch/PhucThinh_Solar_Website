# Independent regression QA — 2026-10-29

Phạm vi: kiểm tra chéo remediation A–F trên Nuxt worktree; không sửa source/commit/deploy.

## Checkpoint

- Nuxt HEAD: `10f3ee3` (chứa `5ccc73d` contract fix và `10f3ee3` functional surface test).
- PHP resolver QA branch: `/home/admin01/src/Web_PTS_PHP-remediation-qa`, `50c1183`.
- PHP master/production không bị sửa.

## Automated checks

| Check | Result | Evidence |
|---|---|---|
| `npm test` | **PASS — 139/139** | independent run, exit 0 |
| `npm run typecheck` | **PASS** | independent run, exit 0 |
| `node --test tests/preview-noindex.test.mjs tests/public-functional-surface.test.mjs` | **PASS — 4/4** | independent run |
| PHP resolver endpoint/helper syntax | **PASS** | Docker `web_pts_php-web:latest` PHP 8.1 `php -l` + helper test |

## Runtime checks

### PASS — PHP QA resolver endpoint

QA PHP container built from branch `50c1183` returned:

- Published news slug: HTTP 200, `{schemaVersion:1, entity:"news", canonical:"/.../"}`.
- Published project slug: resolver DTO returned `entity:"project"` in Nuxt bridge test.
- Unknown slug: HTTP 200 `entity:"not-found", slug:"", canonical:""` (Nuxt page converts this to 404).
- Invalid slug: HTTP 400 `BAD_SLUG`.

### PASS — Nuxt bridge and product 404

Nuxt production build with `NUXT_PHP_ORIGIN=http://127.0.0.1:8082`:

- `/_migration/resolve-public-slug?slug=duoc-gi-khi-hop-tac-cung-phuc-thinh-solar` → 200 canonical news DTO.
- `/_migration/resolve-public-slug?slug=du-an-dien-nlmt-ho-anh-quan` → 200 canonical project DTO.
- `/_migration/resolve-public-slug?slug=not-found-slug` → 200 not-found DTO.
- `/_migration/product-detail?slug=not-found-slug` → **404**, confirming pass-through fix.

### FAIL — Full SSR project detail remains broken

With the same QA PHP origin and rebuilt Nitro:

- News detail `/duoc-gi-khi-hop-tac-cung-phuc-thinh-solar/` → HTTP 200 and article title.
- Project detail `/du-an-dien-nlmt-ho-anh-quan/` → **HTTP 502**, generic “Không tìm thấy nội dung”.
- Unknown slug → HTTP 404.

The resolver itself succeeds for the project, so the remaining defect is downstream project-detail DTO/API/render compatibility, not routing resolution. This remains a P1 release blocker until the project detail request is traced and fixed with a real fixture.

### BLOCKED — Caddy/alias and media final verification

Existing local Caddy evidence reports `/product/` and `/ve-chung-toi/` 301, but `/du-an/` 404 from a stale Nitro build. A fresh build was produced during this QA; Caddy must be restarted against that exact build before closing the alias issue. No real `/upload/*` asset was available for a 200 media assertion.

## Other checks

- UI source checks: Home H1 has explicit accessible label; mobile controls use 44px classes; Catalog no longer uses the four Unicode benefit glyphs.
- Noindex inventory test covers all discovered `migration-*` pages.
- No production DNS/DB/deploy operation performed.

## Independent conclusion

`npm` and bridge-level contract fixes are verified, but migration is **not ready for release** because project-detail SSR still returns 502 and Caddy/media final smoke remains incomplete. Do not close REM-P1-003/REM-P2-005/REM-P2-006 until those checks run against the same rebuilt artifact and a real media fixture.

