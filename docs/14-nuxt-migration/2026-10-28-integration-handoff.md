# Nuxt integration checkpoint — 2026-10-28

## Trạng thái

- Nhánh tích hợp: `feature/pts-nuxt-integration-20261027`
- Checkpoint chức năng hiện tại: `75b276a` (dependency gate); smoke gate được
  mở rộng ở `5e97ab8`.
- `master`, production, Docker/container và public routing không bị thay đổi.
- Nhánh chưa push remote; số commit ahead được kiểm tra bằng
  `git rev-list --left-right --count origin/feature/pts-nuxt-integration-20261027...HEAD`.

## Đã hợp nhất

- Các nhánh QA Nuxt về cutover, shell và visual.
- API query validation cho policy/search (`f7d739b`).
- Gallery/video read-only PHP API, DTO, Nitro adapters và preview pages (`43fd49e`, cherry-picked as `4d14c20`, promoted as part of `92d6fd4`).
- Cache policy private/no-store cho các media adapters.
- Strict TypeScript narrowing cho gallery lightbox.
- Contract tests cho gallery/video (`frontend/tests/gallery-video-contract.test.mjs`).

## Bằng chứng xác minh

- Frontend contract tests: **117/117 PASS**.
- Cutover static tests: **8/8 PASS**; preflight vẫn báo `BLOCKED` đúng thiết kế vì feature flag OFF và chưa có production Nuxt service.
- Visual/SEO fixture tests: **17/17 PASS**.
- P2-A SEO read-only audit đã ghi nhận tại
  `docs/14-nuxt-migration/SEO-PARITY-AUDIT-20261028.md`: canonical/noindex
  preview PASS 26/26, nhưng `/product/` root alias, JSON-LD parity và một số OG
  fields phía PHP legacy còn FAIL/NEEDS VERIFICATION; chưa sửa public owner.
- Nuxt typecheck: exit 0; `vue-router@5.4.0` đã xử lý cảnh báo `vue-router/volar/sfc-route-blocks`.
- Dependency audit: **0 vulnerability** sau khi pin `node-forge@1.4.2` và ép
  `fontless` dùng `esbuild@0.28.2` qua `frontend/package.json` overrides;
  không chạy `npm audit fix` tự động.
- Nuxt production build: **PASS**.
- SSR smoke: **PASS** — base + 4 hub/media routes 200/noindex, SSR HTML và route
  không tồn tại trả 404.
- Reproducibility: `.nvmrc` pin Node 24.20.0 và
  `frontend/package-lock.json` lockfile v3 được track;
  `npm ci --dry-run --ignore-scripts --no-audit --no-fund` PASS trên Node
  24.20.0/npm 11.19.0. Workflow `.github/workflows/nuxt-integration.yml`
  đã được thêm để chạy các gate Nuxt trên push/PR; chưa chạy GitHub-hosted
  runner trong phiên này.

## Chưa làm trong checkpoint này

- Không chạy PHP 8.1/container integration test cho API gallery/video.
- Không chạy full browser PHP-vs-Nuxt parity trên hai origin thật.
- Không ký SEO pre-cutover gate cho tới khi owner chốt các gap trong P2-A audit.
- Không bật feature flag, Caddy route, production service hoặc deploy.
- Không push remote.

## Rollback

Rollback source integration về trước đợt hợp nhất tại `f7d739b`; các commit sau
đó đã bổ sung parity media, policy/search, router compatibility và contract
coverage, vì vậy chỉ revert `92d6fd4` khi đã rà các phụ thuộc mới. Không có
production/container state nào bị thay đổi trong checkpoint này.
