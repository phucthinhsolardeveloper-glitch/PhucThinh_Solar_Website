# Chuẩn hóa danh mục sản phẩm ngày 2026-09-22

## Kết quả

Local hiện có đúng 9 danh mục cấp 1 public theo thứ tự:

1. Đèn năng lượng mặt trời.
2. Đèn sân vườn.
3. Trụ đèn.
4. Quạt năng lượng mặt trời.
5. Tấm pin năng lượng mặt trời.
6. Biến tần (Inverter).
7. Pin lưu trữ.
8. Bơm nước năng lượng mặt trời.
9. Thiết bị & phụ kiện điện mặt trời.

`Hệ khung & phụ kiện tấm pin` được bỏ khỏi giao diện bằng cách gỡ cờ hiển thị,
không xóa bản ghi. Ba danh mục cấp 2 `Thanh ray`, `Kẹp tấm pin` và
`Kẹp mái Kliplock / Seamlock` được chuyển trực tiếp sang `Tấm pin năng lượng
mặt trời`; 12 sản phẩm đi theo danh mục cấp 2 tương ứng. Không có và không tạo
danh mục cấp 3.

## An toàn dữ liệu và URL

- Migration: `migrations/047_normalize_product_top_categories.php`.
- Mặc định migration chỉ dry-run; `--apply` mới ghi dữ liệu.
- Giữ nguyên ID và slug của 9 danh mục còn dùng và ba danh mục cấp 2 được
  chuyển; chỉ chuẩn hóa tên và `numb`.
- URL cấp 1 `he-thong-khung-nhom` và ba URL con cũ chuyển 301 sang đích mới.
- Scope campaign ở danh mục Hệ khung cũ được đổi sang ba danh mục cấp 2, tránh
  vô tình áp chiến dịch cho toàn bộ nhóm Tấm pin.
- Backup trước khi áp local: `backup/local/20260922-150810`; checksum đạt.
- Rollback dữ liệu: restore backup này. Không xóa bản ghi Hệ khung để giữ đường
  lui an toàn.

## Xác minh local

- PHP 7.4 lint migration: đạt.
- Migration apply và dry-run lại sau apply: đạt, trạng thái đích idempotent.
- DB: 9 danh mục public đúng tên/thứ tự; 3 danh mục cấp 2 và 12 sản phẩm khớp
  cha mới; không có bản ghi cấp sâu hơn dưới Hệ khung.
- Header desktop, menu mobile và thanh danh mục landing: cùng thứ tự 1–9.
- Route trang chủ, landing sản phẩm, Tấm pin và Thanh ray: HTTP 200.
- URL Hệ khung cấp 1/cấp 2 cũ: HTTP 301 đúng đích.

## Production

Chưa deploy code và chưa chạy migration 047 trên production. Khi triển khai
phải dry-run trên dữ liệu production, tạo backup có checksum, chạy `--apply`,
xóa cache ứng dụng và kiểm lại menu, landing, URL cũ. Nếu hậu kiểm sai, dừng
website ghi dữ liệu và restore backup gần nhất.
