# Trạng thái chuyển đổi Laravel

Đọc file này trước khi mở lại bất kỳ module nào. Mục đã **CHỐT** không đọc/rà lại, trừ khi có lỗi mới được báo.
Làm xong phần nào: test → commit → chuyển dòng sang CHỐT kèm commit → xóa file tạm của phần đó.

## Đang làm
- [ ] Nhập/xuất sản phẩm đầy đủ thay 3 công cụ mỏng. Đã đọc xong `product_bulk.php`, không cần đọc lại toàn bộ; code chưa viết. Cần có:
  - Cột: thao tác, ID, mã, tên, đường dẫn, danh mục, thương hiệu, kiểu trang, công suất, giá gốc/KM, cờ 1/0, mô tả, nội dung, nổi bật, ứng dụng, hướng dẫn, FAQ dạng markdown, chính sách, video, toàn bộ thông số, thông số khác `Mã | Tên | Giá trị`, ảnh, thư viện, ảnh Shopping, SEO. Nhận cả nhãn cột của file PHP đã phát hành.
  - Nhận diện: ID, mã duy nhất, slug trùng cho chọn cập nhật/tạo/bỏ qua; thao tác auto/create/update/upsert/archive/skip (cả tiếng Việt).
  - Ô trống giữ nguyên, `__CLEAR__` xóa; so sánh ba chiều với sheet ẩn `_HeThong`, xung đột chọn ghi đè từng ô; bỏ ô không đổi.
  - Danh mục/thương hiệu theo tên, thương hiệu mới cho chọn tạo/bỏ trống/ghép; gợi ý tên ảnh gần đúng; kiểm YouTube, giá, profile, nhãn nguồn trong thông số.
  - Xuất XLSX/CSV, mẫu trống, lọc theo tình trạng dữ liệu; danh sách chọn, cột khóa, sheet HuongDan + DanhMuc; chống công thức CSV.
  - Xem trước đếm tạo/sửa/ẩn/không đổi, xác nhận riêng khi có SP bị ẩn, hạn 30 phút, kiểm lại khi áp dụng, một transaction.
  - ZIP ảnh `MA-01.jpg`: 01 là ảnh chính (không thay ảnh có sẵn), còn lại vào thư viện; tối đa 200 ảnh, 20 MB.
- [ ] Chưa kiểm chứng vì Docker mất kết nối (WSL interop). Code cột `feed_image` chưa commit; khi Docker chạy lại, trên `pts_app` và `pts_app_test`:
  `alter table products drop column promo_frame, drop column is_promo, drop column certificate; update migrations set migration='2026_11_10_000600_add_feed_image_to_products' where migration='2026_11_10_000600_add_feed_and_promo_to_products';` rồi chạy phpstan + test và commit.

## Chưa làm (theo thứ tự)
- [ ] Chờ chủ website quyết: có chạy Google Shopping không (có thì làm feed, không thì bỏ cột `feed_image`)
- [ ] Chờ chủ website quyết: khung khuyến mãi theo chiến dịch (bản cũ có code, dữ liệu 0) và chứng nhận (dữ liệu 0, trang công khai không đọc): chưa đưa vào schema
- [ ] Xem trước trên Nuxt cho Giải pháp / Dự án / Chính sách
- [ ] Task 5: chỉnh trang theo khối (Home + About POC)
- [ ] Thứ tự sản phẩm và bài viết vẫn gõ số

## CHỐT
| Phần | Commit | Ghi chú |
|---|---|---|
| Outbox email, hàng đợi | bff35f79 | |
| Vòng đời đơn hàng, nhật ký thay đổi | 8718c11d | |
| Dọn tên trường và chú thích PHP cũ | dbea5695, f8d64fdd, 7768dab0 | |
| Kho media | a3d52638 | |
| Bản nháp & lịch sử chung | 255ba3a3 | |
| Bảng Admin vừa khung, audit sâu | c8e5f8e3 | |
| Slug + chuyển hướng 301, tỉnh/phường dự án, dọn tỉnh cũ, settings, resolver, published_at | cdfccdda | |
