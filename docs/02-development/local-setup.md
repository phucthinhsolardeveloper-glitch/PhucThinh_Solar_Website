# Local/staging setup

Chưa chạy thành công trong môi trường khảo sát: PHP CLI không tồn tại và không có DB local (`ENVIRONMENT_FAILURE`). Quy trình cần xác minh:

1. Dùng bản sao riêng, không trỏ vào production DB/upload.
2. Cài Apache-compatible rewrite, PHP 7.4 trước để lập baseline và MariaDB/MySQL.
3. Import dump vào DB staging; cấu hình bản sao `libraries/config.php` bằng credential riêng.
4. Trỏ document root tới `domains/phucthinhsolar.com/public_html`.
5. Bật extension PDO MySQL, cURL, GD/Imagick, fileinfo, mbstring, OpenSSL theo lỗi runtime.
6. Kiểm tra `/`, `/administrator/`, category, product, contact và cart.

Không tạo `.env.example` vì code hiện không đọc environment variables; tạo file đó sẽ gây hiểu nhầm. Việc chuyển secret ra env là task hardening riêng.
