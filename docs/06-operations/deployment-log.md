# Nhật ký triển khai production

Mỗi lần thay đổi production phải thêm một mục mới, không ghi credential, token,
PII hoặc nội dung bí mật. Nếu deploy chỉ thay docs không rebuild image thì ghi rõ
`docs-only` và lý do không cần runtime deploy.

## 2026-10-09 — khôi phục nội dung tư vấn toàn bộ catalog

- Commit/image: `42db406ce85f` / `phucthinhsolar-web:42db406ce85f`; deploy `SAFE`.
- Phạm vi: giữ khối thẻ nhu cầu và FAQ trước CTA khi dữ liệu Admin thiếu; đồng
  bộ nội dung cho toàn bộ danh mục cha/con bằng migrations 063, 065, 067–071 và
  073; bổ sung 26 ảnh thẻ vào shared upload.
- Backup trước thay đổi: `20261008T155253Z-full` và backup SAFE
  `20261008T161208Z-full`.
- Kiểm tra: 248 Playwright local đạt, 42 skipped; 9 Playwright production đạt,
  5 skipped; audit sitemap đạt 31/31 URL danh mục, 0 lỗi; 26/26 ảnh HTTP 200.
- Production: web/database healthy; log web/proxy không có Fatal, Warning hoặc
  HTTP 5xx mới sau deploy.
- Rollback code: `/srv/phucthinhsolar/scripts/rollback-release.sh`; rollback dữ
  liệu dùng full backup trước migration sau khi xác nhận phạm vi cần phục hồi.
- Hai danh mục con không có row nội dung riêng (`Đèn cắm cỏ`, `Kẹp tấm pin`)
  kế thừa block từ danh mục cha và đã được kiểm tra trực tiếp.

## 2026-09-30 — rà soát lớp backup

- Trạng thái production không thay đổi; đây là cập nhật tài liệu (`docs-only`).
- Backup ứng dụng trên VPS vẫn có timer DB/full backup và các bản backup local có
  checksum.
- CloudFly automatic backup/snapshot là lớp khôi phục toàn VPS đang có theo gói
  hạ tầng hiện hành; cần kiểm tra lịch, retention và restore trên CloudFly Console
  theo định kỳ.
- `/usr/local/sbin/pts-backup-offsite` chưa phải là thành phần bắt buộc của
  baseline hiện tại. Nếu chưa cài hook này, cảnh báo của script chỉ có nghĩa là
  chưa có thêm một bản copy sang nhà cung cấp/object storage độc lập ngoài CloudFly.
- Không cleanup, không thay đổi backup và không thay đổi production trong lần rà
  soát này.

## 2026-09-29 — migration và vận hành sau deploy

- Commit/image đang chạy: `14fdb2672665` / `phucthinhsolar-web:14fdb2672665`.
- Phạm vi: migration 058–060, kiểm tra route, backup retention và dọn Docker
  build cache.
- Migration đã xác minh: payment/shipping tables; payment/carrier columns trên
  `table_order`; project location/date columns trên `table_news`; `weight` trên
  `table_product`.
- Backup ứng dụng giữ lại: `20260929T013137Z-full` và
  `20260929T012822Z-full`.
- Docker build cache: đã kiểm tra và prune phần không dùng; không xóa container,
  volume hoặc image đang chạy.
- Kiểm tra sau thay đổi: stack healthy, các route public chính HTTP 200, không có
  log web/proxy lỗi mới tại thời điểm kiểm tra.
- Rollback point: image trước đó được lưu qua `state/previous-image`; không tự
  rollback DB.

## Mẫu ghi cho lần tiếp theo

```text
Ngày/giờ:
Commit/image:
Mode: AUTO | SAFE | FAST | docs-only
Phạm vi thay đổi:
Migration:
Backup trước deploy:
Smoke/HTTP/Playwright:
Log scan:
Rollback point:
Rủi ro còn lại:
```
