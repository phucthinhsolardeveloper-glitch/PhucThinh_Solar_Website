# Ứng viên triển khai Laravel 13 — chỉ dùng cho rehearsal

Trạng thái 2026-11-09: **chưa triển khai production**. Compose ở đây tạo project
`pts_laravel13_candidate` riêng, chỉ mở Caddy ở `127.0.0.1:18085`. DB và upload
riêng; không thay Caddy, container, volume của website đang chạy, training hoặc ZCRM.
Không chạy `down -v`.

Mốc kiểm 2026-11-08: build lại candidate local từ `e0663f29` với tag riêng;
DB candidate nhận migration `000500` và `000600`. Qua Caddy, `/health`,
`/admin/login`, `/api/v1/site`, `/`, `/san-pham/`, `/gio-hang/`,
`/thanh-toan/`, robots và sitemap đều trả 200; `shop-flow.cjs` đạt (không gửi
đơn). Nuxt và Laravel healthy. Đây là rehearsal local; staging VPS, restore và
rollback qua Caddy production vẫn chưa diễn tập.

Mốc mới `8b116c59`: hai image candidate gắn tag SHA, backend/Nuxt healthy;
qua Caddy `health`, Admin login và home trả 200. `shop-flow` đạt không gửi đơn,
`site-audit` 0 lỗi trên 19 route × 4 viewport, `admin-screens` 46/46 màn
desktop/mobile không có lỗi. Báo cáo và ảnh nằm ở `/tmp/pts-*8b116c59*`
trên máy rehearsal; tài khoản Admin thử đã xóa. Laravel 129 test/908 assertions,
PHPStan 0, Pint 250 file, Nuxt 476 test, typecheck/build và `pts:snapshot`
đều qua. Đây vẫn là candidate **local**; chưa thay thế staging VPS hoặc thử
restore/rollback với Caddy thật.
Route `/robots.txt` của proxy candidate đã được sửa và kiểm trả đúng hai dòng
`User-agent: *` / `Disallow: /`, cùng header `X-Robots-Tag: noindex`.

Mốc `0740fe09`: backend candidate được build lại và healthy; Nuxt vẫn dùng
image `8b116c59` vì không đổi frontend. Kiểm qua trình duyệt trên Caddy:
`/admin/products` 200 và hiện cột **Cần bổ sung**, `/admin/tich-hop` 200;
tài khoản kiểm thử đã xóa. Test backend 131 pass/924 assertions, PHPStan 0,
Pint 252 file và snapshot API không lệch. Bốn hãng vận chuyển vẫn chỉ có
credential contract/readiness; cần adapter cùng mapping trước khi tính phí thật.

Mốc `b79645ba`: build lại cả backend và Nuxt candidate, hai container healthy.
Trên trình duyệt qua Caddy, mã UTM được giữ sau khi chuyển từ trang đầu sang
trang sản phẩm; popup tư vấn gửi đúng `source_url` của sản phẩm cùng mã chiến
dịch. Request biểu mẫu được chặn và trả kết quả giả tại browser, không ghi lead
hoặc gửi mail. Test backend 132 pass/936 assertions, Nuxt 478 test,
typecheck/build, PHPStan 0, Pint 253 file, API snapshot không lệch.
Bài thử có thể chạy lại bằng `tools/visual-tests/audit/lead-attribution.cjs`;
báo cáo lần này ở `/tmp/pts-lead-attribution-b79645ba.json`.

