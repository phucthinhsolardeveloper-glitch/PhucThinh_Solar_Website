# Bàn giao redesign trang Về chúng tôi — 2026-09-14

## Phạm vi và kết quả

- Chỉ thay giao diện nguồn `about`, phục vụ hai route hiện hữu `/ve-chung-toi/` và `/gioi-thieu/`.
- Không sửa template header, footer, homepage hoặc route public.
- Bản kết hợp giữ bố cục ảnh–chữ xen kẽ từ bản trước redesign (`70e0f89`): giới thiệu, lịch sử riêng, con người và ba điểm nhấn, bốn lý do lựa chọn, khối navy tầm nhìn/sứ mệnh/giá trị, ba chứng nhận ngang và CTA gọn.
- Câu chuyện hiển thị tối đa hai câu mở đầu và phần nội dung tiếp theo khi có; mỗi mốc lịch sử hiển thị tối đa hai câu từ Nội dung Admin. Dấu chấm lửng Unicode được nhận là điểm kết câu để không kéo theo tuyên bố dài ngoài đoạn chọn.
- Loại khỏi trang About dải logo đối tác, sản phẩm, dự án, giải pháp, tin tức, chính sách bán hàng và form liên hệ lớn.
- Responsive không dùng cuộn ngang. Trên mobile, các section chia đôi theo thứ tự tiêu đề → ảnh → nội dung.

## Mapping dữ liệu

| Khu vực | Nguồn hiện hữu | Cách dùng |
|---|---|---|
| Câu chuyện/timeline | `table_news`, type `lich-su` | Intro, ảnh thật, mô tả ngắn và năm cột mốc |
| Con người | `news/about-team` | Nhãn, tiêu đề, mô tả, nội dung, ảnh riêng; fallback dữ liệu cũ khi chưa nhập |
| Ba điểm nhấn con người | `news/about-principle` | Tối đa ba mục; tiêu đề, mô tả, ảnh icon, thứ tự và Hiển thị; có fallback |
| Lý do tin chọn | `news/ly-do-chon-chung-toi` | Tối đa bốn mục chọn bằng cờ `about`; fallback các mục phù hợp hiện hữu |
| Kim chỉ nam | `news/su-menh` | Ba record theo thứ tự; tiêu đề ngắn, mô tả và icon do Admin quản lý |
| Chứng nhận | `photo/chungnhan` | Ba card ngang ưu tiên `noibat`, tất cả ảnh Hiển thị trong photobox |
| CTA | `photo/about-cta`, `$optsetting`, `#ptsContactModal` | Tiêu đề, mô tả, ảnh nền, link riêng; hotline/Zalo dùng thiết lập chung |

Hai static block `tom-tat-gioi-thieu` và `su-menh` không được dùng vì audit xác nhận chúng chứa nội dung demo của doanh nghiệp khác.

DB hiện chỉ có một mốc lịch sử xác thực được năm là **2019**. Frontend chỉ hiện mốc này, không tự dựng 2021/2023/2026 theo mockup. Admin đã được mở trường `Năm cột mốc` trên type `lich-su`; có thể bổ sung tối đa bốn record thật theo thời gian.

Không thêm bảng hay page builder. Chỉ mở các cột schema đã tồn tại:

- `desc*` cho `lich-su`, `su-menh`, `ly-do-chon-chung-toi`;
- `solieu` với nhãn `Năm cột mốc` cho `lich-su`;
- `desc*` cho ảnh `chungnhan`.

## File thay đổi

- `domains/phucthinhsolar.com/public_html/sources/about.php`
- `domains/phucthinhsolar.com/public_html/templates/about/about_tpl.php`
- `domains/phucthinhsolar.com/public_html/assets/css/about-v2.css`
- `domains/phucthinhsolar.com/public_html/templates/layout/css.php`
- `domains/phucthinhsolar.com/public_html/assets/js/apps.js`
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-news.php`
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-photo.php`
- `domains/phucthinhsolar.com/public_html/administrator/templates/news/man/man_add_tpl.php`
- `domains/phucthinhsolar.com/public_html/administrator/templates/photo/man/photo_add_tpl.php`
- `domains/phucthinhsolar.com/public_html/administrator/templates/photo/man/photo_edit_tpl.php`
- `domains/phucthinhsolar.com/public_html/libraries/config-type.php`
- `scripts/test-admin-readiness.php`
- `tools/visual-tests/tests/about-page.spec.js`

