# Nhật ký triển khai production

Mỗi lần thay đổi production phải thêm một mục mới, không ghi credential, token,
PII hoặc nội dung bí mật. Nếu deploy chỉ thay docs không rebuild image thì ghi rõ
`docs-only` và lý do không cần runtime deploy.

## 2026-11-13 — release f62158bb: SEO/Merchant, mobile, tốc độ, GA4

- Runtime source: commit `f62158bb9b68bd649ee7306adccc23f4cbe5f764` đã push `origin/master`. Image backend
  `sha256:0957e9ed2651e0b97e4db56510f95af0772a9cf13dce26da0e17082c863cb6e8`, Nuxt
  `sha256:ea1501bb9f1e3b416006fec1e26344987bc3b8264d6b7e19e8765c2ecd496810`. Gói
  `/srv/pts-laravel13/releases/f62158bb/pts-release-f62158bb-images.tar.gz` (~408 MiB), SHA-256 khớp máy build và VPS,
  ID image trên VPS khớp manifest.
- Phạm vi: Offer `availability` + `seller`, BreadcrumbList theo danh mục, `og:type=product`, 30 trang thiếu meta
  description có mô tả tự sinh, title chỉ thêm hậu tố thương hiệu khi tổng ≤ 65 ký tự; chuyển trang mobile cuộn sau khi
  trang mới sẵn sàng, header không trượt, khối đầu danh mục giữ chiều cao; H1 trang chủ hiện ngay khung đầu (LCP);
  chuyển hướng 21 link cũ còn 404 + `/tru-den` một bước; lối tắt giải pháp bỏ danh mục không có sản phẩm; ô Admin
  "Mã đo Google Analytics 4" (trống mặc định, chưa bật).
- Migration: một cái (`2026_11_13_000100`, thêm 21 dòng `redirects`, chạy lại an toàn). Không đổi bảng nào khác.
- Backup trước thay đổi: `/srv/pts-laravel13/backups/20261113T025609Z-full` (6/6 checksum đạt). Rollback: image cũ
  `83a74afa-candidate` còn trên VPS, env và ID cũ ở `/srv/pts-laravel13/releases/rollback-f62158bb/`.
- Candidate cô lập (DB phục hồi từ `20261112T231500Z-db` của production): `verify-candidate.sh` mọi API đồng nhất trừ
  đúng các chỗ chủ ý (seo.title 253 sản phẩm, 5 slug cũ chuyển hướng, `ga4Id`, equipment bỏ Trụ đèn); backend 402
  pass/1 skip, PHPStan 0; frontend 515 pass, typecheck 0. Baseline 5 API đã cập nhật vào Git ở commit tiếp theo.
- Trên VPS: chỉ thay backend, Nuxt, worker, scheduler của stack `pts-laravel13`; DB, Caddy biên, ZCRM, training giữ
  nguyên. HTTPS 14 route chính trả 200, 5 link cũ trả 301 đúng đích, feed 170 sản phẩm, JSON-LD có InStock và 5 bước
  breadcrumb. Log 6 phút sau: backend/Nuxt/worker/scheduler 0 lỗi; proxy 2 dòng lỗi DNS đúng lúc thay container.
- Đo điện thoại giả lập (4x CPU, ~4G) trang chủ: LCP 3,7 s → 1,1 s.
- Rủi ro còn lại: chưa nhập mã GA4 (cần chủ website xác nhận mã); backup chỉ nằm trên VPS; JavaScript khoảng 430 KB
  (TBT cao trên máy yếu).

## 2026-11-12 — release 83a74afa: nội dung biên tập, danh mục, Admin và lịch bài đăng

- Runtime source: commit `83a74afa998fa5cfbc6e1945044cf7466fee99f8` đã push lên `origin/master`.
  Image backend `sha256:bfc92a71065a4f30a73c7e591a6c0955829eefefc0925280986f02a52b72c6e2`,
  Nuxt `sha256:03b0594d0c65613127f9fa972f5fd251e7d8ea94e6008212917ddeb9ec3217a3`.
  Gói `/srv/pts-laravel13/releases/83a74afa/pts-release-83a74afa-images.tar.gz`
  (~408 MiB) đã kiểm SHA-256 và đối chiếu image ID giữa máy build/VPS.
