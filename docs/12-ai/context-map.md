# AI context map

**Bắt đầu từ [docs/SO-TAY.md](../SO-TAY.md)** — nó tra cứu theo *việc cần làm*
và liệt kê sẵn các cạm bẫy đã tốn thời gian thật.

```text
Mọi task            -> SO-TAY.md -> AGENTS.md -> fact-map/status
Hiểu code / tìm file-> 01-architecture/codebase-guide.md -> sources/templates/libraries
Giao diện public    -> 02-development/nuxt-storefront-guide.md -> 15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md
Phản hồi chủ website-> 11-workflows/owner-feedback-loop.md -> tools/visual-tests/audit/README.md
UX contract (dữ liệu/IA/SEO) -> 02-development/public-ui-ux-design-system.md -> ADR 0007
CSS implementation -> 02-development/frontend-guide.md -> assets/css/design-tokens.css
Nội dung sửa ở Admin-> 07-features/admin-guide.md -> libraries/type/config-type-*.php
Database / schema   -> 03-data/database.md -> migrations/
Sửa bug             -> 11-workflows/bug-fixing-playbook.md
Làm tính năng       -> 11-workflows/feature-delivery-playbook.md
Test / cổng deploy  -> 02-development/testing.md -> scripts/predeploy-local.sh, tools/visual-tests
Branch/worktree    -> 02-development/git-worktree-workflow.md -> git status/worktree/branch ancestry
Tự vận hành end-to-end-> 06-operations/owner-system-handbook.md
Deploy production   -> 06-operations/deploy-production-owner.md (AUTO/SAFE/FAST, VPS)
Dựng VPS / chuyển   -> 06-operations/vps-cloudfly-runbook.md
Vận hành VPS / sự cố-> 06-operations/vps-operations.md
Backup / restore    -> 06-operations/backup-restore.md
Điều tra host cũ    -> 06-operations/deployment.md          (lịch sử FTP)
Auth / phân quyền   -> 05-security/auth-rbac.md -> libraries/requick.php, admin_security.php
Quyết định đã chốt  -> 09-decisions/
Hành vi legacy lạ   -> 10-audits/ -> implementation -> BẰNG CHỨNG RUNTIME
```

## Ba quy tắc

1. **Đo, đừng đoán.** Runtime > code > schema > docs. Khi mâu thuẫn, ghi rõ
   xung đột và tin nguồn đáng tin hơn.
2. Ghi `UNKNOWN` / `NEEDS VERIFICATION` thay vì suy diễn.
3. Chỉ nạp context tối thiểu cần thiết. Không đọc/hiển thị secret trong
   `config.php`, `deploy/vps/secrets/`, SQL INSERT, config backup hay dữ liệu mail.