Mốc `5cc09fc7`: backend image `5cc09fc7-candidate`, Nuxt image
`967315c0-candidate` đều healthy. Qua Caddy candidate, `/`, `/health`,
`/admin/login`, `/api/v1/site` và `/admin/favicon.png` trả 200; đường dẫn ảnh
site trùng cũ trả 301 tới bản đang dùng. DB candidate đã dọn 24 banner và
19 page item retired có JSON backup; snapshot 1.754/1.754 không đổi.
21 ảnh chỉ còn liên quan đến banner retired đã được lưu tar kèm SHA-256 trước
khi xóa khỏi **upload candidate**; ảnh banner đang hiển thị không thiếu.
Xem `docs/16-laravel-migration/retired-content-cleanup-2026-11-08.md` để khôi
phục. Giữ hai image `b79645ba-candidate` làm rollback point. Khoảng 1.105
file upload khác chưa có DB/source tham chiếu vẫn được giữ để tránh làm hỏng URL
media cũ. Laravel 146 test/1.042 assertions, PHPStan 0, Pint 263 file; Nuxt
478 test, typecheck/build đều qua trước khi chốt candidate.

Mốc `49dbf3dc`: backend candidate đã build lại và healthy. Route upload chặn
thư mục ẩn của file manager cũ; kiểm qua Caddy: ảnh editor 200, ảnh quarantine
404 (cả URL mã hóa dấu chấm), API snapshot không lệch. Lưu tar kèm SHA-256 rồi
dọn 210 thumbnail cache CKFinder khỏi upload candidate; các media khác vẫn giữ.
Backend 148 test/1.054 assertions, PHPStan 0, Pint 264 file. Backend image
`5cc09fc7-candidate` và Nuxt image `967315c0-candidate` còn để rollback;
production và upload dùng chung không đổi.

Mốc `728541ef` (gồm sửa P0 từ `152c868a`): build lại backend và Nuxt candidate,
cả hai healthy; Caddy
trả 200 cho trang chủ, `/cam-on/`, Admin, API và health. PHP image nhận
`upload_max_filesize=100M`, `post_max_size=384M`; API snapshot 1.754/1.754
không lệch. VNPay chỉ ghi conversion/gửi mail ở lần chuyển sang `paid`, trang
cảm ơn phân biệt pending/failed; review upload có kiểm MIME/container và dọn
file nếu DB rollback. Bằng chứng và giới hạn còn lại:
`docs/16-laravel-migration/commerce-media-correctness-2026-11-09.md`.
Backend 150 test/1.093 assertions, PHPStan 0, Pint 264 file; Nuxt 478 test,
typecheck/build pass. Image rollback gần nhất: backend `49dbf3dc-candidate`,
Nuxt `967315c0-candidate`. Chưa thử thanh toán VNPay sandbox hoặc SMTP thật;
outbox/database queue đã xong ở `bff35f79` (compose có service `worker` và `scheduler`). Production không đổi.

Mốc `e02a4a1b` (2026-11-09, thay `4ab50eaf`): build lại backend và Nuxt, sao lưu DB
ứng viên trước (`candidate-db-before-c3c4c372.sql.gz` trên máy rehearsal), chạy 17
migration mới (không `--fresh`), `pts:ward-codes` (34 tỉnh, 3.321 phường/xã) và
`pts:brand-logos`. Mốc API lưu lại cho layout, cờ chuyển khoản, logo đối tác, mô tả
dự án. `verify-candidate.sh` đạt; `site-audit` 0 lỗi trên 19 route × 4 khổ;
`shop-flow` đạt (không gửi đơn); `admin-screens` DEEP 87 màn không lỗi (tài khoản thử
đã xóa); `ux-seo-checklist` không tràn, og:image 1200×630, LocalBusiness. Với
`APP_ENV=production` + `NTL_MODE=mock`, phí checkout lấy bảng khu vực. Image rollback:
`4ab50eaf-candidate`. Backend 211 test, Nuxt 484 test, typecheck/build, PHPStan, Pint
đều qua. Vẫn chưa có staging VPS, sandbox VNPay/Nhất Tín/SMTP, restore/rollback qua Caddy thật.

## Runtime và quyết định tạm thời

