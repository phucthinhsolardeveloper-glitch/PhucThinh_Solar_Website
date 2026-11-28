# Local setup — Laravel/Nuxt hiện hành

Source đặt trong WSL ~/src/Web_PTS_PHP. Trước khi chạy xác nhận git status, branch và không trùng port/container với worktree đang QA.

## Backend
```bash
cd ~/src/Web_PTS_PHP
docker compose config --services
docker compose ps
# Khi đã xác nhận local DB/volume và env phù hợp:
docker compose up -d
docker exec pts-laravel13-local php artisan migrate:status
# chỉ khi cần migration và đã xác nhận DB local:
# docker exec pts-laravel13-local php artisan migrate
```

docker-compose.yml khai báo laravel13 (pts-laravel13-local, 127.0.0.1:8090 → 8000), db (pts-mariadb-local, MariaDB 10.11), db_data và legacy_upload. Không chạy import legacy --fresh trên DB có dữ liệu. Không overwrite env hoặc đưa secret production vào local. Xem [README](../../README.md) và [compose](../../docker-compose.yml).

## Frontend
```bash
cd ~/src/Web_PTS_PHP/frontend
npm ci
NUXT_API_ORIGIN=http://127.0.0.1:8090 npm run dev
```
npm run dev dùng 127.0.0.1:3100; README có cách chạy bản build ở 3110 với NITRO_PORT. Admin ở http://127.0.0.1:8090/admin. Kiểm port thực tế, không giả định 3110 là dev mặc định.

## Kiểm tra / lỗi
```bash
docker compose ps
docker compose logs --tail=100 laravel13
curl -fsS http://127.0.0.1:8090/health
```
Nếu 8090 không có service, kiểm docker ps, worktree đang chạy QA và port trước khi khởi động lại. Không restart container của agent khác. 502 từ Nuxt: kiểm backend origin, endpoint và payload contract; 401/419: cookie, session, CSRF, không tắt bảo vệ. [Testing](testing.md).

Tại thời điểm khảo sát chỉ thấy pts-cms-qa và pts-mariadb-local đang chạy; chưa chạy lại stack trong đợt docs. Hướng dẫn cũ: git show 5c35860f:docs/02-development/local-setup.md.
