# AI context map

> **Điểm vào hiện hành:** [AI-START.md](../AI-START.md) và [CURRENT.md](../CURRENT.md). Các đường dẫn hoặc trạng thái cũ bên dưới cần đối chiếu source hiện hành, đặc biệt tài liệu trước migration.


Đọc bản đồ hiện hành theo nghiệp vụ trong [Sổ tay chủ project](../SO-TAY-CHU-PROJECT.md).

```text
Mọi task          -> AGENTS.md -> docs/AI-START.md -> docs/CURRENT.md
Tìm code          -> docs/SO-TAY-CHU-PROJECT.md -> frontend/app + backend/app/Domain
Public UI         -> frontend/app/pages, components, composables; docs/02-development/nuxt-storefront-guide.md
Admin             -> backend/app/Filament, backend/app/Domain/Admin
API / schema      -> backend/routes, backend/app/Models, backend/database/migrations
Tests             -> backend/tests, frontend package scripts, tools/visual-tests
Worktree          -> docs/02-development/git-worktree-workflow.md
Deploy Laravel    -> deploy/laravel13/README.md, docs/06-operations/deployment-log.md
Rollback          -> release record + scripts đúng release; kiểm tra runtime
Legacy migration  -> docs/16-laravel-migration (lịch sử, không phải runtime hiện tại)
```

## Ba quy tắc

1. **Đo, đừng đoán.** Runtime > code > schema > docs. Khi mâu thuẫn, ghi rõ
   xung đột và tin nguồn đáng tin hơn.
2. Ghi `UNKNOWN` / `NEEDS VERIFICATION` thay vì suy diễn.
3. Chỉ nạp context tối thiểu cần thiết. Không đọc/hiển thị secret trong
   `config.php`, `deploy/vps/secrets/`, SQL INSERT, config backup hay dữ liệu mail.