`Caddy → Nuxt 4` cho trang public; `Caddy → Laravel 13/PHP 8.4 Apache` cho
`/api/v1`, Admin/Livewire, upload, thumbnail, sitemap và health; `Laravel →
MariaDB 10.11`. Nuxt gọi Laravel qua `NUXT_API_ORIGIN=http://backend:80`.
Apache là baseline rehearsal vì repo đã có đường chạy PHP qua HTTP; quyết định
giữa Apache, PHP-FPM và FrankenPHP để production cần benchmark cùng dữ liệu và
tải. Không đổi runtime chỉ vì tên mới. Image backend và Nuxt phải gắn tag SHA
bất biến; không dùng `latest` cho cutover/rollback.
Snapshot API chuẩn nằm trong `backend/resources/api-snapshots` và được đóng
vào backend image. Volume `app_storage` chỉ chứa state runtime; không dùng file
còn sót từ một volume local cũ làm bằng chứng cho release mới. Các giá trị
CSRF/checkout token biến động đã được thay bằng placeholder; mọi trường còn lại
giữ nguyên so với bộ chuẩn cũ.

Upload ở `${PTS_UPLOAD_DIR}` là bind mount **có thể ghi** cho Admin, nằm ngoài
DocumentRoot PHP. `app_storage` chứa session/cache/log và `db_data` chứa MariaDB;
không xóa chúng khi đổi image. Ảnh site có trong release và mount chỉ đọc cho
Laravel sinh thumbnail. Kiểm quyền ghi của UID `www-data` trong upload trước
khi mở Admin. File `.env` được loại khỏi Docker build context.

## Chuẩn bị staging ngắn nhất

1. Chạy `df -h`, `du -x` theo thư mục release/upload/backup và `docker system df -v`.
   Xác nhận đủ chỗ cho dump DB, bản sao upload, image mới và rollback image.
2. Tạo `runtime/stack.env` từ `stack.env.example` và `runtime/backend.env` từ
   `backend.env.example`, đều mode `0600`. Chỉ backend nhận app secrets;
   Nuxt không nhận DB/root password hay APP_KEY. Giữ
   `APP_LOCALE=vi` và `APP_TIMEZONE=Asia/Ho_Chi_Minh` **trước khi import**.
   Thiếu timezone làm ngày sửa bài gần nửa đêm lệch một ngày trong snapshot.
   Dùng APP_KEY,
   mật khẩu DB và secret riêng của staging. Điền `APP_URL` đúng origin truy cập;
   Compose gắn kết nối `legacy` vào chính DB candidate để import `table_*`
   trong clone. Giữ VNPay ở sandbox và
   `VNPAY_ACTIVE=0` cho đến khi callback test đạt. Giữ `RECAPTCHA_ACTIVE=false`
   chỉ trên staging; production phải có key và gate kiểm hoạt động. Mail dùng
   `log`/hộp thử, không gửi khách thật. Vận chuyển chỉ còn Nhất Tín: giữ `NTL_MODE=mock`
   (trên `APP_ENV=production` checkout dùng bảng phí khu vực, Admin không tạo vận đơn MOCK),
   lên sandbox khi có tài khoản và đã đối chiếu mã tỉnh/phường.
3. Chép upload và một dump legacy **vào thư mục staging riêng**, đối chiếu
   checksum. Dump MariaDB nên dùng `--single-transaction --quick`, không chứa
   `CREATE DATABASE`/`USE` để nạp vào DB candidate. Chỉ dùng dữ liệu được phép
   trên staging; không đưa dump/ảnh chứa PII vào Git hoặc báo cáo.
4. Chốt commit sạch, đặt `PTS_SOURCE_REVISION` bằng **SHA đầy đủ** của commit và
   tag hai image theo 8 ký tự đầu (`<sha8>-candidate`). Từ thư mục này chạy
   `./build-candidate.sh runtime/stack.env`. Script gắn revision label và ghi
   image ID vào `runtime/release-manifest.json`. Để chạy đúng bản đã thử trên
   VPS, chuyển **chính hai image** bằng registry digest hoặc `docker save` /
   `docker load` cùng manifest; đối chiếu SHA-256 file tar và image ID. Không
   build lại cùng tag trên VPS rồi giả định bytes giống nhau. Đo chỗ trống
   trước khi tạo tar. Sau đó `docker compose --env-file runtime/stack.env up -d db`.
