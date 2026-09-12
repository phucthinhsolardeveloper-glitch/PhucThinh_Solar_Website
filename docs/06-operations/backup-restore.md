# Backup and restore

## Local

Tạo backup kiểm thử ngoài webroot:

```bash
./scripts/backup-local.sh
```

Script tạo dump database nhất quán, archive upload, release metadata và `SHA256SUMS` trong thư mục `backup/local/<timestamp>`. Thư mục `backup/` không được Git theo dõi.

Diễn tập restore vào database và thư mục tạm độc lập:

```bash
./scripts/test-backup-restore-local.sh backup/local/<timestamp>
```

Restore test kiểm tra checksum, số bảng và số file upload rồi tự xóa database/thư mục tạm. Script chỉ dùng cấu hình Docker local, không dùng cho production.

## Hosting

Trạng thái vẫn là PARTIAL. Bản bàn giao chứa DirectAdmin archive/database/config/mail metadata, nhưng chưa chứng minh job tự động, retention, encryption, off-site copy hoặc restore test.

Nhà cung cấp cần xác nhận owner, lịch, phạm vi DB/upload/mail, nơi lưu, retention, encryption và RTO/RPO. Restore rehearsal production backup chỉ chạy trên staging: dựng account sạch, import DB, restore upload, cập nhật config/domain, kiểm tra login/content/product/order/email và ghi thời gian/kết quả.

## Đổi hosting (khẩn cấp hoặc chủ động)

Xem [Runbook đổi hosting](hosting-migration-runbook.md) — bao gồm phát hiện quan trọng rằng domain/DNS (P.A Vietnam) tách biệt hoàn toàn khỏi server hosting (VNPT), việc cần làm ngay để phòng bị khoá quyền truy cập đột ngột, backup chi tiết, yêu cầu kỹ thuật host mới và quy trình cutover DNS.
