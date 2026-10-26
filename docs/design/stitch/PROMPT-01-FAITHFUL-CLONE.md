# Stitch — PROMPT 01: V1 clone chuẩn nguồn

Project: **Phuc Thinh Solar | UI Master**.
Section: **01 / V1 Faithful Clone**.
Source of truth: https://phucthinhsolar.com/ và các HTML capture/screenshot đính kèm.

**Nhiệm vụ:** dựng lại đúng website Phúc Thịnh Solar hiện tại trên canvas Stitch, không redesign, không làm 'đẹp hơn' trong pha này.

## Luật bắt buộc
- Chế độ **REFERENCE-LOCKED, HIGH FIDELITY**: giữ nguyên mọi thành phần như nguồn.
- Toàn bộ text UI bằng **tiếng Việt**, tuyệt đối không tự động dịch sang tiếng Anh. Giữ ngữ nghĩa, ngữ điệu, chính tả, danh mục, tên và mã sản phẩm thật.
- Giữ nhận diện logo vàng kim, xanh navy, nền trắng; dùng ảnh/banners thật từ nguồn; không tạo logo giả, ảnh AI tùy tiện, sản phẩm, giá hoặc thông số giả.
- Bố cục phải trùng: header/nav/mega menu, banner, grid, spacing, chiều rộng nội dung, kiểu chữ, kích thước card, breadcrumb, lọc/sắp xếp, footer, CTA nổi, menu mobile.
- Không thêm/xóa/đổi vị trí các module vì sở thích thiết kế; không đưa style Apple hay xu hướng mới vào V1.
- Bảo toàn các trạng thái UI có thể kiểm chứng: hover/focus dropdown, responsive menu, bộ lọc, tìm kiếm, hình/thumbnail sản phẩm, chuyển tab, phân trang, hotline, Zalo, giỏ hàng/nhận tư vấn.
- Với nội dung/ảnh không lấy được, thêm marker `SOURCE_MISSING` vào ghi chú (không tự sáng tác thay thế).
- Không chỉnh sửa hay deploy website đang chạy. Stitch chỉ là môi trường thiết kế.

## Thiết kế thành các màn hình/khung riêng
1. Global header + navigation + desktop mega menu.
2. Home / desktop 1440 và 1280; mobile 390 và 375.
3. Product listing và danh mục có sidebar/filter/sort/pagination.
4. Product detail: gallery, giá, mã SP, thông số, bảo hành, CTA, sản phẩm liên quan.
5. Giải pháp, dự án, kiến thức/bài viết, giới thiệu, hợp tác đại lý, liên hệ.
6. Global footer, nút nổi, các menu, tìm kiếm và trạng thái tương tác.

## QA / Điều kiện chấp nhận
- Mỗi frame ghi rõ URL nguồn, viewport, ngày capture và trạng thái QA.
- So sánh screenshot trước/sau cùng viewport; kiểm tra sections/chiều cao/layout/text/images/card count/CTA.
- Ưu tiên pixel-level fidelity trước aesthetics, không khẳng định 1:1 nếu chưa xác minh.
- Nếu screenshot đầu vào bị dịch sang tiếng Anh, đối chiếu nội dung HTML gốc tiếng Việt và sửa lại trước khi duyệt.
- Khóa bản clone đã nghiệm thu, duplicate bản này để phát triển V2.

**Chỉ hoàn tất bước V1 khi có bộ màn hình clone có thể xem trực tiếp trên Stitch và bảng đánh giá sai khác.**
