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

## Production VPS

VPS có automation DB mỗi 6 giờ, full DB + upload hằng ngày và restore
rehearsal tại `deploy/vps/scripts/`; xem
[Runbook VPS CloudFly](vps-cloudfly-runbook.md). Trạng thái chỉ được nâng thành
READY sau khi đã cấu hình off-site hook và chạy PASS một lần từ bản tải ngược
về, không chỉ từ bản local trên cùng VPS.

Tại 2026-09-20, backup/checksum và timer nội bộ hoạt động nhưng off-site hook
vẫn chưa cấu hình. Vì vậy khả năng phục hồi khi chỉ hỏng app/DB là có, còn tình
huống mất toàn VPS vẫn là rủi ro mở.

Nhà cung cấp cần xác nhận owner, lịch, phạm vi DB/upload/mail, nơi lưu, retention, encryption và RTO/RPO. Restore rehearsal production backup chỉ chạy trên staging: dựng account sạch, import DB, restore upload, cập nhật config/domain, kiểm tra login/content/product/order/email và ghi thời gian/kết quả.

## Đổi hosting (khẩn cấp hoặc chủ động)

Xem [Runbook đổi hosting](hosting-migration-runbook.md) — bao gồm phát hiện quan trọng rằng domain/DNS (P.A Vietnam) tách biệt hoàn toàn khỏi server hosting (VNPT), việc cần làm ngay để phòng bị khoá quyền truy cập đột ngột, backup chi tiết, yêu cầu kỹ thuật host mới và quy trình cutover DNS.
