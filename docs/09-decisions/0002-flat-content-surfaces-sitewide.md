# ADR 0002: Bề mặt phẳng và bỏ khung trang trí trên toàn website

- Ngày: 2026-09-15
- Owner: Chủ project
- Trạng thái: Đã duyệt

## Bối cảnh

Nhiều đợt redesign trước dùng lặp lại nền trắng trên nền trắng/xám nhạt, border,
bo góc và shadow cho hầu hết card. Cách phân tầng này tạo cảm giác nội dung bị
đóng trong nhiều hộp, đặc biệt ở card sản phẩm, danh mục, breadcrumb và các hub.

## Quyết định

1. Card nội dung, sản phẩm, danh mục, bài viết và khối thông tin dùng nền trong
   suốt để hòa vào nền section; không có border, shadow hoặc bo góc trang trí.
2. Dùng khoảng trắng, lưới, typography, ảnh và thay đổi màu chữ khi hover để tạo
   phân cấp. Nền màu chỉ dành cho section/band lớn có vai trò nội dung rõ ràng.
3. Bỏ eyebrow, gạch dưới tiêu đề, đường nối và dấu phân cách thuần trang trí.
4. Breadcrumb không có đường viền phía dưới.
5. Vẫn giữ border khi nó truyền đạt thao tác hoặc cấu trúc: input/select/textarea,
   bảng dữ liệu, focus-visible, trạng thái chọn, modal và control cần ranh giới.
6. Hệ sinh thái thiết bị giữ một hàng. Ở desktop rộng, 9 danh mục được thu gọn
   để hiện đủ; màn hình hẹp tiếp tục cuộn ngang và ẩn scrollbar thị giác.

## Triển khai

`assets/css/flat-surface-system.css` được nạp sau toàn bộ CSS public để áp dụng
nhất quán cho legacy theme và các hub. Các ngoại lệ chức năng được ghi rõ trong
stylesheet; không dùng rule tổng quát kiểu `* { border: 0; }` vì sẽ làm mất trạng
thái form, bảng và khả năng nhận biết focus.

## Rollback

Bỏ dòng nạp `flat-surface-system.css` trong `templates/layout/css.php` và xóa
stylesheet này. Không có thay đổi database, URL hoặc dữ liệu Admin.
