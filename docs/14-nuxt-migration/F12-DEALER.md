# F12 dealer preview parity (checkpoint 2026-10-27)

Preview route: `/migration-dealer-preview` (noindex). Nội dung và form đều
render bằng Nuxt; PHP adapter vẫn là authority của public `/dai-ly/`, CSRF,
validation, rate-limit, reCAPTCHA khi cấu hình, DB/email và mutation legacy.

## Read contract

`GET /api/pts_v1_dealer.php` exposes only published `dai-ly-1/2/3`, `hinh-anh-dai-ly`, `dealer-hero`, `cam-ket-dai-ly`, `phan-hoi-dai-ly` and `so-lieu-thong-ke-dai-ly`. It returns plain text, validated local media paths and allowlisted links; private DB fields and raw HTML are excluded. Unknown query keys and non-GET requests are rejected.

Hero fallback is used only when no `dealer-hero` rows are configured. A configured but hidden/missing-image row suppresses fallback, matching the Admin hide rule.

Form preview lấy CSRF/session cần thiết qua adapter PHP và submit về PHP; không
tạo business state riêng trong Nuxt. Điều hướng nội bộ preview dùng route Nuxt
tương ứng sau `35d0c5f`/`57c786f`; không thêm public Nuxt `/dai-ly/` route.

## Files and verification

Validation checkpoint: `3b9cade..57c786f`, kèm `35d0c5f` cho navigation; local
hub là `http://localhost:3100/migration-hub-preview`. Milestone đã ghi nhận
`php -l` cho 11 PHP file/library mới, `npm test` 98/98, typecheck exit 0 với
warning vue-router/Volar không fatal, production build PASS, smoke PASS và
13/13 route preview quan trọng HTTP 200. Đây vẫn là local noindex preview; chưa
production cutover, deploy hoặc public SEO handoff.
