# Ứng viên triển khai Laravel 13 — chỉ dùng cho rehearsal

Trạng thái 2026-11-08: **chưa triển khai production**. Compose ở đây tạo project
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

## Runtime và quyết định tạm thời

`Caddy → Nuxt 4` cho trang public; `Caddy → Laravel 13/PHP 8.4 Apache` cho
`/api/v1`, Admin/Livewire, upload, thumbnail, sitemap và health; `Laravel →
MariaDB 10.11`. Nuxt gọi Laravel qua `NUXT_API_ORIGIN=http://backend:80`.
Apache là baseline rehearsal vì repo đã có đường chạy PHP qua HTTP; quyết định
giữa Apache, PHP-FPM và FrankenPHP để production cần benchmark cùng dữ liệu và
tải. Không đổi runtime chỉ vì tên mới. Image backend và Nuxt phải gắn tag SHA
bất biến; không dùng `latest` cho cutover/rollback.

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
   `log`/hộp thử, không gửi khách thật. 4 hãng vận chuyển chỉ có config/readiness,
   chưa có adapter tính giá hay tạo vận đơn.
3. Chép upload và một dump legacy **vào thư mục staging riêng**, đối chiếu
   checksum. Dump MariaDB nên dùng `--single-transaction --quick`, không chứa
   `CREATE DATABASE`/`USE` để nạp vào DB candidate. Chỉ dùng dữ liệu được phép
   trên staging; không đưa dump/ảnh chứa PII vào Git hoặc báo cáo.
4. Từ thư mục này: `docker compose --env-file runtime/stack.env config --quiet`;
   `docker compose --env-file runtime/stack.env build backend nuxt`;
   `docker compose --env-file runtime/stack.env up -d db`.
5. Nạp dump vào DB candidate qua `docker compose exec -T db`; sau đó chạy
   `docker compose run --rm --no-deps backend php artisan migrate --force` và
   `docker compose run --rm --no-deps backend php artisan pts:import-legacy --fresh`.
   Không trỏ hai lệnh vào DB production. So counts, quan hệ, orphan, tiền,
   content inheritance, user/SEO/media và snapshot trước khi tiếp tục.
6. `docker compose --env-file runtime/stack.env up -d backend nuxt proxy`.
   Kiểm `curl -f http://127.0.0.1:18085/health`, API, trang public, `/admin/login`,
   `/upload/*`, `/thumbs/*`, sitemap, robots noindex và response 404. Chạy
   `site-audit`, `shop-flow`, `admin-screens`, bảo mật và benchmark trên origin
   staging. Script Admin chỉ mở màn; CRUD/RBAC/save phải có kiểm riêng.

Trước khi đưa staging ra tên miền, cấu hình Caddy đang phục vụ VPS để proxy
hostname staging tới `127.0.0.1:18085`; giữ lớp noindex trong Caddy ứng viên.
Không thay route của training/ZCRM. Kiểm hostname và TLS qua proxy thật.

## Backup, cutover và rollback

- Trước import/cutover: giữ dump DB legacy, bản sao upload, checksum, image IDs,
  Caddyfile đang chạy và release/commit cũ. Thử restore dump vào DB clone mới,
  không chỉ kiểm file tồn tại. Phân biệt bản sao này với snapshot VPS CloudFly.
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
