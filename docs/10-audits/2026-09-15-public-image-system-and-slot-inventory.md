# Rà soát hệ thống ảnh public và vị trí cần thay — 2026-09-15

## Kết luận

Frontend đã có quy tắc tỷ lệ, cách fit và thumbnail riêng theo vai trò ảnh. Bốn
vị trí hình ảnh bán hàng chính có thể bật/tắt bằng Admin: chiến dịch Trang chủ,
banner Danh mục sản phẩm, ảnh mở đầu Liên hệ và ảnh cạnh form Đại lý. Bộ ảnh mẫu
đã đóng gói giúp giao diện không trống trong lúc chờ ảnh thật; chúng không đại
diện cho dự án, khách hàng hay chứng nhận của Phúc Thịnh Solar.

## Ma trận ảnh chuẩn

| Vị trí | File nguồn nên giao | Hiển thị | Fit / vùng an toàn |
|---|---:|---:|---|
| Ảnh chính + gallery sản phẩm | 1200×1200, 1:1 | ô vuông | `contain`; nền trắng/trong, sản phẩm trong 85% khung |
| Danh mục sản phẩm cấp 1 | 800×800, 1:1 | ô vuông nhỏ | `contain`; không chèn chữ |
| Danh mục sản phẩm cấp 2 | 1200×800, 3:2 | khối điều hướng | `cover`; chủ thể giữa |
| Chiến dịch Trang chủ | 1600×900, 16:9 | tối đa 3 card ảnh | `cover`; chi tiết trong 70% vùng giữa |
| Banner Danh mục sản phẩm | 1920×600, 16:5 | ngắt nhịp giữa nhóm | `cover`; chủ thể bên phải, vùng trái thoáng cho chữ HTML |
| Tin tức | 1600×900, 16:9 | card và bài nổi bật | `cover`; điểm chính ở giữa |
| Dự án chính + gallery | 1600×1200, 4:3 | nổi bật 4:3, card 8:5 | `cover`; chừa biên để crop card |
| CTA Dự án / Giải pháp | 1600×500, 16:5 | dải ngang | `cover`; không chèn chữ/logo vào ảnh |
| Giải pháp | 1600×1000, 8:5 | intro/card lựa chọn | `cover`; chủ thể trong 75% vùng giữa |
| Liên hệ | 1600×1000, 8:5 | cạnh thông tin công ty | `cover`; showroom/tư vấn/kỹ thuật thật |
| Đại lý + gallery đại lý | 1600×1000, 8:5 | cạnh form / lưới ảnh | `cover`; kho/showroom/trưng bày thật |
| Câu chuyện + đội ngũ | 1600×1120, 10:7 | section Về chúng tôi | `cover`; người/chủ thể trong vùng giữa |
| CTA Về chúng tôi | 1600×520 | dải ngang | `cover`; vùng chữ đủ tương phản |
| Logo thương hiệu | 600×240 | logo strip | `contain`; canvas trắng/trong, logo trong 80% vùng giữa |
| Chứng nhận | 1600×2200, dọc | tài liệu | `contain`; bản quét rõ, không crop mép |

Ảnh JPEG/WebP dùng cho ảnh chụp; PNG/WebP nền trong dùng cho logo/icon. Không
phóng ảnh nhỏ để đạt kích thước trên: phải xuất lại từ nguồn gốc. Text, CTA và
logo thương hiệu do HTML render để responsive, dễ sửa và không bị mờ.

## Vị trí Admin và hành vi bật/tắt

| Type | Số lượng | Nội dung | Khi chưa có record | Khi đã có record |
|---|---:|---|---|---|
| `home-campaign` | tối đa 3 | ảnh, tên, mô tả, link | dùng 3 ảnh mẫu đóng gói | chỉ record có **Hiển thị** xuất hiện |
| `catalog-campaign` | tối đa 3, dùng record bật đầu tiên | ảnh, tên, mô tả, link | dùng ảnh thiết bị mẫu đóng gói | CTA tư vấn cuối catalog; tắt toàn bộ record sẽ ẩn CTA |
| `contact-visual` | 1 | ảnh, tên, mô tả/link tùy chọn | dùng ảnh tư vấn mẫu | tắt **Hiển thị** sẽ bỏ hẳn ảnh mở đầu |
| `dealer-hero` | 1 | ảnh, tên | dùng ảnh phân phối mẫu | tắt **Hiển thị** sẽ bỏ hẳn ảnh cạnh form |

Các type mới nằm trong nhóm Admin tương ứng. Migration
`042_grant_site_visual_photo_permissions.php` chỉ cấp quyền cho nhóm đã có đủ
bốn quyền `adv`, không tự mở quyền cho nhóm nội dung hạn chế.

## Bộ ảnh mẫu đang dùng

Thư mục: `public_html/assets/images/site-visuals/`.

- `home-street-lighting.jpg` — 1600×900.
- `home-solar-storage.jpg` — 1600×900.
- `home-outdoor-lighting.jpg` — 1600×900.
- `catalog-street-lighting.jpg` — 1920×600.
- `catalog-equipment-system.jpg` — 1920×600.
- `contact-consultation.jpg` — 1600×1000.
- `dealer-distribution.jpg` — 1600×1000.

Ảnh không chứa chữ, logo, người nhận diện được, chứng nhận hoặc tuyên bố dự án.
Khi có ảnh thật, thay qua Admin; không cần sửa code.

## Danh sách ảnh thật cần ưu tiên cung cấp

1. Ảnh sản phẩm legacy hiện có nhiều file nguồn khoảng 300×300: xuất lại đúng
   1200×1200, nền và khoảng thở đồng nhất.
2. Ảnh kho/showroom/đội tư vấn thật để thay `dealer-hero` và `contact-visual`.
3. Ảnh gallery Đại lý cũ đang lẫn tỷ lệ vuông: thay đồng bộ 1600×1000.
4. Ảnh CTA Dự án riêng 1600×500; fallback hiện dùng ảnh dự án thật nhưng phải
   crop mạnh nên không lý tưởng cho vùng chữ ngang.
5. Ảnh câu chuyện, đội ngũ và không gian làm việc thật cho trang Về chúng tôi.
6. Bản quét chứng nhận và logo đối tác từ nguồn chính thức; không dùng ảnh dựng.

## Kiểm soát tái phát

- `media-system.css` áp tỷ lệ và `object-fit` theo vai trò, không áp một selector
  ảnh tổng quát lên icon con.
- `image-resolution.spec.js` đo cả ảnh bị phóng mờ và ảnh tải lớn hơn ba lần
  khung render trên tám trang desktop/mobile.
- `media-system.spec.js` kiểm tra slot, toggle/fallback, tỷ lệ và cơ chế
  `contain`/`cover` quan trọng.