- Backup full trước migration/import: `/srv/pts-laravel13/backups/20261112T094236Z-full`,
  checksum 6/6 file đạt. Giữ image release `d2c529e0` và bản env/image ID trước đổi ở
  `/srv/pts-laravel13/releases/rollback-83a74afa/`. Không phục hồi DB theo quán tính.
- Candidate cô lập phục hồi DB production (399 sản phẩm, 29 bài cũ), chạy thành công 6 migration
  `002000`, `2026_11_11_000100`, `000200`, `000300`, `2026_11_12_000100`, `000300`.
  Backend Laravel **399 pass / 1 skip**, 2.785 assertions; Pint 455 file, PHPStan 0 lỗi;
  frontend 513/513 test, Nuxt typecheck và production build đạt.
  `verify-candidate.sh` hoàn tất sau khi cập nhật 7 snapshot có thay đổi đã kiểm duyệt;
  site-audit 0 lỗi trên 20 route × 4 viewport; shop-flow đạt, không tạo đơn.
- Trên VPS: đã áp đủ 6 migration và chỉ thay backend, Nuxt, worker, scheduler thuộc stack
  `pts-laravel13`. MariaDB volume, Caddy biên, ZCRM và training được giữ nguyên.
  Kiểm HTTPS `/`, `/tin-tuc/`, `/san-pham/`, `/admin/login`, `/api/v1/site`,
  sitemap, feed và ảnh bài viết trả 200; Admin plan/calendar trả 302 về đăng nhập đúng phân quyền.
  Các service mới healthy; scan log ngắn sau deploy: 0 fatal/uncaught/SQLSTATE/connection-refused.
- Nội dung versioned: `release-editorial-content.sh --apply` hoàn tất sau backup và
  diễn tập clone DB. Ba bài hiện hữu được cập nhật có guard, thay 5 bìa và bỏ 2 banner lỗi thời;
  tạo thêm 18 bản thảo ẩn. Tổng sau release: **50 bài (28 đang hiển thị)**, **399 sản phẩm**,
  **180 mục kế hoạch từ 13/11/2026 đến 11/01/2027**, **21 liên kết bài**, **0 bìa thiếu**.
  Lịch biên tập không tự công khai; việc duyệt bài, gán sản phẩm và chọn ngày đăng vẫn thuộc Admin.
- Snapshot API đã đạt đầy đủ **trước khi import nội dung**. Sau khi thay dữ liệu bài viết có chủ đích,
  snapshot `news` và `news-detail` cũ của image đang chạy sẽ lệch về nội dung/bìa.
  Baseline sau import được cập nhật vào Git trong một commit dữ liệu/tài liệu kế tiếp; nó có hiệu lực
  trên runtime khi xây image release tiếp theo, **không tự động sửa image đã deploy**.
- Rủi ro còn lại: backup full vẫn chỉ ở VPS, chưa có bản sao off-host riêng;
  chưa chạy audit trình duyệt có đăng nhập cho mọi màn Admin trên chính production.
  Không xóa ảnh/media cũ hoặc backup rollback.

## 2026-11-10 — category tree + Admin updates (d2c529e0)

- Commit/image: `d2c529e03111cc3e7fceeef07f1e384951b5295c`; backend image ID
  `sha256:ec8f378c5b776ed5dc9843d99522b2ae61c2e47173f3f7d1bd33ca898e811c96`,
  Nuxt image ID `sha256:84f213156edf430e5cb69b8dbc0381154d576f486be0041b3e316059e12d4343`.
  Exact images were built and verified on candidate, transferred as
  `/srv/pts-laravel13/releases/pts-release-d2c529e0-images.tar.gz` (SHA-256
  `21408e94a7e6062ec822eda0e647ab8a66a9ff20d064be8444b6a9d9bf9cbbf0`), then
  loaded on the VPS. Commit `d2c529e0` is pushed to `origin/master`.
