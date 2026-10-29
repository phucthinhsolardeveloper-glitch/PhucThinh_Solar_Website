# Cart/checkout mutation verification (isolated DB) — 2026-10-28

> **Ghi chú hiện trạng:** đây là bằng chứng lịch sử cho backend PHP checkout
> và adapter Nuxt tại checkpoint lúc kiểm thử. Sau đó checkout preview đã
> chuyển sang chỉ đọc: `POST /_migration/checkout` trả 410 và không forward
> tới PHP. Kết quả tạo đơn trong fixture bên dưới không có nghĩa là checkout
> Nuxt hiện cho phép gửi đơn.

Phạm vi: verify thật (ghi DB) luồng cart add/update/delete và checkout tạo
order, qua đúng endpoint PHP mà adapter Nuxt (`frontend/server/routes/_migration/cart.*.ts`,
`checkout.*.ts`) forward tới — **không chạy qua Nuxt container thật** (do giới
hạn RAM/thời gian trong lượt này), nhưng endpoint PHP được gọi trực tiếp với
đúng payload/shape mà code adapter forward, nên kết quả đại diện đúng hành vi
backend mà Nuxt phụ thuộc vào.

## Cách ly môi trường

- MariaDB 10.11 riêng (`pts-mariadb-cart-mutation-test`, volume riêng, KHÔNG
  dùng volume `pts_mariadb_data`/`pts-mariadb-local`).
- Schema lấy qua `mysqldump --no-data --single-transaction` từ
  `pts-mariadb-local` (chỉ đọc cấu trúc bảng, KHÔNG đọc/ghi một dòng dữ liệu
  nào) — an toàn, không lock, không ảnh hưởng DB chia sẻ.
- Toàn bộ dữ liệu (product, city, ward, payment method, setting) là **fixture
  tự tạo**, không lấy từ DB production/local.
- PHP 8.1 riêng (`pts-php81-cart-mutation-test`, port 8096), APP_ENV=local
  trỏ DB_HOST vào MariaDB cách ly trên.
- KHÔNG đọc/ghi/xóa gì vào `pts-mariadb-local` hay volume
  `web_pts_php_pts_web_upload`.

## Kết quả

| Bước | Kết quả | Chi tiết |
|---|---|---|
| GET cart (csrfToken) | PASS | `api/pts_v1_cart.php` trả csrfToken + cart rỗng đúng |
| Add to cart | PASS | qty 2 × 500.000đ → subtotal 1.000.000đ khớp |
| **Update quantity** | **FAIL → đã sửa, PASS sau fix** | Xem "Bug tìm thấy" dưới |
| Delete item | PASS | `Cart::removeProduct()` xóa đúng item theo `code`, giữ nguyên item khác |
| GET checkout metadata | PASS | checkoutToken, cities, payments (fixture) trả đúng |
| **Checkout → tạo order** | PASS | `table_order`/`table_order_detail` ghi đúng: fullname/phone/email khớp input, `city=1/ward=1/district=0` đúng quy tắc 2 cấp, `ship_price=30000` lấy đúng từ fixture ward, `temp_price=4.500.000`+`ship=30.000`=`total_price=4.530.000` khớp toán, `order_detail.quantity=9` khớp cart sau update |
| Replay cùng `checkout_token` | PASS | Không tạo order thứ 2 (count giữ nguyên 1/1), khớp tài liệu `checkout-write-path.md` |

## Bug tìm thấy và đã sửa — `api/pts_v1_cart_mutate.php` action `update`

**Severity: cao.** Action `update` trả `{"ok":true,...}` nhưng **không ghi quantity
mới vào session** — người dùng sửa số lượng trên giỏ hàng Nuxt sẽ thấy báo
thành công nhưng số lượng thật không đổi.

Root cause xác nhận bằng PHP reproduction độc lập (không chỉ đọc code):

```php
foreach((array)(isset($_SESSION['cart']) ? $_SESSION['cart'] : array()) as &$item)
```

