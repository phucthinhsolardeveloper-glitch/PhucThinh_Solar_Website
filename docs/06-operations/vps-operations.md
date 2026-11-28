# Vận hành VPS hằng ngày và xử lý sự cố

> **Cảnh báo kiến trúc:** một phần hướng dẫn thuộc PHP legacy. Chỉ thực thi lệnh đã đối chiếu với source hiện hành. Bắt đầu tại [Sổ tay](../SO-TAY-CHU-PROJECT.md), [CURRENT](../CURRENT.md) và [Laravel deploy](../../deploy/laravel13/README.md).


Cập nhật: 2026-11-16. Website production hiện chạy Nuxt 4 + Laravel 13 tại
/srv/pts-laravel13; stack PHP cũ /srv/phucthinhsolar đã ngừng phục vụ website.
Lần dựng/chuyển VPS xem vps-cloudfly-runbook.md, quy trình deploy hằng ngày xem
deploy-production-owner.md.

VPS dùng chung với Zalo CRM, sales-training và hyans-mcp staging. Chỉ thao tác
trên tài nguyên pts-laravel13; không dừng, xóa hoặc prune container, volume,
image hay dữ liệu của các hệ thống còn lại.

## 1. Stack đang chạy

| Hạng mục | Giá trị |
|---|---|
| Gốc stack production | /srv/pts-laravel13 |
| Compose/runtime | /srv/pts-laravel13/app/deploy/laravel13 |
| Backend | container pts-laravel13-backend |
| Upload sản phẩm | /srv/pts-laravel13/upload/product/ (tên file được lưu trong products.image) |
| Backup ứng dụng | /srv/pts-laravel13/backups/ |
| Lock thao tác | /srv/pts-laravel13/state/operation.lock |

Các container ứng dụng gồm pts-laravel13-backend, pts-laravel13-nuxt,
pts-laravel13-worker, pts-laravel13-scheduler và service DB/proxy của Compose.
Không giả định container PHP cũ hoặc cấu trúc shared/upload còn là production.

```bash
cd /srv/pts-laravel13/app/deploy/laravel13
docker compose --env-file runtime/stack.env -f docker-compose.yml -f docker-compose.production.yml ps
docker logs --tail=100 pts-laravel13-backend
```

Thao tác deploy/backup/restore dùng lock chung. Không chạy lệnh ghi song song với
release hoặc restore. PHP Artisan chạy trong backend container; ví dụ:

```bash
docker exec pts-laravel13-backend sh -c 'cd /var/www/html && php artisan about'
```

Khi cần chạy mã PHP tạm qua Tinker, truyền nội dung script không có dòng <?php
qua /tmp theo quy trình đã được review; không đưa secret hoặc dữ liệu cá nhân
vào shell history.

## 2. Deploy và rollback

Deploy và rollback Laravel làm theo deploy/laravel13/README.md: build image
candidate (`build-candidate.sh`), kiểm (`verify-candidate.sh`), đổi image trong
`runtime/stack.env`; rollback bằng image candidate cũ và bản env lưu ở
`/srv/pts-laravel13/releases/rollback-<sha>/stack.env.before`. Quy trình một lệnh
trong deploy-production-owner.md là của stack PHP cũ, không còn dùng. Trước thao tác ghi dữ liệu phải có backup DB phù hợp.
Không chạy migration hoặc sửa production trực tiếp khi task chỉ yêu cầu thay
source/docs.

## 3. Backup ứng dụng

Systemd chạy full backup hằng ngày lúc 02:40 và DB-only mỗi 6 giờ, ở phút 15.
Timer gọi /srv/pts-laravel13/scripts/backup.sh, bản source ở
deploy/laravel13/scripts/backup.sh.

```bash
systemctl list-timers 'pts-laravel13*'
systemctl start pts-laravel13-db-backup.service
journalctl -u pts-laravel13-db-backup.service -n 30 --no-pager
ls -lh /srv/pts-laravel13/backups/
```

Mỗi thư mục backup có manifest và SHA256SUMS; script tự kiểm tra checksum sau
khi tạo. Full backup gồm DB, upload/ và storage/app; DB-only chỉ gồm dump DB.
Chính sách mặc định giữ 2 full mới nhất (BACKUP_KEEP_FULL, tối thiểu 1) và
DB-only theo BACKUP_RETENTION_DAYS (mặc định 7 ngày). Bản vừa tạo luôn được giữ.
Chỉ các thư mục có tên khớp 20??????T??????Z-full hoặc
20??????T??????Z-db mới nằm trong phạm vi xoay.

Backup ứng dụng hiện nằm trên VPS. CloudFly snapshot là lớp bảo vệ toàn VPS riêng;
định kỳ kiểm tra lịch/retention/khả năng phục hồi ở CloudFly Console. Chưa có bản
sao ứng dụng độc lập ngoài VPS; đây là lớp dự phòng bổ sung cần cân nhắc.

## 4. Dọn dung lượng an toàn

Bắt đầu bằng đo đạc, rồi xác định current/previous/rollback trước khi xóa mục cụ
thể:

```bash
df -h
du -x -h -d1 /srv/pts-laravel13 | sort -h
du -x -h -d1 /srv/pts-laravel13/backups | sort -h
docker system df -v
```

Ưu tiên build cache không dùng sau khi kiểm tra. Không dùng
docker system prune -a --volumes; không xóa volume MariaDB, upload/, secrets,
state tham chiếu, backup chưa xác minh hoặc tài nguyên của Zalo CRM,
sales-training và hyans-mcp.

## 5. Restore và kiểm tra lỗi

Tham khảo backup-restore.md. Restore vào môi trường cô lập trước; không khôi
phục DB production theo quán tính. Đối chiếu manifest/checksum, đúng stack và
đúng checkpoint rollback trước thao tác.

```bash
docker ps --filter name=pts-laravel13
docker logs --tail=100 pts-laravel13-backend
```

Nếu API/Admin lỗi, kiểm tra trạng thái backend, DB và log trước khi quyết định
rollback release. Không sửa production hoặc nhập đè database khi chưa được giao
rõ phạm vi.
