# Testing

Project có regression PHP/CLI và Playwright responsive chạy local; chưa có CI
trên server. Có thể chạy toàn bộ cổng kiểm tra local bằng:

```bash
./scripts/predeploy-local.sh
```

Người vận hành có thể dùng wrapper AUTO/SAFE/FAST thay vì nhớ từng cổng; xem
[Sổ tay chủ hệ thống](../06-operations/owner-system-handbook.md).

Script build lại Docker PHP 8.1, lint PHP/JS, chạy security/RBAC/session/Excel regression, smoke frontend, kiểm log và từ chối hoàn tất nếu còn dữ liệu test.

- Production homepage/category HTTP: PASS.
- Public sensitive URL checks: REAL_BUG (HTTP 200).
- PHP 8.1 lint trong container Docker local: khả dụng.
- Bulk product CSV/XLS/XLSX, preview/commit, conflict, `__CLEAR__`, rollback và cleanup dữ liệu test: PASS ngày 2026-08-29.
- `scripts/test-security-hardening.php`: kiểm CSRF helper, allowlist, rate limit và MIME ảnh/file giả; chạy bằng PHP 8.1 trong container.
- `scripts/test-admin-readiness.php`: kiểm ma trận RBAC tạm, login/session/cookie, 42 form Admin và cleanup.
- CMS authenticated flow, contact/order email, member flow: NEEDS VERIFICATION.

Baseline đề xuất: syntax check; HTTP smoke; form success/failure/duplicate; auth/permission; category -> product -> cart -> checkout -> confirmation; responsive; SEO; backup/rollback rehearsal trên staging.

## Verification evidence và phân loại

Build hoặc unit test xanh không thay thế integration, security, browser hay UAT.
Checkpoint phải ghi rõ lớp đã chạy và lớp chưa chạy:

1. syntax/lint + focused contract;
2. PHP đúng version/container + API status/negative path;
3. regression/typecheck/production build;
4. browser smoke, screenshot/responsive, console, media, overflow và SEO;
5. commerce flow và permission/session/CSRF;
6. UAT có người vận hành, chỉ khi thay đổi cần business/provider acceptance.

Mỗi failure dùng một nhãn: `PRE_EXISTING`, `REGRESSION`, `ENVIRONMENT`,
`REAL_BUG` hoặc `UNKNOWN`. Báo kèm command, exit code, artifact và known issue;
không dùng kết quả build để claim business correctness.

Nhóm giao diện commerce/mobile chạy riêng bằng:

```bash
cd tools/visual-tests
npx playwright test tests/mobile-commerce-experience.spec.js tests/checkout-controls.spec.js tests/typography-system.spec.js
```

Test phủ card tên dài không bị cắt, ngôn ngữ trong Mmenu, footer không còn đệm
152px, buy box mobile, cart/checkout và thang chữ 2K. Với giỏ hàng phải kiểm cả
popup và trang checkout ở 320/390/768/1024/1440px, số lượng `1` và `999`, tên
dài, giá/tổng tiền dài, tâm dọc tên–giá–counter và `scrollWidth` không vượt
viewport. Luồng có giá phải được smoke trên local với fixture có rollback;
không đổi công tắc giá production chỉ để chạy test.
