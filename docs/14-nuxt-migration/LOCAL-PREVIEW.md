# Phúc Thịnh Solar — local Nuxt development and preview

## Runtime status observed — 2026-10-31

Nuxt HMR is the active frontend at `http://localhost:3100`. The PHP-rendered
website on `:8080` is stopped and its stopped container was removed. The
read-only PHP API/media bridge is named `pts-nuxt-api-bridge` on `:8088`; it
allows `/api/*` and `/upload/*` while denying direct legacy page routes. Open
`:3100` for the website; do not use `:8088` as a frontend preview. MariaDB,
images and Docker volumes remain intact. The stale Nuxt snapshot and Caddy
rehearsal containers were removed earlier; their images and all Docker volumes
were kept. The interrupted capture and cleanup checkpoint are in
[`BRANCH-INTEGRATION-AND-LOCAL-QA-20261031.md`](BRANCH-INTEGRATION-AND-LOCAL-QA-20261031.md).

## Daily frontend development (HMR)

Nuxt daily development needs the local DB and PHP API/media bridge, not the
legacy PHP website renderer:

```bash
docker compose -f docker-compose.local.yml up -d db
docker compose -f docker-compose.nuxt-preview.yml up -d api-bridge
```

Then run Nuxt in a separate terminal:

```bash
cd /home/admin01/src/Web_PTS_PHP/frontend
set -a
. ../.env                 # cấu hình local bị Git ignore, gồm API/media bridge :8088
set +a
npm ci                 # lần đầu hoặc sau khi package-lock.json thay đổi
npm run dev
```

Mở **http://localhost:3100/** (trang liên hệ: `/lien-he/`). Khi Nuxt sẵn sàng,
terminal cũng in URL `Local`. Vite HMR cập nhật Vue/TypeScript/CSS ngay khi lưu
file; đây là luồng mặc định để code và xem giao diện, không cần build.

PHP API được gọi từ Nuxt qua `127.0.0.1:8088`. Container này chỉ cho phép
`/api/*` và `/upload/*`; truy cập trực tiếp các trang PHP bị chặn. Nếu dữ liệu/API
không tải được, kiểm tra `pts-nuxt-api-bridge` và MariaDB; nếu giao diện không cập nhật, xem
terminal Nuxt để biết watcher/HMR có báo lỗi hay không. Ảnh CMS và asset PHP
được proxy trong dev qua allowlist đường dẫn ảnh; production vẫn tắt proxy theo
mặc định.

Các cờ `NUXT_PUBLIC_*_PUBLIC_ROUTE_ENABLED` chỉ mở URL public trong preview để
owner xem giao diện. Mọi cờ `NUXT_PUBLIC_*_PUBLIC_ROUTE_INDEXABLE` trong `.env`
local phải là `false`; chúng độc lập với cờ hiển thị route và giữ meta robots ở
`noindex` trong lúc PHP vẫn là public canonical owner. Nuxt đọc `.env` khi khởi
động, vì vậy thay đổi file này cần restart tiến trình; sửa Vue/TypeScript/CSS
vẫn dùng HMR.

`npm run build` và `scripts/nuxt-local-preview.sh restart` chỉ dành cho snapshot
SSR để kiểm tra bản đã build. Script snapshot dùng API bridge cổng 8088 và cùng
cổng Nuxt 3100, nên dừng tiến trình `npm run dev` bằng Ctrl+C trước khi chạy
script. Khi quay lại code, khởi động lại `npm run dev` và dùng link trên.

## Migration preview routes

## Open on Windows
- Unified header + real CMS Solutions + footer: http://localhost:3100/migration-site-preview
- Header/menu/footer inspection: http://localhost:3100/migration-shell-preview
- Solutions-only preview: http://localhost:3100/migration-solutions-preview
- Home preview: http://localhost:3100/migration-home-preview
- Product catalog preview: http://localhost:3100/migration-catalog-preview
- Product detail preview (fixture record): http://localhost:3100/migration-product-preview?slug=den-ufo-nang-luong-mat-troi-oriana-ufo-1000-1000w
- Search preview: http://localhost:3100/migration-search-preview
- Cart preview: http://localhost:3100/migration-cart-preview
- Checkout preview: http://localhost:3100/migration-checkout-preview
- Order success preview: http://localhost:3100/migration-order-success
- Projects preview: http://localhost:3100/migration-projects-preview
- Project detail preview: http://localhost:3100/migration-project-detail-preview?slug=du-an-dien-nlmt-ho-anh-hoa-thanh-an
- News preview: http://localhost:3100/migration-news-preview
- News detail preview: http://localhost:3100/migration-news-detail-preview?slug=8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio
- Solution detail preview: http://localhost:3100/migration-solution-detail-preview?slug=dien-mat-troi-ho-gia-dinh
- About preview: http://localhost:3100/migration-about-preview
- Policy/support detail preview: http://localhost:3100/migration-policy-preview?slug=<cms-slug>
- Contact preview (Nuxt UI, PHP-secured form adapter): http://localhost:3100/migration-contact-preview
- Dealer preview (Nuxt UI, PHP-secured form adapter): http://localhost:3100/migration-dealer-preview
- Central migration preview hub: http://localhost:3100/migration-hub-preview
- Nuxt frontend: http://localhost:3100/
- Legacy PHP renderer (`:8080`, stopped): not part of the daily Nuxt workflow
- PHP API/media bridge (`:8088`, local-only): backend dependency, not the frontend to review

