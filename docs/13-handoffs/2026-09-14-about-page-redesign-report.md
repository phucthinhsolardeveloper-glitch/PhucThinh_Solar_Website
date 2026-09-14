# Bàn giao redesign trang Về chúng tôi — 2026-09-14

## Phạm vi và kết quả

- Chỉ thay giao diện nguồn `about`, phục vụ hai route hiện hữu `/ve-chung-toi/` và `/gioi-thieu/`.
- Không sửa template header, footer, homepage hoặc route public.
- Trang mới gồm đúng sáu phần: câu chuyện, con người, lý do tin chọn, tầm nhìn/sứ mệnh/giá trị, chứng nhận và CTA liên hệ.
- Loại khỏi trang About dải logo đối tác, sản phẩm, dự án, giải pháp, tin tức, chính sách bán hàng và form liên hệ lớn.
- Responsive không dùng cuộn ngang. Trên mobile, các section chia đôi theo thứ tự tiêu đề → ảnh → nội dung.

## Mapping dữ liệu

| Khu vực | Nguồn hiện hữu | Cách dùng |
|---|---|---|
| Câu chuyện/timeline | `table_news`, type `lich-su` | Intro, ảnh thật, mô tả ngắn và năm cột mốc |
| Con người | `ly-do-chon-chung-toi`, `su-menh`, ảnh `photo/adv` mở modal tư vấn | Tái dùng nội dung/ảnh công ty; mô tả ngắn trong Admin được ưu tiên |
| Lý do tin chọn | `ly-do-chon-chung-toi`, `cam-ket` | Chỉ chọn nội dung phù hợp About; không đưa giao hàng/thanh toán vào |
| Kim chỉ nam | `table_news`, type `su-menh` | Tầm nhìn, Sứ mệnh và ba từ khóa thật `Tâm`, `Tầm`, `Tình` |
| Chứng nhận | `table_photo`, type `chungnhan` | Hiện bốn card nổi bật, đủ năm ảnh trong photobox hiện hữu |
| CTA | `$optsetting` và `#ptsContactModal` | Giữ hotline, Zalo và modal tư vấn hiện tại |

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
- `tools/visual-tests/tests/about-page.spec.js`

## Kiểm thử

- PHP 7.4 lint toàn bộ PHP thay đổi: pass.
- `node --check assets/js/apps.js`: pass.
- `git diff --check`: pass.
- `LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh`: pass toàn bộ security/Admin/CRUD/form/commerce/migration/backup-restore smoke.
- Playwright `about-page.spec.js` + `public-pages.spec.js`: 19 pass, 1 desktop skip có chủ đích.
- Đã kiểm tra desktop và Pixel 7: không ảnh hỏng, không lỗi JS, không request local lỗi, không overflow ngang.
- Chứng nhận mở photobox hiện hữu và có đủ năm ảnh; CTA mở đúng hotline, Zalo và `#ptsContactModal`.

## Deploy và rollback

- Chưa deploy production và không ghi đè dữ liệu production.
- Deploy theo quy trình release hiện hữu; CSS About chỉ được nạp khi `$source === 'about'`.
- Không có migration DB bắt buộc.
- Rollback an toàn bằng revert commit triển khai này; dữ liệu cũ không bị xóa nên trang/template cũ có thể khôi phục nguyên trạng.

## Rủi ro còn lại

- Timeline chỉ có một mốc do dữ liệu xác thực hiện tại; cần chủ project bổ sung các mốc thật trong Admin nếu muốn timeline bốn điểm như mockup.
- Các mô tả fallback ngắn được dùng khi trường mô tả Admin còn trống. Khi Admin nhập `Mô tả`, frontend tự ưu tiên nội dung đã quản lý.
