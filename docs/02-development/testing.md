# Testing

Chưa có framework unit/E2E hoặc CI. Repository hiện có script tích hợp `scripts/test-product-bulk.php` dành riêng cho luồng nhập/xuất sản phẩm.

- Production homepage/category HTTP: PASS.
- Public sensitive URL checks: REAL_BUG (HTTP 200).
- PHP 7.4 lint trong container Docker local: khả dụng.
- Bulk product CSV/XLS/XLSX, preview/commit, conflict, `__CLEAR__`, rollback và cleanup dữ liệu test: PASS ngày 2026-08-29.
- `scripts/test-security-hardening.php`: kiểm CSRF helper, allowlist, rate limit và MIME ảnh/file giả; chạy bằng PHP 7.4 trong container.
- CMS authenticated flow, contact/order email, member flow: NEEDS VERIFICATION.

Baseline đề xuất: syntax check; HTTP smoke; form success/failure/duplicate; auth/permission; category -> product -> cart -> checkout -> confirmation; responsive; SEO; backup/rollback rehearsal trên staging.