## Kiểm thử

- PHP 7.4 lint toàn bộ PHP thay đổi: pass.
- `node --check assets/js/apps.js`: pass.
- `git diff --check`: pass.
- Predeploy toàn bộ: đã pass ở vòng redesign đầu; chưa chạy lại cho bản kết hợp. Không coi kết quả cũ là chứng nhận deploy cho bản mới.
- Admin readiness vòng bổ sung: pass, bao gồm form Con người, ba điểm nhấn, icon kim chỉ nam, chọn chứng nhận và CTA.
- Public-pages sau bổ sung Admin: 14 pass. About sau kết hợp: 5 pass, 1 desktop skip có chủ đích.
- PHP 7.4 fixture render dữ liệu rỗng và lịch sử thiếu ảnh: pass, không warning; CTA fallback còn hoạt động.
- Đã kiểm tra desktop và Pixel 7: không ảnh hỏng, không lỗi JS, không request local lỗi, không overflow ngang.
- Chứng nhận mở photobox hiện hữu và có đủ năm ảnh; CTA mở đúng hotline, Zalo và `#ptsContactModal`.

## Deploy và rollback

- Chưa deploy production và không ghi đè dữ liệu production.
- Deploy theo quy trình release hiện hữu; CSS About chỉ được nạp khi `$source === 'about'`.
- Sau khi deploy code, chạy `migrations/037_grant_redesign_page_permissions.php --apply` để các nhóm vốn quản lý Lịch sử/Chứng nhận nhận quyền cho Con người, Điểm nhấn và CTA mới. Migration chỉ thêm quyền, dùng transaction và có thể chạy lặp.
- Snapshot trước kết hợp: `9132a60`, nhánh `backup/about-redesign-v1`. Công việc kết hợp trên `refactor/about-page-v2`. Nếu cần quay lại bản thử nghiệm, phục hồi chọn lọc presentation từ snapshot sau khi kiểm tra diff; giữ dữ liệu và các trường Admin.

## Rủi ro còn lại

- Timeline chỉ có một mốc do dữ liệu xác thực hiện tại; cần chủ project bổ sung các mốc thật trong Admin nếu muốn timeline bốn điểm như mockup.
- Các mô tả fallback ngắn được dùng khi trường mô tả Admin còn trống. Khi Admin nhập `Mô tả`, frontend tự ưu tiên nội dung đã quản lý.
- Chứng nhận hiện dùng ảnh và tên thật. Mô tả quan hệ đối tác/logo riêng như ảnh tham khảo chưa có dữ liệu tương ứng; không tự thêm tuyên bố đối tác từ mockup.
- Nhóm Admin: `Trang Về chúng tôi`. Các module mới chưa được seed dữ liệu; nội dung fallback vẫn hiển thị khi chưa nhập. Các nhãn section mang tính cấu trúc vẫn là chuỗi VI/EN trong source.

## Audit trước kết hợp

- Presentation: template About, CSS riêng và điều kiện nạp CSS; lấy lại hướng ảnh–chữ xen kẽ từ bản trước redesign, giữ style scoped.
- Data/Admin: source About, config news/photo/menu và ba template form Admin; giữ toàn bộ bổ sung hữu ích, không đổi schema.
- Behavior: JavaScript mở chứng nhận và CTA/modal được giữ; không sửa backend liên hệ, CSRF, reCAPTCHA hay rate limit.
- Responsive/accessibility: giữ heading, alt, button gallery và kiểm tra overflow; điều chỉnh test ảnh lazy khi Con người chuyển xuống dưới lịch sử.
- Xung đột: template cũ phụ thuộc `sumenh_title` legacy và `$partner`; phục hồi nguyên file sẽ mất các section Admin mới và tái đưa logo đối tác vào. Vì vậy chỉ tham chiếu bố cục cũ.
- Không rollback code homepage/header/footer hay route. Bộ ba con người được giữ theo yêu cầu ảnh mẫu, nên bản kết hợp có bảy section chính thay vì sáu.
