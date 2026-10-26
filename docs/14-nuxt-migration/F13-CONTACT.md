# F13 contact migration checkpoint

F13 chỉ cung cấp projection GET read-only cho preview nội bộ và không claim
route public Nuxt. Nguồn dữ liệu giữ parity với `sources/contact.php`: setting
public, static `lienhe`, locations `he-thong-cua-hang` và photo slot
`contact-visual`.

- API: `GET /api/pts_v1_contact.php`; method khác trả 405. Query không được
  đưa vào contract Nuxt.
- Visual lấy theo thứ tự CMS; khi đã có record nhưng tắt Hiển thị, visual bị
  ẩn. Fallback chỉ dùng khi không có record `contact-visual` nào và file thực
  tế tồn tại.
- `map_iframe` chỉ trích xuất `src` HTTPS Google Maps allowlist; API không trả
  raw iframe HTML.
- Preview `/migration-contact-preview` dùng `SiteHeader`/`SiteFooter`, không có
  form POST/CSRF/cookie/endpoint giả. CTA bàn giao về `/lien-he/` của PHP trong
  giai đoạn hybrid.

PHP Docker verification, Nuxt build và browser QA thuộc coordinator; chưa chạy
production, deploy, push hoặc merge.
