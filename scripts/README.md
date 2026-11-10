# Script vận hành

Các lệnh nghiệp vụ là lệnh Artisan trong `backend/` (chạy trong container `pts-laravel13-local`):

| Lệnh | Tác động | Dùng khi |
|---|---|---|
| `php artisan pts:import-legacy [--fresh]` | Ghi database `pts_app` | Nhập dữ liệu một lần từ database site cũ |
| `php artisan pts:snapshot [api] [--save]` | Chỉ đọc (`--save` ghi file mốc local) | So 27 API với câu trả lời đã lưu |
| `php artisan pts:data-health` | Chỉ đọc | Tìm sản phẩm thiếu ảnh, sai giá, trùng đường dẫn |

Script đo giao diện ở `tools/visual-tests/audit/` (xem README trong đó).

## Dọn Docker và WSL (máy dev)

File ổ ảo `.vhdx` trên Windows **không tự nhỏ lại** khi xóa dữ liệu, nên phải dọn rồi nén. Hai bước:

| Script | Chạy ở đâu | Tác động |
|---|---|---|
| `scripts/maintenance/clean-docker-wsl.sh [--apply]` | Trong WSL | Mặc định chỉ xem. `--apply` xóa build cache, image PTS cũ không container nào dùng, layer treo, cache npm/pnpm. Chỉ **liệt kê** volume, image của project khác (ZCRM), thư mục home lớn: không tự xóa |
| `scripts/maintenance/Compact-WslDisks.ps1` | PowerShell "Run as administrator" | Xóa bản sao upload thừa của bản thử nghiệm (cần root), `fstrim`, tắt WSL + Docker Desktop, nén mọi `.vhdx` (Ubuntu, Docker), in dung lượng trước/sau |

Thứ tự: `bash scripts/maintenance/clean-docker-wsl.sh --apply` rồi script `.ps1` ở terminal admin. Không bao giờ chạy `docker compose down -v` hay `docker volume prune`: volume `pts-local_db_data` là DB local (bản sao dữ liệu production).

Local gồm **hai container**: `pts-laravel13-local` (Laravel, cổng 8090) và `pts-mariadb-local` (MariaDB 10.11 UTC, như production), cộng Nuxt chạy bằng node trên máy. DB trống (volume mới): `docker compose up -d db` rồi `bash scripts/maintenance/local-db-init.sh [dump.sql]` để tạo database/user theo `backend/.env` và nạp dump.

`cd frontend && npm run build && NUXT_LOCAL_API_PROXY=1 NITRO_HOST=0.0.0.0 NITRO_PORT=3110 NUXT_API_ORIGIN=http://127.0.0.1:8090 node .output/server/index.mjs`.

