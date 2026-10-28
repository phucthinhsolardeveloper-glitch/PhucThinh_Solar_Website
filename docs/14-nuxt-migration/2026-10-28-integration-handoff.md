# Nuxt integration checkpoint — 2026-10-28

## Trạng thái

- Nhánh tích hợp hiện tại: `stage4-batch-b-cart-checkout-20261028`
  (worktree `/home/admin01/src/pts-nuxt-batch-b-20261028`).
- HEAD đã kiểm tra: `a740593` (`feat(nuxt): add flag-gated public routes for cart and order-success`), trên nền
  `069d8a2` (catalog/PDP/brand/search) và `7e5ceaf` (ghi nhận false positive
  từ container PHP local cũ). Đây là checkpoint source local, không phải
  checkpoint production.
- `master`, production, Docker/container và public routing không bị thay đổi.
- Nhánh Batch B chưa có upstream tracking branch; không dùng số ahead của
  `feature/pts-nuxt-integration-20261027` làm số liệu cho nhánh này. Worktree
  có hai thay đổi tài liệu của Lane 4 chưa commit; các thay đổi ứng dụng tại
  HEAD là commit `a740593`.

## Đã hợp nhất

- Các nhánh QA Nuxt về cutover, shell và visual.
- API query validation cho policy/search (`f7d739b`).
- Gallery/video read-only PHP API, DTO, Nitro adapters và preview pages (`43fd49e`, cherry-picked as `4d14c20`, promoted as part of `92d6fd4`).
- Cache policy private/no-store cho các media adapters.
- Strict TypeScript narrowing cho gallery lightbox.
- Contract tests cho gallery/video (`frontend/tests/gallery-video-contract.test.mjs`).
- Batch B thêm public Nuxt route `/gio-hang/` và `/cam-on/`, đều flag-gated
  mặc định OFF; cart/order success components được tách từ preview và giữ
  PHP làm backend session/nghiệp vụ. `/cam-on/?token=...` forward token khi
  flag OFF redirect về PHP.
- Không có public Nuxt route PHP tương đương `/dat-hang/`; checkout tiếp tục
  là bước trong luồng giỏ hàng PHP. `migration-checkout-preview.vue` vẫn là
  preview nội bộ và không được coi là route public.

## Bằng chứng xác minh

- Frontend contract tests: **117/117 PASS**.
- Cutover static tests: **8/8 PASS**; preflight vẫn báo `BLOCKED` đúng thiết kế vì feature flag OFF và chưa có production Nuxt service.
- Visual/SEO fixture tests: **17/17 PASS**.
- P2-A SEO read-only audit đã ghi nhận tại
  `docs/14-nuxt-migration/SEO-PARITY-AUDIT-20261028.md`: canonical/noindex
  preview PASS 26/26, nhưng `/product/` root alias, JSON-LD parity và một số OG
  fields phía PHP legacy còn FAIL/NEEDS VERIFICATION; chưa sửa public owner.
- FE/BE gap audit của Claude đã hợp nhất tại
  `docs/14-nuxt-migration/FE-BE-GAP-AUDIT-20261028.md`; không có blocker mới,
  chỉ ghi nhận origin-debug 502 và thứ tự `NULL` khi sort giá.
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
- Batch B riêng: `npm test` **130/130 PASS**, `npm run typecheck` exit 0,
  `npm run build` PASS; smoke trên Nuxt build local xác nhận flag OFF redirect
  `/gio-hang/` và `/cam-on/` về PHP, giữ nguyên `token` khi có. Chưa xác minh
  flag ON với session/cart/order thật qua PHP.

## Chưa làm trong checkpoint này

- Không chạy PHP 8.1/container integration test cho API gallery/video.
- Không chạy full browser PHP-vs-Nuxt parity trên hai origin thật.
- Không ký SEO pre-cutover gate cho tới khi owner chốt các gap trong P2-A audit.
- Không bật feature flag, Caddy route, production service hoặc deploy.
- Không push remote.
- Không suy ra deployment từ việc route có trong source: flag, Caddy mapping
  và production Nuxt service vẫn chưa bật.

## Rollback

Rollback riêng Batch B về source ngay trước commit `a740593` (`7e5ceaf`) chỉ
sau khi xác nhận không có commit phụ thuộc phía sau. Các route vẫn flag OFF;
không có production/container state nào bị thay đổi trong checkpoint này.
