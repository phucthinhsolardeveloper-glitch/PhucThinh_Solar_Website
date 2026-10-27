# Nuxt integration checkpoint — 2026-10-28

## Trạng thái

- Nhánh tích hợp: `feature/pts-nuxt-integration-20261027`
- Commit hiện tại: `ab6c3f4`
- `master`, production, Docker/container và public routing không bị thay đổi.
- Nhánh đang ahead remote 21 commit; chưa push.

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
- Nuxt typecheck: exit 0; `vue-router@5.4.0` đã xử lý cảnh báo `vue-router/volar/sfc-route-blocks`.
- Dependency audit còn 2 transitive advisories (1 high, 1 low); chưa chạy `npm audit fix` tự động.
- Nuxt production build: **PASS**.
- SSR smoke: **PASS** — 200, SSR HTML, `noindex`, route không tồn tại trả 404.
- Reproducibility: `frontend/package-lock.json` lockfile v3 được track;
  `npm ci --dry-run --ignore-scripts --no-audit --no-fund` PASS trên Node
  24.20.0/npm 11.19.0. Chưa có workflow CI hoặc runtime pin trong repository,
  nên đây là gate thủ công cho tới khi owner bổ sung pipeline.

## Chưa làm trong checkpoint này

- Không chạy PHP 8.1/container integration test cho API gallery/video.
- Không chạy full browser PHP-vs-Nuxt parity trên hai origin thật.
- Không bật feature flag, Caddy route, production service hoặc deploy.
- Không push remote.

## Rollback

Rollback source integration về trước đợt hợp nhất tại `f7d739b`; các commit sau
đó đã bổ sung parity media, policy/search, router compatibility và contract
coverage, vì vậy chỉ revert `92d6fd4` khi đã rà các phụ thuộc mới. Không có
production/container state nào bị thay đổi trong checkpoint này.
