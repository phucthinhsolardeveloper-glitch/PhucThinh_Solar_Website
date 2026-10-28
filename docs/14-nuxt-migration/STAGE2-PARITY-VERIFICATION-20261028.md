# Stage 2 — PHP parity verification (2026-10-28)

Verify trên code đã merge vào `feature/pts-nuxt-integration-20261027` (HEAD
`663668e`, đã gồm fix redirect/OG `663668e` + 2 audit doc), chạy trong container
PHP 8.1 riêng `pts-php81-stage2-test` (port 8098, build từ
`docker/php81/Dockerfile`, DB dùng chung `pts-mariadb-local` qua network
`web_pts_php_default`, đọc-only). Không đụng `pts-php81-local` (8080).

## 1. PHP contract test (`php scripts/test-pts-public-*.php`)

Toàn bộ 17 script PASS, gồm `test-pts-public-api-integration.php` (bộ chạy
tổng, 58 HTTP check PASS/0 FAIL/7 SKIP, 18 slice PASS/0 FAIL), có khẳng định
riêng **"database snapshot unchanged — read-only tables stable before/after"**
→ xác nhận không ghi DB trong lúc test. Gồm cả Gallery/Video
(`test-pts-public-media-library-api.php`: album DTO canonical, hidden album bị
loại, video DTO nocookie embed, unsafe video bị loại — RESULT: PASS) và mọi
API session-bound mutation (cart/review/lead-form) trả đúng 405/419 khi gọi
không có CSRF/session hợp lệ.

**Kết luận: PASS**, không FAIL nào.

## 2. Negative slug / 404

- Slug không tồn tại trên `/san-pham/...`, `/tin-tuc/...` → `404` (xác nhận
  bằng curl trực tiếp lên container).
- DTO-level: `test-pts-public-*-api.php` đã có assertion riêng cho record ẩn
  (status rỗng/không `hienthi`) bị loại khỏi payload (ví dụ catalog "hidden
  record omitted", news "bài ẩn bị loại khỏi DTO", project "record ẩn không
  được hồi sinh").
- **NEEDS VERIFICATION**: chưa có fixture DB thật với 1 sản phẩm/bài cụ thể ở
  trạng thái `hienthi` → bị chuyển ẩn ngay trong session test này để xác nhận
  HTTP 404 end-to-end qua route công khai (không chỉ ở tầng DTO). Không tạo
  fixture mới vì có thể đụng dữ liệu `pts-mariadb-local` dùng chung với
  container QA khác.

## 3. SEO fix verify lại trên code đã merge (không tin lại audit cũ)

- `/product/` → `301` tới `/san-pham/` — **PASS** (`curl -I` xác nhận
  `Location: http://127.0.0.1:8098/san-pham/`).
- OG fallback trên các route trước đây thiếu (`tim-kiem`, `gio-hang`,
  `giai-phap`, `du-an`, `tin-tuc`) — **PASS**, `og:description`/`og:url`/
  `og:image` đều có giá trị (fallback site name/canonical/logo chung), không
  rỗng.
- JSON-LD Organization/WebSite — **PASS sau khi sửa quyền
  `chown -R www-data:www-data /var/www/html/assets`** trong container test
  riêng này. Trước khi sửa quyền, container tái hiện đúng lỗi gốc
  (`mkdir(): Permission denied` trong `class.CssMinify.php:31`, vì bind-mount
  giữ owner host `1000:1000` trên `assets/`), khiến trang chết giữa chừng
  trước khi tới `strucdata.php` — **xác nhận độc lập** kết luận của nhánh fix
  trước: đây là vấn đề quyền container test, không phải lỗi code. Sau khi
  chown, JSON-LD `@graph` Organization + WebSite render hợp lệ (parse JSON
  thật, có `name`/`url`/`logo`/`address`/`telephone`).
- robots.txt / sitemap.xml: `200 OK` cả hai, không kiểm tra thêm nội dung (đã
  có ở audit P2-A trước, không lặp).

## 4. Cart/checkout/form trên DB cách ly

Không chạy mutation thật (POST tạo đơn/giỏ hàng) trong lượt này — rủi ro ghi
vào `pts-mariadb-local` dùng chung với các agent/QA khác đang hoạt động, và
không có DB cách ly riêng được cấp. Chỉ xác nhận qua
`test-pts-public-api-integration.php`: `POST /api/pts_v1_cart_mutate.php` và
`POST /api/pts_v1_lead_form.php` bị từ chối đúng `419` khi thiếu CSRF/session
hợp lệ (an toàn, không tạo record), và DB snapshot được chính script xác nhận
không đổi.

**NEEDS VERIFICATION**: luồng mutation thật (thêm vào giỏ → checkout → tạo đơn
thành công) trên DB cách ly — cần được giao riêng với DB snapshot/volume riêng,
ngoài phạm vi lượt verify này.

## Tổng kết gate

| Gate | Kết quả |
|---|---|
| PHP contract test (17 script, kể cả Gallery/Video) | PASS |
| Negative slug DTO-level | PASS |
| Negative slug end-to-end fixture cụ thể | NEEDS VERIFICATION |
| `/product/` redirect | PASS |
| OG fallback | PASS |
| JSON-LD Organization/WebSite | PASS (sau khi sửa quyền container test) |
| robots/sitemap status | PASS |
| Cart/checkout mutation thật trên DB cách ly | NEEDS VERIFICATION (chưa chạy, cần DB riêng) |

## Container

`pts-php81-stage2-test` (port 8098) — build/run/test xong, đã `docker compose
down -v` để xoá cả volume riêng của lượt test này. Không đụng `pts-php81-local`
(8080) hay bất kỳ container khác đang chạy.
