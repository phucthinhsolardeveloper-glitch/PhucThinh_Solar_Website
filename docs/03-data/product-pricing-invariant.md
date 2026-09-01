# Invariant giá sản phẩm

Cập nhật: 2026-09-01. Phạm vi: Admin local, chưa triển khai production.

`libraries/admin_product_pricing.php` là validator và normalizer dùng chung cho hai đường ghi giá sản phẩm:

- CRUD thủ công trong `administrator/sources/product.php::saveMan()`.
- Preview/validate bulk import trong `administrator/sources/product_bulk.php`.

Quy tắc:

- `regular_price` và `sale_price` phải là số không âm; ô trống được chuẩn hóa thành 0.
- Khi cả hai giá lớn hơn 0, `sale_price` không được lớn hơn `regular_price`.
- `discount` không được tin từ input; hệ thống luôn tính lại bằng `round((regular_price - sale_price) / regular_price * 100)` và giới hạn trong khoảng 0–100.
- Khi thiếu một trong hai giá hoặc hai giá bằng nhau, `discount = 0`.

Form Admin chặn lưu nếu quan hệ giá không hợp lệ. Bulk import giữ hành vi chặn cũ nhưng dùng cùng helper, tránh hai đường ghi phát sinh invariant khác nhau.

Characterization và regression nằm trong:

- `scripts/test-product-bulk.php`.
- `scripts/test-product-pricing-admin-http.php`.

Smoke HTTP dùng account/product fixture tạm, submit đúng form Admin và cleanup sau test. Tùy chọn `--repair-product=<id>` chỉ dùng khi có phê duyệt sửa một bản ghi local cụ thể qua form Admin; không dùng raw SQL để sửa product.