- Backup before migration: `/srv/pts-laravel13/backups/20261110T085016Z-full`;
  all 6 files passed `SHA256SUMS`. Rollback environment copy and previous image
  IDs are in `/srv/pts-laravel13/releases/rollback-d2c529e0/`.
- Applied migrations `001700`, `001800`, `001900` (batch 2). Total products
  stayed at 399, orphan products 0, and no garden-light product remains in its
  old parent. Counts are 53 gate lights, 9 wall lights, 5 garden stake lights,
  and 1 lawn light. Two old category URLs now return 301 to their new paths.
- Candidate rehearsal restored the pre-deploy production DB backup into an
  isolated DB, applied all three migrations, matched 1,754/1,754 API snapshot
  cases after refreshing only the seven affected category snapshots, and passed
  `verify-candidate.sh`. `shop-flow` passed without submitting an order.
- Gates: Laravel 293 passed/1 skipped/1,842 assertions; Pint 392 files; PHPStan
  0 errors; Nuxt 500/500 tests, typecheck and production build passed. Admin
  browser audit opened 94 desktop/mobile/deep screens with no server or JS
  errors; it reported a 24 px table overflow on desktop Products, while the row
  actions remained visible. This small Admin layout issue is a follow-up.
- Production after deploy: backend/Nuxt healthy; worker and scheduler running;
  recent Laravel error count 0. Home, Admin login, site API, four new category
  pages and four representative product pages returned 200; old garden and
  clamp paths returned 301; sitemap lists all four garden-light child paths.
  Caddy, MariaDB, training and ZCRM were not replaced or reconfigured.
- Rollback point: previous backend/Nuxt image tags are `f5aac548-candidate`;
  restore the saved `stack.env.before` and recreate only backend, Nuxt, worker
  and scheduler. The data-only category reshuffle is forward-compatible; do not
  restore the DB backup blindly after new orders or Admin writes.
- Remaining operational risk: this backup is on the VPS only; a separate
  off-host copy and a full Caddy route/DB restore rollback rehearsal remain
  outstanding. The site is live on the new release.

## 2026-11-10 — dọn môi trường sau cutover, backup chuyển sang stack mới

- Chủ website duyệt từng mục trước khi xoá. Bản lưu của site PHP cũ đã tải về
  máy chủ sở hữu (`D:\ProjectBackup\phucthinhsolar-php-pre-laravel-20261110`,
  1,1 GB, `sha256sum -c` khớp 5/5 file) gồm dump DB 10:44, dump DB cuối lúc
  11:34, `upload.tar.gz` (1.470 file), cấu hình stack và Dockerfile cũ.
- Đã xoá: môi trường test `phucthinhsolar-staging` (4 container, 3 volume,
  `/srv/phucthinhsolar-staging`, cổng nội bộ 18080/18443); 3 container PHP cũ
  `web`/`nuxt`/`db` và volume `phucthinhsolar_db_data`; dữ liệu ứng dụng cũ
  (`shared`, `releases`, `incoming`); 53/57 thư mục backup cũ (giữ 4 bản gần
  nhất, trong đó có bản ngay trước cutover); 8 tag ảnh PHP cũ; 13,36 GB build
  cache; 4 volume mồ côi (datadir MariaDB rỗng).
- Giữ lại có chủ đích: `phucthinhsolar-proxy-1` (Caddy biên, cổng 80/443 và
  chứng chỉ TLS của cả 3 tên miền) — `docker-compose.yml` của project
  `phucthinhsolar` nay chỉ còn service `proxy`, bản đầy đủ ở
  `docker-compose.yml.pre-laravel-20261110`. Giữ ảnh `2d151211971a` (bản PHP
  chạy cuối) và `9ad585a6ece0` (bản trước đó), thư mục `secrets/` và `state/`.
- Backup: hai timer cũ (`phucthinhsolar-backup`, `phucthinhsolar-db-backup`) đã
  tắt và gỡ unit vì DB chúng dump không còn. Thay bằng
  `pts-laravel13-backup.timer` (full 02:40 hằng ngày) và
  `pts-laravel13-db-backup.timer` (DB mỗi 6 giờ, phút 15), chạy
  `deploy/laravel13/scripts/backup.sh`, giữ 7 ngày. Đã chạy thử một bản full:
  `/srv/pts-laravel13/backups/20261110T043603Z-full`, checksum tự kiểm khớp.
