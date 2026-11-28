# Dependency map hiện hành

Đối chiếu manifest và Dockerfile ngày 2026-11-26, source 5c35860f.

| Thành phần | Phiên bản/ràng buộc đã thấy | Nguồn |
|---|---|---|
| Nuxt | ^4.6.0 | [frontend/package.json](../../frontend/package.json) |
| Vue / Nuxt UI / motion-v | ^3.5.0 / ^4.12.0 / ^2.9.0 | frontend/package.json |
| Laravel / Filament | ^13.17 / ^5.0 | [backend/composer.json](../../backend/composer.json) |
| PHP | Composer ^8.3; Docker 8.4 CLI local, 8.4 Apache production | [local Dockerfile](../../backend/docker/Dockerfile), [production Dockerfile](../../deploy/laravel13/backend.Dockerfile) |
| PDF / spreadsheet | dompdf ^3.1 / phpspreadsheet ^5.11 | backend/composer.json |
| Node production | 24.20.0 Alpine | [Nuxt Dockerfile](../../deploy/laravel13/nuxt.Dockerfile) |
| Database local | MariaDB 10.11 | [docker-compose.yml](../../docker-compose.yml) |

Các ràng buộc không phải exact installed version. Lockfile frontend/package-lock.json và backend/composer.lock mới là phiên bản khóa; runtime kiểm riêng. Tài nguyên public ở frontend/public/assets; media sản phẩm dùng storage/media routes. Browser audit ở [tools/visual-tests](../../tools/visual-tests). Không thêm thư viện legacy chỉ vì docs trước cutover nhắc tên.

Lịch sử: git show 5c35860f:docs/01-architecture/dependency-map.md.
