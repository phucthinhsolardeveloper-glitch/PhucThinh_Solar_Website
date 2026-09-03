# Đường ghi checkout và hoàn tác bù trừ

Cập nhật: 2026-09-01. Phạm vi: Docker local, chưa triển khai production.

Checkout public trong `sources/order.php` ghi một dòng `table_order`, sau đó ghi đúng một dòng `table_order_detail` cho từng item hợp lệ trong session cart.

Hai bảng commerce `table_order` và `table_order_detail` đã dùng InnoDB trên Docker local sau migration 008, nên transaction PDO cung cấp atomicity thật cho đường ghi checkout. Lớp bảo vệ ADM-P0-001 vẫn được giữ để kiểm tra lỗi rõ ràng và phòng vệ khi production chưa chạy migration:

- `startTransaction()` / `commit()` / `rollback()` bao trọn insert order và toàn bộ order detail.
- Mọi giá trị trả về từ insert order/detail đều được kiểm tra.
- Mỗi sản phẩm được đọc lại trước khi insert detail; thiếu sản phẩm hoặc quantity không hợp lệ làm hủy checkout.
- Khi lỗi sau khi đã có order ID, hệ thống xóa `order_detail` theo đúng `id_order`, rồi xóa chính order đó.
- Controller bắt exception để rollback; shutdown cleanup vẫn là lớp phòng vệ cuối nếu request dừng bất thường giữa chuỗi insert.
- Email, token cảm ơn, conversion state và xóa cart chỉ chạy sau khi toàn bộ dòng DB được lưu thành công.
- Khi thất bại, cart được giữ nguyên và khách được đưa về giỏ hàng với thông báo lỗi.

Trên local, InnoDB là lớp bảo đảm atomic chính; xóa bù trừ và shutdown cleanup là phòng vệ bổ sung. Production vẫn dùng trạng thái hiện hữu cho đến khi được phép chạy `migrations/008_convert_commerce_to_innodb.sql`; trước khi ALTER phải backup/restore-check, xác nhận orphan bằng 0 và dừng checkout. Rollback về MyISAM sẽ làm mất transaction thật.

Checkout phát token ngẫu nhiên 256-bit theo session/form và lưu vào `table_order.checkout_token` với unique index từ migration 009. Request lặp lại cùng token nhận lại đúng ID/code của đơn đã commit, không insert detail hoặc gửi email lần hai; token chỉ được xoay sau khi đã tạo token trang cảm ơn. PHP session lock, CSRF và persistent rate limit vẫn là các lớp bảo vệ bổ sung.

Regression: `scripts/test-checkout-atomicity.php` kiểm happy path, partial detail failure, missing product, shutdown giữa insert, double-submit model, replay token bền vững, cleanup count và orphan. `scripts/test-commerce-transaction.php` kiểm engine/index idempotency và chứng minh `ROLLBACK` xóa thật cả order lẫn detail mà không đổi tổng dữ liệu.
