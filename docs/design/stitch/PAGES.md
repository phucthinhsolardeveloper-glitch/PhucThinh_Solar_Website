# Route inventory và lịch chụp clone

Trạng thái (2026-10-26): chỉ đánh dấu CAPTURED khi đã có file HTML từ CLI. Captured không phải đã import Stitch.

| Ưu tiên | Mẫu màn hình | URL nguồn | Desktop HTML | Mobile | Stitch QA |
| --- | --- | --- | --- | --- | --- |
| P0 | Trang chủ | https://phucthinhsolar.com/ | CAPTURED | TODO | TODO |
| P0 | Danh mục đèn NLMT | https://phucthinhsolar.com/danh-muc-san-pham/den-nlmt/ | CAPTURED | TODO | TODO |
| P0 | Chi tiết sản phẩm JD-18500 | https://phucthinhsolar.com/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-18500-500w | CAPTURED | TODO | TODO |
| P0 | Tìm kiếm + mega menu + các state | Hành vi thực tế header | TODO | TODO | TODO |
| P1 | Tất cả sản phẩm | https://phucthinhsolar.com/san-pham/ | CAPTURED | TODO | TODO |
| P1 | Giải pháp | https://phucthinhsolar.com/giai-phap/ | TODO | TODO | TODO |
| P1 | Dự án | https://phucthinhsolar.com/du-an-da-thuc-hien/ | TODO | TODO | TODO |
| P1 | Kiến thức / bài viết | https://phucthinhsolar.com/tin-tuc/ | TODO | TODO | TODO |
| P1 | Giới thiệu | https://phucthinhsolar.com/ve-chung-toi/ | TODO | TODO | TODO |
| P1 | Đăng ký đại lý / Liên hệ | link thật trong site | TODO | TODO | TODO |
| P2 | Giỏ hàng, hỗ trợ, form, empty/loading states | hành vi thực tế | TODO | TODO | TODO |

## Sai khác phát hiện cần xử lý
- CLI screenshot của homepage đang hiển thị nội dung UI tiếng Anh mặc dù HTTP HTML và công cụ đọc website cho ra tiếng Việt. **Không dùng bản chụp tiếng Anh làm chuẩn text V1**; cần tái chụp trình duyệt cấu hình Vietnamese hoặc điều chỉnh locale trước khi tạo màn Stitch.
- URL chi tiết JD-T300 từ kết quả crawl cũ trả về trang 404 khi chụp trực tiếp (visibleTextLength=16). **Bỏ kết quả này; tìm PDP live thực tế**.
- Screenshot do CLI tạo đang là viewport, phải bổ sung ảnh full-page/mobile và test menu cho baseline QA.
- Status đăng nhập Stitch từ CLI: `authenticated=false`, `STITCH_API_KEY` chưa có; chưa thể upload canvas.

## Chính sách QA
Bắt buộc so cùng route + cùng viewport + ảnh thật và nội dung VI; check cả states menu/lọc/modal. Không đánh dấu PASS chỉ vì HTML capture đã tạo.
