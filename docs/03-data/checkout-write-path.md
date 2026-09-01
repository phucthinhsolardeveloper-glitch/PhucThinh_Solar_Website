# Đường ghi checkout và hoàn tác bù trừ

Cập nhật: 2026-09-01. Phạm vi: Docker local, chưa triển khai production.

Checkout public trong `sources/order.php` ghi một dòng `table_order`, sau đó ghi đúng một dòng `table_order_detail` cho từng item hợp lệ trong session cart.

Database hiện dùng MyISAM nên transaction PDO không cung cấp atomicity thực. ADM-P0-001 triển khai lớp bảo vệ interim:

- `startTransaction()` / `commit()` / `rollback()` được giữ làm best-effort và sẽ có tác dụng khi bảng chuyển sang InnoDB.
- Mọi giá trị trả về từ insert order/detail đều được kiểm tra.
- Mỗi sản phẩm được đọc lại trước khi insert detail; thiếu sản phẩm hoặc quantity không hợp lệ làm hủy checkout.
- Khi lỗi sau khi đã có order ID, hệ thống xóa `order_detail` theo đúng `id_order`, rồi xóa chính order đó.
- Shutdown cleanup xử lý trường hợp PDODb dừng request bằng `die()` giữa chuỗi insert.
- Email, token cảm ơn, conversion state và xóa cart chỉ chạy sau khi toàn bộ dòng DB được lưu thành công.
- Khi thất bại, cart được giữ nguyên và khách được đưa về giỏ hàng với thông báo lỗi.

Đây là bù trừ best-effort, không phải atomic transaction: process/server có thể dừng trước khi shutdown cleanup hoàn tất. Chuyển bảng commerce sang InnoDB và atomic transaction thật thuộc ADM-P1-004, phải có migration/backup/rollback riêng.

Double-submit hiện không thêm token mới: PHP session khóa tuần tự request cùng session; request thành công xóa cart trước redirect nên request POST kế tiếp gặp cart rỗng. CSRF và persistent rate limit tiếp tục giữ nguyên.

Regression: `scripts/test-checkout-atomicity.php` kiểm happy path, partial detail failure, missing product, shutdown giữa insert, double-submit model, cleanup count và orphan.
