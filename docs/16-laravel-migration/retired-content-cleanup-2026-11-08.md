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
- DB candidate tách biệt `pts_laravel13_candidate` (2026-11-09): cùng lệnh với
  `--isolated-candidate` lưu 24 banner và 19 page item tại
  `app_storage/app/private/cleanup/retired-content-20261109-070330-aeeqj1zi.json`
  (mode 0600). Sau dọn còn 0 + 0; `pts:snapshot` vẫn khớp 1.754/1.754.
  Kiểm tất cả banner đang hiển thị có ảnh: 0 file thiếu.
- Trong **upload clone của candidate**, 21 ảnh chỉ còn gắn với banner đã bỏ,
  không được DB schema mới hay runtime source tham chiếu, đã được kiểm SHA-256
  rồi lưu tại `deploy/laravel13/runtime/backups/retired-banner-media-20261109.tar`
  cùng manifest `.json` (cả hai mode 0600). Kiểm 21 thành viên và checksum
  trước khi xóa; thu hồi 35.194.459 byte. File icon trùng với favicon đang dùng
  được giữ lại. Upload dùng chung với website PHP không bị tác động.
- Nuxt bỏ 4 bản sao byte-for-byte trong `/images/site-visuals/`; route cũ
  trả 301 tới file tương ứng ở `/assets/images/site-visuals/`. Bỏ thêm ảnh
  `about-team-fallback-20261102.webp` và video/poster thử Solar Cinema vì
  không có consumer runtime. CSS lab chỉ nạp trên route lab. Filament dùng
  favicon PTS thay cho file favicon rỗng.

`adv` còn là ảnh fallback của phần đội ngũ ở trang Giới thiệu nên được giữ.
Ảnh và icon PDP vẫn được API cấp cho trang sản phẩm; logo/Bộ Công Thương vẫn có
consumer. Không xóa file trong upload dùng chung với site PHP và nguồn import.
Không xóa container, image, volume hay backup khác.

Quét rộng upload clone từng tìm khoảng 1.105 file (khoảng 251 MB) không được DB
mới hoặc source runtime tham chiếu. Sau đó xác định 210 file `.tmb` là thumbnail
cache của CKFinder cũ, không có consumer trong Laravel/Filament; đã lưu bản sao
và xóa **chỉ trên candidate** (xem dưới). Các file còn lại **chưa đủ bằng chứng
để xóa**: URL media cũ có thể nằm trong backlink, chỉ mục tìm kiếm hoặc nội dung
ngoài hệ thống. Giữ chúng đến khi đối chiếu URL/public access và có phương án
redirect hoặc tiếp tục phục vụ URL cũ.

## Cache ẩn và đường phục vụ media (2026-11-09)

Route `/upload/*` trước đây vẫn trả file ảnh trong thư mục ẩn của CKFinder
(`.tmb`, `.trash`, `.quarantine`). Đã xác nhận HTTP 200 với một thumbnail cache
thật trước khi sửa. Từ commit `49dbf3dc`, route trả 404 cho mọi path có thành
phần bắt đầu bằng dấu chấm; `filemanager/editor` vẫn phục vụ ảnh công khai.
Candidate `49dbf3dc-candidate` đã kiểm ảnh editor 200, ảnh quarantine 404,
biến thể `%2E` 404; backend healthy và snapshot vẫn khớp.

210 thumbnail `.tmb` (746.917 byte) đã lưu trong
`deploy/laravel13/runtime/backups/legacy-ckfinder-thumbs-20261109.tar` cùng
manifest `.json` SHA-256, mode 0600. Đã kiểm toàn bộ thành viên và checksum
trong tar trước khi xóa từng file trên upload clone. Hai thư mục ẩn `.trash` và
`.quarantine` không bị xóa; không đụng upload production.

## Vận hành và rollback

`pts:prune-retired-content` mặc định chỉ báo số lượng. `--apply` lưu bản sao
trước khi xóa; lệnh từ chối môi trường production, trừ khi có cờ
`--isolated-candidate` và tên DB đúng `pts_laravel13_candidate`. Giữ file JSON
backup riêng khỏi Git. Nếu cần khôi phục, dừng ghi Admin, đọc hai mảng
`banners`/`page_items` trong file rồi insert lại bằng transaction vào đúng DB;
không ghi đè ID đã được tái sử dụng. Các file asset trong Git khôi phục được
từ commit trước đợt dọn.
21 ảnh candidate có thể khôi phục bằng tar nêu trên sau khi đối chiếu manifest
SHA-256; không giải nén vào upload production. Image candidate trước đợt dọn
được giữ với tag `b79645ba-candidate` cho cả backend và Nuxt.
Cache CKFinder cũng có tar và manifest riêng; chỉ khôi phục vào candidate nếu
cần điều tra, không mở lại đường công khai tới thư mục ẩn.
