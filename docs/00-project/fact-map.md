# Verified project fact map

Ngày khảo sát: 2026-08-26.

- Mục đích: website doanh nghiệp/catalogue/e-commerce-lite cho Phúc Thịnh Solar.
- Stack: PHP tự xây dựng, AltoRouter, PDODb/PDO, MariaDB, jQuery/Bootstrap, PHPMailer, PHPExcel, PHPWord, WebpConvert, CKEditor/CKFinder/elFinder.
- Entry/routing: `public_html/index.php`, `.htaccess`, `libraries/router.php`.
- Business/view/API: `sources/`, `templates/`, `api/`; CMS riêng trong `administrator/`.
- Data: dump có 48 bảng `table_*`, engine MyISAM, chỉ thấy primary key và không thấy foreign key/index nghiệp vụ.
- Storage: `upload/` persistent; `thumbs/`, `caches/` derived. `wp-content/` là legacy asset, không nằm trong bootstrap PHP hiện tại.
- Admin auth: session + DB token + lockout theo IP. Password dùng `md5(secret + password + salt)`.
- RBAC có schema/code nhưng `permission.active = false`.
- Product/price/sale/gallery/spec/SEO, news/project/static/contact: IMPLEMENTED.
- Cart/checkout/order/email: code có; admin order config tắt; NEEDS VERIFICATION runtime.
- Comment/star/photo: code/schema có; product comment config tắt.
- Filter: PARTIAL, có brand/sort; chưa có wattage/type structured.
- SMTP và reCAPTCHA: có implementation. OneSignal có implementation nhưng tắt. Không thấy payment/shipping provider.
- Runtime: GET `/` và category trả 200 ngày khảo sát. Không có CI/test/migration/IaC.
- P0: SQL dump, ZIP 482 MB và logs tải công khai từ production.
- UNKNOWN: deploy owner/runbook, backup schedule/restore, web server chain, usage thực tế của member/order/comment, source `solar-light-web`.

Evidence chính: `index.php`, `libraries/router.php`, `libraries/requick.php`, `libraries/config-type.php`, `libraries/type/`, `sources/`, `api/`, `administrator/`, `phucthinhsolar_com.sql`, runtime HTTP.
