# Runbook VPS CloudFly — dựng mới, deploy, chuyển nhà và rollback

Ngày chuẩn hóa: 2026-09-17. Baseline: Ubuntu 24.04, Docker Compose, Caddy,
Apache/PHP 7.4 và MariaDB 10.11. Nguồn triển khai nằm ở `deploy/vps/`.

## 1. Kiến trúc xác nhận từ mã nguồn

Ứng dụng là monolith PHP server-rendered, dùng `.htaccess`, Apache rewrite,
MariaDB và filesystem. Không có queue/Redis/object storage bắt buộc. Dữ liệu
cần giữ độc lập với code gồm:

- MariaDB: sản phẩm, nội dung, tài khoản, đơn/form và cấu hình Admin.
- `upload/`: ảnh/file gốc do Admin quản lý, không tái tạo được nếu mất.
- `thumbs/`, `watermark/`, `caches/`, `assets/caches/`: dữ liệu dẫn xuất nhưng
  tách volume để web ghi được và deploy không xóa nhầm.
- Secrets: DB, SMTP, app secret/salt và license; không nằm trong Git/image.

Release không chứa `libraries/config.php`, `upload`, cache hoặc log. Config VPS
được mount read-only; password đọc từ Docker secret file.

## 2. Phiên bản và nợ kỹ thuật

- Giữ PHP 7.4 trong lần chuyển đầu vì project mới được kiểm thử đầy đủ trên
  nhánh này. PHP 7.4 đã EOL nên phải đặt sau proxy/firewall, export image
  off-site và lập đợt riêng nâng lên PHP 8.x.
- Dùng MariaDB 10.11 LTS thay MariaDB 10.4 đã EOL. Chuyển bằng logical dump vào
  DB rỗng; không gắn trực tiếp data directory 10.4 vào container 10.11.
- Caddy quản lý certificate/gia hạn HTTPS. Apache tiếp tục xử lý `.htaccess`
  ở mạng Docker nội bộ.

## 3. Tài nguyên và mức dịch vụ

VPS thử nghiệm 8 CPU/16 GB/120 GB đủ cho triển khai, restore test và đo tải.
Không hạ trước khi có ít nhất 7 ngày metrics. Nếu CPU p95 dưới 30%, RAM ổn
định dưới 4 GB và I/O thấp, có thể cân nhắc 4 CPU/8 GB. Không dùng gói
1 CPU/1 GB/20 GB cho cả web, DB và backup.

Giữ tối thiểu 30% disk trống. Mục tiêu ban đầu: RPO tối đa 6 giờ cho DB,
24 giờ cho upload; RTO 2–4 giờ khi đã có VPS sạch, backup off-site và quyền DNS.

## 4. Access và Cloud Firewall

1. Thêm SSH public key; dùng root đúng lần bootstrap đầu.
2. Tạo user `deploy` có sudo và kiểm tra ở **phiên SSH thứ hai**.
3. Chỉ sau khi phiên thứ hai hoạt động mới tắt password/root login.
4. Cloud Firewall inbound:
   - TCP 22 từ IP quản trị cố định/VPN; nếu IP động chỉ mở tạm khi làm.
   - TCP 80, 443 từ Internet.
   - UDP 443 cho HTTP/3; có thể bỏ mà website vẫn chạy h1/h2.
   - Không mở 3306, cổng container hoặc Docker API.
5. Console CloudFly chỉ là đường cứu hộ.

Docker có thể bypass một số rule UFW khi publish cổng; nguồn kiểm soát chính
là Cloud Firewall và việc Compose chỉ publish 80/443. DB chỉ ở mạng `data`
internal.

## 5. Bootstrap VPS

Chưa trỏ DNS production. Copy `deploy/vps` lên máy mới rồi chạy:

```bash
sudo deploy/vps/scripts/bootstrap-ubuntu.sh
sudo deploy/vps/scripts/install-stack.sh
```

Script dùng apt repository Docker chính thức, không dùng convenience script và
không sửa SSH/DNS/firewall. Kiểm tra:

```bash
sudo docker version
sudo docker compose version
timedatectl
df -h / /var/lib/docker /srv/phucthinhsolar
```

## 6. Secrets và tương thích tài khoản cũ

Tạo file theo `deploy/vps/secrets/README.md`, thư mục mode `0700`, file `0600`.

- `app_secret`/`app_salt` phải lấy đúng từ config production cũ. Tự sinh mới
  có thể khiến mật khẩu legacy không đăng nhập được.
- DB password trên VPS phải tạo mới, không tái dùng mật khẩu hosting cũ.

Không chép secret vào `.env`, Git, image, release hoặc chat. SMTP chỉ bật sau
khi hostname mail có certificate hợp lệ.

Chỉnh `/srv/phucthinhsolar/.env`: domain staging, email ACME, DB name/user và
image tags. Tạo DNS như `staging.phucthinhsolar.com` trỏ tới VPS để Caddy cấp
certificate thật trước khi cutover.

## 7. Chuẩn bị backup nguồn

Từ hosting cũ lấy logical SQL dump và toàn bộ `upload/`, đóng thành:

```text
migration-input/
  database.sql.gz
  upload.tar.gz        # nhận cả upload/ bao ngoài hoặc nội dung upload ở gốc
  SHA256SUMS
```

Không dùng dump local thay production nếu chưa được chủ project xác nhận.
Chạy rehearsal trước restore thật:

```bash
sudo /srv/phucthinhsolar/scripts/restore-check.sh /path/to/migration-input
```

