# Mốc test và runtime — Phase 0

Ghi ngày 2026-11-07. Đây là mốc để Laravel không làm hỏng hành vi đang chạy.

## Runtime

| Môi trường | Phiên bản |
|---|---|
| Production | commit `2d151211`, image `phucthinhsolar-web:2d151211971a` và `phucthinhsolar-nuxt:2d151211971a`, deploy FAST lúc 18:53 |
| Source baseline của nhánh chuyển đổi | `master` tại `2d151211`; tài liệu đặc tả ghi `a8632eed` là mốc cũ hơn |
| PHP legacy | 8.1 (container `pts-php81-local`) |
| Nuxt | 4, Vue 3, Nuxt UI 4, Tailwind 4 |
| Database | MariaDB 10.11, 59 bảng InnoDB, 1 khóa ngoại |

Dữ liệu production đã đổi trong ngày, nằm ngoài code: 9 thương hiệu mới (migration
077) và `options.hotline_technical` (migration 078). Database local và bản sao
chuyển đổi cũng đã có các thay đổi này.

## Test

| Bộ | Kết quả gần nhất |
|---|---|
| `frontend` `npm test` (hợp đồng nguồn) | 509 đạt |
| `frontend` `npx nuxt typecheck` | 0 lỗi |
| `tools/visual-tests` `npm run test:nuxt` | 45 đạt, 25 bỏ qua |
| Playwright đầy đủ (bước SAFE của deploy) | 248 đạt, 42 bỏ qua |
| Playwright production `tests/public-pages.spec.js` | 18 đạt |
| `scripts/test-admin-crud-lifecycle.php` (predeploy) | 40 đạt, khi Nuxt diễn tập trỏ `pts-php81-local` |
| `tools/visual-tests/audit/site-audit.cjs` | 0 lỗi trên 19 trang |

Các bài test PHP trong `scripts/test-*.php` là đặc tả chạy được của hành vi legacy.
Phase 2 phải chạy lại chúng với Laravel trước khi chuyển bất kỳ đường ghi nào.

## Đã loại khỏi kiểm kê

Không đọc hay ghi: `config.php`, `deploy/vps/secrets/`, dữ liệu dòng của bảng có
thông tin cá nhân (`table_contact`, `table_order`, `table_user`), log mail.