`(array)(ternary expression)` tạo một **bản copy tạm**, không phải tham chiếu
tới biến `$_SESSION['cart']` thật — nên `&$item` chỉ sửa bản copy, không bao
giờ ghi lại vào session. Đã verify: trước fix, `var_dump($_SESSION['cart'])`
sau foreach vẫn giữ giá trị cũ.

**Đã sửa** (commit trong branch này): iterate trực tiếp `$_SESSION['cart']`
(không qua `(array)`/ternary) để `&$item` là tham chiếu thật, có khởi tạo
`$_SESSION['cart'] = array()` an toàn nếu chưa tồn tại. Verify lại sau fix:
update quantity 1 → 9 phản ánh đúng ngay trong GET tiếp theo, và order detail
cuối cùng ghi đúng `quantity=9`.

## Phát hiện phụ (không sửa, không thuộc phạm vi task này)

- `libraries/class/class.Email.php:50,54` warning khi gửi mail xác nhận đơn vì
  fixture không có `table_photo` (logo) / company settings — đây là do fixture
  tối thiểu thiếu dữ liệu, KHÔNG phải bug thật (production luôn có logo/company
  config). Nhưng ghi nhận: warning in-band trước `header()` khiến PHP fallback
  sang `<script>window.location.replace(...)</script>` thay vì HTTP
  `Location:` header thật — nếu trường hợp này từng xảy ra ở production (ví dụ
  thiếu ảnh logo tạm thời), `frontend/server/routes/_migration/checkout.post.ts`
  sẽ nhận `response.headers.get('location') === null` và trả lỗi 502 cho người
  dùng Nuxt dù order đã tạo thành công trong DB — một gap nhỏ giữa "đã tạo đơn"
  và "Nuxt báo thành công" khi output có warning. Không sửa trong task này
  (ngoài phạm vi, cần review rộng hơn cách xử lý error-level trên production).
- **Bug trong file compose đã merge vào integration** (ngoài phạm vi worktree
  này, cần người giữ vai integration owner sửa riêng): lần fix trước
  (`fix(nuxt): mount the real assets/caches path...`) đổi
  `docker-compose.nuxt-preview.yml` từ chỉ mount `/var/www/html/caches` sang
  CHỈ mount `/var/www/html/assets/caches` — nhưng cả hai đường dẫn đều cần
  thật: `libraries/class/class.Cache.php` dùng `ROOT.'caches'` (root), còn
  `class.CssMinify.php`/`class.JsMinify.php` dùng `assets/'.'caches'`. Thiếu
  1 trong 2 sẽ làm phần còn lại die "Failed to create folders..." — xác nhận
  bằng thực nghiệm trong lượt này khi dựng container test riêng (gặp đúng lỗi
  trên `class.Cache.php` cho tới khi thêm cả 2 tmpfs mount). `docker-compose.stage3-qa.yml`
  đã có sẵn cả 2 đường dẫn đúng — nên copy cách đó sang `docker-compose.nuxt-preview.yml`.

## NEEDS VERIFICATION (chưa làm, không bịa)

- Mã giảm giá / coupon: không tìm thấy action liên quan trong
  `api/pts_v1_cart_mutate.php` hiện tại — có thể tính năng chưa tồn tại ở PHP,
  không phải gap của Nuxt. Chưa xác minh chắc chắn 100%, chỉ grep nhanh.
  Cần hỏi chủ project thay vì suy đoán.
- Chưa chạy qua container Nuxt thật (chỉ gọi thẳng endpoint PHP mà adapter
  forward tới) — do giới hạn RAM/thời gian. Code adapter (`cart.post.ts`,
  `checkout.post.ts`) đã được đọc kỹ và khớp chính xác payload/response shape
  đã test ở đây, nên rủi ro sai lệch thấp nhưng chưa phải bằng chứng end-to-end
  qua browser thật.
- Thanh toán VNPay (redirect bên thứ 3) — không test, chỉ test payment
  method "legacy"/COD.

## Cleanup

Đã `docker compose -f docker-compose.cart-mutation-test.yml down -v` — xóa
sạch cả 2 container và network riêng, không còn volume nào sót lại. Không
đụng `pts-mariadb-local`, `pts-php81-local`, hay volume `pts_web_upload`.