Chỉ tiếp tục khi checksum, số bảng và số file upload đều PASS.

## 8. Restore lần đầu

Script chỉ cho phép DB/upload đích đang rỗng, không thể dùng nhầm để ghi đè:

```bash
sudo /srv/phucthinhsolar/scripts/restore-initial.sh \
  /path/to/migration-input INITIAL_EMPTY_DATABASE
```

Không chạy migration ghi dữ liệu trong bước restore.

## 9. Đóng gói và deploy release

Máy phát triển phải ở clean commit đã qua predeploy:

```bash
./scripts/predeploy-local.sh
./scripts/package-hosting-release.sh
```

Khi cần đóng gói một ref khác: `./scripts/package-hosting-release.sh
/đường/dẫn/release <git-ref>`. Tham số đầu tiên luôn là thư mục đầu ra.

Upload nguyên thư mục release có checksum lên VPS, rồi:

```bash
sudo /srv/phucthinhsolar/scripts/deploy-release.sh /path/to/release-directory
sudo /srv/phucthinhsolar/scripts/verify-stack.sh
```

Deploy xác minh checksum, build image theo commit, đợi healthcheck và tự quay
lại image trước nếu web không healthy. Nó không tự chạy migration/đổi DNS.

Copy preflight từ release tools vào web container và chạy chỉ đọc:

```bash
cd /srv/phucthinhsolar
web_id="$(sudo docker compose ps -q web)"
sudo docker cp releases/<commit>/migrations/000_preflight_production.php "$web_id":/tmp/
sudo docker exec "$web_id" php /tmp/000_preflight_production.php
```

Migration thiếu phải preview, backup ngay trước khi chạy và áp dụng đúng thứ
tự. Không import nguyên dump staging đè DB đang chạy.

## 10. Smoke test trước cutover

- Trang chủ, landing/category/product detail.
- Tin tức, Giải pháp, Dự án, Giới thiệu, Liên hệ, Đại lý.
- Login Admin và quyền tài khoản nghiệp vụ.
- Upload WebP/JPG, thumbnail và file manager.
- Category → product → cart → checkout → confirmation.
- Form liên hệ/đại lý lưu DB; SMTP test riêng khi TLS đạt chuẩn.
- Robots, sitemap, canonical, structured data và Merchant feed.
- Desktop lớn/2K, tablet và mobile.

Không cutover nếu có fatal/warning public, upload thực thi script, preflight
PARTIAL, SMTP sai certificate, backup chưa restore được hoặc chưa có rollback.

## 11. Cutover DNS

1. Hạ TTL xuống 300 trước ít nhất một chu kỳ TTL cũ nếu có thể.
2. Chụp backup cuối và ngừng nhập liệu trên host cũ.
3. Đồng bộ delta DB/upload cuối.
4. Đổi bản ghi A, không đổi nameserver cùng thời điểm.
5. Theo dõi qua mạng khác nhau, log web, order/contact và certificate.
6. Giữ host cũ tối thiểu 7 ngày.

## 12. Backup, off-site và rehearsal

```bash
sudo /srv/phucthinhsolar/scripts/install-systemd.sh
```

- DB logic mỗi 6 giờ; full DB + upload hằng ngày khoảng 02:30.
- Retention local mặc định 14 ngày.
- CloudFly automatic backup là snapshot toàn VPS do nhà cung cấp quản lý, phù
  hợp cho khôi phục sự cố VPS. Nó không thay thế backup ứng dụng chọn lọc DB +
  upload; uploader off-site độc lập chỉ là lớp phòng vệ bổ sung nếu cần tách nhà
  cung cấp.

Tạo `/usr/local/sbin/pts-backup-offsite` để đẩy backup sang nơi khác nhà cung
cấp (S3/Object Storage/rclone encryption). Khi thiếu hook, script cảnh báo.
Mỗi tháng tải một bản off-site xuống và chạy `restore-check.sh`.

Sau release ổn định:

```bash
sudo /srv/phucthinhsolar/scripts/export-runtime-images.sh
```

Copy bundle image off-site. Đây là bảo hiểm vì PHP 7.4/base Debian đã EOL và
khả năng build lại từ Internet không được xem là đảm bảo lâu dài.

## 13. Rollback

Nếu lỗi code và DB chưa đổi:

```bash
sudo /srv/phucthinhsolar/scripts/rollback-release.sh
```

Rollback image không động DB/upload. Nếu lỗi migration, dừng ghi và đánh giá;
chỉ restore DB từ backup pre-migration khi chủ project xác nhận vì sẽ mất dữ
liệu phát sinh sau backup. Nếu lỗi sau cutover, có thể trả bản ghi A về host cũ
còn nguyên dữ liệu; không để hai host cùng nhận đơn/form.

## 14. Chuyển VPS lần sau

1. Dựng Ubuntu sạch bằng cùng bootstrap.
2. Copy `deploy/vps`, `.env` không secret và nhập secret từ password manager.
3. Load runtime image bundle; tạo stack nhưng chưa nhận traffic.
4. Lấy full backup + DB delta; chạy restore rehearsal.
5. Restore vào DB/upload rỗng, deploy đúng image/commit.
6. Verify/smoke, đồng bộ delta cuối, đổi DNS.
7. Giữ nhà cũ 7 ngày; xác nhận off-site từ nhà mới rồi mới hủy.

Quy trình không phụ thuộc panel CloudFly, áp dụng được cho nhà cung cấp khác có
Ubuntu 24.04, Docker và quyền quản trị DNS.
