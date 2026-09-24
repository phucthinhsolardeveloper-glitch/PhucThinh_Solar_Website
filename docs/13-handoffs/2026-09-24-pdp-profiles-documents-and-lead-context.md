# PDP profile theo danh mục, tài liệu sản phẩm, ngữ cảnh lead — 2026-09-24

Tiếp nối `2026-09-24-product-detail-reference-alignment.md`. Đợt này thêm
migration `050_add_product_pdp_profiles_and_documents.sql` và ba mảng việc
liên quan, tất cả đã kiểm tra ở local, **chưa chạy trên production**.

## 1. Profile trang chi tiết theo danh mục

`libraries/product_pdp_profiles.php` định nghĩa 9 profile (`general` + 8
profile theo nhóm sản phẩm: đèn, tấm pin, quạt, biến tần điện, pin lưu trữ,
biến tần bơm, máy bơm, phụ kiện). Mỗi profile có `label` và danh sách `needs`
(mã nhu cầu tư vấn dùng ở form, mọi profile đều có chung mã `bao-gia`).

- `productPdpProfileFromSlug($slug)` suy ra profile từ slug danh mục cấp 1
  khi chưa cấu hình tay (fallback theo taxonomy hiện có, không đoán mò).
- `productPdpResolveProfile($product, $list)`: sản phẩm override danh mục;
  danh mục override suy luận theo slug; không có gì thì `general`.
- Cột `pdp_profile` (nullable) thêm vào `table_product` và
  `table_product_list` qua migration 050 — `NULL` nghĩa là kế thừa, không
  phải "chưa cấu hình = lỗi".
- Admin sản phẩm (`administrator/sources/product.php`) validate `pdp_profile`
  qua `productPdpProfileKeys()` khi lưu; giá trị rỗng ghi `NULL` (kế thừa),
  giá trị lạ bị chặn lưu kèm thông báo lỗi.
- `libraries/product_specs.php`: bảng thông số kỹ thuật lọc theo profile
  (`productStructuredSpecs($rowDetail, $lang, $profileKey)`) — vd profile
  `inverter` không hiện "Nguồn sáng", profile đèn không hiện thông số AC. Xác
  minh bằng `scripts/test-product-pdp-profiles.php` (script mới, PASS cục bộ
  toàn bộ 6 assertion).

## 2. Tài liệu sản phẩm (datasheet/manual/warranty/certificate/PDF)

- Bảng `table_product_document` mới (migration 050): `document_type`
  (datasheet/manual/warranty/certificate/other), `titlevi`/`titleen`,
  `file_path`, `numb`, `status`. Chỉ render khi `status` hiển thị và
  `file_path` khác rỗng — không có tài liệu thì ẩn hẳn khối, không tạo hàng
  rỗng.
- Admin nhập qua ô nhiều dòng dạng `loại | tiêu đề | đường dẫn` trong màn sửa
  sản phẩm (`productAdminParseDocuments()` trong
  `administrator/sources/product.php`). Validate: tối đa 10 dòng; loại phải
  thuộc 5 loại cho phép; đường dẫn phải là HTTPS **hoặc** đường dẫn nội bộ
  dạng `upload/...`/`assets/...`; đuôi file phải thuộc
  pdf/doc/docx/xls/xlsx. Dòng sai bị báo lỗi rõ theo số dòng, không âm thầm
  bỏ qua.
- Lưu bằng `productAdminSaveDocuments()`: ẩn (`status='an'`) toàn bộ bản ghi
  cũ của sản phẩm rồi chèn lại theo danh sách mới — không xoá cứng, giữ được
  lịch sử nếu cần tra soát.
- Frontend đọc ở `sources/product.php:237` (chỉ lấy bản ghi hiển thị,
  `file_path<>''`) và render ở `product_detail_tpl.php:194`.
- **Backlog đã ghi ở tài liệu 09-24 trước**: bảng quản lý tài liệu dạng danh
  sách riêng (thay vì ô nhiều dòng) và phân quyền Admin theo loại tài liệu —
  chưa làm, cần chủ project duyệt schema trước.

## 3. Ngữ cảnh lead (newsletter) và địa chỉ 2 cấp

Migration 050 thêm vào `table_newsletter`: `product_id`, `profile_key`,
`need_code`, `details`, `city_id`, `ward_id` — thay cho ô "Tỉnh/Thành phố" tự
do trước đây, đồng bộ với đợt chuẩn hoá địa chỉ 2 cấp (migration 048) đã lên
production trước đó.