5. Nạp dump vào DB candidate qua `docker compose exec -T db`; sau đó chạy
   `docker compose run --rm --no-deps backend php artisan migrate --force` và
   `docker compose run --rm --no-deps backend php artisan pts:import-legacy --fresh`.
   Đây chỉ là **lần import đầu trên DB clone riêng**. Bản nâng cấp sau đó chạy
   migration mới trên clone và kiểm dữ liệu; tuyệt đối không chạy lại `--fresh`
   trên DB đã nhận đơn/lead/Admin write. Không trỏ hai lệnh vào DB production.
   So counts, quan hệ, orphan, tiền, content inheritance, user/SEO/media.
6. `docker compose --env-file runtime/stack.env up -d backend nuxt proxy`.
   Chạy `./verify-candidate.sh runtime/stack.env runtime/release-manifest.json`:
   gate so image ID của **container đang chạy** với manifest, DB chỉ nằm trong
   stack tách biệt, env contract (APP_KEY/URL/timezone/session/mail/VNPay),
   quyền ghi upload/storage của `www-data`, migration không pending, snapshot
   API khớp, route chính 200 và candidate noindex. Nếu fail thì giữ route cũ;
   chẩn đoán DB/image/config
   trước khi thử lại. Kiểm thêm `/upload/*`, `/thumbs/*` và response 404. Chạy
   `site-audit`, `shop-flow`, `admin-screens`, bảo mật và benchmark trên origin
   staging. Script Admin chỉ mở màn; CRUD/RBAC/save phải có kiểm riêng.

Trước khi đưa staging ra tên miền, cấu hình Caddy đang phục vụ VPS để proxy
hostname staging tới `127.0.0.1:18085`; giữ lớp noindex trong Caddy ứng viên.
Không thay route của training/ZCRM. Kiểm hostname và TLS qua proxy thật.

## Backup, cutover và rollback

- Trước import/cutover: giữ dump DB legacy, bản sao upload, checksum, image IDs,
  Caddyfile đang chạy và release/commit cũ. Thử restore dump vào DB clone mới,
  không chỉ kiểm file tồn tại. Phân biệt bản sao này với snapshot VPS CloudFly.
- Mỗi release phải có **cùng image ID** ở candidate đã kiểm và VPS staging.
  Ghi manifest, checksum tar/digest registry, migration batch, số dòng quan
  trọng, kết quả snapshot và route smoke. Migration luôn chạy trên DB clone
  trước; khi cutover được duyệt, tạo backup mới rồi mới áp migration lên DB đích.
  Nếu migration đổi schema không tương thích image cũ, rollback cần DB snapshot
  hoặc forward fix đã diễn tập, không chỉ đổi tag image.
- `Caddyfile.production-site` chỉ là **site block ứng viên** để merge vào Caddy
  hiện có sau duyệt. Kiểm network của Caddy cũ rồi mới nối tới container mới;
  không thay toàn bộ Caddyfile. Chạy `caddy validate` trên cấu hình đã merge,
  lưu bản trước, thử route bằng `--resolve`, rồi mới reload.
- Rollback route/image chỉ an toàn nếu chưa có write trên Laravel. Khi Admin,
  lead hoặc order đã ghi vào DB mới, quay lại PHP cũ sẽ tạo hai nguồn dữ liệu;
  cần đóng băng write, đối soát và kế hoạch replay/restore cụ thể trước cutover.
  Không tự restore DB production vì sẽ mất write phát sinh sau snapshot.
- Sau cutover cần giám sát 5xx, log, form, checkout, callback, SEO/404, media
  và image ID đang chạy; giữ current + previous image/release và 1–2 backup đầy đủ.

## Gate chưa đóng

Chưa có staging runtime trên VPS, benchmark Apache/FPM/FrankenPHP, restore và
rollback rehearsal qua Caddy thật. Không dùng file này để suy ra production đã
đổi. Phải có chủ project duyệt rõ trước mọi thao tác production.
