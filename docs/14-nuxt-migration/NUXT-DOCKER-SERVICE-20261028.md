# Nuxt Docker service — staging/local rehearsal (2026-10-28)

Hoàn tất phần còn thiếu của Giai đoạn 4: Nuxt chạy như 1 container service
độc lập (không phải `npm run dev`), có healthcheck, image Node pin version cụ
thể, network riêng, và cấu hình PHP upstream qua allowlist thay vì hardcode.
Đây chỉ là bản rehearsal cho staging/local — KHÔNG đổi Caddy/DNS/production,
KHÔNG bật route public thật cho người dùng ngoài.

## Current checkpoint — 2026-10-30

- Các cờ sở hữu route Nuxt `*PublicRouteEnabled` đã được gỡ khỏi Nuxt config,
  Compose, middleware và wrapper. Mọi route đã có template Nuxt chạy trực tiếp;
  SEO indexability và công tắc hiển thị section trong CMS vẫn độc lập.
- Local rehearsal được build lại từ working tree hiện tại và healthy ở
  `127.0.0.1:3101`. GET smoke manifest: 33/33 entry không lỗi (29 HTTP 200 và
  4 alias HTTP 301 tới canonical Nuxt path). Frontend test 396/396, typecheck
  và build PASS.
- Working tree còn WIP chưa commit. Image trước rebuild được giữ ở tag
  `web_pts_php-nuxt-service:rollback-before-icon-interaction-audit-20261030`.
  Không đổi Caddy, DNS, production, DB/volume hoặc gửi request mutation.
- Các lệnh, flag defaults và route results ở những checkpoint dưới đây là lịch
  sử lịch sử; dùng checkpoint này làm trạng thái hiện tại.

## File

- `frontend/Dockerfile` — multi-stage: `node:24.20.0-alpine` (pin đúng
  `.nvmrc`, không dùng `latest`/`lts`) build `.output/`, copy sang stage
  runtime chạy user non-root `nuxt`, `HEALTHCHECK` gọi
  `/migration-hub-preview` nội bộ bằng `node -e fetch(...)` (không cần cài
  thêm curl/wget vào image).
- `frontend/.dockerignore` — loại `node_modules`/`.output`/`.nuxt` khỏi build
  context.
- `docker-compose.nuxt-service.yml` — service thật, dùng khi deploy rehearsal:
  set `NUXT_PHP_ORIGIN`/`NUXT_PHP_ORIGIN_ALLOWLIST`/`NUXT_PUBLIC_PHP_PUBLIC_ORIGIN`
  qua biến môi trường (mặc định trỏ `php-upstream:80`, phải override khi
  dùng thật), mọi flag `NUXT_PUBLIC_*_ROUTE_ENABLED` mặc định `false` — operator
  phải tự set `true` nếu muốn bật rehearsal cho 1 route, không có default bật
  sẵn trong image/compose. Network riêng `pts-nuxt-service-rehearsal-net`,
  cổng `127.0.0.1:3101:3000` (không đụng 3100 của preview dev hiện có).
- `docker-compose.nuxt-service.test.yml` — override CHỈ DÙNG ĐỂ TỰ TEST, thêm
  1 container PHP tạm (`php-upstream`) nối cả network rehearsal và network
  local hiện có để có dữ liệu DB thật khi kiểm tra. Không dùng file này khi
  deploy thật — ở môi trường thật, `NUXT_PHP_ORIGIN_ALLOWLIST` phải trỏ tới
  container PHP đã tồn tại từ trước, không dựng PHP mới ở đây.

## Build / run / stop

```bash
# Rehearsal có PHP test tạm (dev máy này):
mkdir -p domains/phucthinhsolar.com/public_html/{caches,assets/caches,logs,thumbs}  # gitignored, PHP container tự cần các dir này tồn tại trước khi tmpfs mount đè lên
cp <đường dẫn local config.php của bạn> domains/phucthinhsolar.com/public_html/libraries/config.php
docker compose -f docker-compose.nuxt-service.yml -f docker-compose.nuxt-service.test.yml up -d --build
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:3101/migration-hub-preview   # expect 200
docker compose -f docker-compose.nuxt-service.yml -f docker-compose.nuxt-service.test.yml down -v

# Rehearsal trỏ PHP đã có sẵn (staging thật, không dựng PHP mới):
NUXT_PHP_ORIGIN=http://<host-php-thật>:<port> \
NUXT_PHP_ORIGIN_ALLOWLIST=<host-php-thật>:<port> \
NUXT_PUBLIC_PHP_PUBLIC_ORIGIN=http://<host-php-thật>:<port> \
docker compose -f docker-compose.nuxt-service.yml up -d --build
```

Logs: `docker compose -f docker-compose.nuxt-service.yml logs -f`

## Rollback

`docker compose -f docker-compose.nuxt-service.yml down` (thêm `-v` nếu dùng
file `.test.yml`). Không có bước nào trong file này đổi PHP/Caddy/DNS/
production — rollback chỉ là tắt container rehearsal.

## Đã verify thật (2026-10-28)

- `docker compose ... build`: PASS, SSR build 24-25s, image ~252MB.
- `docker compose ... up -d` (kèm `.test.yml`): cả 2 container `Started`.
  **Lưu ý runtime:** `docker-compose.nuxt-preview.yml`/các compose PHP khác
  bind-mount `public_html:/var/www/html:ro` rồi tmpfs lên `caches/`,
  `assets/caches/`, `logs/`, `thumbs/` — các dir này bị `.gitignore` (dòng
  6-10 `.gitignore` gốc) nên **không tồn tại trên 1 worktree mới**; tmpfs
  không tự tạo được mountpoint trên bind mount read-only
  (`mkdirat ... read-only file system`). Phải `mkdir -p` 4 dir đó trước khi
  `up` lần đầu trên worktree mới — không phải bug code, là thiếu bước chuẩn
  bị thư mục cục bộ, giống các compose PHP khác đã gặp.
- Healthcheck: `pts-nuxt-service-rehearsal` chuyển `healthy` trong ~12s.
- Route qua service: `/migration-hub-preview` → 200, `/migration-home-preview`
  → 200, `/gioi-thieu` (flag `aboutPublicRouteEnabled` mặc định false) → 307
  redirect về PHP — đúng hành vi flag-gated đã thiết kế ở Lane D/E.
- Dọn sạch: `down -v` xóa container + network; `docker rmi` xóa image build
  test; xóa `libraries/config.php` cục bộ và 4 dir gitignored đã tạo để test.
  Không còn container/image/network nào tên `nuxt-service-rehearsal` sau khi
  dọn; không đụng `pts-php81-local`/`pts-mariadb-local`/volume upload chung.

## Chưa làm / ngoài phạm vi

- Chưa nối vào Caddy/reverse proxy thật nào — đây vẫn là rehearsal cục bộ,
  việc nối hybrid routing thật thuộc P2-B (`NEXT-EXECUTION-PLAN.md`), cần
  phê duyệt riêng trước khi làm.
- Image chưa có CI build/push tự động (repo chưa có pipeline CI, xem ghi
  chú ở `STAGE2-PARITY-VERIFICATION-20261028.md`).