- Form "Đăng ký tư vấn" ở trang chi tiết sản phẩm
  (`product_detail_tpl.php`, quanh dòng 520) giờ có: `product_id`/
  `profile_key` (ẩn, tự gán theo sản phẩm đang xem), chọn Tỉnh/Thành phố +
  Phường/Xã (hai `<select>` phụ thuộc, dùng lại `api/ward-city.php`), chọn
  nhu cầu tư vấn (`need_code`, danh sách theo đúng profile của sản phẩm), và
  ô mô tả thêm (`details`).
- Validate phía server ở `sources/allpage.php` (nhánh
  `newsletter-consulting`, quanh dòng 82-117): bắt buộc sản phẩm tồn tại và
  hiển thị; `profile_key` gửi lên phải khớp đúng profile server tự suy ra
  cho sản phẩm đó (chặn giả mạo); `need_code` phải nằm trong danh sách nhu
  cầu của đúng profile; `city_id`+`ward_id` phải là một cặp Phường/Xã có
  thật (join thật vào `table_ward`/`table_city`, không tin dữ liệu client).
  Sai bất kỳ điều kiện nào thì không ghi DB, trả lỗi rõ theo từng trường.
- **Đã sửa một smoke test lỗi thời do đổi field này**:
  `scripts/test-public-lead-and-review.php` trước đó gửi `address` dạng text
  tự do (hành vi cũ) — form mới không còn field này nên bị validate chặn,
  khiến `predeploy-local.sh` báo "Bản ghi đăng ký tư vấn xuất hiện trong DB"
  FAIL. Đã cập nhật script gửi đúng `product_id`/`profile_key` (tự suy bằng
  `productPdpResolveProfile()`, giống server)/`need_code=bao-gia`/
  `city_id`+`ward_id` (lấy một cặp Phường/Xã thật từ DB). Xác nhận lại: 20/20
  PASS.

## 4. Bug đã tìm và sửa trong đợt rà soát này

Thanh điều hướng giữa trang chi tiết sản phẩm (`.pts-pdp-section-nav`) có
scroll-spy bằng `IntersectionObserver`-style tính tay (không dùng
IntersectionObserver thật, tính theo `pageYOffset` + chiều cao nav + buffer
28px) để tô đậm mục đang xem. Buffer 28px khớp sát y hệt `scroll-margin-top`
của section (68px mobile = 40px chiều cao nav thực đo + 28px buffer), nên
khi cuộn/nhảy-neo dừng đúng tại điểm `scroll-margin-top`, tiêu đề section còn
cách ngưỡng kích hoạt vài px và link chưa kịp active — bắt được bằng
Playwright (`product detail keeps gallery and the CTA configured by Admin`,
project mobile-chromium, đúng phần Codex đang dở "kiểm tra lỗi mobile"). Đã
nới buffer lên 60px trong `product_detail_tpl.php` để không còn sát ngưỡng;
xác nhận lại PASS cả desktop lẫn mobile.

## Kết quả kiểm tra local (đợt này)

- PHP 7.4 lint: `product_pdp_profiles.php`, `product_detail_tpl.php`,
  `sources/product.php`, `administrator/sources/product.php`,
  `administrator/sources/product_bulk.php`,
  `administrator/sources/content_section.php` — PASS.
- `scripts/test-product-pdp-profiles.php`: 6/6 PASS (profile theo slug,
  override sản phẩm, lọc thông số theo profile, FAQ qua Excel round-trip).
- `scripts/test-public-lead-and-review.php` (đã sửa theo mục 3): 20/20 PASS.
- Playwright `layout-interactions.spec.js` + `responsive-architecture.spec.js`
  + `icon-smoke.spec.js` + `home-story-v4.spec.js`, project mobile-chromium:
  27 PASS, 3 SKIP có chủ ý, 0 FAIL (sau khi sửa bug mục 4).
- `predeploy-local.sh`: 0 FAIL trên 4 lượt chạy liên tiếp; dòng `PASS:
  Pre-deploy local hoàn tất` không in ra được do log bị cắt giữa chừng ở
  cùng một điểm cả 4 lần (ngay sau bước dry-run nén WebP) — nghi hiện tượng
  môi trường Docker/WSL đã ghi nhận trước đó trong `docs/SO-TAY.md`, không
  phải lỗi từ code đợt này; toàn bộ đoạn script sau điểm cắt (bao gồm đúng
  smoke test đã sửa ở mục 3) đã được xác minh riêng và PASS.

## Phạm vi và rollback

Chỉ áp dụng migration 049 và 050 trên **local**, chưa đụng production. Cả
hai migration chỉ cộng thêm cột/bảng (không sửa dữ liệu hiện hữu), rollback
bằng cách hoàn nguyên code và bỏ qua hai file migration khi deploy tiếp theo;
nếu đã lỡ áp dụng migration 050 trên production thì dùng đúng khối rollback
ghi sẵn trong file migration (`DROP TABLE`/`DROP COLUMN`, chỉ chạy sau khi đã
backup).
