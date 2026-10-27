# Development workflow

Áp dụng vòng lặp:

```text
INSPECT → UNDERSTAND → PLAN → IMPLEMENT → TEST → DEBUG → VERIFY → DOCUMENT → REPORT
```

## 1. Inspect và ownership

1. Đọc `AGENTS.md`, context map, docs domain và status hiện tại.
2. Kiểm tra `git status`, branch, worktree và owner trước khi sửa; giữ nguyên WIP
   chưa rõ nguồn.
3. Tái hiện hành vi hiện tại bằng URL, input, viewport, dữ liệu và log tối thiểu.
4. Trace đúng chuỗi sở hữu: route → source/template/API → auth/session →
   schema/config/storage/provider. Tìm behavior đã tồn tại trước khi tạo code
   hoặc projection thứ hai.
5. Tách `FACT`, `INFERENCE`, `UNKNOWN` và `NEEDS VERIFICATION`; không biến
   comment cũ, mockup hoặc báo cáo agent thành bằng chứng runtime.

Quy tắc branch/worktree chi tiết nằm trong
[git-worktree-workflow.md](../02-development/git-worktree-workflow.md). Không
checkout/reset/xóa worktree có WIP của owner khác; integration phải có commit
ancestry/diff/test evidence.

## 2. Plan trước khi implement

Ghi rõ trong task hoặc handoff:

- file/route/data nằm trong phạm vi và phần **không làm**;
- acceptance criteria, positive path và negative/permission/error path;
- tương thích URL/schema/session và tác động dữ liệu;
- test theo rủi ro, rollback point và điều kiện dừng;
- owner module và abstraction sẽ reuse, tránh duplicate ownership.

Schema phải additive, có migration ordering/backfill/backup phù hợp. SQL mới
phải parameterized; form mutation phải có session/quyền/CSRF. Không sửa live,
không đổi credential/production chỉ để làm test chạy.

## 3. Implement

Giữ diff nhỏ, reversible và đúng owner. Không refactor/cleanup ngoài phạm vi.
Nội dung public phải lấy từ dữ liệu quản lý hoặc fallback đã duyệt; không bịa
dữ liệu theo mockup. Với thay đổi giao diện, giữ route/slug và kiểm đúng hệ
thống UI/UX trước khi thay layout.

## 4. Verification ladder

Chọn mức kiểm tra theo rủi ro, không dùng build thay cho integration/security:

1. syntax/lint và focused unit/contract test;
2. PHP đúng version/container mục tiêu, API status và negative path;
3. regression suite, typecheck và production build;
4. browser smoke/visual/responsive/SEO, media và horizontal overflow;
5. commerce smoke `category → product → cart → checkout → confirmation`;
6. backup/rollback, log/health và UAT khi có deploy hoặc dữ liệu/permission.

Mỗi failure phải phân loại `PRE_EXISTING`, `REGRESSION`, `ENVIRONMENT`,
`REAL_BUG` hoặc `UNKNOWN`, kèm command, exit code và artifact/log liên quan.
Không nới assertion, cập nhật golden hoặc retry mù để biến đỏ thành xanh.

## 5. Hoàn tất và release

Review diff/staged diff tìm secret, PII, backup và file ngoài scope; chạy
`git diff --check`. Cập nhật canonical docs/changelog khi behavior, contract,
schema, deploy hoặc rollback thay đổi.

Chỉ deploy controlled diff sau khi có backup/rollback point, smoke test và
monitoring. Với Theme V2, giữ template cũ làm rollback cho tới khi page group
đạt parity, responsive, SEO và conversion verification.
