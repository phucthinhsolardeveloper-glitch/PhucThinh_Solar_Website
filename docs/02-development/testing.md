# Testing

Project có regression PHP/CLI và Playwright responsive chạy local; chưa có CI
trên server. Có thể chạy toàn bộ cổng kiểm tra local bằng:

```bash
./scripts/predeploy-local.sh
```

Người vận hành có thể dùng wrapper SAFE/FAST thay vì nhớ từng cổng; xem
[Sổ tay chủ hệ thống](../06-operations/owner-system-handbook.md).

Script build lại Docker PHP 7.4, lint PHP/JS, chạy security/RBAC/session/Excel regression, smoke frontend, kiểm log và từ chối hoàn tất nếu còn dữ liệu test.

- Production homepage/category HTTP: PASS.
- Public sensitive URL checks: REAL_BUG (HTTP 200).
- PHP 7.4 lint trong container Docker local: khả dụng.
- Bulk product CSV/XLS/XLSX, preview/commit, conflict, `__CLEAR__`, rollback và cleanup dữ liệu test: PASS ngày 2026-08-29.
- `scripts/test-security-hardening.php`: kiểm CSRF helper, allowlist, rate limit và MIME ảnh/file giả; chạy bằng PHP 7.4 trong container.
- `scripts/test-admin-readiness.php`: kiểm ma trận RBAC tạm, login/session/cookie, 42 form Admin và cleanup.
- CMS authenticated flow, contact/order email, member flow: NEEDS VERIFICATION.

Baseline đề xuất: syntax check; HTTP smoke; form success/failure/duplicate; auth/permission; category -> product -> cart -> checkout -> confirmation; responsive; SEO; backup/rollback rehearsal trên staging.

Nhóm giao diện commerce/mobile chạy riêng bằng:

```bash
cd tools/visual-tests
npx playwright test tests/mobile-commerce-experience.spec.js tests/typography-system.spec.js
```

Test phủ card tên dài không bị cắt, ngôn ngữ trong Mmenu, footer không còn đệm
152px, buy box mobile và thang chữ 2K. Luồng có giá phải được smoke trên local
với fixture có rollback; không đổi công tắc giá production chỉ để chạy test.
