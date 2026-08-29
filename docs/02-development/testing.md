# Testing

Chưa có framework unit/E2E hoặc CI trên server. Có thể chạy toàn bộ cổng kiểm tra local bằng:

```bash
./scripts/predeploy-local.sh
```

Script build lại Docker PHP 7.4, lint PHP/JS, chạy security/RBAC/session/Excel regression, smoke frontend, kiểm log và từ chối hoàn tất nếu còn dữ liệu test.

- Production homepage/category HTTP: PASS.
- Public sensitive URL checks: REAL_BUG (HTTP 200).
- PHP 7.4 lint trong container Docker local: khả dụng.
- Bulk product CSV/XLS/XLSX, preview/commit, conflict, `__CLEAR__`, rollback và cleanup dữ liệu test: PASS ngày 2026-08-29.
- `scripts/test-security-hardening.php`: kiểm CSRF helper, allowlist, rate limit và MIME ảnh/file giả; chạy bằng PHP 7.4 trong container.
- `scripts/test-admin-readiness.php`: kiểm ma trận RBAC tạm, login/session/cookie, 42 form Admin và cleanup.
- CMS authenticated flow, contact/order email, member flow: NEEDS VERIFICATION.

Baseline đề xuất: syntax check; HTTP smoke; form success/failure/duplicate; auth/permission; category -> product -> cart -> checkout -> confirmation; responsive; SEO; backup/rollback rehearsal trên staging.
