# Bàn giao Theme V2 foundation - 2026-08-27

## Mục tiêu

Port ngôn ngữ giao diện của `solar-light-web` sang website PHP hiện tại, giữ nguyên URL, dữ liệu, route và luồng backend. Chỉ triển khai trên Docker local, chưa thay đổi production.

## Phạm vi đã hoàn thành

- Đọc lịch sử thay đổi của Claude và đối chiếu các component React tham chiếu.
- Tạo lớp CSS độc lập `assets/css/theme-v2.css`, nạp sau CSS legacy để dễ tháo bỏ.
- Nâng top bar, header, logo wordmark, Home Hero và CTA.
- Nâng trang danh mục: Hero, trust strip, sidebar/filter, sort, grid và product card.
- Nâng trang chi tiết: gallery, buy box, giá/khuyến mãi, nút mua, thông số và khu nội dung.
- Giữ nguyên handler giỏ hàng; nút tròn trên card gọi lại API thêm hàng hiện có.

## File chính thay đổi

- `templates/layout/css.php`
- `templates/layout/header.php`
- `templates/layout/slide.php`
- `templates/index/index_tpl.php`
- `templates/product/product_tpl.php`
- `templates/product/product_detail_tpl.php`
- `api/fillter_service.php`
- `api/product2.php`
- `assets/css/theme-v2.css`

## Kiểm thử local

- PHP 7.4 lint cho toàn bộ template/API thay đổi.
- HTTP 200: trang chủ, danh mục đèn NLMT, chi tiết sản phẩm, giới thiệu, liên hệ và giỏ hàng.
- Commerce smoke: thêm sản phẩm bằng API rồi xác nhận sản phẩm xuất hiện trong giỏ theo cùng session.

## Việc tiếp theo

- Đối chiếu trực quan desktop/mobile và sửa spacing, typography, ảnh thật.
- Thay các section trang chủ legacy còn lại bằng markup Theme V2.
- Chuẩn hóa giao diện các trang nội dung/tin tức/dự án.
- Chỉ triển khai production sau khi có backup, checklist deploy và phê duyệt rõ ràng.

## Ghi chú Docker build context

Build sạch gửi khoảng 567 MB vì Dockerfile copy toàn bộ `public_html`. Các phần lớn nhất gồm `assets` khoảng 243 MB (trong đó `assets/wp-content` 187 MB), `upload` 157 MB và `thumbs` 75 MB. DB backup chỉ khoảng 5,3 MB; file ZIP 505 MB đã được `.dockerignore` loại trừ.

Đã thử loại media khỏi context và bind-mount khi chạy: context giảm còn khoảng 135 MB, nhưng Docker Desktop trên máy hiện tại báo thiếu WSL distro mount service (`ubuntu.sock`). Thử nghiệm đã được hoàn nguyên; cấu hình Docker ổn định ban đầu được giữ nguyên.

Hướng tối ưu an toàn về sau: sửa WSL integration của Docker Desktop, hoặc tách media thành image/volume khởi tạo riêng rồi mới loại khỏi build context.

## Rollback local

Gỡ khai báo `theme-v2.css` trong `templates/layout/css.php`, sau đó hoàn nguyên các template/API liệt kê ở trên. Không cần rollback schema hoặc dữ liệu.
