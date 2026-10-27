# F17 — Preview hub / current migration status

Checkpoint 2026-10-27, preview-only tại base `07a50ea` (preview implementation
`ae811bb`).

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
localhost. Điều hướng nội bộ giữa các trang preview đã được giữ trong Nuxt; hub
không nhận URL upstream từ request và không thêm
proxy tùy ý.

## Kiểm chứng

Validation checkpoint trước đó là `ae811bb`; local URL:
`http://localhost:3100/migration-hub-preview`. Milestone đã ghi nhận `npm test`
109/109, typecheck exit 0 (warning vue-router/Volar không fatal) và production
build PASS. PHP 8.1/Docker và full browser manifest không thuộc lane này.

Production public URLs vẫn do PHP sở hữu; Nuxt preview localhost/noindex, chưa
cutover hoặc deploy. Gaps chính trước cutover là hybrid routing, SEO
canonical/OG/sitemap/robots/redirect parity, gallery/video/account routes và
final visual/browser QA. Catalog filter/sort và brand detail đã được ghi nhận
đã nối ở `c00aa0e`, `ae811bb`, `f0cf5ce`; crawler mới chỉ tạo evidence, không
đổi trạng thái ownership.

