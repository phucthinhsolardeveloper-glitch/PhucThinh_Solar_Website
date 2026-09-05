# Bàn giao nhập ảnh sản phẩm ORIANA ngày 2026-09-05

## Phạm vi

- Nguồn: thư mục `ĐÈN ORIANA` ở project local.
- Chỉ nhập vào Docker local; chưa thay đổi production.
- Backup trước khi nhập: `backup/local/20260905-153505`.
- Script đối chiếu có chặn môi trường ngoài local: `scripts/import-oriana-images-local.php`.

## Kết quả

- 102 file ảnh nguồn, tương ứng 98 nội dung ảnh duy nhất.
- 101 ảnh đại diện được gán cho sản phẩm.
- 56 ảnh phụ được thêm vào album sản phẩm.
- Tổng cộng 157 file đã chuẩn hóa trong `upload/product`, tên dạng `oriana-product-{id}-{thứ_tự}.jpg`.
- Ảnh sau chuẩn hóa đều là JPEG hợp lệ, không vượt 1920×1920 và 5 MB.
- Chạy lại dry-run nhận đủ 157 mapping là đã tồn tại, không tạo bản ghi trùng.

## Quy tắc đối chiếu đáng chú ý

- Ảnh tổng thể của đúng model dùng làm ảnh đại diện; ảnh cận cảnh, phụ kiện và ảnh thực tế dùng làm album.
- Ảnh trụ cổng có nhiều kích thước được dùng cho mọi biến thể cùng mã model.
- Hai ảnh nguồn `PL-0506` và `PL-0507` được đối chiếu sang mã sản phẩm hiện có `SL-0506` và `SL-0507`.
- Sản phẩm `DB01-1` đã có ảnh đại diện nên giữ nguyên ảnh cũ; năm ảnh mới chỉ được bổ sung vào album.
- Bốn ảnh trong thư mục `DB-OT1000W` thực tế là DB60100/200/300/400 và trùng byte-for-byte với ảnh trong `DB60`; không gán chúng cho DB-OT1000 và không nhập bản trùng.
- File shortcut `PHỤ KIỆN.lnk` không phải ảnh và không được xử lý.

## Xác minh

- Trang mẫu thuộc DB60, DB3 PRO, DB88, DB6300S, DB-UFO, DB01-1 và đèn trụ cổng trả HTTP 200 và render đúng tên file đã nhập.
- Thumbnail 600×600 mẫu của ảnh chính, ảnh phụ và đèn trụ cổng trả HTTP 200 với MIME `image/jpeg`.
- Playwright `product detail keeps gallery and quote-only CTA`: 2/2 đạt (desktop và mobile).

## Rollback

- Rollback đầy đủ local bằng backup `backup/local/20260905-153505` theo quy trình restore local hiện có.
- Không xóa trực tiếp thư mục ảnh gốc `ĐÈN ORIANA`.
- Trước khi triển khai production cần xin phép riêng, chuyển đủ file upload và áp dữ liệu theo bản backup/mapping đã xác minh.