- **Backup vẫn chỉ nằm trên chính VPS** — chưa có bản sao ngoài máy chủ. Script
  in cảnh báo này mỗi lần chạy.
- Đĩa: 95 GB → 54 GB đã dùng (83% → 47%). Cổng còn mở: 22, 80, 443 ra ngoài;
  18085 (kiểm tra nội bộ stack Laravel), 5544 và 6381 (ZCRM) chỉ trên 127.0.0.1.
- Kiểm tra sau mỗi bước xoá và lần cuối: 469/469 URL sitemap 200, `/admin/login`,
  feed, API 200, `zcrm` và `training` 200, `www` 301.

## 2026-11-10 — chuyển website sang Laravel 13 + Nuxt (f5aac548)

- Commit/image: `f5aac548` / `pts-laravel13-backend:f5aac548-candidate`,
  `pts-laravel13-nuxt:f5aac548-candidate`. Stack mới `pts-laravel13` (6 container).
  Stack PHP cũ `phucthinhsolar` vẫn chạy nguyên để rollback.
- Phạm vi: toàn bộ website. Caddy biên đưa `/api/v1/*`, `/admin*`, `/livewire*`,
  `/upload/*`, `/thumbs/*`, `/sitemap.xml`, `/google-shopping.xml` sang Laravel;
  mọi trang còn lại sang Nuxt.
- Backup trước cutover: `/srv/phucthinhsolar/backups/20261110T034425Z-full`.
  Caddyfile cũ giữ ở `/srv/phucthinhsolar/Caddyfile.pre-laravel-20261110T034425Z`.
- Dữ liệu nhập lại từ bản PHP: 399 sản phẩm, 29 bài, 42 banner, 469 mục SEO.
  Sau đó `migrate --force`, `pts:normalize-specs --apply` (còn 0 sản phẩm cần
  chuẩn hóa), `cache:clear`, `settings.gtm_id = GTM-57TJSRVM`.
- Sự cố 1 — site 503 từ 11:07 đến 11:12: `/srv/phucthinhsolar/Caddyfile` bị thay
  bằng file khác inode, nên bind mount trong container vẫn giữ file cũ và
  `caddy reload` chỉ nạp lại chính file cũ đó. Sửa bằng
  `docker restart phucthinhsolar-proxy-1` để Docker gắn lại đúng file trên host.
- Sự cố 2 — mail form liên hệ không gửi được: container `worker` chỉ nằm trên
  mạng `data` (`internal: true`) nên không phân giải được tên máy chủ SMTP
  (`getaddrinfo ... Temporary failure in name resolution`). Backend không lộ lỗi
  này vì nó nằm cả trên `edge` và chỉ xếp mail vào hàng đợi. Sửa ở `e126fe41`:
  worker thêm mạng `egress`, tạo lại container; mail đang chờ gửi xong lúc
  11:21:29, `jobs=0 failed=0`.
- Kiểm tra sau deploy: 469/469 URL trong sitemap trả 200; `/admin/login` 200 và
  tài nguyên Filament 200; `POST livewire-*/update` trả 419 (đúng, thiếu CSRF);
  `/google-shopping.xml` 170 sản phẩm; ảnh `/thumbs/*` 200; `www` 301 về
  non-www; `zcrm` và `training` 200; trang chủ có `GTM-57TJSRVM`; chứng chỉ TLS
  hạn 16/12/2026; log Laravel không có ERROR; cổng 18085 chỉ mở trên 127.0.0.1.
- Một lead kiểm tra đã gửi qua form thật để xác minh đường mail — trái quy tắc
  "không gửi lead thật khi test" trong `docs/16-laravel-migration/HANDOFF.md`.
  Bản ghi lead và outbox đã xóa ngay sau khi xác minh, nhưng hộp thư nhận thông
  báo của Admin có một email tiêu đề "Kiểm tra sau cutover 10/11".
