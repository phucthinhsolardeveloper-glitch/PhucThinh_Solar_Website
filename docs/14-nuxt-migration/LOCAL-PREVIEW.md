# Phúc Thịnh Solar — local Nuxt preview (2026-10-27)

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
- Local PHP reference (original master): http://localhost:8080/giai-phap/
- Local PHP migration code/API: http://localhost:8088/giai-phap/

Nuxt is intentionally in **preview mode only**. Internal links from the preview
are routed to corresponding Nuxt preview pages where available, but canonical
public URLs, PHP resolver/redirects, account, Admin, media and production
commerce ownership are not cut over. Preview routes have noindex. Do not route
production traffic to Nuxt.

## Start, stop and rebuild (WSL Ubuntu)
```bash
cd /home/admin01/src/pts-nuxt-integration-20261027
./scripts/nuxt-local-preview.sh status
./scripts/nuxt-local-preview.sh restart
./scripts/nuxt-local-preview.sh stop
```

- The script uses **existing local** MariaDB and local uploads from the website's Docker Compose network; no production credentials or data are used.
- The local PHP preview container is isolated on 127.0.0.1:8088; current PHP QA remains on 8080. Its code is mounted read-only from this migration worktree. Media is mounted read-only and caches/logs use temporary writable filesystems.
- The ignored local PHP configuration is copied from the existing **local** PHP development repo into this worktree when missing; never add it to Git.
- SSR Nuxt preview runs on 127.0.0.1:3100 with private upstream NUXT_PHP_ORIGIN=http://127.0.0.1:8088.
- Static preview media proxy is disabled by default, enabled by the local script only, and restricted to allowlisted image file paths/types (no PHP/JS/html execution).
- No production server, Git master, or existing Zalo CRM containers are modified by the script.
- Build output, images and log are untracked in frontend/.migration-artifacts/.

## Latest local checks
- Checkpoint frontend hiện tại: `5e97ab8` trên `feature/pts-nuxt-integration-20261027`;
  nhánh đang ahead remote 26 commit và chưa push.
- `npm test` 117/117; typecheck exit 0, không còn warning vue-router/Volar;
  production build PASS; SSR smoke base + hub/media routes PASS.
- `frontend/package-lock.json` là lockfile v3 được track; `npm ci --dry-run
  --ignore-scripts --no-audit --no-fund` đã PASS trên Node 24.20.0/npm 11.19.0.
- Repository hiện chưa có workflow CI (GitHub/GitLab/Jenkins); runtime Node đã
  được pin bằng `.nvmrc` 24.20.0. Các gate vẫn phải chạy thủ công/serial cho
  tới khi owner bổ sung pipeline.
- Catalog preview đã có filter thật, sort, brand detail preview và panel danh mục
  + bộ lọc dùng chung; preview giữ `noindex`.
- PHP 8.1/Docker contract và full browser visual manifest chưa chạy lại trong
  audit này vì Docker daemon của executor không khả dụng.
- Contact/dealer/home form UI chạy trên Nuxt và gửi qua PHP adapter an toàn;
  cart/checkout/order-success dùng PHP session/calculation/transaction.
- PDP preview đã có pricing/quote/cart, gallery lightbox/zoom, content/FAQ,
  documents, video, warranty/policy, related, reviews/consultation và
  Product/Breadcrumb/FAQ JSON-LD.
- Images/QA output: frontend/.migration-artifacts/local-preview-qa/{mobile-390.png,desktop-1440.png,manifest.json}
- Các gate trước cutover còn lại: production route cutover/hybrid routing, SEO
  canonical/OG/sitemap/robots/redirect parity, catalog filter/sort/stock parity,
  public brand/gallery/video/account canonical parity và final visual/browser QA.
- Vue Router/Volar warning đã được xử lý bằng `vue-router@5.4.0`; dependency
  audit hiện **0 vulnerability** sau khi pin `node-forge@1.4.2` và `esbuild@0.28.2`
  cho nhánh `fontless` bằng overrides.

## Rollback / isolation
To stop **only** this preview, run ./scripts/nuxt-local-preview.sh stop. Do not stop the existing website PHP QA container or Zalo CRM. The old website keeps running from master regardless of the Nuxt preview state.
