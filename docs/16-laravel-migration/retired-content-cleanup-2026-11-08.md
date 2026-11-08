# Dọn nội dung legacy không còn consumer

Ngày 2026-11-08, nhánh `migration/laravel13-integration`. Phạm vi là DB clone
Laravel và release Nuxt/Laravel mới; website production/master không đổi.

## Bằng chứng và thay đổi

- `banners`: 24 bản ghi thuộc `home-hero-bg`, `slide`, `banner-top`, `doitac`,
  `social`, `watermark`, `watermark-news`. Không có consumer trong Laravel API,
  Nuxt, Filament nghiệp vụ hoặc cấu hình hiện hành. `socialLinks` lấy từ
  `settings.fanpage/youtube`; Hero mới lấy `hero-carousel`. Importer bỏ qua các
  placement này, Admin chỉ cho chọn placement còn dùng.
- `page_items`: 19 bản ghi `archive.*` chỉ do mapping legacy cũ tạo ra; không
  có API/FE đọc. Importer bỏ mapping archive; Admin chỉ hiển thị section có
  consumer. Bổ sung lựa chọn `catalog.benefits`, `search.suggestions` vốn có
  consumer nhưng trước đây thiếu trong form.
- DB local `pts_app`: `pts:prune-retired-content --apply` đã lưu JSON chứa đúng
  bản ghi trước khi xóa tại
  `backend/storage/app/private/cleanup/retired-content-20261108-225520-pplw0tjj.json`
  (mode 0600), rồi xóa 24 + 19 bản ghi trong một transaction. Chạy lại lệnh
  không có `--apply` cho kết quả 0 + 0. API snapshot sau xóa khớp 1.754/1.754.
- Nuxt bỏ 4 bản sao byte-for-byte trong `/images/site-visuals/`; route cũ
  trả 301 tới file tương ứng ở `/assets/images/site-visuals/`. Bỏ thêm ảnh
  `about-team-fallback-20261102.webp` và video/poster thử Solar Cinema vì
  không có consumer runtime. CSS lab chỉ nạp trên route lab. Filament dùng
  favicon PTS thay cho file favicon rỗng.

`adv` còn là ảnh fallback của phần đội ngũ ở trang Giới thiệu nên được giữ.
Ảnh và icon PDP vẫn được API cấp cho trang sản phẩm; logo/Bộ Công Thương vẫn có
consumer. Không xóa file trong upload dùng chung với site PHP và nguồn import.
Không xóa container, image, volume hay backup khác.

## Vận hành và rollback

`pts:prune-retired-content` mặc định chỉ báo số lượng. `--apply` lưu bản sao
trước khi xóa; lệnh từ chối môi trường production, trừ khi có cờ
`--isolated-candidate` và tên DB đúng `pts_laravel13_candidate`. Giữ file JSON
backup riêng khỏi Git. Nếu cần khôi phục, dừng ghi Admin, đọc hai mảng
`banners`/`page_items` trong file rồi insert lại bằng transaction vào đúng DB;
không ghi đè ID đã được tái sử dụng. Các file asset trong Git khôi phục được
từ commit trước đợt dọn.