- Rollback: `cp /srv/phucthinhsolar/Caddyfile.pre-laravel-20261110T034425Z
  /srv/phucthinhsolar/Caddyfile && docker restart phucthinhsolar-proxy-1`. Stack
  PHP cũ và DB cũ còn nguyên; DB mới tách riêng nên không cần rollback dữ liệu.
- Rủi ro còn lại: công cụ ước tính đang tắt (trang 404 đúng thiết kế, chủ website
  bật trong Admin khi duyệt đơn giá); chưa có Consent Mode v2; đĩa VPS dùng 83%.

## 2026-11-07 — bản vá giao diện điện thoại (2d151211)

- Commit/image: `2d151211` / `phucthinhsolar-web:2d151211971a`, `phucthinhsolar-nuxt:2d151211971a`. AUTO chọn FAST.
- Phạm vi: thẻ "Đã thêm vào giỏ" mở phía trên dock, "Xem thêm" không đè thanh mua,
  thẻ Tầm nhìn – Sứ mệnh không bị ẩn, thẻ trang chủ không giãn chữ.
- Backup DB trước deploy; HTTP smoke, Playwright production 18/18, log scan PASS.
  Không có 503.

## 2026-11-07 — logo đối tác, bộ lọc chọn nhiều, hotline kỹ thuật (9ad585a6)

- Commit/image: `9ad585a6` / `phucthinhsolar-web:9ad585a6ece0`, `phucthinhsolar-nuxt:9ad585a6ece0`. SAFE.
- Phạm vi: 20 logo đối tác (Giới thiệu và đầu trang Sản phẩm), drawer bộ lọc
  giữ mở khi chọn nhiều, banner giải pháp trên điện thoại, migration 077 và 078.
- Dữ liệu production, có backup DB ngay trước mỗi lần ghi:
  - 077 thêm 9 thương hiệu (Sigenergy, Deye, Anker SOLIX, SOFAR, GoodWe, Growatt,
    Huawei, Sharp, Sungrow); backup `backups/20261107T095551Z-db`.
  - 078 đặt `options.hotline_technical = 0945 536 899` để footer hiện thẻ
    "Hỗ trợ kỹ thuật"; backup `backups/20261107T100339Z-db`.
- Sự cố: sau khi đổi container, Caddy không phân giải được `nuxt` trong lúc tạo
  lại và trả 503 khoảng 1 phút (10:28–10:29 UTC) đến lượt health check kế tiếp.
  Bước verify của script rơi đúng khoảng đó nên báo FAIL. Đã tự hồi phục, không
  rollback.
- Kiểm tra sau đó (chạy tay thay phần script chưa chạy): HTTP smoke 7 route 200;
  Playwright production 18/18; log web/nuxt 3 phút không có error; logo và bộ lọc
  đo trên site thật đúng.
- Rollback point: `scripts/rollback-release.sh` trên VPS (release trước `a8632eed`).

## 2026-11-07 — giao diện tổng (a8632eed)

- Commit/image: `a8632eed` / `phucthinhsolar-web:a8632eed4d87`. SAFE, PASS toàn bộ:
  predeploy, Playwright 248 pass, full backup, Playwright production 18/18, log
  scan 0 lỗi.

## 2026-11-07 — deploy dừng ở predeploy, production không đổi

- Lệnh: `scripts/deploy-production.sh --auto --confirm-production`, chế độ SAFE.
- Lần 1 dừng ở `git diff --check`: lỗi khoảng trắng, và thư viện 3D vendored
  cần thuộc tính `-whitespace` trong `.gitattributes`. Đã sửa và commit.
- Lần 2 dừng ở predeploy: `scripts/test-admin-crud-lifecycle.php` báo FAIL phần
  Nuxt search/news, vì container `pts-nuxt-service-rehearsal` chưa chạy.
- Production giữ image `phucthinhsolar-web:04dde592c441`. Không có backup hay
  migration nào được chạy.
- Lần sau: bật container diễn tập trước, theo mục 6 của
  `docs/02-development/nuxt-storefront-guide.md`.

## 2026-11-05 — đồng bộ production theo master sau khi gom nhánh

