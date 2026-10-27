# F13 contact migration checkpoint (2026-10-27)

F13 cung cấp projection dữ liệu cho preview nội bộ và không claim route public
Nuxt. Nguồn dữ liệu giữ parity với `sources/contact.php`: setting public, static
`lienhe`, locations `he-thong-cua-hang` và photo slot `contact-visual`. Nội dung
và form render bằng Nuxt; PHP adapter giữ CSRF, rate-limit, reCAPTCHA khi cấu
hình, DB/email và mutation authority.

- API: `GET /api/pts_v1_contact.php`; method khác trả 405. Query không được
  đưa vào contract Nuxt.
- Visual lấy theo thứ tự CMS; khi đã có record nhưng tắt Hiển thị, visual bị
  ẩn. Fallback chỉ dùng khi không có record `contact-visual` nào và file thực
  tế tồn tại.
- Map embed Google Maps được sinh từ địa chỉ CMS; `mapUrl` CMS vẫn được giữ như
  external link. API không trả raw iframe HTML không cần thiết.
- Preview `/migration-contact-preview` dùng `SiteHeader`/`SiteFooter`; form
  submit qua adapter PHP an toàn, không tạo mutation/session business riêng.
  Điều hướng nội bộ preview giữ trong Nuxt theo `35d0c5f`/`57c786f`; public
  `/lien-he/` vẫn là route PHP.

Validation checkpoint: `3b9cade..57c786f`, kèm `35d0c5f`; local hub
`http://localhost:3100/migration-hub-preview`. Milestone đã ghi nhận `php -l`
cho 11 PHP file/library mới, `npm test` 98/98, typecheck exit 0 với warning
vue-router/Volar không fatal, production build PASS, smoke PASS và 13/13 route
preview quan trọng HTTP 200. Preview vẫn noindex; chưa production cutover,
deploy, push hoặc merge.
