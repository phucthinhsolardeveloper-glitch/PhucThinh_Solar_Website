# Local/staging setup

Quy trình nối từ local → test → Git → deploy/VPS dành cho người tiếp quản nằm
tại [Sổ tay chủ hệ thống](../06-operations/owner-system-handbook.md).

## Docker local (dev nhanh)

Mặc định local bật `debug-developer` để phát triển. Khi cần cho chủ project test
phân quyền thật qua tunnel, khởi động lại web với:

```bash
LOCAL_ENFORCE_RBAC=1 docker compose -f docker-compose.local.yml up -d --force-recreate web
```

Trả về chế độ phát triển bằng cùng lệnh với `LOCAL_ENFORCE_RBAC=0`. Tài khoản
demo chỉ tạo bằng `scripts/create-local-rbac-demo-accounts.php`; không mang các
tài khoản/mật khẩu này lên production.

```bash
docker compose -f docker-compose.local.yml up -d
```

Web `http://localhost:8080`, admin `http://localhost:8080/administrator/`. DB MariaDB 10.4 trong service `db` (`phucthinhs_local` / `pts_local` / `pts_local_only`).

Từ 2026-09-01, service `web` bind-mount `domains/phucthinhsolar.com/public_html` vào `/var/www/html`, nên **sửa file `.php` / `.js` / `.css` là thấy ngay, không cần `docker compose build web`**. Các thư mục runtime (`caches`, `logs`, `upload`, `thumbs`) nằm trong named volume để Apache (`www-data`) ghi được và không làm bẩn cây git; lần đầu chạy chúng được seed từ nội dung image (Dockerfile `mkdir` + `chown` sẵn `caches` và `logs`).

- Chỉ build lại image khi đổi `docker/php74/Dockerfile`, `docker/php74/local-php.ini` hoặc phiên bản PHP.
- Sau khi kéo compose mới lần đầu: `docker compose -f docker-compose.local.yml up -d --force-recreate web`.
- Nếu cần làm mới cache trang: `docker compose -f docker-compose.local.yml exec web rm -f /var/www/html/caches/cache_*`.
- Dùng file manager admin để upload vào `assets/` trên local sẽ vướng quyền (host `assets/` thuộc user host, container chạy `www-data`); chưa xử lý trong phạm vi này.

## Thiết lập thủ công (không Docker)

Chưa chạy thành công trong môi trường khảo sát: PHP CLI không tồn tại và không có DB local (`ENVIRONMENT_FAILURE`). Quy trình cần xác minh:

1. Dùng bản sao riêng, không trỏ vào production DB/upload.
2. Cài Apache-compatible rewrite, PHP 7.4 trước để lập baseline và MariaDB/MySQL.
3. Import dump vào DB staging; cấu hình bản sao `libraries/config.php` bằng credential riêng.
4. Trỏ document root tới `domains/phucthinhsolar.com/public_html`.
5. Bật extension PDO MySQL, cURL, GD/Imagick, fileinfo, mbstring, OpenSSL theo lỗi runtime.
6. Kiểm tra `/`, `/administrator/`, category, product, contact và cart.

Không tạo `.env.example` vì code hiện không đọc environment variables; tạo file đó sẽ gây hiểu nhầm. Việc chuyển secret ra env là task hardening riêng.