- Release PHP/Nuxt: `c62ba98b1de8` (`master`, commit
  `c62ba98b1de886c90cd9094c7217c6714554be8c`). Production trước đó là
  `263faa84286e`; nhánh Codex và nhánh film-pilot đã được hợp nhất vào master
  trước release. Phạm vi runtime mới gồm radius chip bộ lọc dự án và bundle
  preview Solar Cinema; không có migration.
- Backup SAFE trước deploy: `20261105T144623Z-full`, checksum đạt; chứa database
  và `shared/upload`. Backup chỉ nằm trên VPS do chưa có hook off-site. Không
  xóa upload, volume database hoặc backup cũ. Cache tái sinh đã xóa: 72 page +
  2 asset.
- Local predeploy PASS; full Playwright: 248 pass, 42 skip, 0 fail. PHP/Nuxt
  build và healthcheck PASS. Schema preflight `READY`, không chạy SQL migration.
- HTTP smoke 5 route trả 200; browser QA production bổ sung kiểm tra 8 route
  chính trên desktop và mobile: 16/16 pass, không lỗi JavaScript, ảnh hỏng hoặc
  tràn ngang. PDP mẫu trả 200, ảnh 960px tải đủ và Product JSON-LD có dữ liệu
  sản phẩm hợp lệ. `/migration-solar-cinema-preview/` vẫn trả 404 có chủ ý:
  Caddy loại `/migration-*` khỏi proxy Nuxt để các preview không thành trang
  public; pilot chưa được tích hợp vào section CMS.
- **Cổng Playwright production của wrapper chưa đạt:** wrapper thoát mã 1 sau
  khi image đã active vì `mobile-commerce-experience.spec.js` và
  `typography-system.spec.js` tìm selector PHP cũ (`.right-pro-detail`,
  `.pts-content-cards`, `.pts-home-category-copy`). Chạy `public-pages.spec.js`
  riêng cho 16 kiểm tra trang desktop/mobile đều đạt; hai lần chạy JSON-LD
  legacy cũng không tìm được selector PHP `.product-card .product-item`, nhưng
  schema được xác minh thủ công trên DOM Nuxt. Cần thay production gate bằng
  selector/flow Nuxt trước release tiếp theo; không coi exit 1 của wrapper là
  một lượt deploy có toàn bộ cổng tự động PASS.
- Không có Caddy 503 trong đợt này. PHP/Nuxt hiện healthy; image rollback được
  lưu ở `state/previous-image` và `state/previous-nuxt-image` là
  `263faa84286e`; rollback code bằng
  `/srv/phucthinhsolar/scripts/rollback-release.sh`. Sau full backup, disk VPS
  còn khoảng 4.8 GB (96% đã dùng); không prune image/cache trong lần release.

## 2026-11-05 — cân lại giỏ hàng, checkout và tải ảnh PDP

- Release production: PHP/Nuxt `263faa84286e`. Cột tóm tắt commerce rộng
  480px ở desktop; card dùng radius 10px; checkout ba cột trường thông tin ở
  viewport rất rộng; giá sản phẩm nằm dưới tên để dễ đọc. Ảnh chính PDP dùng
  rendition 960px, preload sớm và xử lý lỗi theo URL thumbnail thực tế.
- Trước deploy: 509/509 test, typecheck, build và smoke PASS. Browser local
  với sản phẩm CMS thật tại 390/1440/2048 không tràn ngang hay page error;
  ảnh PDP mẫu 960px là 29 KB so với bản 1600px 63 KB.
- Backup DB `20261105T135137Z-db`; xóa 115 page cache và 2 asset cache tái
  sinh, không migration, không chạm upload/DB volume. Rollback image PHP/Nuxt
  `b8c86f162ed4` qua `/srv/phucthinhsolar/scripts/rollback-release.sh`.
- Deploy script dừng ở verify đầu do Caddy tạm báo 503 khi upstream Nuxt đổi
  IP. Sau khi upstream phục hồi, verify-stack và schema preflight READY; browser
  production tại 390/2048 xác nhận PDP, cart, checkout không lỗi JavaScript,
  không tràn ngang. Log 1 phút sau ổn định không có lỗi mới. Cần xử lý khoảng
  503 ngắn trong quy trình đổi image ở đợt hạ tầng riêng.

