# Handoff 2026-08-26 — Chuẩn bị website chạy quảng cáo

Bàn giao từ Claude cho Codex (hoặc dev tiếp theo). Toàn bộ việc bên dưới đã code + test trên **Docker local** (`docker-compose.local.yml`), **chưa đụng production**. Git đã init tại root repo, 11 commit, branch `master`.

## Môi trường local

```bash
docker compose -f docker-compose.local.yml up -d --build
```

- Web: http://localhost:8080 — Admin: http://localhost:8080/administrator/
- Admin local: `admin` / `123456789` (chỉ local, đã đổi qua DB trực tiếp, xem `libraries/config.php` phần `APP_ENV=local` override)
- DB: MariaDB 10.4 container `pts-mariadb-local`, seed từ `phucthinhs_data-production-2026-08-26-before-ads.sql`

## Đã triển khai (theo checklist "TƯ VẤN CHỈNH SỬA WEBSITE" của chủ project)

### 1. Badge % giảm giá — commit `145a094`
Tính `discountPercent` từ `regular_price`/`sale_price` có sẵn, hiển thị badge đỏ "-XX%" ở danh mục, chi tiết sản phẩm, sản phẩm liên quan.
File: `templates/product/product_tpl.php`, `templates/product/product_detail_tpl.php`

### 2. Bộ lọc công suất + mức giá — commit `2d120ec`
- Thêm cột `cong_suat` (DECIMAL) vào `table_product` — xem `migrations/001_add_product_cong_suat.sql` + `migrations/002_backfill_product_cong_suat.php` (script dò công suất từ tên SP bằng regex, dry-run mặc định, cần `--apply`). **Đã chạy trên local: 39/81 SP dò được, 42 SP còn lại (phụ kiện/inverter/pin...) không có "công suất đèn" nên đúng là bỏ qua — cần rà tay 42 SP này qua admin.**
- Filter `is_congsuat` + `is_price` (4 khung giá) thêm vào `sources/product.php` + `api/fillter_service.php`, parameterized (dùng `?` placeholder).
- **Tiện tay vá 1 lỗ hổng SQL injection có sẵn từ trước** ở `is_brand`/`is_origin` (nối chuỗi trực tiếp GET vào SQL) — đã thêm whitelist regex `^[0-9,]+$` ở cả 2 file trên.
- UI filter mới trong `templates/product/product_tpl.php` (sidebar), JS thu thập trong `assets/js/functions.js` (`fetch_product()`).
- Admin: field "Công suất" thêm vào form add/edit SP — `libraries/type/config-type-product.php`, `administrator/templates/product/man/man_add_tpl.php`, `administrator/sources/product.php`.

### 3. Mô tả sản phẩm mở rộng + video — **CHƯA LÀM**
Còn lại trong checklist gốc. DB đã có sẵn field `thongsokythuatvi/en`, `contentvi/en` — chỉ thiếu field video sản phẩm + cần viết lại nội dung mô tả chi tiết cho từng SP theo mẫu tham khảo (kitawa.vn) chủ project gửi.

### 4. Giỏ hàng / Mua ngay + trang cảm ơn — commit `34241ae`, sửa UI thêm ở `f3f1527`
- Backend cart/order (`libraries/class/class.Cart.php`, `api/cart.php`, `sources/order.php`, `templates/order/order_tpl.php`) **đã có sẵn đầy đủ từ trước**, chỉ chưa nối vào frontend.
- Thêm nút "Thêm giỏ hàng"/"Mua ngay" ở danh mục + chi tiết SP (dùng `class="addcart"` + `data-id`/`data-action`, JS handler có sẵn trong `assets/js/apps.js`).
- Route mới `cam-on/` (trang cảm ơn thật, đi qua layout chính nên GTM load được) — `libraries/router.php`, `sources/thankyou.php` (mới), `templates/order/thanks_tpl.php` (mới). Có sẵn `dataLayer.push({event:'order_success', order_value, order_code})` để gắn conversion Ads/GA4.
- `sources/order.php`: redirect sau đặt hàng thành công sang `cam-on/?code=X` thay vì về trang chủ qua trang trung gian không có GTM.
- **Vá bug nghiêm trọng**: `class.Email.php` dùng `PHPMailer(true)` nhưng KHÔNG có try/catch ở bất kỳ đâu trong code base → SMTP lỗi sẽ fatal error ngay sau khi đơn đã lưu DB, khách không thấy xác nhận gì. Đã bọc try/catch quanh gửi mail trong `order.php`.
- Thêm icon giỏ hàng (trước đây KHÔNG tồn tại trên header) + badge số lượng — `templates/layout/header.php`.
- Phương án thanh toán: **COD/chuyển khoản thủ công** theo xác nhận của chủ project — chưa tích hợp cổng thanh toán online (không nằm trong scope hiện tại).

**Phương thức thanh toán trong DB** (`table_news type='hinh-thuc-thanh-toan'`, id 9/10/11): Thanh toán tại công ty / chuyển khoản / tại điểm giao hàng — đã có sẵn, không cần thêm.

