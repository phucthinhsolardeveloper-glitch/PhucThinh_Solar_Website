# ADR 0004: Gom điều hướng chính thành năm nhóm có thứ bậc

- Ngày: 2026-09-15
- Owner: Chủ project
- Trạng thái: Đã duyệt

## Bối cảnh

Header cũ đặt `Giới thiệu | Giải pháp | Dự án | Tin tức | Đại lý | Liên hệ`
ngang hàng. Đây là cấu trúc theo loại trang kỹ thuật hơn là theo nhu cầu khách
hàng; `Tin tức`, `Đại lý`, `Liên hệ` chiếm cùng trọng lượng với các trụ cột bán
hàng dù search, hotline và form tư vấn đã luôn hiện trên website.

## Bằng chứng

- Dữ liệu local có bốn nhóm bài thực: Tin mới nhất, Tin truyền thông, Tin tuyển
  dụng và Kiến thức sản phẩm; nhãn `Kiến thức` bao phủ chúng tốt hơn `Tin tức`.
- Trang Đại lý đã có form đăng ký, năng lực phân phối và chính sách hỗ trợ, đủ
  để làm landing `Hợp tác` mà không cần tạo một trang rỗng mới.
- Trang Giới thiệu đã có lịch sử, tầm nhìn/sứ mệnh và chứng nhận; trang Liên hệ
  đã có chi nhánh và form, phù hợp làm các đích con của cùng nhóm doanh nghiệp.

## Quyết định

1. Nút `Danh mục sản phẩm` đứng riêng ở đầu thanh điều hướng.
2. Điều hướng chính chỉ còn năm nhóm:
   `Giới thiệu | Giải pháp | Dự án | Kiến thức | Hợp tác`.
3. `Kiến thức` dùng landing `/tin-tuc/` và tự lấy danh mục có bài đang hiển thị
   từ Admin; không dựng menu con cho danh mục rỗng.
4. `Hợp tác` dùng landing `/dai-ly` với anchor cho đăng ký, năng lực phân phối,
   hỗ trợ đại lý và liên hệ kinh doanh.
5. `Giới thiệu` gom câu chuyện, lịch sử, tầm nhìn/sứ mệnh, chứng nhận, chi nhánh
   và liên hệ. Desktop và mobile dùng cùng cấu trúc thông tin.
6. Không xóa hoặc redirect `/tin-tuc/`, `/dai-ly`, `/lien-he/`: đây vẫn là các
   landing chức năng và URL đã công bố. “Giảm trang” ở lớp điều hướng nghĩa là
   không tạo thêm landing trùng lặp và không cho từng loại nội dung chiếm một
   mục top-level.
7. Header desktop được giảm chiều cao nhưng vẫn giữ search, hotline và lối vào
   danh mục; Dự án tiếp tục là mục top-level vì là bằng chứng năng lực bán hàng.

## Phương án đã cân nhắc

- Xóa/redirect trang Liên hệ vào Giới thiệu: làm mất form chuyên dụng, bản đồ và
  đích chuyển đổi đang được nhiều CTA dùng.
- Gộp Dự án vào Kiến thức: làm yếu lối vào bằng chứng thi công.
- Tạo landing `/kien-thuc/` và `/hop-tac/` mới: phát sinh trang trùng nội dung,
  migration URL và SEO không cần thiết.

## Hệ quả

- Header ít mục hơn, dễ quét và mở rộng B2B dưới nhóm Hợp tác.
- URL kỹ thuật cũ có thể khác nhãn public (`/tin-tuc/` hiển thị Kiến thức); đây
  là lựa chọn tương thích ngược có chủ ý.
- Anchor trong dropdown là giao diện public và phải được giữ khi đổi template.

## Rollback

Khôi phục markup `menu.php`/`mmenu.php`, kích thước cũ trong `header-v3.css` và
nhãn fallback trong `router.php`/`news_hub.php`. Xóa các anchor mới không ảnh
hưởng dữ liệu; không có database migration cho quyết định này.
