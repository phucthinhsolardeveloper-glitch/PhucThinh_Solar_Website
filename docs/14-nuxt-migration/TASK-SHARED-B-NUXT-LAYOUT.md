# CODEX B — S3 | Shared layout / one site fetch / responsive primitives
Base branch: feature/pts-nuxt4-migration. Đọc AGENTS.md và SHARED_FIRST_EXECUTION_PLAN_20261026.md; xem trực tiếp SiteHeader.vue SiteFooter.vue SiteBreadcrumb.vue, tokens.css và about preview.
Mục tiêu: 16 preview không được copy site fetch/header/footer/breadcrumb/noindex/fallback cho từng trang. Xây layout dùng chung nhưng **thí điểm 1 trang** để giảm rủi ro.
Ownership ONLY:
- NEW frontend/app/layouts/migration.vue hoặc frontend/app/components/layout/MigrationShell.vue (chọn một)
- NEW frontend/app/composables/usePublicSite.ts
- NEW frontend/app/components/layout/**, assets/css/layout-*.css
- NEW frontend/tests/shared-layout-*.test.mjs
- UPDATE frontend/app/pages/migration-about-preview.vue làm pilot
Cấm sửa frontend/server/**, shared/contracts/**, PHP, Caddy, shared QA scripts, CSS site shell cũ, các preview khác và production.
SSR: site DTO lấy 1 lần, Header/Footer/breadcrumb hiển thị theo CMS, noindex preview, lỗi API có trạng thái rõ ràng, không fabricated data, useAsyncData không hydration mismatch; không sinh placeholder marketing. Các link route chưa port chuyển về PHP local/hybrid đúng source, không đẻ route public mới.
QA pilot: PHP localhost:8080/gioi-thieu/ vs Nuxt localhost:3100/migration-about-preview cùng browser/viewport 390,768,1440; diff khi có ảnh/HTML khác phải báo, không tự cập nhật baseline.
PASS npm test, Nuxt typecheck; coordinator một lần build + Chromium sau merge. Chỉ sau approval mới nhân rộng layout sang 15 trang còn lại.
Report files, JS errors, screenshots before/after, test status, risks; commit; không merge/push/deploy.