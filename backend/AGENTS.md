# Backend Laravel 13 — hợp đồng cho agent

Bổ sung cho `../AGENTS.md`, không thay thế nó. Đọc trước:
`../docs/16-laravel-migration/README.md` và `../docs/12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md`.

- Thư mục này chỉ tồn tại trên nhánh `migration/laravel13-integration`. Không code Laravel trên `master`.
- Chạy PHP, Composer, Artisan trong container `pts-laravel13-local` (`docker-compose.laravel.yml`).
  Không cài PHP lên máy và không chạy script cài đặt tải từ internet.
- Database duy nhất được dùng: `pts_laravel13_clone` với user `pts_laravel`. Không trỏ vào
  `phucthinhs_local` hay production. Bảng legacy (`table_*`) chỉ đọc cho đến khi một phase
  cho phép ghi domain đó; không dual-write với PHP.
- Không đổi contract `frontend/shared/contracts/*` để test Laravel pass.
- Mỗi thay đổi phải qua `php artisan test`, `vendor/bin/pint --test` và `vendor/bin/phpstan` trước khi commit.