### 5. Đánh giá sao/review sản phẩm — commit `5eba220`
Module review (rating, ảnh/video feedback, avg score) **đã có sẵn đầy đủ** ở `class.Comments.php` + `templates/product/comment.php` + `api/comment.php`, chỉ chưa include vào trang chi tiết SP + JS/CSS bị comment tắt. Đã bật lại, không cần code mới.

### 6. Giấy chứng nhận/kiểm nghiệm — **CHƯA LÀM**
Không có module riêng trong DB/code. Cần quyết định hình thức hiển thị (badge trên card SP như ảnh mẫu chủ project gửi, hay 1 section tĩnh dùng module `table_static` có sẵn).

### 7. Công trình dự án nổi bật — **CHƯA LÀM (code đã sẵn 100%)**
Module "Dự án" (`case 'du-an'` trong `router.php`, `templates/news/project_tpl.php`/`project_list_tpl.php`/`project_detail_tpl.php`, config admin ở `libraries/type/config-type-news.php`) **đã hoạt động đầy đủ end-to-end** — chỉ cần chủ project cung cấp ảnh/nội dung dự án thật rồi nhập qua admin (menu "Dự án" đã có sẵn).

### 8. Chính sách chân trang — commit `413278a`
DB production **đã có sẵn 5 bài chính sách** (bảo hành/mua bán/đổi trả/vận chuyển/bảo mật, `table_news type='chinh-sach'`, id 30-34, status hiển thị) nhưng nội dung rỗng và **không có menu admin để sửa**. Đã thêm menu "Chính sách" vào admin (`libraries/type/config-type-news.php`, dùng chung controller generic có sẵn). Route `ho-tro/<slug>/` ở footer đã tự hoạt động đúng từ trước (không phải bug).
**Chờ chủ project cung cấp nội dung thật** (đang chờ "sếp" đưa) rồi nhập qua admin.

## Bug đã sửa thêm (ngoài checklist, phát hiện khi test)

- **`docker/php74/Dockerfile` (`8dfa947`)**: thiếu PHP extension `calendar` → dashboard admin fatal error `cal_days_in_month()` ngay sau đăng nhập, khiến UI treo mãi ở "Please wait" (đây là **PHP fatal error, không phải lỗi JS**). Đã thêm extension. Cần audit xem **production có bị lỗi tương tự không** (extension `calendar` có bật trên hosting thật hay không — nếu không, admin production cũng đang bị y hệt bug này).
- **`libraries/config-type.php` (`775e409`)**: Notice `Undefined index: tom-tat-gioi-thieu` ở menu admin — type này bị comment tắt hoàn toàn ở `config-type-static.php` nhưng vẫn còn tham chiếu trong `$config['group']`. Đã xác minh: fetch ở `sources/about.php` nhưng KHÔNG render ở template nào → tính năng chết hoàn toàn, xoá reference an toàn.

## Đã phát hiện nhưng CHƯA sửa — cần chủ project quyết định

1. **Lịch sử tại thời điểm 2026-08-26 — đã superseded**: khi đó SMTP còn lấy từ
   `table_setting.options` và cấu hình active bị thiếu. Runtime hiện tại không còn
   đọc transport/password SMTP từ DB; nguồn đúng là `$config['mail']` ngoài Git,
   theo `docs/08-integrations/smtp-production.md`.
2. **`$config['order']['active'] = false`** trong `config-type.php` — nghi ngờ đây là cờ ẩn menu "Quản lý đơn hàng" trong admin. Giờ storefront đã cho khách đặt hàng thật (mục 4), nếu cờ này ẩn luôn màn hình admin xem đơn thì đơn vào DB nhưng nhân viên không thấy để xử lý. **Chưa kiểm tra/bật** — cần xác nhận trước khi launch ads thật.
3. **reCAPTCHA và OneSignal**: có key thật trong `config.php` nhưng đang tắt (`active: false`). Không đụng, chỉ note lại.
4. Domain/hosting/tài khoản ngoài (GTM, GA4, email quản trị...) — xem lịch sử chat trước, không nhắc lại ở đây vì không liên quan code.

## Việc còn lại (checklist gốc)

- Mục 3: mô tả sản phẩm mở rộng + video sản phẩm
- Mục 6: giấy chứng nhận/kiểm nghiệm
- Mục 7: nhập nội dung dự án thật (code đã xong)
- Nhập nội dung 5 bài chính sách (mục 8, đang chờ nội dung thật)
- Rà 42 sản phẩm chưa dò được công suất qua admin
- Xác nhận 2 vấn đề "chưa sửa" ở trên trước khi deploy production

## Deploy lên production — CHƯA làm, cần xác nhận riêng

Toàn bộ commit trên chỉ tồn tại trong Git local + Docker local. Trước khi đẩy lên production cần: backup DB/code production trước, chạy `migrations/001_...sql` trên DB production, đối chiếu `config.php` production có cần thêm gì không (không có thay đổi schema nào khác ngoài `cong_suat`), và xác nhận hosting PHP có extension `calendar` hay chưa (xem mục bug ở trên).