Nuxt is intentionally in **preview mode only**. Internal links from the preview
are routed to corresponding Nuxt preview pages where available, but canonical
public URLs, PHP resolver/redirects, account, Admin, media and production
commerce ownership are not cut over. Preview routes have noindex. Do not route
production traffic to Nuxt.

## Optional built snapshot controls (WSL Ubuntu)
```bash
cd /home/admin01/src/Web_PTS_PHP
./scripts/nuxt-local-preview.sh status
./scripts/nuxt-local-preview.sh restart
./scripts/nuxt-local-preview.sh stop
```

- The script uses **existing local** MariaDB and local uploads from the website's Docker Compose network; no production credentials or data are used.
- The read-only PHP API/media bridge container `pts-nuxt-api-bridge` on `127.0.0.1:8088` stays active for Nuxt data. Apache allows only `/api/*` and `/upload/*`; direct legacy PHP pages are denied. Its source and media mounts are read-only.
- The ignored local PHP configuration is copied from the existing **local** PHP development repo into this worktree when missing; never add it to Git.
- SSR Nuxt preview runs on 127.0.0.1:3100 with private upstream NUXT_PHP_ORIGIN=http://127.0.0.1:8088.
- Static preview media proxy is disabled by default, enabled by the local script only, and restricted to allowlisted image file paths/types (no PHP/JS/html execution).
- No production server, Git master, or existing Zalo CRM containers are modified by the script.
- Build output, images and log are untracked in frontend/.migration-artifacts/.

## Archived checkpoint details (2026-10-27)

The following branch, test, and audit notes describe the migration checkout at
that date; they are historical evidence and do not describe the current `master`
HEAD or the daily HMR workflow above.
- Checkpoint frontend hiện tại: `aee4538` trên `feature/pts-nuxt-integration-20261027`;
  nhánh chưa push remote; kiểm tra số commit ahead bằng `git rev-list`.
- `npm test` 117/117; typecheck exit 0, không còn warning vue-router/Volar;
  production build PASS; SSR smoke base + hub/media routes PASS.
- `frontend/package-lock.json` là lockfile v3 được track; `npm ci --dry-run
  --ignore-scripts --no-audit --no-fund` đã PASS trên Node 24.20.0/npm 11.19.0.
- Workflow GitHub Actions `.github/workflows/nuxt-integration.yml` đã khai báo
  install, audit, contract test, typecheck, build, SSR smoke, cutover static và
  visual/SEO harness; runtime Node được pin bằng `.nvmrc` 24.20.0. Runner thật
  chưa được kích hoạt trong phiên này nên vẫn giữ bằng chứng local.
- Catalog preview đã có filter thật, sort, brand detail preview và panel danh mục
  + bộ lọc dùng chung; preview giữ `noindex`.
- PHP 8.1/Docker contract và full browser visual manifest chưa chạy lại trong
  audit này vì Docker daemon của executor không khả dụng.
- Contact/dealer/home form UI chạy trên Nuxt và gửi qua PHP adapter an toàn.
  Cart đọc session và mutation giỏ hàng dùng PHP adapter; checkout preview chỉ
  đọc tóm tắt giỏ hàng, không nhận thông tin đặt hàng, còn `POST
  /_migration/checkout` trả 410 và không tạo đơn. Giao dịch thật tiếp tục qua
  PHP `/gio-hang/`; order-success chỉ đọc kết quả theo token đã có.
- PDP preview đã có pricing/quote/cart, gallery lightbox/zoom, content/FAQ,
  documents, video, warranty/policy, related, reviews/consultation và
  Product/Breadcrumb/FAQ JSON-LD.
- Images/QA output: frontend/.migration-artifacts/local-preview-qa/{mobile-390.png,desktop-1440.png,manifest.json}
- Các gate trước cutover còn lại: production route cutover/hybrid routing, SEO
  canonical/OG/sitemap/robots/redirect parity, catalog filter/sort/stock parity,
  public brand/gallery/video/account canonical parity và final visual/browser QA.
- P2-A audit read-only mới nhất nằm tại
  `docs/14-nuxt-migration/SEO-PARITY-AUDIT-20261028.md`; không được dùng preview
  noindex để kết luận SEO parity khi PHP legacy còn gap JSON-LD/OG và alias
  `/product/` root cần owner xác nhận.
- FE/BE audit minor của Claude đã hợp nhất tại
  `docs/14-nuxt-migration/FE-BE-GAP-AUDIT-20261028.md`; không có lỗi nghiêm trọng
  mới trong catalog, PDP hoặc lead-form adapter.
- Vue Router/Volar warning đã được xử lý bằng `vue-router@5.4.0`; dependency
  audit hiện **0 vulnerability** sau khi pin `node-forge@1.4.2` và `esbuild@0.28.2`
  cho nhánh `fontless` bằng overrides.

## Rollback / isolation
Daily Nuxt HMR requires the `:8088` API/media bridge and local MariaDB. The
legacy PHP website on `:8080` can remain stopped. To return to the legacy PHP
website for a separate task, start only its `web` service with
`docker compose -f docker-compose.local.yml up -d web`; it does not need to be
running while developing Nuxt. The optional `scripts/nuxt-local-preview.sh`
snapshot flow shares port `:3100`; stop `npm run dev` before using it.