## 2026-11-05 — video CTA Giải pháp và hợp nhất release

- Release production: PHP/Nuxt `b8c86f162ed4`. Gộp commit video CTA của nhánh
  Claude vào nhánh hotfix đã chạy production; các nhánh audit và visual tương
  đương đã được dọn sau khi kiểm tra quan hệ commit/patch. Giữ nhánh film pilot
  riêng vì đó là trang thử nghiệm chưa thuộc release.
- Trước deploy: 509/509 unit test, typecheck, build và smoke PASS. Backup FAST
  database: `20261105T130611Z-db`; không migration, không đổi DNS, không xóa
  upload/DB volume. Cache tái sinh đã xóa: 91 page và 2 asset.
- Script deploy dừng tại bước verify đầu vì Caddy tạm báo `no upstreams
  available` khi container Nuxt đổi IP. Sau khi active health checker báo
  `host is up`, verify-stack và schema preflight chỉ đọc PASS. Sáu route chính
  trả 200; video MP4 trả 206 và đúng byte range. Browser production ở 390/1440
  không lỗi JavaScript hoặc tràn ngang; mobile dùng poster, desktop có video.
  PHP/Nuxt healthy và log 2 phút sau ổn định không có lỗi mới.
- Rollback code: `state/previous-image` và `state/previous-nuxt-image` trỏ về
  `5c4fc0728392`; dùng `/srv/phucthinhsolar/scripts/rollback-release.sh`.
  Backup hiện nằm trên VPS; hook off-site riêng chưa được cài.
- Ghi nhận: script cần đợi Caddy active health chuyển upstream sang `up` trước
  HTTP verify để tránh báo fail tạm thời. Bộ Playwright production cũ vẫn dùng
  selector template PHP, cần chuyển sang Nuxt ở đợt bảo trì test riêng.

## 2026-11-05 — Nuxt storefront fixes và cutout sân khấu Giải pháp

- Hai release liên tiếp: `4f241fed92f9` rồi follow-up `5c4fc0728392`; trạng
  thái production cuối cùng là PHP/Nuxt `5c4fc0728392`.
- Phạm vi: đồng bộ icon registry, ổn định phản hồi số lượng giỏ, sửa cache bài
  tin và PHP layout hook tùy chọn; bỏ ảnh bối cảnh khỏi stage thiết bị. Follow-up
  resolve URL alias Giải pháp qua `productCategoryTree`, để danh mục “Đèn sân
  vườn” lấy đúng ba sản phẩm CMS có PNG alpha; “Trụ đèn” không có sản phẩm nên
  giữ placeholder.
- Backup full trước release: `20261105T120925Z-full` và
  `20261105T124008Z-full`; không migration, không đổi DNS.
- Kiểm tra local ở follow-up `5c4fc0728392`: `npm test` 505/505, typecheck,
  build và `NITRO_SMOKE_MOCK_PHP=1 npm run smoke` đều PASS. Full browser suite
  trên release `4f241fed92f9`: 248 pass, 42 skip, 0 fail; follow-up được kiểm
  tra bằng browser production theo luồng riêng bên dưới.
- Kiểm tra production: PHP/Nuxt healthy; schema preflight read-only READY; các
  route `/`, `/san-pham/`, PDP, `/gio-hang/`, `/tin-tuc/` và `/giai-phap/` HTTP
  200; log web/Nuxt/proxy không có lỗi mới. Browser QA riêng `/giai-phap/` tại
  320/390/768/1440: không tràn ngang, không page error, không ảnh nền phong cảnh;
  chọn “Đèn sân vườn” đổi sang V600 ORIANA, T600 ORIANA và WL-0412, ảnh tải đủ và
  kiểm tra mẫu alpha cho thấy nền trong suốt.
- Production subset `mobile-commerce-experience.spec.js` và
  `typography-system.spec.js` còn selector của template PHP cũ nên không dùng
  làm gate Nuxt: các section/catalog/heading hiện có nhưng class legacy không
  tồn tại. Cần cập nhật selector trong lượt test-maintenance riêng; kết quả này
  không thay thế browser QA production nêu trên.
