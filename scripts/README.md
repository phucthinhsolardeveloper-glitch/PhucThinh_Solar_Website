# Script vận hành

Các lệnh nghiệp vụ là lệnh Artisan trong `backend/` (chạy trong container `pts-laravel13-local`):

| Lệnh | Tác động | Dùng khi |
|---|---|---|
| `php artisan pts:import-legacy [--fresh]` | Ghi database `pts_app` | Nhập dữ liệu một lần từ database site cũ |
| `php artisan pts:snapshot [api] [--save]` | Chỉ đọc (`--save` ghi file mốc local) | So 27 API với câu trả lời đã lưu |
| `php artisan pts:data-health` | Chỉ đọc | Tìm sản phẩm thiếu ảnh, sai giá, trùng đường dẫn |

Script đo giao diện ở `tools/visual-tests/audit/` (xem README trong đó).
