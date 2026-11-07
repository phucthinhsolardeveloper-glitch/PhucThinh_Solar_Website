# Backend Laravel 13 — hợp đồng cho agent

Bổ sung cho `../AGENTS.md`, không thay thế nó. Đọc trước `../docs/16-laravel-migration/README.md`.

- Laravel phục vụ `/api/v1/*` cho Nuxt, `/upload`, `/thumbs`, `/sitemap.xml`, `/health` và Admin Filament.
- Chạy PHP, Composer, Artisan trong container `pts-laravel13-local` (`../docker-compose.yml`).
  Không cài PHP lên máy và không chạy script cài đặt tải từ internet.
- Dữ liệu ở database `pts_app` (kiểm thử: `pts_app_test`). Kết nối `legacy` chỉ là nguồn đọc cho
  `pts:import-legacy`; không code nào khác được đọc bảng `table_*`.
- Đổi cấu trúc dữ liệu bằng migration Laravel mới; không sửa migration đã chạy.
- Không đổi contract `frontend/shared/contracts/*` chỉ để test pass; đổi contract thì sửa cả hai phía.
- Mỗi thay đổi phải qua `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan` và
  `php artisan pts:snapshot` trước khi commit. Khác biệt có chủ đích thì xem kỹ rồi `--save`.
