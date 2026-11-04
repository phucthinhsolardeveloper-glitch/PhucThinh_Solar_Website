# ADR 0001: Trang chủ product-first và kiểm soát mật độ trang trí

- Ngày: 2026-09-15
- Owner: Chủ project
- Trạng thái: Đã duyệt

> Phạm vi: các quyết định homepage tại mục 1–2 đã được ADR 0006/0009 điều chỉnh;
> quy tắc radius ở mục 8 được ADR 0010 supersede. Mục 3–7 chỉ còn giá trị khi
> phù hợp với cấu trúc, dữ liệu và owner direction hiện hành. Đây là quyết định
> lịch sử, không phải bản thiết kế bắt buộc cho trang chủ hiện nay.

## Bối cảnh

Trang chủ cũ đưa nhiều nội dung giới thiệu doanh nghiệp, giải pháp, quy trình và dự án vào cùng một luồng. Tiêu đề section còn dùng eyebrow nhỏ và gạch trang trí, làm tăng khoảng trống nhưng không hỗ trợ người dùng tìm sản phẩm. Chủ project yêu cầu bám nhịp bán hàng của Kitawa, trong khi vẫn giữ nhận diện và dữ liệu thật của Phúc Thịnh Solar.

## Quyết định

1. Trang chủ không render Hero và ưu tiên theo thứ tự: banner/danh mục nổi bật, thanh danh mục, các dải sản phẩm, chứng nhận, thương hiệu hợp tác, đại lý, tin tức và CTA.
2. Giới thiệu công ty, giải pháp chi tiết, quy trình và dự án không render ở trang chủ; người dùng truy cập các hub chuyên biệt qua menu/header.
3. Danh mục sản phẩm luôn là một rail ngang, không xuống nhiều hàng. Thanh scrollbar thị giác bị ẩn; desktop kéo chuột/trackpad, mobile vuốt chạm.
4. Section chỉ giữ một tiêu đề chính. Không dùng eyebrow hoặc gạch trang trí nếu không truyền thêm trạng thái, thông tin hoặc hành động.
5. Badge như `HOT`, ngày bài viết, thông số, giá/báo giá và CTA được giữ vì có giá trị chức năng.
6. Banner chiến dịch, danh mục, sản phẩm, chứng nhận, logo đối tác và tin tức phải lấy từ Admin. Nếu chưa có banner chiến dịch, dùng danh mục nổi bật làm fallback.
7. Header không hiển thị mục Trang chủ; logo đảm nhiệm điều hướng về `/`. Nhãn menu `/ve-chung-toi/` là “Giới thiệu”, URL không đổi.
8. (Đã supersede) Bản ghi ban đầu giới hạn card/khung/nút chữ ở radius 2–5px.

## Hệ quả

- Trang dài hơn ở phần catalog nhưng ngắn hơn ở nội dung doanh nghiệp không liên quan trực tiếp đến mua hàng.
- Ảnh thumbnail trong ba dải sản phẩm được tải bằng `src` để tránh card trắng do lazy-loader legacy bỏ qua section sâu.
- Nội dung các hub Về chúng tôi, Giải pháp và Dự án không bị xóa hay đổi URL.
- Template và dữ liệu Hero được giữ nguyên để rollback, nhưng không tạo HTML/khoảng trống trên trang chủ.

## Rollback

Gỡ `home-retail-v3.css`, bỏ dòng nạp CSS theo source `index`, khôi phục `templates/index/index_tpl.php` và `sources/index.php` từ commit trước thay đổi. Không cần rollback database vì triển khai không ghi/xóa dữ liệu.
