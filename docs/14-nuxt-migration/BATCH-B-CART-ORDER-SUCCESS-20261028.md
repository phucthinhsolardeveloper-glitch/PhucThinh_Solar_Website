# Batch B: route public Cart, Order success — Checkout bị bỏ qua (NEEDS VERIFICATION)

## Đã làm

- `/gio-hang/` — route public flag-gated (`cartPublicRouteEnabled`, mặc định
  OFF), tái dùng `CartPage.vue` (extract từ `migration-cart-preview.vue`,
  không đổi logic mutation/session). Path PHP xác nhận tại
  `libraries/router.php` dòng `case 'gio-hang':`.
- `/cam-on/` — route public flag-gated (`orderSuccessPublicRouteEnabled`,
  mặc định OFF), tái dùng `OrderThanksPage.vue` (extract từ
  `migration-order-success.vue`). Path + query param `token` xác nhận tại
  `router.php` (`case 'cam-on':`) và `sources/thankyou.php:12`
  (`$_GET['token']`). Middleware redirect khi OFF giữ lại `token` nếu có,
  không làm rớt khi redirect về PHP.
- `phpPublicRouteUrl()` trong `public-route-gate.ts` mở rộng thêm tham số
  `query?: Record<string,string>` để forward query string khi redirect —
  dùng chung được cho các route path-with-token khác sau này.

## KHÔNG làm: Checkout — không có path PHP canonical riêng

`docs/14-nuxt-migration/ROUTE_PARITY_MATRIX.md` mục F17 ghi rõ: "PHP không
có public `/dat-hang/`: checkout mutation gửi POST tại `/gio-hang/` rồi
redirect `cam-on/?token=...`". `router.php` xác nhận không có route
`checkout`/`thanh-toan` nào khác `gio-hang`/`cam-on`.

`migration-checkout-preview.vue` là 1 bước UI riêng trong Nuxt (không phải
URL PHP riêng) — tạo 1 public route Nuxt-only (ví dụ `/thanh-toan/`) cho
bước này sẽ là 1 URL **không có tương đương PHP thật**, không phải parity,
mà là quyết định sản phẩm mới. Theo đúng chỉ dẫn "không bịa path PHP",
KHÔNG tạo route public cho checkout trong batch này.

**NEEDS VERIFICATION / cần quyết định sản phẩm trước khi làm:** khi
`/gio-hang/` được bật thật, luồng "Tiếp tục thanh toán" trong `CartPage.vue`
vẫn trỏ `/migration-checkout-preview` (preview nội bộ, noindex) — đến lúc
cutover thật cần quyết định: (a) giữ checkout là 1 state/modal trong chính
trang `/gio-hang/` giống PHP gốc, hay (b) chốt 1 URL Nuxt-only mới (chấp
nhận lệch 1:1 với PHP, có rủi ro SEO/analytics khác hành vi cũ). Không tự
quyết trong batch này.

## Kết quả test

`npm test` 130/130 PASS, `npm run typecheck` exit 0, `npm run build` PASS
(25.6s). Smoke test thật qua `.output/server/index.mjs` tự build (KHÔNG
dùng `pts-php81-local`, theo bài học ở
`BATCH-A-CATALOG-URL-FALSE-POSITIVE-20261028.md`):
- `/gio-hang/` flag OFF → 307 → `http://127.0.0.1:8080/gio-hang/`
- `/cam-on/?token=abc123` flag OFF → 307 → `.../cam-on/?token=abc123` (token giữ nguyên)
- `/cam-on/` (không token) flag OFF → 307 → `.../cam-on/` (không query rác)

Chưa verify flag-ON với dữ liệu cart/order thật qua container PHP (cần
session/mutation thật, ngoài phạm vi smoke test nhanh của batch này — logic
bên trong `CartPage.vue`/`OrderThanksPage.vue` không đổi so với bản preview
đã được verify PASS ở các lượt QA trước, chỉ đổi vỏ route).
