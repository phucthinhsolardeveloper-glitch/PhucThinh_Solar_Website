# Backup and restore

## Local

Tạo backup kiểm thử ngoài webroot:

```bash
./scripts/backup-local.sh
```

Script tạo dump database nhất quán, archive upload, release metadata và
SHA256SUMS trong backup/local/<timestamp>. Thư mục backup/ không được Git theo
dõi.

Diễn tập restore vào database và thư mục tạm độc lập:

```bash
./scripts/test-backup-restore-local.sh backup/local/<timestamp>
```

Restore test kiểm tra checksum, số bảng và số file upload rồi tự xóa database/
thư mục tạm. Script chỉ dùng cấu hình Docker local, không dùng cho production.

## Production VPS — Laravel 13

Production chạy tại /srv/pts-laravel13; backup ứng dụng nằm ở
/srv/pts-laravel13/backups/. Backend container là pts-laravel13-backend. Full
backup gồm dump MariaDB, upload/ và volume pts-laravel13_app_storage (phần
storage/app); DB-only chỉ gồm dump database.

Systemd chạy full backup hằng ngày lúc 02:40 và DB-only mỗi 6 giờ, phút 15.
Có thể gọi thủ công:

```bash
/srv/pts-laravel13/scripts/backup.sh --full
/srv/pts-laravel13/scripts/backup.sh --db-only
```

Mỗi backup có manifest và checksum, được kiểm tra ngay sau khi tạo. Chính sách
mặc định giữ 2 thư mục *-full mới nhất (BACKUP_KEEP_FULL, tối thiểu 1), và giữ
*-db theo BACKUP_RETENTION_DAYS (mặc định 7 ngày). Bản mới tạo luôn được bảo vệ.
Thư mục khác mẫu timestamp *-full/*-db không bị chọn để xóa.

Backup ứng dụng trên VPS hỗ trợ khôi phục chọn lọc DB và media. CloudFly snapshot
là lớp backup toàn VPS do nhà cung cấp quản lý, độc lập với backup ứng dụng.
Định kỳ xác nhận lịch, retention và khả năng restore của lớp snapshot; hiện chưa
có bản sao backup ứng dụng riêng ngoài VPS.

Trước restore, kiểm tra checksum và chọn đúng backup. Thử restore trong môi
trường cô lập/staging; xác nhận stack, database, upload và rollback point trước
khi ghi production. Không restore DB theo quán tính hoặc ghi đè/xóa file trong
upload/. Địa chỉ ảnh sản phẩm hiện là tên file trong upload/product/, cột
products.image.

## Đổi hosting (khẩn cấp hoặc chủ động)

Xem Runbook đổi hosting (hosting-migration-runbook.md) — bao gồm quyền quản lý
domain/DNS, backup chi tiết, yêu cầu kỹ thuật host mới và quy trình cutover DNS.