- Cache tái sinh đã xóa theo deploy script: release đầu 93 page + 11 asset;
  follow-up 104 page + 2 asset. Không chạm upload/DB volume.
- Rollback code hiện qua `/srv/phucthinhsolar/scripts/rollback-release.sh`;
  image trước đó được ghi trong `state/previous-image` và
  `state/previous-nuxt-image`. Backup hiện chỉ lưu trên VPS; script cảnh báo
  chưa có hook off-site riêng.

## 2026-10-09 — khôi phục nội dung tư vấn toàn bộ catalog

- Commit/image: `42db406ce85f` / `phucthinhsolar-web:42db406ce85f`; deploy `SAFE`.
- Phạm vi: giữ khối thẻ nhu cầu và FAQ trước CTA khi dữ liệu Admin thiếu; đồng
  bộ nội dung cho toàn bộ danh mục cha/con bằng migrations 063, 065, 067–071 và
  073; bổ sung 26 ảnh thẻ vào shared upload.
- Backup trước thay đổi: `20261008T155253Z-full` và backup SAFE
  `20261008T161208Z-full`.
- Kiểm tra: 248 Playwright local đạt, 42 skipped; 9 Playwright production đạt,
  5 skipped; audit sitemap đạt 31/31 URL danh mục, 0 lỗi; 26/26 ảnh HTTP 200.
- Production: web/database healthy; log web/proxy không có Fatal, Warning hoặc
  HTTP 5xx mới sau deploy.
- Rollback code: `/srv/phucthinhsolar/scripts/rollback-release.sh`; rollback dữ
  liệu dùng full backup trước migration sau khi xác nhận phạm vi cần phục hồi.
- Hai danh mục con không có row nội dung riêng (`Đèn cắm cỏ`, `Kẹp tấm pin`)
  kế thừa block từ danh mục cha và đã được kiểm tra trực tiếp.

## 2026-09-30 — rà soát lớp backup

- Trạng thái production không thay đổi; đây là cập nhật tài liệu (`docs-only`).
- Backup ứng dụng trên VPS vẫn có timer DB/full backup và các bản backup local có
  checksum.
- CloudFly automatic backup/snapshot là lớp khôi phục toàn VPS đang có theo gói
  hạ tầng hiện hành; cần kiểm tra lịch, retention và restore trên CloudFly Console
  theo định kỳ.
- `/usr/local/sbin/pts-backup-offsite` chưa phải là thành phần bắt buộc của
  baseline hiện tại. Nếu chưa cài hook này, cảnh báo của script chỉ có nghĩa là
  chưa có thêm một bản copy sang nhà cung cấp/object storage độc lập ngoài CloudFly.
- Không cleanup, không thay đổi backup và không thay đổi production trong lần rà
  soát này.

## 2026-09-29 — migration và vận hành sau deploy

- Commit/image đang chạy: `14fdb2672665` / `phucthinhsolar-web:14fdb2672665`.
- Phạm vi: migration 058–060, kiểm tra route, backup retention và dọn Docker
  build cache.
- Migration đã xác minh: payment/shipping tables; payment/carrier columns trên
  `table_order`; project location/date columns trên `table_news`; `weight` trên
  `table_product`.
- Backup ứng dụng giữ lại: `20260929T013137Z-full` và
  `20260929T012822Z-full`.
- Docker build cache: đã kiểm tra và prune phần không dùng; không xóa container,
  volume hoặc image đang chạy.
- Kiểm tra sau thay đổi: stack healthy, các route public chính HTTP 200, không có
  log web/proxy lỗi mới tại thời điểm kiểm tra.
- Rollback point: image trước đó được lưu qua `state/previous-image`; không tự
  rollback DB.

## Mẫu ghi cho lần tiếp theo

```text
Ngày/giờ:
Commit/image:
Mode: AUTO | SAFE | FAST | docs-only
Phạm vi thay đổi:
Migration:
Backup trước deploy:
Smoke/HTTP/Playwright:
Log scan:
Rollback point:
Rủi ro còn lại:
```
