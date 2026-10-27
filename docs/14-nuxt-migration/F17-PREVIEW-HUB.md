# F17 — Preview hub / current migration status

Checkpoint 2026-10-27, preview-only tại HEAD `57c786f`.

## Phạm vi

`/migration-hub-preview` là landing SSR nội bộ, có `noindex`, dùng registry
thuần dữ liệu tại `frontend/shared/contracts/migration-preview-routes.ts` để
liệt kê các route preview cấp cao. Registry hiện có 13 entry; milestone cũng
có các route query/detail/order-success/policy preview liên quan ngoài danh
sách entry này. Registry không thay đổi public routing.

Hub gọi các adapter GET cần cho catalog/news/projects/solutions và tạo sample
link bằng slug CMS thật khi có. News, project và solution detail hiện đã có
preview tương ứng. Các page preview cart, checkout, order success, search,
policy/support, contact/dealer/home forms và review/consultation mutation đi
qua các adapter PHP đã allowlist; PHP vẫn giữ session, CSRF, rate-limit,
reCAPTCHA khi cấu hình, DB/email và order transaction authority.

`preview-links.mjs` chỉ cho phép path preview đã đăng ký và PHP fallback ở
localhost. Điều hướng nội bộ giữa các trang preview đã được giữ trong Nuxt theo
`35d0c5f` và `57c786f`; hub không nhận URL upstream từ request và không thêm
proxy tùy ý.

## Kiểm chứng

Validation checkpoint là `3b9cade..57c786f`, kèm `35d0c5f`; local URL:
`http://localhost:3100/migration-hub-preview`. Milestone đã ghi nhận 11 PHP
file/library mới qua `php -l`, `npm test` 98/98, typecheck exit 0 (warning
vue-router/Volar không fatal), production build PASS, smoke PASS và 13/13 route
preview quan trọng HTTP 200.

Production public URLs vẫn do PHP sở hữu; Nuxt preview localhost/noindex, chưa
cutover hoặc deploy. Gaps chính trước cutover là hybrid routing, SEO
canonical/OG/sitemap/robots/redirect parity, catalog filter/sort/stock parity,
brand/gallery/video/account routes và final visual/browser QA.

