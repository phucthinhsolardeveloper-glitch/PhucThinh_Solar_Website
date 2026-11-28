# Fact map — kiến trúc hiện hành

Cập nhật 2026-11-26; đối chiếu source local master 5c35860f. Trạng thái release: [CURRENT](../CURRENT.md).

- Website public: Nuxt 4/Vue 3/Nuxt UI 4; manifest frontend/package.json, frontend/nuxt.config.ts. SSR/Nitro, route ở frontend/app/pages và proxy ở frontend/server/routes.
- Backend: Laravel 13, Filament 5, Docker PHP 8.4 (Composer yêu cầu ^8.3). Manifest backend/composer.json.
- Database: MariaDB 10.11 theo Docker local; schema quản lý bằng Laravel migrations, không dùng bảng table_* legacy làm schema chính.
- Admin: backend/app/Providers/Filament/AdminPanelProvider.php, backend/app/Filament/Resources, backend/app/Filament/Pages.
- Nghiệp vụ: backend/app/Domain; model: backend/app/Models; API: backend/routes/api.php, backend/routes/web.php, backend/routes/stateless.php.
- Tích hợp có code: Merchant feed, sitemap, lead/email, analytics, chuyển khoản/QR, Nhất Tín, VNPay callback. Có code không đồng nghĩa provider đã bật, nhận tiền tự động hoặc được nghiệm thu.
- Production Docker/Caddy; runtime ngày 28/11 đã xác minh revision 596ce498 của bốn container website. Xem [release log](../06-operations/deployment-log.md) và [CURRENT](../CURRENT.md) để biết giới hạn kiểm thử.
- Đọc [sổ tay](../SO-TAY-CHU-PROJECT.md), [architecture](../01-architecture/overview.md), [database](../03-data/database.md).

Bản fact map trước migration được lưu trong Git: git show 5c35860f:docs/00-project/fact-map.md.
