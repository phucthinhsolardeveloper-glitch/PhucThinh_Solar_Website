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
[Runbook VPS CloudFly](vps-cloudfly-runbook.md). Backup CloudFly là lớp snapshot
toàn VPS do nhà cung cấp quản lý; backup ứng dụng là lớp phục hồi chọn lọc DB và
upload. Uploader off-site độc lập là lớp tăng cường, không phải điều kiện chặn
deploy khi CloudFly backup đang hoạt động.

Tại lần rà soát 2026-09-29, backup/checksum và timer nội bộ hoạt động; VPS cũng
có lớp backup CloudFly. Uploader off-site riêng chưa cấu hình, nên đây vẫn là
lớp phòng vệ bổ sung có thể triển khai nếu cần yêu cầu độc lập nhà cung cấp.

Nhà cung cấp cần xác nhận owner, lịch, phạm vi DB/upload/mail, nơi lưu, retention, encryption và RTO/RPO. Restore rehearsal production backup chỉ chạy trên staging: dựng account sạch, import DB, restore upload, cập nhật config/domain, kiểm tra login/content/product/order/email và ghi thời gian/kết quả.

## Đổi hosting (khẩn cấp hoặc chủ động)

Xem [Runbook đổi hosting](hosting-migration-runbook.md) — bao gồm phát hiện quan trọng rằng domain/DNS (P.A Vietnam) tách biệt hoàn toàn khỏi server hosting (VNPT), việc cần làm ngay để phòng bị khoá quyền truy cập đột ngột, backup chi tiết, yêu cầu kỹ thuật host mới và quy trình cutover DNS.
