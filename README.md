# Phúc Thịnh Solar — phucthinhsolar.com

Website bán hàng và giới thiệu của Phúc Thịnh Solar.

```text
Trình duyệt ── Nuxt 4 (frontend/) ── trang, SEO, giỏ hàng, form
                    │  /_api/* gọi /api/v1/* phía server
                    ▼
               Laravel 13 (backend/) ── API, Admin Filament, /upload, /thumbs, /sitemap.xml
                    │
                    ▼
               MariaDB (database pts_app)
```

- `frontend/` — Nuxt 4, Vue 3, Tailwind v4, Nuxt UI v4. Ảnh trang trí của site ở `frontend/public/assets/`.
- `backend/` — Laravel 13 trên PHP 8.4. Mã nghiệp vụ ở `app/Domain/*`, cấu trúc dữ liệu ở
  `database/migrations/`, cấu hình site ở `config/site.php`.
- `tools/visual-tests/` — đo giao diện thật bằng Playwright (`audit/`) và smoke test Nuxt (`nuxt-tests/`).
- `docs/` — tài liệu; bắt đầu từ `docs/16-laravel-migration/README.md`.
- `deploy/` — cấu hình triển khai VPS.

## Chạy local

Laravel chạy trong Docker, nối vào container MariaDB `pts-mariadb-local` qua mạng `web_pts_php_default`.

```bash
docker compose up -d                       # Laravel: http://127.0.0.1:8090
docker exec pts-laravel13-local php artisan migrate
```

Nhập dữ liệu một lần từ database site cũ (đặt `LEGACY_DB_DATABASE` trong `backend/.env`):

```bash
docker exec pts-laravel13-local php artisan pts:import-legacy --fresh
```

Nuxt:

```bash
cd frontend
npm ci
npx nuxi build
NITRO_PORT=3110 NUXT_API_ORIGIN=http://127.0.0.1:8090 node .output/server/index.mjs
```

Mở http://127.0.0.1:3110/. Admin Filament: http://127.0.0.1:8090/admin/.

## Kiểm tra trước khi commit

```bash
# backend (trong container)
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
php artisan pts:snapshot          # so 27 API với câu trả lời đã lưu

# frontend
npm test
npx nuxi typecheck

# giao diện thật (Nuxt đang chạy)
AUDIT_BASE=http://127.0.0.1:3110 node tools/visual-tests/audit/site-audit.cjs
AUDIT_BASE=http://127.0.0.1:3110 node tools/visual-tests/audit/shop-flow.cjs
```

Quy tắc làm việc cho người và AI: `AGENTS.md`.
