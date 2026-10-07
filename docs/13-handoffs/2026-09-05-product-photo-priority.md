# Ưu tiên sản phẩm có ảnh ở danh sách public — 2026-09-05

## Kết quả

- Thứ tự mặc định của danh sách sản phẩm là: có ảnh đại diện trước, chưa có ảnh sau.
- Trong từng nhóm vẫn giữ thứ tự cũ `numb ASC, id DESC`.
- Các lựa chọn chủ động của khách (`A–Z`, `Z–A`, giá, mới/cũ) không bị chèn thêm ưu tiên ảnh.
- Áp dụng cho trang danh mục, sản phẩm liên quan, tìm kiếm, lọc AJAX và các khối sản phẩm AJAX ở trang chủ.
- Không đổi schema, không migration và không cập nhật dữ liệu.

## Dữ liệu local được kiểm tra

- 443 sản phẩm public: 181 có ảnh đại diện, 262 chưa có ảnh.
- Toàn bộ tên file ảnh đại diện đang được ưu tiên đều tồn tại trong `upload/product`.
- Ghi chú cập nhật 2026-10-07: card, PDP, SEO, structured data và Admin đã dùng
  chung `libraries/product_images.php`. Nếu `photo` trống hoặc trỏ tới file đã
  mất, ảnh gallery đang hiển thị đầu tiên còn file thật được dùng làm fallback.
  Bộ lọc Admin “Thiếu ảnh hiển thị” chỉ báo khi không còn ảnh dùng được ở cả hai
  nguồn. Thứ tự SQL mặc định vẫn ưu tiên theo field `photo`; chưa đổi schema hay
  dữ liệu chỉ để phục vụ sắp xếp.

## Ảnh hưởng hành vi

- Tổng số sản phẩm, bộ lọc, URL/slug và số trang không đổi.
- Ranh giới giữa các trang mặc định có thể đổi vì sản phẩm có ảnh được đưa lên trước.
- `numb` chỉ điều khiển vị trí bên trong nhóm có ảnh hoặc nhóm thiếu ảnh; không kéo một sản phẩm thiếu ảnh lên trên nhóm có ảnh ở chế độ mặc định.
- Truy vấn thêm biểu thức `CASE` trong `ORDER BY`; với 443 bản ghi local, rủi ro hiệu năng thấp và không cần thêm index/schema.

## Kiểm thử

- PHP 7.4 lint cho helper và toàn bộ source/API thay đổi.
- `scripts/test-product-photo-priority.php`: kiểm tra phân nhóm, khóa phụ `numb/id`, alias SQL an toàn, file ảnh thật và dữ liệu hai nhóm.
- HTTP 200: trang danh mục, API danh mục mặc định, API lọc mặc định và API lọc A–Z.
- Playwright: 6/6 bài liên quan qua trên desktop/mobile (tìm kiếm, danh mục nổi bật trang chủ, sidebar/filter danh mục).

## Deploy và rollback

- Deploy code cùng file helper mới; xóa cache ứng dụng nếu môi trường deploy không tự làm mới cache. Chuỗi SQL cache đã đổi nên không dùng nhầm kết quả cũ.
- Rollback chỉ cần hoàn nguyên các lời gọi `publicProductDefaultOrder()` và file helper. Không cần phục hồi DB.
