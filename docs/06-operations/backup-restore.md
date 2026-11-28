# Backup / restore — Laravel hiện hành

Nguồn: [backup.sh](../../deploy/laravel13/scripts/backup.sh), [backup-retention.sh](../../deploy/laravel13/scripts/backup-retention.sh), [release log](deployment-log.md). Source local 5c35860f, 26/11/2026.

- Script backup production mặc định stack /srv/pts-laravel13, compose project pts-laravel13; yêu cầu root/sudo, runtime/stack.env và compose production đúng trên VPS. --full gồm DB, upload và Laravel storage; --db-only chỉ DB. Script dùng operation.lock, không chạy đồng thời deploy/restore.
- runtime env chứa secret không được chép trong backup script; lưu riêng có kiểm soát quyền. Không xuất nội dung vào chat/log.
- Xác nhận backup hoàn tất, checksum, dung lượng, phạm vi DB/media/storage; giữ bản trước release và off-host theo chính sách đã duyệt.
- Restore rehearsal: dựng stack/DB/volume cô lập, đúng phiên bản image/schema, kiểm checksum trước giải nén, kiểm migration, health, sản phẩm, media, đơn hàng và quyền. Không overwrite DB thật; nếu cần restore production phải có quyết định và kế hoạch downtime/rollback riêng.
- Release 63b9ff7e có backup ghi nhận trong release log; lượt tài liệu này không chạy backup/restore và chưa chứng minh bản backup khôi phục được.

Không dùng scripts/backup-local.sh của PHP cũ. Lịch sử: git show 5c35860f:docs/06-operations/backup-restore.md.
