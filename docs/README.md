# Documentation index

> **Mới bắt đầu, hoặc AI vừa mất context? Đọc [SO-TAY.md](SO-TAY.md) trước.**
> File này liệt kê tài liệu theo thư mục; `SO-TAY.md` tra cứu theo *việc cần làm*
> và nói trước các cạm bẫy.

## Sổ tay thao tác (đọc vào là làm được)

- `SO-TAY.md`: cửa vào duy nhất — tra cứu theo việc, bản đồ hệ thống, cạm bẫy đã gặp thật.
- `01-architecture/codebase-guide.md`: URL → `$source` → `$template`; vai trò từng thư mục; hợp đồng `getImage()`.
- `02-development/public-ui-ux-design-system.md`: nguồn chuẩn UI/UX public — định hướng, token, component, ảnh, responsive, accessibility và Definition of Done.
- `02-development/frontend-guide.md`: thứ tự nạp CSS, hệ design token, 4 lỗi layout thật và cách sửa.
- `07-features/admin-guide.md`: 3 hệ lưu nội dung; 5 bước làm một khối sửa được ở Admin.
- `11-workflows/bug-fixing-playbook.md`: đo đừng đoán; 4 lần kết luận sai và dấu hiệu nhận ra.
- `11-workflows/feature-delivery-playbook.md`: từ yêu cầu tới production, kèm danh sách "không tự làm".
- `06-operations/vps-operations.md`: vận hành VPS, backup, sự cố thường gặp.
- `06-operations/owner-system-handbook.md`: sổ tay end-to-end cho chủ website: local, script, deploy, dựng/cứu VPS.
- `06-operations/deploy-production-owner.md`: deploy production AUTO/SAFE/FAST một lệnh và rollback cho chủ website.
- `06-operations/documentation-maintenance.md`: lịch rà soát, bảo trì và lịch sử cập nhật tài liệu.
- `06-operations/cutover-dns-va-host-cu.md`: hướng dẫn từng bước đổi DNS và xử lý host cũ, kèm rủi ro SMTP.

## Theo thư mục

- `00-project/fact-map.md`, `status.md`: sự thật và trạng thái.
- `01-architecture/overview.md`, `request-flow.md`: kiến trúc và luồng.
- `02-development/local-setup.md`, `testing.md`: phát triển/kiểm thử.
- `03-data/database.md`, `data-safety.md`: database/backup.
- `03-data/product-pricing-invariant.md`: invariant giá sản phẩm dùng chung cho CRUD Admin và bulk import.
- `03-data/checkout-write-path.md`: đường ghi checkout, hoàn tác bù trừ MyISAM và giới hạn trước ADM-P1-004.
- `04-api/overview.md`: API/AJAX.
- `05-security/security-model.md`, `auth-rbac.md`: security/auth.
- `06-operations/deployment.md`, `backup-restore.md`: vận hành.
- `07-features/README.md`: feature inventory.
- `08-integrations/README.md`: tích hợp.
- `10-audits/initial-handover-audit.md`: audit bàn giao.
- `10-audits/2026-09-01-admin-modernization-technical-audit.md`: audit kỹ thuật Admin về endpoint, auth/DB coupling, query, validation, transaction, dữ liệu, hiệu năng và testability.
- `13-handoffs/2026-08-29-admin-operations-followup.md`: trạng thái nâng cấp filter, liên hệ và bulk sản phẩm trong Admin.
- `13-handoffs/2026-08-29-next-work-checklist.md`: checklist chốt batch Excel, nhận dữ liệu thật và chuẩn bị production.
- `13-handoffs/2026-08-31-ai-continuity-handoff.md`: điểm vào khi chuyển tab/tài khoản AI, phương pháp làm việc, trạng thái và bước tiếp theo.
- `13-handoffs/2026-08-31-ads-hosting-readiness-checklist.md`: checklist cha đang điều phối toàn bộ công việc readiness.
- `11-workflows/development-workflow.md`: workflow.
- `12-ai/context-map.md`, `agent-workflow.md`, `task-template.md`: AI context.
- `CHANGELOG.md`, `ROADMAP.md`: lịch sử tài liệu và ưu tiên.
